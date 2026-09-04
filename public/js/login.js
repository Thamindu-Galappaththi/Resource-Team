document.addEventListener('DOMContentLoaded', function () {

    document.body.classList.add('loaded');

    const usernameInput = document.getElementById('username');
    const passwordInput = document.getElementById('password');

    usernameInput.addEventListener('input', () => {
        usernameInput.classList.remove('is-invalid');
        usernameInput.closest('.field')?.classList.remove('is-invalid');
    });

    passwordInput.addEventListener('input', () => {
        passwordInput.classList.remove('is-invalid');
        passwordInput.closest('.field')?.classList.remove('is-invalid');
    });

    document.getElementById('loginForm').addEventListener('submit', function (e) {
        let valid = true;

        if (!usernameInput.value.trim()) {
            usernameInput.classList.add('is-invalid');
            usernameInput.closest('.field')?.classList.add('is-invalid');
            valid = false;
        }

        if (!passwordInput.value.trim()) {
            passwordInput.classList.add('is-invalid');
            passwordInput.closest('.field')?.classList.add('is-invalid');
            valid = false;
        }

        if (!valid) e.preventDefault();
    });

    const togglePassword = document.getElementById('togglePassword');
    const togglePasswordIcon = document.getElementById('togglePasswordIcon');

    togglePassword.addEventListener('click', () => {
        const hidden = passwordInput.type === 'password';
        passwordInput.type = hidden ? 'text' : 'password';
        togglePasswordIcon.classList.toggle('bi-eye');
        togglePasswordIcon.classList.toggle('bi-eye-slash');
    });

});
