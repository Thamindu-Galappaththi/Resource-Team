(function () {
    const button = document.getElementById('week-sheet-png');
    const sheet = document.getElementById('week-sheet');
    const status = document.getElementById('week-sheet-status');
    if (!button || !sheet) return;

    function setStatus(message) {
        if (!status) return;
        status.hidden = !message;
        status.textContent = message || '';
    }

    async function downloadImage() {
        if (typeof html2canvas !== 'function') {
            setStatus('Image export is not available. Use Download PDF instead.');
            return;
        }
        button.disabled = true;
        setStatus('Preparing image…');
        try {
            const canvas = await html2canvas(sheet, {
                backgroundColor: '#ffffff',
                scale: Math.min(2, window.devicePixelRatio || 1.5),
                useCORS: true,
            });
            const link = document.createElement('a');
            link.download = button.dataset.filename || 'weekly-reservations.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
            setStatus('Image downloaded. You can share the PNG or use Download PDF.');
        } catch (error) {
            setStatus('Could not create the image. Use Download PDF instead.');
        } finally {
            button.disabled = false;
        }
    }

    button.addEventListener('click', downloadImage);
})();
