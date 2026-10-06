document.addEventListener('DOMContentLoaded', function () {

    document.body.classList.add('loaded');

    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');
    const loginForm = document.getElementById('loginForm');

    usernameInput?.addEventListener('input', () => {
        usernameInput.classList.remove('is-invalid');
        usernameInput.closest('.field')?.classList.remove('is-invalid');
    });

    passwordInput?.addEventListener('input', () => {
        passwordInput.classList.remove('is-invalid');
        passwordInput.closest('.field')?.classList.remove('is-invalid');
    });

    loginForm?.addEventListener('submit', function (e) {
        let valid = true;

        if (usernameInput && !usernameInput.value.trim()) {
            usernameInput.classList.add('is-invalid');
            usernameInput.closest('.field')?.classList.add('is-invalid');
            valid = false;
        }

        if (passwordInput && !passwordInput.value.trim()) {
            passwordInput.classList.add('is-invalid');
            passwordInput.closest('.field')?.classList.add('is-invalid');
            valid = false;
        }

        if (!valid) e.preventDefault();
    });

    const togglePassword = document.getElementById('togglePassword');
    const togglePasswordIcon = document.getElementById('togglePasswordIcon');

    togglePassword?.addEventListener('click', () => {
        if (!passwordInput || !togglePasswordIcon) {
            return;
        }
        const hidden = passwordInput.type === 'password';
        passwordInput.type = hidden ? 'text' : 'password';
        togglePasswordIcon.classList.toggle('bi-eye');
        togglePasswordIcon.classList.toggle('bi-eye-slash');
    });

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordToggle);
            const icon = button.querySelector('i');
            if (!input || !icon) {
                return;
            }
            const showingPassword = input.type === 'text';
            input.type = showingPassword ? 'password' : 'text';
            icon.classList.toggle('bi-eye', showingPassword);
            icon.classList.toggle('bi-eye-slash', !showingPassword);
            button.setAttribute('aria-label', showingPassword ? 'Show password' : 'Hide password');
        });
    });

    initStarfield();
});

