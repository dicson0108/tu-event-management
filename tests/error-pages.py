"""Error-page HTTP checks using an isolated Apache site; no database required.

Usage: python tests/error-pages.py --xampp C:/xampp
"""
import argparse
import json
import shutil
import socket
import subprocess
import tempfile
import time
import urllib.error
import urllib.request
from pathlib import Path


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--xampp', type=Path, default=Path('C:/xampp'))
    args = parser.parse_args()
    root = Path(__file__).resolve().parents[1]
    with tempfile.TemporaryDirectory(prefix='tu-errors-') as temporary:
        directory = Path(temporary)
        app = directory / 'TUEventManagement'
        shutil.copytree(root, app, ignore=shutil.ignore_patterns(
            '.git', 'graphify-out', 'UIUX Designs', '*PRD*.md', 'Report*.md', 'tests'))
        with socket.socket() as sock:
            sock.bind(('127.0.0.1', 0))
            port = sock.getsockname()[1]
        # Reserve an unused port so database failure never touches application data.
        with socket.socket() as unavailable_db:
            unavailable_db.bind(('127.0.0.1', 0))
            db_port = unavailable_db.getsockname()[1]
            db_config = app / 'config/db.php'
            db_config.write_text(db_config.read_text().replace(
                "define('DB_PORT', 3306);", f"define('DB_PORT', {db_port});"))
            (app / 'probe-failure.php').write_text(
                "<?php require __DIR__.'/config/app.php'; echo 'PARTIAL OUTPUT'; "
                "throw new RuntimeException('PRIVATE ERROR DETAIL');")
            (app / 'api/probe-failure.php').write_text(
                "<?php require __DIR__.'/../config/app.php'; "
                "throw new RuntimeException('PRIVATE API DETAIL');")
            (app / 'blocked').mkdir()
            (app / 'blocked/.htaccess').write_text('Require all denied\n')
            (app / 'broken').mkdir()
            (app / 'broken/.htaccess').write_text('RewriteEngine On\nRewriteRule ^ - [R=500,L]\n')
            apache = args.xampp / 'apache'
            php = args.xampp / 'php'
            config = directory / 'httpd.conf'
            modules = ['authz_core', 'authz_host', 'dir', 'mime', 'log_config', 'rewrite']
            config.write_text('\n'.join([
                f'ServerRoot "{apache.as_posix()}"',
                f'Listen 127.0.0.1:{port}', 'ServerName localhost',
                f'PidFile "{directory.as_posix()}/httpd.pid"',
                f'ErrorLog "{directory.as_posix()}/apache.log"',
                *[f'LoadModule {name}_module modules/mod_{name}.so' for name in modules],
                f'LoadFile "{php.as_posix()}/php8ts.dll"',
                f'LoadModule php_module "{php.as_posix()}/php8apache2_4.dll"',
                f'PHPIniDir "{php.as_posix()}"',
                'php_flag display_errors Off', 'php_value output_buffering 4096',
                f'DocumentRoot "{directory.as_posix()}"',
                f'<Directory "{directory.as_posix()}">',
                'Require all granted', 'AllowOverride FileInfo AuthConfig', '</Directory>',
                '<FilesMatch "\\.php$">', 'SetHandler application/x-httpd-php', '</FilesMatch>',
                'DirectoryIndex index.php',
            ]) + '\n')
            command = [str(apache / 'bin/httpd.exe'), '-f', str(config)]
            subprocess.run([*command, '-t'], check=True, capture_output=True)
            server = subprocess.Popen([*command, '-X'], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
            base = f'http://127.0.0.1:{port}/TUEventManagement'

            def request(path):
                try:
                    response = urllib.request.urlopen(base + path, timeout=10)
                except urllib.error.HTTPError as error:
                    response = error
                with response:
                    return response.code, response.headers, response.read().decode()

            try:
                for _ in range(100):
                    try:
                        request('/404.php')
                        break
                    except urllib.error.URLError:
                        if server.poll() is not None:
                            raise RuntimeError((directory / 'apache.log').read_text())
                        time.sleep(0.05)
                for code, title in [(404, 'Page not found'), (403, 'Access denied'), (500, 'Something went wrong')]:
                    status, headers, body = request(f'/{code}.php')
                    assert status == code and f'<h1>{title}</h1>' in body, f'{code} page missing or incorrect'
                    assert 'text/html' in headers['Content-Type']
                    assert 'href="/TUEventManagement/index.php"' in body
                    assert 'href="/TUEventManagement/events.php"' in body
                    assert 'id="main-content"' in body
                    print(f'PASS: {code} page renders without database; correct status and recovery links')
                for path, code in [('/missing/deep/page', 404), ('/blocked/', 403), ('/broken/', 500)]:
                    status, _, body = request(path)
                    assert status == code and f'data-error-code="{code}"' in body, (path, status, body)
                    print(f'PASS: Apache {code} routes to shared error page')
                for path in ['/probe-failure.php', '/event-detail.php?id=0']:
                    status, _, body = request(path)
                    assert status == 500 and 'data-error-code="500"' in body, path
                    assert all(secret not in body for secret in ['PRIVATE ERROR DETAIL', 'PARTIAL OUTPUT', 'SQLSTATE', 'Stack trace'])
                    print(f'PASS: {path} returns safe complete 500 page')
                for path in ['/api/probe-failure.php', '/api/register-event.php']:
                    status, headers, body = request(path)
                    # Registration API is unauthenticated; no DB access is needed.
                    assert status == (401 if path.endswith('register-event.php') else 500)
                    assert 'application/json' in headers['Content-Type'] and json.loads(body)['success'] is False
                    assert 'PRIVATE API DETAIL' not in body
                    print(f'PASS: {path} preserves JSON errors')
                status, _, body = request('/assets/css/errors.css')
                assert status == 200 and 'prefers-reduced-motion' in body
                print('PASS: error stylesheet loads')
                assert 'PRIVATE ERROR DETAIL' in (directory / 'apache.log').read_text()
                print('PASS: exception details logged server-side')
            finally:
                server.terminate()
                server.wait(timeout=10)


if __name__ == '__main__':
    main()
