@once
@push('styles')
<style>
    .cke_chrome { border-radius: 0.375rem !important; border-color: #e5e7eb !important; overflow: hidden; }
    .cke_top { background: #f9fafb !important; border-bottom-color: #e5e7eb !important; }
    .cke_bottom { display: none !important; }
    .rich-text-wrap textarea[data-ckeditor-ready] { display: none; }
</style>
@endpush
@push('scripts')
<script src="https://cdn.ckeditor.com/4.22.1/full/ckeditor.js"></script>
<script>
(function () {
    function initRichTextEditors() {
        if (typeof CKEDITOR === 'undefined') {
            return;
        }

        document.querySelectorAll('[data-ckeditor]').forEach(function (textarea) {
            if (textarea.dataset.ckeditorInitialized) {
                return;
            }

            textarea.dataset.ckeditorInitialized = '1';

            CKEDITOR.replace(textarea, {
                height: 280,
                versionCheck: false,
                removePlugins: 'elementspath',
                resize_enabled: true,
                toolbar: [
                    { name: 'document', items: ['Source', '-', 'Preview', 'Print'] },
                    { name: 'clipboard', items: ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo'] },
                    { name: 'editing', items: ['Find', 'Replace', '-', 'SelectAll'] },
                    '/',
                    { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat'] },
                    { name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'] },
                    { name: 'links', items: ['Link', 'Unlink'] },
                    { name: 'insert', items: ['Table', 'HorizontalRule', 'SpecialChar'] },
                    '/',
                    { name: 'styles', items: ['Format', 'Font', 'FontSize'] },
                    { name: 'colors', items: ['TextColor', 'BGColor'] },
                ],
                on: {
                    instanceReady: function () {
                        textarea.setAttribute('data-ckeditor-ready', '1');
                    },
                },
            });
        });

        document.querySelectorAll('form').forEach(function (form) {
            if (form.dataset.ckeditorSubmitBound) {
                return;
            }

            form.dataset.ckeditorSubmitBound = '1';
            form.addEventListener('submit', function () {
                for (const instance in CKEDITOR.instances) {
                    CKEDITOR.instances[instance].updateElement();
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initRichTextEditors);
    } else {
        initRichTextEditors();
    }
})();
</script>
@endpush
@endonce
