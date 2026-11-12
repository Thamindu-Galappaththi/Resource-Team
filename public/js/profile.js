(function () {
    const page = document.querySelector('[data-rs-profile]');
    if (!page) {
        return;
    }

    const title = page.querySelector('[data-rs-title]');
    const lead = page.querySelector('[data-rs-lead]');
    const tabs = [...page.querySelectorAll('[data-rs-tab-btn]')];
    const copy = {
        profile: {
            title: 'My Profile',
            lead: 'Update the details used on reservations. Password changes are on the Security tab.',
        },
        security: {
            title: 'Security',
            lead: 'Change your password here. Contact details stay on the Profile tab.',
        },
    };

    function showTab(name, url, replace) {
        const tab = name === 'security' ? 'security' : 'profile';
        page.dataset.rsTab = tab;
        page.querySelectorAll('[data-rs-panel]').forEach((panel) => {
            panel.hidden = panel.getAttribute('data-rs-panel') !== tab;
        });
        tabs.forEach((button) => {
            const active = button.getAttribute('data-rs-tab-btn') === tab;
            button.classList.toggle('is-active', active);
            if (active) {
                button.setAttribute('aria-current', 'page');
            } else {
                button.removeAttribute('aria-current');
            }
        });
        if (title) {
            title.textContent = copy[tab].title;
        }
        if (lead) {
            lead.textContent = copy[tab].lead;
        }
        document.title = copy[tab].title;
        if (url) {
            history[replace ? 'replaceState' : 'pushState']({ rsTab: tab }, '', url);
        }
    }

    tabs.forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            const tab = button.getAttribute('data-rs-tab-btn');
            if (tab === page.dataset.rsTab) {
                return;
            }
            showTab(tab, button.getAttribute('href'), false);
        });
    });

    window.addEventListener('popstate', () => {
        const params = new URLSearchParams(window.location.search);
        showTab(params.get('tab') === 'security' ? 'security' : 'profile');
    });

    function showAlert(target, message, type) {
        const host = page.querySelector(`[data-rs-alert="${target}"]`);
        if (!host) {
            return;
        }
        host.innerHTML = `<div class="rs-alert rs-alert-${type === 'danger' ? 'err' : 'ok'}" role="${type === 'danger' ? 'alert' : 'status'}">${message}</div>`;
    }

    function csrfHeaders() {
        return {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
        };
    }

    function firstError(data) {
        if (data?.errors) {
            const messages = Object.values(data.errors).flat();
            if (messages.length) {
                return messages[0];
            }
        }
        return data?.message || 'Could not save those details.';
    }

    function applyProfile(user) {
        if (!user) {
            return;
        }
        if (user.name) {
            page.querySelector('.rs-avatar-meta strong')?.replaceChildren(document.createTextNode(user.name));
            const greeting = document.querySelector('#greeting b');
            if (greeting) {
                greeting.textContent = user.name;
            }
        }
        if (user.avatar_url) {
            page.querySelector('.rs-avatar-block img')?.setAttribute('src', user.avatar_url);
            document.getElementById('headerAvatar')?.setAttribute('src', user.avatar_url);
        }
        const removeWrap = document.getElementById('remove_avatar')?.closest('.form-check');
        if (removeWrap) {
            removeWrap.hidden = !user.has_avatar;
            if (!user.has_avatar) {
                const box = document.getElementById('remove_avatar');
                if (box) {
                    box.checked = false;
                }
            }
        }
    }

    function submitAjax(form, { alert, onSuccess } = {}) {
        const submit = form.querySelector('[type="submit"]');
        if (submit) {
            submit.disabled = true;
        }
        return fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: csrfHeaders(),
            credentials: 'same-origin',
        }).then(async (response) => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(firstError(data));
            }
            if (data.csrf_token) {
                const meta = document.querySelector('meta[name="csrf-token"]');
                if (meta) {
                    meta.setAttribute('content', data.csrf_token);
                }
                form.querySelectorAll('input[name="_token"]').forEach((input) => {
                    input.value = data.csrf_token;
                });
                document.querySelectorAll('input[name="_token"]').forEach((input) => {
                    input.value = data.csrf_token;
                });
            }
            showAlert(alert, data.message || 'Saved.', 'success');
            onSuccess?.(data);
        }).catch((error) => {
            showAlert(alert, error.message || 'Could not save those details.', 'danger');
        }).finally(() => {
            if (submit) {
                submit.disabled = false;
            }
        });
    }

    document.getElementById('rs-profile-form')?.addEventListener('submit', (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        if (!window.RMSelect?.getValue('rs-location')) {
            showAlert('profile', 'Select a location.', 'danger');
            return;
        }
        submitAjax(form, {
            alert: 'profile',
            onSuccess: (data) => {
                const avatar = form.querySelector('#avatar');
                if (avatar) {
                    avatar.value = '';
                }
                const fileName = document.getElementById('rs-file-name');
                if (fileName) {
                    fileName.textContent = 'No file chosen';
                }
                applyProfile(data.user);
            },
        });
    });

    document.getElementById('rs-password-form')?.addEventListener('submit', (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        submitAjax(form, {
            alert: 'security',
            onSuccess: () => form.reset(),
        });
    });

    document.getElementById('avatar')?.addEventListener('change', (event) => {
        const file = event.target.files?.[0];
        const name = document.getElementById('rs-file-name');
        if (name) {
            name.textContent = file ? file.name : 'No file chosen';
        }
    });

    page.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            const wrap = button.closest('.rs-password-wrap');
            const input = wrap?.querySelector('input')
                || document.getElementById(button.getAttribute('data-password-toggle'));
            if (!(input instanceof HTMLInputElement)) {
                return;
            }
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            button.querySelector('i')?.classList.toggle('ti-eye', !show);
            button.querySelector('i')?.classList.toggle('ti-eye-off', show);
        });
    });
}());
