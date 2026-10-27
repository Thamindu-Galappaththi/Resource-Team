(function bindSltRequestsPager() {
    let pending = null;

    async function loadPage(url) {
        const current = document.getElementById('slt-results');
        if (!current) {
            return;
        }

        pending?.abort();
        pending = new AbortController();
        current.classList.add('is-loading');

        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
                signal: pending.signal,
                credentials: 'same-origin',
            });
            if (!response.ok) {
                throw new Error('Unable to load requests.');
            }

            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            const next = page.querySelector('#slt-results');
            if (!next) {
                throw new Error('Unable to load requests.');
            }

            current.replaceWith(next);
            history.replaceState(null, '', url);
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }
            document.getElementById('slt-results')?.classList.remove('is-loading');
        }
    }

    document.addEventListener('click', function (event) {
        const link = event.target.closest('#slt-results .pagination a.page-link');
        if (!link) {
            return;
        }

        event.preventDefault();
        loadPage(link.href);
    });
})();

(function bindSltRequestActions() {
    const swalFire = (options) => (window.RMSwal?.fire ? window.RMSwal.fire(options) : window.Swal?.fire?.(options));

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (character) => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
        }[character]));
    }

    document.addEventListener('click', async function (event) {
        const button = event.target.closest('[data-slt-delete]');
        if (!button || button.disabled) {
            return;
        }

        event.preventDefault();
        const needsReason = button.dataset.reason === '1';
        const result = await swalFire({
            title: button.dataset.title || 'Delete this request?',
            html: `<p class="slt-swal-sub">${escapeHtml(button.dataset.sub || '')}</p>${needsReason ? '<label class="visually-hidden" for="slt-swal-reason">Cancellation reason</label><textarea id="slt-swal-reason" class="form-control slt-swal-reason" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" data-lpignore="true" data-1p-ignore="true" placeholder="Cancellation reason" rows="3"></textarea>' : ''}`,
            showCancelButton: true,
            confirmButtonText: 'Delete',
            cancelButtonText: 'Keep',
            reverseButtons: true,
            focusConfirm: false,
            allowOutsideClick: false,
            scrollbarPadding: false,
            heightAuto: false,
            width: 480,
            preConfirm: () => {
                if (!needsReason) {
                    return true;
                }
                const value = document.getElementById('slt-swal-reason')?.value.trim() || '';
                if (!value) {
                    window.Swal?.showValidationMessage('A reason is required.');
                    return false;
                }
                return value;
            },
            customClass: {
                ...(window.RMSwal?.base?.customClass || {}),
                popup: 'rm-swal slt-swal',
                title: 'slt-swal-title',
                htmlContainer: 'slt-swal-body',
                confirmButton: 'btn btn-danger rm-swal-btn',
                cancelButton: 'btn btn-outline-secondary rm-swal-btn',
                actions: 'slt-swal-actions',
                container: 'slt-swal-backdrop',
            },
        });

        if (!result?.isConfirmed) {
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = button.dataset.action;
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const method = (button.dataset.method || 'POST').toUpperCase();
        form.innerHTML = `<input type="hidden" name="_token" value="${token}">${method !== 'POST' ? `<input type="hidden" name="_method" value="${method}">` : ''}`;
        if (needsReason) {
            const reason = document.createElement('input');
            reason.type = 'hidden';
            reason.name = 'cancellation_reason';
            reason.value = String(result.value || '').trim();
            form.appendChild(reason);
        }
        document.body.appendChild(form);
        form.submit();
    });
})();

(function bindSltCalendar() {
    const root = document.querySelector('.slt-cal');
    const pop = document.getElementById('slt-cal-pop');
    if (!root || !pop) {
        return;
    }

    const titleEl = document.getElementById('slt-cal-pop-title');
    const listEl = document.getElementById('slt-cal-pop-list');
    const createEl = document.getElementById('slt-cal-pop-create');

    function closePop() {
        pop.hidden = true;
    }

    function openDay(cell) {
        let items = [];
        try {
            items = JSON.parse(cell.dataset.items || '[]');
        } catch (error) {
            items = [];
        }

        if (!items.length) {
            if (cell.dataset.create) {
                window.location.href = cell.dataset.create;
            }
            return;
        }

        titleEl.textContent = cell.dataset.label || cell.dataset.date || '';
        createEl.href = cell.dataset.create || '#';
        listEl.replaceChildren();

        items.forEach(function (item) {
            const mine = Boolean(item.mine && item.href);
            const node = document.createElement(mine ? 'a' : 'div');
            node.className = 'slt-cal-pop__item slt-status--' + (item.tone || 'pending') + (mine ? ' is-mine' : '');
            node.textContent = item.title || 'Reservation';
            if (mine) {
                node.href = item.href;
            }
            listEl.appendChild(node);
        });

        pop.hidden = false;
    }

    root.addEventListener('click', function (event) {
        if (event.target.closest('.slt-cal__day, .slt-cal__event[href], .slt-cal__nav')) {
            return;
        }

        const more = event.target.closest('[data-slt-more]');
        const cell = event.target.closest('.slt-cal__cell:not(.is-empty)');
        if (!cell) {
            return;
        }

        event.preventDefault();
        openDay(cell);
        more?.blur();
    });

    pop.addEventListener('click', function (event) {
        if (event.target === pop || event.target.closest('[data-slt-pop-close]')) {
            closePop();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !pop.hidden) {
            closePop();
        }
    });
})();

