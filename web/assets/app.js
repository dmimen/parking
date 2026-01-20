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
    const tableBody = document.querySelector('[data-car-results]');
    const table = tableBody ? tableBody.closest('table') : null;
    const isAdmin = table?.dataset.admin === '1';
    const hasActions = table?.dataset.actions === '1';
    const defaultRows = tableBody ? tableBody.innerHTML : '';
    if (searchInput && resultsBox && tableBody) {
        const runSearch = debounce(() => {
            const query = searchInput.value.trim();
            if (searchIndicator) {
                searchIndicator.textContent = query ? 'Поиск…' : '';
            }
            if (!query) {
                resultsBox.innerHTML = '';
                tableBody.innerHTML = defaultRows;
                return;
            }
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `/api/cars_search?q=${encodeURIComponent(query)}`, true);
            xhr.withCredentials = true;
            xhr.onreadystatechange = () => {
                if (xhr.readyState !== 4) {
                    return;
                }
                if (xhr.status >= 300 && xhr.status < 400) {
                    resultsBox.innerHTML = '<div class="text-muted">Требуется повторный вход</div>';
                    tableBody.innerHTML = defaultRows;
                    return;
                }
                if (xhr.status !== 200) {
                    resultsBox.innerHTML = '<div class="text-muted">Ошибка загрузки поиска</div>';
                    tableBody.innerHTML = defaultRows;
                    return;
                }
                let data = null;
                try {
                    data = JSON.parse(xhr.responseText);
                } catch (error) {
                    resultsBox.innerHTML = '<div class="text-muted">Ошибка обработки ответа</div>';
                    tableBody.innerHTML = defaultRows;
                    return;
                }
                if (!data.results || !data.results.length) {
                    resultsBox.innerHTML = '<div class="text-muted">Ничего не найдено</div>';
                    tableBody.innerHTML = '';
                    return;
                }
                resultsBox.innerHTML = data.results.map(row => `
                    <div class="search-result">
                        <div class="fw-semibold">${row.car_model}</div>
                        <div>${row.car_number}</div>
                        <div class="text-muted">${row.comment || '-'}</div>
                    </div>
                `).join('');
                tableBody.innerHTML = data.results.map(row => `
                    <tr>
                        <td>${row.car_number}</td>
                        <td>${row.car_model}</td>
                        <td>${row.comment || ''}</td>
                        ${isAdmin ? `<td>${row.date_added}</td>` : ''}
                        ${isAdmin ? '<td>—</td>' : ''}
                        ${hasActions ? '<td class="text-end">—</td>' : ''}
                    </tr>
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
