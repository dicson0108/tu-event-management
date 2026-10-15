/**
 * Taylor's University Event Management App
 * Client-side dynamic interactions (AJAX, Modals, Menus, Toasts, Responsive Navigation)
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Toast Notification System
    window.showToast = function(message, type = 'info', duration = 3500) {
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

        const icons = {
            success: `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>`,
            error: `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`,
            warning: `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`,
            info: `<svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>`
        };

        toast.innerHTML = `
            <div class="toast-icon">${icons[type] || icons.info}</div>
            <div class="toast-content">${message}</div>
            <button type="button" class="toast-close" aria-label="Close notification">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        `;

        toast.querySelector('.toast-close').addEventListener('click', () => {
            removeToast(toast);
        });

        container.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('visible'));

        const timeout = setTimeout(() => {
            removeToast(toast);
        }, duration);

        function removeToast(t) {
            clearTimeout(timeout);
            t.classList.remove('visible');
            t.addEventListener('transitionend', () => t.remove(), { once: true });
        }
    };

    // 2. Custom Accessible Confirmation Dialog
    window.showConfirm = function({ title = 'Confirm Action', message = 'Are you sure you want to proceed?', confirmText = 'Confirm', cancelText = 'Cancel', isDanger = false } = {}) {
        return new Promise((resolve) => {
            let modal = document.getElementById('tu-confirm-modal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'tu-confirm-modal';
                modal.className = 'modal-backdrop confirm-backdrop';
                modal.setAttribute('role', 'dialog');
                modal.setAttribute('aria-modal', 'true');
                modal.innerHTML = `
                    <div class="modal-card confirm-modal-card">
                        <div class="confirm-modal-header">
                            <h3 class="confirm-modal-title" id="tu-confirm-title"></h3>
                        </div>
                        <p class="confirm-modal-body" id="tu-confirm-body"></p>
                        <div class="confirm-modal-actions">
                            <button type="button" class="btn-secondary" id="tu-confirm-cancel">${cancelText}</button>
                            <button type="button" class="btn-primary" id="tu-confirm-ok">${confirmText}</button>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
            }

            const titleEl = document.getElementById('tu-confirm-title');
            const bodyEl = document.getElementById('tu-confirm-body');
            const cancelBtn = document.getElementById('tu-confirm-cancel');
            const okBtn = document.getElementById('tu-confirm-ok');

            titleEl.textContent = title;
            bodyEl.textContent = message;
            cancelBtn.textContent = cancelText;
            okBtn.textContent = confirmText;

            if (isDanger) {
                okBtn.classList.remove('btn-primary');
                okBtn.classList.add('btn-danger');
            } else {
                okBtn.classList.remove('btn-danger');
                okBtn.classList.add('btn-primary');
            }

            modal.classList.add('show');
            okBtn.focus();

            function cleanup(result) {
                modal.classList.remove('show');
                cancelBtn.removeEventListener('click', onCancel);
                okBtn.removeEventListener('click', onOk);
                document.removeEventListener('keydown', onKeyDown);
                modal.removeEventListener('click', onBackdropClick);
                resolve(result);
            }

            function onCancel() { cleanup(false); }
            function onOk() { cleanup(true); }
            function onBackdropClick(e) { if (e.target === modal) cleanup(false); }
            function onKeyDown(e) { if (e.key === 'Escape') cleanup(false); }

            cancelBtn.addEventListener('click', onCancel);
            okBtn.addEventListener('click', onOk);
            modal.addEventListener('click', onBackdropClick);
            document.addEventListener('keydown', onKeyDown);
        });
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
