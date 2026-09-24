const form = document.getElementById('member-form');
if (form) {
    const directWrap = document.getElementById('direct-membership-wrap');
    const indirectWrap = document.getElementById('indirect-membership-wrap');
    const clientSelect = document.getElementById('source_client_id');

    const toggle = () => {
        const indirect = form.querySelector('input[name="membership_type"]:checked')?.value === 'indirect';
        directWrap?.classList.toggle('d-none', indirect);
        indirectWrap?.classList.toggle('d-none', !indirect);
        clientSelect?.toggleAttribute('required', indirect);
    };

    form.querySelectorAll('input[name="membership_type"]').forEach((input) => {
        input.addEventListener('change', toggle);
    });
    toggle();
}
