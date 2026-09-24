const form = document.getElementById('member-profile-form');
if (form) {
    const input = document.getElementById('photo');
    const preview = document.getElementById('photo-preview');
    const initials = document.getElementById('photo-initials');
    input?.addEventListener('change', () => {
        const file = input.files?.[0];
        if (!file || !preview) {
            return;
        }
        preview.src = URL.createObjectURL(file);
        preview.classList.remove('d-none');
        initials?.classList.add('d-none');
    });
}
