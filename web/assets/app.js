function debounce(fn, delay) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
}

const searchInput = document.querySelector('[data-car-search]');
if (searchInput) {
    const tableBody = document.querySelector('[data-car-results]');
    const defaultRows = tableBody ? tableBody.innerHTML : '';

    const runSearch = debounce(async () => {
        const query = searchInput.value.trim();
        if (!query) {
            tableBody.innerHTML = defaultRows;
            return;
        }
        const response = await fetch(`/api/cars_search.php?q=${encodeURIComponent(query)}`);
        if (!response.ok) {
            return;
        }
        const data = await response.json();
        tableBody.innerHTML = data.results.map(row => `
            <tr>
                <td>${row.car_number}</td>
                <td>${row.car_model}</td>
                <td>${row.comment || ''}</td>
                <td>${row.date_added}</td>
            </tr>
        `).join('');
    }, 300);

    searchInput.addEventListener('input', runSearch);
}
