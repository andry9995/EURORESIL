document.addEventListener('DOMContentLoaded', () => {
    const reasonInput = document.getElementById('cancellation_contract_reason');
    const suggestContainer = document.getElementById('reasonsSuggest');

    if (!reasonInput || !suggestContainer) return;

    suggestContainer.addEventListener('click', (event) => {
        const button = event.target.closest('.suggest-btn');
        if (!button) return;

        event.preventDefault();

        reasonInput.value = button.dataset.reason;
    });
});