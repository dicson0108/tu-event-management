"""HTTP regressions against a disposable DB server, never the application database.

Usage: python tests/workflows.py --db-port 33079 --php C:/xampp/php/php.exe
The server must have a passwordless local root account and be disposable.
"""
import argparse
import concurrent.futures
import http.cookiejar
import json
import re
import shutil
import socket
import subprocess
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request
from pathlib import Path


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--db-port', type=int, required=True)
    parser.add_argument('--php', default='php')
    args = parser.parse_args()
    if args.db_port < 1024 or args.db_port == 3306:
        parser.error('Use a disposable database server on a non-default port.')
    root = Path(__file__).resolve().parents[1]
    failures = []
    count = 0
    processes = []
    with tempfile.TemporaryDirectory(prefix='tu-workflow-') as directory:
        app = Path(directory) / 'TUEventManagement'
        for folder in ('config', 'includes', 'api', 'admin', 'database'):
            shutil.copytree(root / folder, app / folder)
        for source in root.glob('*.php'):
            shutil.copy2(source, app / source.name)
        config = app / 'config/db.php'
        config.write_text(config.read_text().replace("define('DB_PORT', 3306);", f"define('DB_PORT', {args.db_port});"))

        def sql(query, params=None):
            code = ('$p=new PDO("mysql:host=127.0.0.1;port=' + str(args.db_port)
                    + ';dbname=tu_event_management;charset=utf8mb4","root","",'
                    + '[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);'
                    + '$s=$p->prepare($argv[1]);$s->execute(json_decode($argv[2],true));'
                    + 'echo json_encode($s->columnCount()?$s->fetchAll(PDO::FETCH_ASSOC):[]);')
            result = subprocess.run([args.php, '-r', code, query, json.dumps(params or [])], capture_output=True, text=True, check=True)
            return json.loads(result.stdout)

        class Client:
            def __init__(self, port):
                self.base = f'http://127.0.0.1:{port}/TUEventManagement'
                self.opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
                self.token = None

            def request(self, path, data=None, as_json=False):
                headers = {}
                if data is not None:
                    headers['Content-Type'] = 'application/json' if as_json else 'application/x-www-form-urlencoded'
                    data = (json.dumps(data) if as_json else urllib.parse.urlencode(data)).encode()
                request = urllib.request.Request(self.base + path, data=data, headers=headers)
                try:
                    with self.opener.open(request, timeout=15) as response:
                        return response.status, response.read().decode()
                except urllib.error.HTTPError as error:
                    return error.code, error.read().decode()

            def login(self, email):
                _, page = self.request('/login.php')
                self.token = re.search(r'name="csrf_token" value="([^"]+)"', page)[1]
                status, page = self.request('/login.php', {'email': email, 'password': 'Password123!', 'csrf_token': self.token})
                status, page = self.request('/profile.php')
                assert status == 200 and 'Log out' in page, 'Fixture login failed'
                self.token = re.search(r'name="csrf-token" content="([^"]+)"', page)[1]

            def action(self, event, action, token=None):
                status, body = self.request('/api/register-event.php', {'event_id': event, 'action': action, 'csrf_token': self.token if token is None else token}, True)
                return status, json.loads(body)

        def check(name, function):
            nonlocal count
            count += 1
            try:
                assert function(), 'Assertion failed'
                print('PASS:', name, flush=True)
            except Exception as error:
                failures.append(name)
                print(f'FAIL: {name}: {error}', flush=True)

        try:
            ports = []
            for _ in range(2):
                with socket.socket() as sock:
                    sock.bind(('127.0.0.1', 0))
                    ports.append(sock.getsockname()[1])
                processes.append(subprocess.Popen([args.php, '-d', 'date.timezone=Asia/Kuala_Lumpur', '-S', f'127.0.0.1:{ports[-1]}', '-t', directory], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL))
            for port in ports:
                for _ in range(100):
                    try:
                        with socket.create_connection(('127.0.0.1', port), .2):
                            break
                    except OSError:
                        time.sleep(.05)
            guest = Client(ports[0])
            assert guest.request('/index.php')[0] == 200
            # All fixture writes are confined to the explicitly selected disposable server.
            for table in ('comments', 'registrations'):
                sql(f'DELETE FROM {table}')
            sql("UPDATE events SET event_date=DATE_ADD(CURDATE(), INTERVAL 7 DAY), status='published', capacity=1, allow_waitlist=1")
            sql("UPDATE events SET status='draft' WHERE id=2")
            sql("UPDATE events SET status='completed', event_date=DATE_SUB(CURDATE(), INTERVAL 2 DAY) WHERE id=3")
            sql("UPDATE events SET status='cancelled' WHERE id=4")
            sql("UPDATE events SET allow_waitlist=0 WHERE id=5")
            sql("UPDATE events SET event_date=DATE_SUB(CURDATE(), INTERVAL 2 DAY) WHERE id=6")
            people = sql("SELECT id,email FROM users WHERE role='user' ORDER BY id LIMIT 3")
            admin_email = sql("SELECT email FROM users WHERE role='admin' ORDER BY id LIMIT 1")[0]['email']
            a, b, c = [Client(ports[i % 2]) for i in range(3)]
            for client, person in zip((a, b, c), people):
                client.login(person['email'])
            admin = Client(ports[1])
            admin.login(admin_email)
            def rejected_registration(email, password):
                _, page = guest.request('/register.php')
                token = re.search(r'name="csrf_token" value="([^"]+)"', page)[1]
                before = len(sql('SELECT id FROM users'))
                status, body = guest.request('/register.php', {'name': 'Regression Student', 'email': email, 'password': password, 'confirm_password': password, 'terms': '1', 'csrf_token': token})
                message = 'Use @taylors.edu.my or @sd.taylors.edu.my.' if '@gmail.com' in email else 'Use at least 8 characters, a letter and a number.'
                return status == 200 and len(sql('SELECT id FROM users')) == before and message in body
            check('External-domain account creation rejected', lambda: rejected_registration('regression@gmail.com', 'Password123!'))
            check('Short password account creation rejected', lambda: rejected_registration('regression@sd.taylors.edu.my', 'Ab1'))
            check('Keyword search uses native PDO without errors', lambda: guest.request('/events.php?q=Design')[0] == 200 and 'SQLSTATE' not in guest.request('/events.php?q=Design')[1])
            check('SQL injection string remains a search value', lambda: 'SQLSTATE' not in guest.request('/events.php?q=' + urllib.parse.quote("' OR '1'='1' --"))[1])
            check('Public direct draft access rejected', lambda: guest.request('/event-detail.php?id=2')[0] == 404)
            check('Student direct draft access rejected', lambda: a.request('/event-detail.php?id=2')[0] == 404)
            check('Admin can preview draft', lambda: admin.request('/event-detail.php?id=2')[0] == 200)
            for event, label in ((2, 'draft'), (3, 'completed'), (4, 'cancelled'), (6, 'past')):
                check(f'Direct booking of {label} event rejected', lambda event=event: not a.action(event, 'join')[1]['success'])
            def feedback(client, event):
                client.request(f'/event-detail.php?id={event}', {'action': 'save_feedback', 'rating': 4, 'comment': 'Regression feedback', 'csrf_token': client.token})
                return int(sql('SELECT COUNT(*) n FROM comments WHERE event_id=?', [event])[0]['n'])
            check('Upcoming feedback POST rejected', lambda: feedback(a, 1) == 0)
            check('Non-attendee past feedback POST rejected', lambda: feedback(a, 3) == 0)
            sql('DELETE FROM registrations')
            sql('DELETE FROM comments')
            sql("INSERT INTO registrations(event_id,user_id,status) VALUES(3,?,'confirmed')", [people[0]['id']])
            check('Confirmed past participant can submit feedback', lambda: feedback(a, 3) == 1)
            check('CSRF rejects booking without writes', lambda: not a.action(1, 'join', 'invalid')[1]['success'] and len(sql('SELECT id FROM registrations WHERE event_id=1')) == 0)
            check('Student admin access rejected', lambda: a.request('/admin/events.php')[0] == 403)
            check('First seat can be booked', lambda: a.action(1, 'join')[1]['success'])
            check('Duplicate registration rejected', lambda: not a.action(1, 'join')[1]['success'])
            check('Full capacity cannot be booked', lambda: not b.action(1, 'join')[1]['success'])
            check('Full-event waitlist insertion works', lambda: b.action(1, 'join-waitlist')[1]['success'])
            reg_b = sql('SELECT id FROM registrations WHERE event_id=1 AND user_id=?', [people[1]['id']])[0]['id']
            def full_manual_promotion():
                admin.request('/admin/attendees.php?id=1', {'action': 'promote', 'reg_id': reg_b, 'csrf_token': admin.token})
                return sql('SELECT status FROM registrations WHERE id=?', [reg_b])[0]['status'] == 'waitlisted'
            check('Admin cannot promote beyond capacity', full_manual_promotion)
            sql("UPDATE registrations SET status='waitlisted',waitlist_position=1 WHERE id=?", [reg_b])
            def rollback_failed_promotion():
                sql("CREATE TRIGGER test_promotion_failure BEFORE UPDATE ON registrations FOR EACH ROW BEGIN IF OLD.status='waitlisted' AND NEW.status='confirmed' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Regression failure'; END IF; END")
                try:
                    result = a.action(1, 'leave')[1]
                    return not result['success'] and len(sql("SELECT id FROM registrations WHERE event_id=1 AND status='confirmed' AND user_id=?", [people[0]['id']])) == 1
                finally:
                    sql('DROP TRIGGER test_promotion_failure')
            check('Failed promotion rolls back cancellation', rollback_failed_promotion)
            check('Disabled waitlist rejects direct request', lambda: not c.action(5, 'join-waitlist')[1]['success'])
            check('Open event does not accept waitlist request', lambda: not c.action(7, 'join-waitlist')[1]['success'])
            def promotion():
                a.action(1, 'leave')
                rows = sql('SELECT user_id,status,waitlist_position FROM registrations WHERE event_id=1')
                return len(rows) == 1 and int(rows[0]['user_id']) == int(people[1]['id']) and rows[0]['status'] == 'confirmed' and rows[0]['waitlist_position'] is None
            check('Cancellation promotes FIFO participant', promotion)
            check('Second participant joins waitlist', lambda: c.action(1, 'join-waitlist')[1]['success'])
            check('Third participant joins waitlist', lambda: a.action(1, 'join-waitlist')[1]['success'])
            check('Middle queue cancellation reindexes positions', lambda: c.action(1, 'leave-waitlist')[1]['success'] and int(sql("SELECT waitlist_position FROM registrations WHERE event_id=1 AND user_id=?", [people[0]['id']])[0]['waitlist_position']) == 1)
            def admin_removal():
                reg_id = sql('SELECT id FROM registrations WHERE event_id=1 AND user_id=?', [people[1]['id']])[0]['id']
                admin.request('/admin/attendees.php?id=1', {'action': 'remove', 'reg_id': reg_id, 'csrf_token': admin.token})
                return sql('SELECT status FROM registrations WHERE event_id=1 AND user_id=?', [people[0]['id']])[0]['status'] == 'confirmed'
            check('Admin removal promotes next participant', admin_removal)
            def concurrent_seat():
                with concurrent.futures.ThreadPoolExecutor(2) as pool:
                    results = list(pool.map(lambda client: client.action(8, 'join')[1]['success'], (a, b)))
                return sum(results) == 1 and int(sql("SELECT COUNT(*) n FROM registrations WHERE event_id=8 AND status='confirmed'")[0]['n']) == 1
            check('Concurrent requests reserve only one final seat', concurrent_seat)
            check('GET logout cannot destroy session', lambda: a.request('/logout.php')[0] == 405 and 'Log out' in a.request('/profile.php')[1])
            check('POST logout rejects invalid CSRF', lambda: a.request('/logout.php', {'csrf_token': 'invalid'})[0] == 400)
            check('POST logout accepts valid CSRF', lambda: 'Log out' not in a.request('/logout.php', {'csrf_token': a.token})[1])
        finally:
            for process in processes:
                process.terminate()
                process.wait(timeout=10)
    print(f'RESULT: {count - len(failures)}/{count} checks passed; {len(failures)} failed.')
    raise SystemExit(bool(failures))


if __name__ == '__main__':
    main()
