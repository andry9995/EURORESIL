document.addEventListener('DOMContentLoaded', () => {
    window.sendLre = function(id, url) {
        const btn = document.getElementById('sendBtn');
        const tokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = tokenMeta ? tokenMeta.content : '';

        if (!csrfToken) {
            console.error('Erreur : Balise meta CSRF introuvable dans le DOM.');
            return;
        }

        if (btn) btn.disabled = true;

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json'
            }
        })
            .then(r => r.json())
            .then(d => {
                if (d.redirect) {
                    window.location.href = d.redirect;
                } else if (btn) {
                    btn.disabled = false;
                }
            })
            .catch(() => {
                if (btn) btn.disabled = false;
            });
    };
});