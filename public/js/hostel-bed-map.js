(function () {
    const input = document.getElementById('hbm-date');
    if (!input) return;
    let request = null;

    input.addEventListener('change', async () => {
        if (!input.value) return;
        const url = new URL(input.dataset.url, window.location.origin);
        url.searchParams.set('date', input.value);
        const loading = document.getElementById('hbm-loading');
        request?.abort();
        request = new AbortController();
        if (loading) loading.hidden = false;
        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
                credentials: 'same-origin',
                signal: request.signal,
            });
            if (!response.ok) throw new Error('Could not load bed availability.');
            const next = new DOMParser().parseFromString(await response.text(), 'text/html').getElementById('hbm-body');
            const current = document.getElementById('hbm-body');
            if (next && current) current.replaceWith(next);
        } catch (error) {
            if (error.name !== 'AbortError') window.alert(error.message);
        } finally {
            if (loading) loading.hidden = true;
        }
    });
})();
