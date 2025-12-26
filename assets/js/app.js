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

    const formatDatePreview = (date) => (
        date.toLocaleString('en-MY', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        })
    );

    document.querySelectorAll('[data-offset-months]').forEach((input) => {
        const targetSelector = input.getAttribute('data-offset-target');
        if (!targetSelector) {
            return;
        }
        const target = document.querySelector(targetSelector);
        if (!target) {
            return;
        }
        const defaultText = target.dataset.defaultText || target.textContent;
        const months = Number(input.getAttribute('data-offset-months')) || 0;

        const updatePreview = () => {
            const value = input.value;
            if (!value) {
                target.textContent = defaultText;
                return;
            }
            const baseDate = new Date(value);
            if (Number.isNaN(baseDate.getTime())) {
                target.textContent = defaultText;
                return;
            }
            const future = new Date(baseDate.getTime());
            future.setMonth(future.getMonth() + months);
            target.textContent = formatDatePreview(future);
        };

        updatePreview();
        input.addEventListener('input', updatePreview);
        input.addEventListener('change', updatePreview);
    });

    const profileEditButton = document.getElementById('profileEditButton');
    const profileForm = document.getElementById('profileForm');

    if (profileEditButton && profileForm) {
        profileEditButton.addEventListener('click', () => {
            profileForm.hidden = false;
            profileEditButton.hidden = true;
            const firstField = profileForm.querySelector('input:not([type="hidden"])');
            if (firstField) {
                firstField.focus();
            }
        });
    }
});
