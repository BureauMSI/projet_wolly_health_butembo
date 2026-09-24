const form = document.getElementById('user-form');
if (form) {
    const roleSelect = document.getElementById('user-role');
    const branchWrap = document.getElementById('user-branch-wrap');
    const branchSelect = document.getElementById('user-branch');
    const hint = document.getElementById('user-role-hint');

    const syncRole = () => {
        if (!roleSelect) {
            return;
        }
        const option = roleSelect.selectedOptions[0];
        const needsBranch = option?.dataset.needsBranch === '1';
        if (hint && option?.dataset.hint) {
            hint.textContent = option.dataset.hint;
        }
        branchWrap?.classList.toggle('d-none', !needsBranch);
        branchSelect?.toggleAttribute('required', needsBranch);
        if (!needsBranch && branchSelect) {
            branchSelect.value = '';
            if (branchSelect.tomselect) {
                branchSelect.tomselect.clear(true);
            }
        }
    };

    roleSelect?.addEventListener('change', syncRole);
    syncRole();
}
