document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('typeSearch');
    const hiddenInput = document.getElementById('typeContratVal');
    const form = document.getElementById('typeForm');
    const items = document.querySelectorAll('.ctype-item');
    const listContainer = document.getElementById('typeList');

    if (!hiddenInput || !form) return;

    if (hiddenInput.value) {
        items.forEach(button => {
            if (button.dataset.key === hiddenInput.value) {
                button.classList.add('active');
            } else {
                button.classList.remove('active');
            }
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.toLowerCase().trim();

            items.forEach(button => {
                const label = button.dataset.label ? button.dataset.label.toLowerCase() : '';
                if (label.includes(query)) {
                    button.style.display = '';
                } else {
                    button.style.display = 'none';
                }
            });
        });
    }

    if (listContainer) {
        listContainer.addEventListener('click', (event) => {
            const button = event.target.closest('.ctype-item');
            if (!button) return;

            event.preventDefault();

            items.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');

            hiddenInput.value = button.dataset.key;

            form.submit();
        });
    }
});