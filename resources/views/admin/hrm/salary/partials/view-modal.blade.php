<div id="salary-view-modal" class="fixed inset-0 z-[100] hidden" aria-modal="true" role="dialog">
    <div class="absolute inset-0 bg-black/45" data-close-salary-modal></div>
    <div class="absolute inset-0 flex items-end sm:items-start justify-center sm:p-4 sm:pt-16 overflow-y-auto">
        <div class="bg-white rounded-t-xl sm:rounded-sm shadow-2xl border border-erp-border w-full sm:max-w-3xl max-h-[92dvh] sm:max-h-[85vh] flex flex-col relative">
            <div class="flex items-center justify-between gap-3 px-4 py-3 border-b border-erp-border bg-gray-50/80 shrink-0 safe-top">
                <h3 id="salary-view-modal-title" class="text-sm font-semibold text-gray-800 truncate pr-2">Details</h3>
                <button type="button" data-close-salary-modal class="text-gray-400 hover:text-gray-700 text-2xl leading-none p-1 -mr-1 min-w-[2.5rem] min-h-[2.5rem] flex items-center justify-center" aria-label="Close">&times;</button>
            </div>
            <div id="salary-view-modal-body" class="p-3 sm:p-4 overflow-y-auto text-sm flex-1 overscroll-contain">
                <p class="text-gray-400 text-center py-8">Loading…</p>
            </div>
            <div class="px-4 py-3 border-t border-erp-border bg-gray-50/50 shrink-0 text-right safe-bottom">
                <button type="button" data-close-salary-modal class="erp-btn-secondary w-full sm:w-auto">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('salary-view-modal');
    const body = document.getElementById('salary-view-modal-body');
    const titleEl = document.getElementById('salary-view-modal-title');

    if (!modal || !body) return;

    function openModal() {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        body.innerHTML = '';
        titleEl.textContent = 'Details';
    }

    function loadView(url) {
        const fetchUrl = url + (url.includes('?') ? '&' : '?') + 'modal=1';
        body.innerHTML = '<p class="text-gray-400 text-center py-8">Loading…</p>';
        openModal();

        fetch(fetchUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
            credentials: 'same-origin',
        })
            .then(r => { if (!r.ok) throw new Error('Failed'); return r.text(); })
            .then(html => {
                body.innerHTML = html;
                const titleInput = body.querySelector('.salary-modal-title');
                if (titleInput) {
                    titleEl.textContent = titleInput.value;
                    titleInput.remove();
                }
            })
            .catch(() => {
                body.innerHTML = '<p class="text-red-600 text-center py-8">Could not load details.</p>';
            });
    }

    document.addEventListener('click', function (e) {
        const viewBtn = e.target.closest('[data-salary-view]');
        if (viewBtn) {
            e.preventDefault();
            loadView(viewBtn.dataset.salaryView);
            return;
        }
        if (e.target.closest('[data-close-salary-modal]')) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
})();
</script>