function initStarfield() {
    const canvas = document.getElementById('starfield');
    const catalog = window.NEBULA_STARS;
    if (!canvas || !catalog || !catalog.stars || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const ctx = canvas.getContext('2d');
    const imgW = catalog.width || 3582;
    const imgH = catalog.height || 1978;

    // Only animate the clearly visible bright stars — top 28 by size score
    const visible = catalog.stars
        .slice()
        .sort(function (a, b) { return b.s - a.s; })
        .slice(0, 28);

    const stars = visible.map(function (star) {
        return {
            x: star.x,
            y: star.y,
            size: star.s,
            phase: Math.random() * Math.PI * 2,
            speed: 0.18 + Math.random() * 0.7,
            shimmerPhase: Math.random() * Math.PI * 2,
            shimmerSpeed: 1.2 + Math.random() * 2.8,
        };
    });

    let width = 0;
    let height = 0;
    let animationId = 0;
    let lastTime = 0;

    function resize() {
        width = window.innerWidth;
        height = window.innerHeight;
        var ratio = window.devicePixelRatio || 1;
        canvas.width = Math.floor(width * ratio);
        canvas.height = Math.floor(height * ratio);
        canvas.style.width = width + 'px';
        canvas.style.height = height + 'px';
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    }

    // Map normalized photo coords to screen coords using cover logic
    function coverPoint(nx, ny) {
        var scale = Math.max(width / imgW, height / imgH);
        var drawW = imgW * scale;
        var drawH = imgH * scale;
        return {
            x: (width - drawW) / 2 + nx * drawW,
            y: (height - drawH) / 2 + ny * drawH,
        };
    }

    function drawStar(x, y, size, pulse) {
        // pulse: 0.0 = dark, 1.0 = full brightness
        var brightness = pulse;

        // Glow halo — large soft radial gradient
        var glowR = (8 + size * 40) * (0.5 + 0.5 * brightness);
        var grd = ctx.createRadialGradient(x, y, 0, x, y, glowR);
        var glowAlpha = (0.28 + size * 0.5) * brightness;
        grd.addColorStop(0.0, 'rgba(255,252,240,' + Math.min(1, glowAlpha * 1.4) + ')');
        grd.addColorStop(0.3, 'rgba(210,230,255,' + (glowAlpha * 0.7) + ')');
        grd.addColorStop(1.0, 'rgba(200,220,255,0)');
        ctx.beginPath();
        ctx.fillStyle = grd;
        ctx.arc(x, y, glowR, 0, Math.PI * 2);
        ctx.fill();

        // Diffraction spike helper
        function spike(angle, length, lineW, alpha) {
            ctx.save();
            ctx.translate(x, y);
            ctx.rotate(angle);
            var grad = ctx.createLinearGradient(0, -length, 0, length);
            grad.addColorStop(0,   'rgba(255,255,255,0)');
            grad.addColorStop(0.45,'rgba(255,255,255,' + alpha + ')');
            grad.addColorStop(0.5, 'rgba(255,255,255,' + Math.min(1, alpha * 1.15) + ')');
            grad.addColorStop(0.55,'rgba(255,255,255,' + alpha + ')');
            grad.addColorStop(1,   'rgba(255,255,255,0)');
            ctx.strokeStyle = grad;
            ctx.lineWidth = lineW;
            ctx.lineCap = 'round';
            ctx.beginPath();
            ctx.moveTo(0, -length);
            ctx.lineTo(0, length);
            ctx.stroke();
            ctx.restore();
        }

        // Main cross spikes (vertical + horizontal)
        var spikeLen = (10 + size * 50) * brightness;
        var spikeW   = 0.8 + size * 2.0;
        var spikeA   = (0.5 + size * 0.45) * brightness;

        if (spikeLen > 1) {
            spike(0,            spikeLen,        spikeW,        spikeA);
            spike(Math.PI / 2,  spikeLen * 0.9,  spikeW * 0.9,  spikeA * 0.9);
        }

        // Diagonal shorter spikes for the brighter stars
        if (size > 0.45 && spikeLen > 4) {
            spike(Math.PI / 4,  spikeLen * 0.38, spikeW * 0.5, spikeA * 0.45);
            spike(-Math.PI / 4, spikeLen * 0.38, spikeW * 0.5, spikeA * 0.45);
        }

        // Bright core dot
        var coreR = (0.6 + size * 2.2) * (0.4 + 0.6 * brightness);
        var coreA = (0.55 + size * 0.4) * brightness;
        ctx.beginPath();
        ctx.fillStyle = 'rgba(255,255,250,' + Math.min(1, coreA) + ')';
        ctx.arc(x, y, coreR, 0, Math.PI * 2);
        ctx.fill();
    }

    function draw(time) {
        if (!lastTime) lastTime = time;
        var delta = Math.min(32, time - lastTime) / 16.67;
        lastTime = time;

        ctx.clearRect(0, 0, width, height);

        for (var i = 0; i < stars.length; i++) {
            var star = stars[i];
            star.phase        += star.speed        * 0.018 * delta;
            star.shimmerPhase += star.shimmerSpeed * 0.022 * delta;

            // Primary slow breathe: goes between 0 and 1
            var breathe = 0.5 + 0.5 * Math.sin(star.phase);

            // Secondary fast shimmer: small extra flicker
            var shimmer = 0.88 + 0.12 * Math.sin(star.shimmerPhase);

            // Combined: breathe drives most of the swing, shimmer adds micro-flicker
            var pulse = breathe * shimmer;

            // Dim small stars more aggressively so only bright ones blaze
            var minBrightness = star.size < 0.4 ? 0.05 : 0.12;
            pulse = minBrightness + (1.0 - minBrightness) * pulse;

            var pt = coverPoint(star.x, star.y);
            if (pt.x < -60 || pt.y < -60 || pt.x > width + 60 || pt.y > height + 60) {
                continue;
            }

            drawStar(pt.x, pt.y, star.size, pulse);
        }

        animationId = window.requestAnimationFrame(draw);
    }

    function start() {
        if (!animationId) {
            lastTime = 0;
            animationId = window.requestAnimationFrame(draw);
        }
    }

    function stop() {
        window.cancelAnimationFrame(animationId);
        animationId = 0;
    }

    window.addEventListener('resize', resize);
    document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
            stop();
        } else {
            start();
        }
    });

    resize();
    start();
}
