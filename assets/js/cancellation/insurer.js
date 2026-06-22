document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('insSearch');
    const listContainer = document.getElementById('insList');
    const hiddenInput = document.getElementById('assureurVal');
    const nextButton = document.getElementById('nextBtn');
    const form = document.getElementById('assureurForm');

    if (!searchInput || !listContainer) return;

    const apiUrl = searchInput.dataset.apiUrl;
    let debounceTimeout;

    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimeout);
        const query = searchInput.value.trim();

        if (query.length < 2) {
            listContainer.innerHTML = '';
            return;
        }

        debounceTimeout = setTimeout(() => {
            fetch(`${apiUrl}?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    listContainer.innerHTML = data.map(item => {
                        const safeName = item.name.replace(/&/g, '&amp;').replace(/'/g, '&#39;').replace(/"/g, '&quot;');
                        return `<button type="button" class="ins-item" data-id="${item.id}">${safeName}</button>`;
                    }).join('');
                });
        }, 250);
    });

    listContainer.addEventListener('click', (event) => {
        const item = event.target.closest('.ins-item');
        if (!item) return;

        const selectedId = item.dataset.id;
        searchInput.value = item.textContent;
        hiddenInput.value = selectedId;
        listContainer.innerHTML = '';

        if (nextButton) nextButton.disabled = false;

        form.submit();
    });
});