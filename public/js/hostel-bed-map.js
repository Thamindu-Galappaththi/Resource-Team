(function () {
    const input = document.getElementById('hbm-date');
    if (!input) return;
    let request = null;

    const syncPdf = () => {
        const link = document.getElementById('hbm-pdf');
        if (!link || !input.dataset.pdfUrl || !input.value) return;
        const pdf = new URL(input.dataset.pdfUrl, window.location.origin);
        pdf.searchParams.set('date', input.value);
        link.href = pdf.toString();
    };

    input.addEventListener('change', async () => {
        if (!input.value) return;
        syncPdf();
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
