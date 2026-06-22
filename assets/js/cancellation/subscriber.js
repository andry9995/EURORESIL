document.addEventListener('DOMContentLoaded', () => {
    const radioPart = document.getElementById('cancellation_subscriber_subscriber_category_0');
    const radioPro = document.getElementById('cancellation_subscriber_subscriber_category_1');

    if (!radioPart || !radioPro) return;

    const toggleCategory = () => {
        const isPro = radioPro.checked;

        document.getElementById('catParticulier').classList.toggle('on', !isPro);
        document.getElementById('catProfessionnel').classList.toggle('on', isPro);

        document.getElementById('proFields').style.display = isPro ? 'grid' : 'none';
    };

    radioPart.addEventListener('change', toggleCategory);
    radioPro.addEventListener('change', toggleCategory);

    toggleCategory();
});