/**
 * Taylor's University Event Management App
 * Client-side dynamic interactions (AJAX, Modals, Menus, Toasts, Responsive Navigation)
 */

document.addEventListener('DOMContentLoaded', () => {
    // Enable instantaneous :active state on iOS Safari & mobile devices
    document.addEventListener('touchstart', () => {}, { passive: true });

    // 1. Toast Notification System
    window.showToast = function(message, type = 'info', duration = 3000) {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container';
            container.setAttribute('aria-live', 'polite');
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast-pill toast-${type}`;
        toast.textContent = message;
        container.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('visible'));

        setTimeout(() => {
            toast.classList.remove('visible');
            toast.addEventListener('transitionend', () => toast.remove(), { once: true });
        }, duration);
    };

    // 2. Confirmation Dialog (native prompt fallback or dialog)
    window.showConfirm = function({ message = 'Are you sure you want to proceed?' } = {}) {
        return Promise.resolve(window.confirm(message));
    };

    // 3. User dropdown menu toggle & keyboard accessibility
    const userMenuPill = document.querySelector('.user-menu-pill');
    const userDropdown = document.querySelector('.user-dropdown');
    if (userMenuPill && userDropdown) {
        userMenuPill.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = userDropdown.classList.contains('show');
            userDropdown.classList.toggle('show');
            userMenuPill.setAttribute('aria-expanded', !isOpen);
        });
        document.addEventListener('click', () => {
            userDropdown.classList.remove('show');
            userMenuPill.setAttribute('aria-expanded', 'false');
        });
    }

    // 4. Mobile Menu Drawer Navigation
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const mobileDrawer = document.querySelector('.mobile-drawer');
    const mobileDrawerClose = document.querySelector('.mobile-drawer-close');
    const mobileDrawerBackdrop = document.querySelector('.mobile-drawer-backdrop');

    if (mobileMenuBtn && mobileDrawer) {
        const toggleDrawer = (open) => {
            if (open) {
                mobileDrawer.classList.add('open');
                if (mobileDrawerBackdrop) mobileDrawerBackdrop.classList.add('open');
                mobileMenuBtn.setAttribute('aria-expanded', 'true');
                document.body.style.overflow = 'hidden';
            } else {
                mobileDrawer.classList.remove('open');
                if (mobileDrawerBackdrop) mobileDrawerBackdrop.classList.remove('open');
                mobileMenuBtn.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
            }
        };

        mobileMenuBtn.addEventListener('click', () => toggleDrawer(!mobileDrawer.classList.contains('open')));
        if (mobileDrawerClose) mobileDrawerClose.addEventListener('click', () => toggleDrawer(false));
        if (mobileDrawerBackdrop) mobileDrawerBackdrop.addEventListener('click', () => toggleDrawer(false));
    }

    // 5. Admin Mobile Sidebar Toggle
    const adminMobileToggle = document.querySelector('.admin-mobile-toggle');
    const adminSidebar = document.querySelector('.admin-sidebar');
    if (adminMobileToggle && adminSidebar) {
        adminMobileToggle.addEventListener('click', () => {
            adminSidebar.classList.toggle('mobile-open');
        });
        document.addEventListener('click', (e) => {
            if (!adminSidebar.contains(e.target) && !adminMobileToggle.contains(e.target)) {
                adminSidebar.classList.remove('mobile-open');
            }
        });
    }

    // 6. Modal Open & Close Handlers (General Modals)
    document.querySelectorAll('[data-modal-open]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modalId = btn.getAttribute('data-modal-open');
            const targetModal = document.getElementById(modalId);
            if (targetModal) {
                targetModal.classList.add('show');
                const firstInput = targetModal.querySelector('input:not([type="hidden"]), textarea, select, button');
                if (firstInput) firstInput.focus();
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

    document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) {
                backdrop.classList.remove('show');
            }
        });
    });

    // Close any modal or dropdown with Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-backdrop.show').forEach(m => m.classList.remove('show'));
            if (userDropdown) userDropdown.classList.remove('show');
            if (mobileDrawer && mobileDrawer.classList.contains('open')) {
                mobileDrawer.classList.remove('open');
                if (mobileDrawerBackdrop) mobileDrawerBackdrop.classList.remove('open');
                document.body.style.overflow = '';
            }
            if (adminSidebar) adminSidebar.classList.remove('mobile-open');
        }
    });

    // 7. Interactive Star Rating selector on review forms
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

    // 8. AJAX Registration action (Join, Leave, Waitlist, Leave Waitlist) with Toast & Confirm
    document.querySelectorAll('[data-action-event]').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const eventId = btn.getAttribute('data-event-id');
            const action = btn.getAttribute('data-action-event');
            const csrfToken = btn.getAttribute('data-csrf') || document.querySelector('meta[name="csrf-token"]')?.content;

            const isLeaveAction = action.includes('leave');
            const actionVerb = action.replace('-', ' ');
            const confirmed = await window.showConfirm({
                title: isLeaveAction ? 'Leave Event' : 'Event Registration',
                message: isLeaveAction 
                    ? `Are you sure you want to cancel your spot? Your seat will be offered to waitlisted members.`
                    : `Confirm registration for this event?`,
                confirmText: isLeaveAction ? 'Yes, cancel spot' : 'Confirm spot',
                cancelText: 'Go back',
                isDanger: isLeaveAction
            });

            if (!confirmed) return;

            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = `<span class="btn-spinner"></span> Processing...`;

            try {
                const response = await fetch((document.querySelector('meta[name="app-base-path"]')?.content || '') + '/api/register-event.php', {
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
                    window.showToast(res.message || 'Updated successfully!', 'success');
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    window.showToast(res.message || 'Action failed.', 'error');
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            } catch (err) {
                console.error(err);
                window.showToast('Network error occurred. Please try again.', 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        });
    });

    // 10. Floating Back-to-Top Button Handler
    const backToTopBtn = document.getElementById('back-to-top');
    if (backToTopBtn) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                backToTopBtn.classList.add('visible');
            } else {
                backToTopBtn.classList.remove('visible');
            }
        }, { passive: true });

        backToTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
});
