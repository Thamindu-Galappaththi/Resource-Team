/**
 * Main resource dropdown plus a checkbox list of its related resources.
 * Picking a main resource loads its saved links and ticks them.
 */
window.RMLinkPicker = function ({ selectId, listId, countId, errorId, showUrl, resources, onReady }) {
    let saved = new Set();
    let request = 0;
    const emptyText = 'Select a main resource to see its related resources.';
    const list = () => document.getElementById(listId);
    const linkable = () => (resources() || []).filter((item) => item.status !== 'deleted' && !window.RMHostelFields?.isHostelRecord(item));

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char]));
    }

    function label(item) {
        return item.name_model || 'Resource';
    }

    function message(text) {
        const target = list();
        if (target) target.innerHTML = `<p class="text-muted small mb-0">${escapeHtml(text)}</p>`;
        const count = document.getElementById(countId);
        if (count) count.hidden = true;
        onReady?.(false);
    }

    function checkedIds() {
        return [...(list()?.querySelectorAll('input[name="linked_resource_ids[]"]:checked') || [])].map((input) => input.value);
    }

    function updateCount() {
        const count = document.getElementById(countId);
        if (!count) return;
        count.textContent = `${checkedIds().length} selected`;
        count.hidden = false;
    }

    function clearError() {
        document.getElementById(errorId)?.classList.remove('d-block');
    }

    function showError(text) {
        const feedback = document.getElementById(errorId);
        if (!feedback) return;
        feedback.textContent = text;
        feedback.classList.add('d-block');
    }

    function render(mainId) {
        const target = list();
        if (!target) return;
        const checked = target.querySelector('input') ? new Set(checkedIds()) : saved;
        const others = linkable().filter((item) => String(item.id) !== String(mainId));
        if (!others.length) {
            message('No other resources are available to link.');
            return;
        }
        target.replaceChildren(...others.map((item) => {
            const id = `${listId}-${item.id}`;
            const row = document.createElement('label');
            row.className = 'rm-linked-item';
            row.setAttribute('for', id);
            row.innerHTML = `<input type="checkbox" id="${id}" name="linked_resource_ids[]" value="${item.id}" ${checked.has(String(item.id)) ? 'checked' : ''}><span>${escapeHtml(label(item))}</span>`;
            return row;
        }));
        updateCount();
        onReady?.(true);
    }

    async function load(mainId) {
        clearError();
        saved = new Set();
        const current = request += 1;
        if (!mainId) {
            message(emptyText);
            return;
        }
        message('Loading related resources…');
        try {
            const response = await fetch(showUrl(mainId), { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Request failed');
            const resource = await response.json();
            if (current !== request) return;
            saved = new Set((resource.linked_resources || []).map((item) => String(item.id)));
            render(mainId);
        } catch (error) {
            if (current === request) message('Could not load related resources. Select the main resource again.');
        }
    }

    /** Refreshes the main resource options; pass an id to select it and load its links. */
    function setOptions(selectedId = null) {
        const items = linkable().map((item) => ({
            value: item.id,
            label: item.name_model,
        }));
        const current = selectedId ?? window.RMSelect.getValue(selectId);
        const placeholder = window.RMSelect.wrapFor(selectId)?.dataset.placeholder || 'Select a resource';
        window.RMSelect.setOptions(selectId, items, placeholder);
        const selected = items.find((item) => String(item.value) === String(current));
        if (selected) {
            const unchanged = window.RMSelect.getValue(selectId) === String(selected.value);
            window.RMSelect.setValue(selectId, selected.value, selected.label);
            if (selectedId !== null && unchanged) load(selected.value);
            else if (selectedId === null && list()?.querySelector('input')) render(selected.value);
        } else if (selectedId !== null) {
            window.RMSelect.setValue(selectId, '', placeholder);
            message(emptyText);
        }
    }

    document.getElementById(selectId)?.addEventListener('change', (event) => load(event.target.value));
    list()?.addEventListener('change', () => {
        clearError();
        updateCount();
    });

    return {
        load,
        setOptions,
        checkedIds,
        showError,
        clearError,
        mainId: () => window.RMSelect.getValue(selectId),
        hasSaved: () => saved.size > 0,
        markSaved: (ids) => { saved = new Set(ids.map(String)); },
        focus: () => list()?.focus(),
    };
};
