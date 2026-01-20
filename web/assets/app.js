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
            document.documentElement.style.setProperty('--navbar-height', `${nav.offsetHeight}px`);
        }
    };
    updateNavbarHeight();
    setTimeout(updateNavbarHeight, 200);
    const searchInput = document.querySelector('[data-car-search]');
    const searchIndicator = document.querySelector('[data-search-indicator]');
    if (searchInput) {
        const tableBody = document.querySelector('[data-car-results]');
        const table = tableBody ? tableBody.closest('table') : null;
        const hasAdminColumns = table?.dataset.adminColumns === '1';
        const hasActions = table?.dataset.actions === '1';
        const defaultRows = tableBody ? tableBody.innerHTML : '';

        const runSearch = debounce(async () => {
            const query = searchInput.value.trim();
            if (searchIndicator) {
                searchIndicator.textContent = query ? 'Поиск…' : '';
            }
            if (!query) {
                tableBody.innerHTML = defaultRows;
                return;
            }
            const response = await fetch(`/api/cars_search.php?q=${encodeURIComponent(query)}`);
            if (!response.ok) {
                return;
            }
            const data = await response.json();
            if (!data.results.length) {
                tableBody.innerHTML = `<tr><td colspan="${4 + (hasAdminColumns ? 1 : 0) + (hasActions ? 1 : 0)}" class="text-center text-muted py-3">Ничего не найдено</td></tr>`;
                return;
            }
            tableBody.innerHTML = data.results.map(row => `
                <tr>
                    <td>${row.car_number}</td>
                    <td>${row.car_model}</td>
                    <td>${row.comment || ''}</td>
                    <td>${row.date_added}</td>
                    ${hasAdminColumns ? '<td>—</td>' : ''}
                    ${hasActions ? '<td class="text-end">—</td>' : ''}
                </tr>
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
