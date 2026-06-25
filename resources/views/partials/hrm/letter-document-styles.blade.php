<style>
    .hr-letter-doc {
        position: relative;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 4px;
        overflow: visible;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
    }
    .hr-letter-doc-accent {
        height: 4px;
        background: linear-gradient(90deg, #1e3a5f 0%, #2563eb 50%, #1e3a5f 100%);
    }
    .hr-letter-doc-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        padding: 28px 32px 20px;
        border-bottom: 1px solid #f3f4f6;
    }
    .hr-letter-doc-org {
        font-size: 20px;
        font-weight: 700;
        color: #1e3a5f;
        letter-spacing: -0.02em;
        line-height: 1.2;
    }
    .hr-letter-doc-dept {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #6b7280;
        margin-top: 4px;
    }
    .hr-letter-doc-badge {
        display: inline-block;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        padding: 6px 12px;
        background: #eff6ff;
        color: #1d4ed8;
        border: 1px solid #bfdbfe;
        border-radius: 999px;
        white-space: nowrap;
    }
    .hr-letter-doc-ref {
        display: flex;
        flex-wrap: wrap;
        gap: 16px 24px;
        padding: 10px 32px;
        background: #f9fafb;
        border-bottom: 1px solid #f3f4f6;
        font-size: 11px;
        color: #6b7280;
    }
    .hr-letter-doc-body {
        padding: 28px 32px 36px;
        font-family: Georgia, 'Times New Roman', Times, serif;
        font-size: 14px;
        line-height: 1.75;
        color: #1f2937;
    }
    .hr-letter-doc-label {
        display: inline-block;
        min-width: 56px;
        font-family: system-ui, -apple-system, sans-serif;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #9ca3af;
        margin-right: 8px;
    }
    .hr-letter-doc-date { margin-bottom: 20px; }
    .hr-letter-doc-to {
        margin-bottom: 20px;
        padding-left: 64px;
    }
    .hr-letter-doc-to p { margin: 2px 0; }
    .hr-letter-doc-subject {
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 1px solid #e5e7eb;
    }
    .hr-letter-doc-content p {
        margin: 0 0 14px;
        text-align: justify;
    }
    .hr-letter-doc-sign {
        margin-top: 32px;
        padding-top: 8px;
    }
    .hr-letter-doc-sign p:first-child { font-weight: 600; }
    .hr-letter-doc-sign p { margin: 2px 0; }
    @media print {
        .hr-letter-doc { border: none; box-shadow: none; border-radius: 0; }
        .hr-letter-doc-accent { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
    }
</style>
