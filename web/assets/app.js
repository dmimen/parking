function debounce(fn, delay) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
}

document.addEventListener('DOMContentLoaded', () => {
    const updateNavbarHeight = () => {
        const nav = document.querySelector('.navbar');
        if (nav) {
            const height = `${nav.offsetHeight}px`;
            document.documentElement.style.setProperty('--navbar-height', height);
            document.body.style.setProperty('--navbar-height', height);
        }
    };
    updateNavbarHeight();
    setTimeout(updateNavbarHeight, 200);
    const searchInput = document.querySelector('[data-car-search]');
    const searchIndicator = document.querySelector('[data-search-indicator]');
    const resultsBox = document.querySelector('[data-search-results]');
    if (searchInput && resultsBox) {
        const runSearch = debounce(async () => {
            const query = searchInput.value.trim();
            if (searchIndicator) {
                searchIndicator.textContent = query ? 'Поиск…' : '';
            }
            if (!query) {
                resultsBox.innerHTML = '';
                return;
            }
            const response = await fetch(`/api/cars_search.php?q=${encodeURIComponent(query)}`, { credentials: 'same-origin' });
            if (!response.ok) {
                resultsBox.innerHTML = '<div class="text-muted">Ошибка загрузки поиска</div>';
                return;
            }
            if (response.redirected && response.url.includes('/login.php')) {
                resultsBox.innerHTML = '<div class="text-muted">Требуется повторный вход</div>';
                return;
            }
            const data = await response.json();
            if (!data.results.length) {
                resultsBox.innerHTML = '<div class="text-muted">Ничего не найдено</div>';
                return;
            }
            resultsBox.innerHTML = data.results.map(row => `
                <div class="search-result">
                    <div class="fw-semibold">${row.car_number}</div>
                    <div class="text-muted small">${row.car_model} • ${row.date_added}</div>
                    <div class="small">${row.comment || 'Без комментария'}</div>
                </div>
            `).join('');
        }, 400);

        searchInput.addEventListener('input', runSearch);
        searchInput.addEventListener('keyup', runSearch);
    }

    document.querySelectorAll('[data-confirm]')
        .forEach((button) => {
            button.addEventListener('click', (event) => {
                const modalId = button.dataset.confirm;
                const modal = document.getElementById(modalId);
                if (!modal) {
                    return;
                }
                const formId = button.dataset.form;
                const form = document.getElementById(formId);
                const confirmButton = modal.querySelector('[data-confirm-submit]');
                if (confirmButton && form) {
                    confirmButton.onclick = () => form.submit();
                }
            });
        });
    window.addEventListener('resize', updateNavbarHeight);
});