document.addEventListener('DOMContentLoaded', function () {
    const slides = Array.from(document.querySelectorAll('.slt-hub__slide'));
    const dotsWrap = document.querySelector('[data-slt-dots]');
    const captionEl = document.querySelector('[data-slt-caption]');
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const intervalMs = 6500;
    let index = 0;
    let timer = 0;

    function setSlide(next) {
        if (!slides.length) {
            return;
        }
        slides[index].classList.remove('is-active');
        index = (next + slides.length) % slides.length;
        slides[index].classList.add('is-active');
        if (captionEl) {
            captionEl.textContent = slides[index].getAttribute('data-caption') || '';
        }
        if (dotsWrap) {
            Array.from(dotsWrap.children).forEach(function (dot, i) {
                dot.classList.toggle('is-active', i === index);
                dot.setAttribute('aria-selected', i === index ? 'true' : 'false');
            });
        }
    }

    if (slides.length > 1 && dotsWrap) {
        slides.forEach(function (_, i) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'slt-hub__dot' + (i === 0 ? ' is-active' : '');
            button.setAttribute('role', 'tab');
            button.setAttribute('aria-label', 'Show background ' + (i + 1));
            button.setAttribute('aria-selected', i === 0 ? 'true' : 'false');
            button.addEventListener('click', function () {
                setSlide(i);
                restart();
            });
            dotsWrap.appendChild(button);
        });
    }

    function tick() {
        setSlide(index + 1);
    }

    function restart() {
        if (timer) {
            window.clearInterval(timer);
            timer = 0;
        }
        if (!reduced && slides.length > 1) {
            timer = window.setInterval(tick, intervalMs);
        }
    }

    restart();

    const sky = document.querySelector('.slt-hub__sky');
    if (sky && timer) {
        sky.addEventListener('mouseenter', function () {
            if (timer) {
                window.clearInterval(timer);
                timer = 0;
            }
        });
        sky.addEventListener('mouseleave', restart);
    }

    const canvas = document.getElementById('slt-hub-stars');
    if (!canvas || reduced) {
        return;
    }

    const ctx = canvas.getContext('2d');
    const dots = Array.from({ length: 36 }, function () {
        return {
            x: Math.random(),
            y: Math.random(),
            r: 0.6 + Math.random() * 1.6,
            speed: 0.08 + Math.random() * 0.22,
            phase: Math.random() * Math.PI * 2,
        };
    });

    let width = 0;
    let height = 0;
    let frame = 0;

    function resize() {
        const ratio = window.devicePixelRatio || 1;
        width = canvas.clientWidth;
        height = canvas.clientHeight;
        canvas.width = Math.floor(width * ratio);
        canvas.height = Math.floor(height * ratio);
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    }

    function draw(time) {
        ctx.clearRect(0, 0, width, height);
        dots.forEach(function (dot) {
            dot.phase += dot.speed * 0.02;
            const pulse = 0.35 + 0.65 * (0.5 + 0.5 * Math.sin(dot.phase + time / 900));
            const x = dot.x * width;
            const y = (dot.y * height + Math.sin(dot.phase) * 8 + height) % height;
            ctx.beginPath();
            ctx.fillStyle = 'rgba(255,255,255,' + (0.18 + pulse * 0.55) + ')';
            ctx.arc(x, y, dot.r * pulse, 0, Math.PI * 2);
            ctx.fill();
        });
        frame = window.requestAnimationFrame(draw);
    }

    window.addEventListener('resize', resize);
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            window.cancelAnimationFrame(frame);
            frame = 0;
        } else if (!frame) {
            frame = window.requestAnimationFrame(draw);
        }
    });

    resize();
    frame = window.requestAnimationFrame(draw);
});
