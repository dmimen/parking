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
    const csrfToken = table?.dataset.csrf;
    const defaultRows = tableBody ? tableBody.innerHTML : '';
    const digitButtons = document.querySelector('[data-digit-buttons]');
    const normalizePlate = (value) => {
        const cleaned = value.replace(/[\s-]+/g, '');
        const map = {
            а: 'A',
            в: 'B',
            е: 'E',
            к: 'K',
            м: 'M',
            н: 'H',
            о: 'O',
            р: 'P',
            с: 'C',
            т: 'T',
            у: 'Y',
            х: 'X',
        };
        return cleaned
            .split('')
            .map((ch) => {
                const lower = ch.toLowerCase();
                if (map[lower]) {
                    return map[lower];
                }
                return ch;
            })
            .join('')
            .toUpperCase();
    };
    const splitPlateRegion = (plate) => {
        const normalized = normalizePlate(plate);
        const match = normalized.match(/(\d+)$/);
        if (!match) {
            return { main: normalized, region: '' };
        }
        const region = match[1];
        return { main: normalized.slice(0, -region.length), region };
    };
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
                if (xhr.status === 401) {
                    window.location.assign('/login');
                    return;
                }
                if (xhr.status >= 300 && xhr.status < 400) {
                    window.location.assign('/login');
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
                const normalizedQuery = normalizePlate(query);
                const filteredResults = normalizedQuery && /^\d+$/.test(normalizedQuery)
                    ? data.results.filter((row) => {
                        const { main, region } = splitPlateRegion(row.car_number);
                        return !(region.includes(normalizedQuery) && !main.includes(normalizedQuery));
                    })
                    : data.results;

                resultsBox.innerHTML = filteredResults.length
                    ? filteredResults.map(row => `
                    <div class="search-result">
                        <div class="fw-semibold">${row.car_model}</div>
                        <div>${row.car_number}</div>
                        <div class="text-muted">${row.comment || '-'}</div>
                    </div>
                `).join('')
                    : '<div class="text-muted">Ничего не найдено</div>';
                const deleteCell = (row) => {
                    if (!hasActions || !row.id) {
                        return '';
                    }
                    if (!csrfToken) {
                        return '<td class="text-end">—</td>';
                    }
                    return `
                        <td class="text-end">
                            <form id="delete-car-${row.id}" method="post" action="/api/cars" class="d-inline">
                                <input type="hidden" name="csrf_token" value="${csrfToken}">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="car_id" value="${row.id}">
                                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteCarModal" data-confirm="deleteCarModal" data-form="delete-car-${row.id}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    `;
                };

                tableBody.innerHTML = data.results.map(row => `
                    <tr>
                        <td>${row.car_number}</td>
                        <td>${row.car_model}</td>
                        <td>${row.comment || ''}</td>
                        ${isAdmin ? `<td>${row.date_added}</td>` : ''}
                        ${isAdmin ? '<td>—</td>' : ''}
                        ${hasActions ? deleteCell(row) : ''}
                    </tr>
                `).join('');
            };
            xhr.send();
        }, 400);

        searchInput.addEventListener('input', runSearch);
        searchInput.addEventListener('keyup', runSearch);

        if (digitButtons) {
            digitButtons.addEventListener('click', (event) => {
                const button = event.target.closest('button');
                if (!button) {
                    return;
                }
                if (button.dataset.action === 'backspace') {
                    searchInput.value = searchInput.value.slice(0, -1);
                } else if (button.dataset.digit) {
                    searchInput.value = `${searchInput.value}${button.dataset.digit || ''}`;
                } else {
                    return;
                }
                searchInput.dispatchEvent(new Event('input'));
            });
        }
    }

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-confirm]');
        if (!button) {
            return;
        }
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

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        if (!link) {
            return;
        }
        if (link.target || link.hasAttribute('download')) {
            return;
        }
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
            return;
        }
        const href = link.getAttribute('href');
        if (!href || !href.startsWith('/')) {
            return;
        }
        event.preventDefault();
        window.location.assign(href);
    });
    const sessionPoll = () => {
        fetch('/api/session_check', { credentials: 'same-origin' })
            .then((response) => {
                if (response.status === 401) {
                    window.location.assign('/login');
                }
            })
            .catch(() => {});
    };
    sessionPoll();
    setInterval(sessionPoll, 60000);
    window.addEventListener('resize', updateNavbarHeight);
});
