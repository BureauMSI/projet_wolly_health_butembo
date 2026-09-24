const eye = 'bi-eye';
const eyeOff = 'bi-eye-slash';

document.querySelectorAll('[data-toggle-password]').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.getElementById(button.getAttribute('data-toggle-password'));
        if (!input) {
            return;
        }

        const hidden = input.type === 'password';
        input.type = hidden ? 'text' : 'password';
        const icon = button.querySelector('i');
        if (icon) {
            icon.classList.toggle(eye, !hidden);
            icon.classList.toggle(eyeOff, hidden);
        }
        button.setAttribute(
            'aria-label',
            hidden ? button.dataset.hideLabel || '' : button.dataset.showLabel || '',
        );
    });
});
