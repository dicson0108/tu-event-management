/**
 * Taylor's University Event Management App
 * Client-side dynamic interactions (AJAX, Modals, Menus)
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. User dropdown menu toggle
    const userMenuPill = document.querySelector('.user-menu-pill');
    const userDropdown = document.querySelector('.user-dropdown');
    if (userMenuPill && userDropdown) {
        userMenuPill.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('show');
        });
        document.addEventListener('click', () => {
            userDropdown.classList.remove('show');
        });
    }

    // 2. Modal open & close handlers
    document.querySelectorAll('[data-modal-open]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modalId = btn.getAttribute('data-modal-open');
            const targetModal = document.getElementById(modalId);
            if (targetModal) {
                targetModal.classList.add('show');
            }
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal-backdrop');
            if (modal) {
                modal.classList.remove('show');
            }
        });
    });

    // Close on backdrop click
    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) {
                backdrop.classList.remove('show');
            }
        });
    });

    // 3. Interactive Star Rating selector on review forms
    const starContainer = document.querySelector('.star-rating-selector');
    if (starContainer) {
        const ratingInput = document.getElementById('selected-rating-input');
        const stars = starContainer.querySelectorAll('.star-icon');
        stars.forEach(star => {
            star.addEventListener('click', () => {
                const val = parseInt(star.getAttribute('data-val'), 10);
                if (ratingInput) ratingInput.value = val;
                stars.forEach(s => {
                    const sVal = parseInt(s.getAttribute('data-val'), 10);
                    if (sVal <= val) {
                        s.classList.add('active');
                        s.style.color = '#c81e27';
                    } else {
                        s.classList.remove('active');
                        s.style.color = '#d1d5db';
                    }
                });
            });
        });
    }

    // 4. AJAX Registration action (Join, Leave, Waitlist, Leave Waitlist)
    document.querySelectorAll('[data-action-event]').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const eventId = btn.getAttribute('data-event-id');
            const action = btn.getAttribute('data-action-event');
            const csrfToken = btn.getAttribute('data-csrf') || document.querySelector('meta[name="csrf-token"]')?.content;

            if (!confirm(`Are you sure you want to ${action.replace('-', ' ')}?`)) {
                return;
            }

            btn.disabled = true;
            try {
                const response = await fetch('/api/register-event.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        event_id: parseInt(eventId, 10),
                        action: action,
                        csrf_token: csrfToken
                    })
                });
                const res = await response.json();
                if (res.success) {
                    window.location.reload();
                } else {
                    alert(res.message || 'Action failed.');
                    btn.disabled = false;
                }
            } catch (err) {
                console.error(err);
                alert('An error occurred. Please try again.');
                btn.disabled = false;
            }
        });
    });
});
