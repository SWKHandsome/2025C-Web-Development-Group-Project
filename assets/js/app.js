document.addEventListener('DOMContentLoaded', () => {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.app-sidebar');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('is-open');
        });
    }

    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach((input) => {
        input.addEventListener('change', () => {
            if (!input.files?.length) {
                return;
            }
            const label = input.closest('label');
            if (label) {
                const info = document.createElement('small');
                info.className = 'muted';
                info.textContent = `${input.files[0].name} selected`;
                label.appendChild(info);
            }
        });
    });
});
