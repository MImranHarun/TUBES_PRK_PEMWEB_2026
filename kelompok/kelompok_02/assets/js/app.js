
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.querySelector('[data-nav-toggle]');
    const nav = document.querySelector('[data-nav]');

    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            nav.classList.toggle('is-open');
        });
    }

    const search = document.getElementById('catalog-search');
    const category = document.getElementById('catalog-category');
    const form = document.getElementById('catalog-filter-form');

    if (category && form) {
        category.addEventListener('change', function () {
            form.submit();
        });
    }
});
