import 'bootstrap';
import 'bootstrap/dist/css/bootstrap.min.css';
import './styles/app.css';

// Initialisation des tooltips Bootstrap
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el);
    });
});