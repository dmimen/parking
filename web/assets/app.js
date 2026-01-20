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
        const runSearch = debounce(() => {
            const query = searchInput.value.trim();
            if (searchIndicator) {
                searchIndicator.textContent = query ? 'Поиск…' : '';
            }
            if (!query) {
                resultsBox.innerHTML = '';
                return;
            }
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `/api/cars_search.php?q=${encodeURIComponent(query)}`, true);
            xhr.withCredentials = true;
            xhr.onreadystatechange = () => {
                if (xhr.readyState !== 4) {
                    return;
                }
                if (xhr.status >= 300 && xhr.status < 400) {
                    resultsBox.innerHTML = '<div class="text-muted">Требуется повторный вход</div>';
                    return;
                }
                if (xhr.status !== 200) {
                    resultsBox.innerHTML = '<div class="text-muted">Ошибка загрузки поиска</div>';
                    return;
                }
                let data = null;
                try {
                    data = JSON.parse(xhr.responseText);
                } catch (error) {
                    resultsBox.innerHTML = '<div class="text-muted">Ошибка обработки ответа</div>';
                    return;
                }
                if (!data.results || !data.results.length) {
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
            };
            xhr.send();
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
