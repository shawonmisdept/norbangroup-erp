<style>
    :root {
        --careers-navy: #0f2744;
        --careers-blue: #1e4d8c;
        --careers-accent: #3b82f6;
        --careers-accent-light: #60a5fa;
        --careers-surface: #ffffff;
        --careers-muted: #64748b;
        --careers-border: #e2e8f0;
        --careers-gradient: linear-gradient(135deg, #0f2744 0%, #1e4d8c 45%, #2563eb 100%);
        --portal-max-width: 87.5rem;
        --portal-gutter: 1.25rem;
    }

    .portal-container {
        max-width: var(--portal-max-width);
        margin-inline: auto;
        width: 100%;
        padding-inline: var(--portal-gutter);
    }
    .portal-section { padding-block: 2.5rem; }

    .careers-page { background: #f1f5f9; min-height: 100vh; display: flex; flex-direction: column; overflow-x: hidden; }
    .careers-header {
        position: sticky; top: 0; z-index: 50;
        background: linear-gradient(180deg, rgba(10, 28, 52, 0.97) 0%, rgba(15, 39, 68, 0.94) 100%);
        border-bottom: 1px solid rgba(255,255,255,0.06);
        box-shadow: 0 8px 32px rgba(8, 20, 40, 0.28);
    }
    .careers-header-accent {
        height: 3px;
        background: linear-gradient(90deg, #2563eb 0%, #60a5fa 35%, #93c5fd 50%, #60a5fa 65%, #2563eb 100%);
        background-size: 200% 100%;
        animation: careers-header-shimmer 6s ease-in-out infinite;
    }
    @keyframes careers-header-shimmer {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }
    .careers-header-inner {
        max-width: var(--portal-max-width); margin: 0 auto; padding: 0.875rem var(--portal-gutter);
        display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;
    }
    .careers-header-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        width: 100%;
    }
    .careers-brand {
        display: flex; align-items: center; gap: 0.875rem;
        min-width: 0; text-decoration: none;
        transition: opacity 0.2s;
    }
    .careers-brand:hover { opacity: 0.92; }
    .careers-brand-logo {
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .careers-brand-logo-img {
        height: 2.25rem; width: auto; max-width: 9rem;
        object-fit: contain;
        filter: drop-shadow(0 2px 8px rgba(0,0,0,0.2));
    }
    .careers-logo-mark {
        width: 2.25rem; height: 2.25rem; border-radius: 0.625rem;
        background: linear-gradient(145deg, #3b82f6, #60a5fa);
        display: flex; align-items: center; justify-content: center;
        font-size: 0.875rem; font-weight: 800; color: #fff;
        box-shadow: 0 4px 14px rgba(59,130,246,0.35);
    }
    .careers-brand-divider {
        width: 1px; height: 1.75rem;
        background: linear-gradient(180deg, transparent, rgba(255,255,255,0.28), transparent);
        flex-shrink: 0;
    }
    .careers-brand-label {
        font-size: 1.375rem; font-weight: 700; letter-spacing: -0.03em;
        background: linear-gradient(135deg, #ffffff 0%, #bfdbfe 100%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        line-height: 1;
    }

    .careers-site-nav-toggle {
        display: none;
        align-items: center; justify-content: center;
        width: 2.375rem; height: 2.375rem;
        border-radius: 0.625rem;
        border: 1px solid rgba(255,255,255,0.14);
        background: rgba(255,255,255,0.06);
        color: #fff; cursor: pointer;
        flex-shrink: 0;
    }
    .careers-site-nav-toggle-icon { width: 1.25rem; height: 1.25rem; }
    .careers-site-nav-toggle-close { display: none; }
    .careers-header-inner.is-nav-open .careers-site-nav-toggle-open { display: none; }
    .careers-header-inner.is-nav-open .careers-site-nav-toggle-close { display: block; }

    .careers-header-nav-panel {
        display: flex; align-items: center; gap: 0.625rem;
        min-width: 0; flex: 1; justify-content: flex-end;
    }

    .careers-site-nav {
        display: flex; align-items: center; gap: 0.125rem;
        min-width: 0; flex-wrap: wrap; justify-content: flex-end;
    }
    .careers-site-nav > a,
    .careers-site-nav-trigger {
        display: inline-flex; align-items: center; gap: 0.25rem;
        color: rgba(255,255,255,0.78); font-size: 0.6875rem; font-weight: 600;
        letter-spacing: 0.04em; text-transform: uppercase;
        padding: 0.4375rem 0.5rem; border-radius: 0.375rem;
        transition: all 0.2s; white-space: nowrap;
        text-decoration: none; border: none; background: transparent; cursor: pointer;
        font-family: inherit;
    }
    .careers-site-nav > a:hover,
    .careers-site-nav-trigger:hover {
        color: #fff; background: rgba(255,255,255,0.08);
    }
    .careers-site-nav-cta {
        background: linear-gradient(135deg, #c9a84c 0%, #e8c96a 100%) !important;
        color: #0f2744 !important;
        font-weight: 700 !important;
        padding: 0.4375rem 0.75rem !important;
        box-shadow: 0 2px 10px rgba(201,168,76,0.35);
    }
    .careers-site-nav-cta:hover {
        background: linear-gradient(135deg, #d4b45a 0%, #f0d478 100%) !important;
        transform: translateY(-1px);
    }
    .careers-site-nav-chevron {
        width: 0.75rem; height: 0.75rem; opacity: 0.7; transition: transform 0.2s;
    }
    .careers-site-nav-dropdown { position: relative; }
    .careers-site-nav-menu {
        display: none;
        position: absolute; top: 100%; left: 0;
        min-width: 12.5rem;
        background: #fff;
        border: 1px solid var(--careers-border);
        border-radius: 0.625rem;
        box-shadow: 0 12px 32px rgba(15,39,68,0.18);
        padding: 0.375rem; z-index: 60;
    }
    .careers-site-nav-menu::before {
        content: '';
        position: absolute;
        top: -0.5rem;
        left: 0;
        right: 0;
        height: 0.5rem;
    }
    .careers-site-nav-menu a {
        display: block;
        padding: 0.5rem 0.75rem;
        color: var(--careers-navy);
        font-size: 0.8125rem; font-weight: 500;
        text-transform: none; letter-spacing: normal;
        border-radius: 0.375rem; text-decoration: none;
        transition: background 0.15s;
    }
    .careers-site-nav-menu a:hover {
        background: #f1f5f9; color: var(--careers-blue);
    }
    @media (min-width: 1024px) {
        .careers-header-top { display: contents; }
        .careers-site-nav-toggle { display: none !important; }
        .careers-header-nav-panel {
            display: flex !important;
            align-items: center;
            gap: 0.625rem;
            min-width: 0;
            flex: 1;
            justify-content: flex-end;
        }
        .careers-site-nav-dropdown:hover .careers-site-nav-menu,
        .careers-site-nav-dropdown:focus-within .careers-site-nav-menu,
        .careers-site-nav-dropdown.is-open .careers-site-nav-menu {
            display: block;
        }
        .careers-site-nav-dropdown:hover .careers-site-nav-chevron,
        .careers-site-nav-dropdown:focus-within .careers-site-nav-chevron,
        .careers-site-nav-dropdown.is-open .careers-site-nav-chevron {
            transform: rotate(180deg);
        }
        .careers-site-nav-dropdown.is-open .careers-site-nav-trigger {
            color: #fff;
            background: rgba(255,255,255,0.1);
        }
    }

    @media (min-width: 1024px) and (max-width: 1279px) {
        .careers-site-nav > a,
        .careers-site-nav-trigger { font-size: 0.625rem; padding: 0.375rem 0.4375rem; }
    }
    @media (min-width: 1280px) {
        .careers-site-nav > a,
        .careers-site-nav-trigger { font-size: 0.75rem; padding: 0.4375rem 0.625rem; }
    }

    .careers-nav {
        display: flex; align-items: center; gap: 0.375rem;
        padding: 0.25rem;
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 9999px;
        flex-shrink: 0;
    }
    .careers-nav a {
        display: inline-flex; align-items: center; gap: 0.375rem;
        color: rgba(255,255,255,0.72); font-size: 0.8125rem; font-weight: 500;
        padding: 0.5rem 0.875rem; border-radius: 9999px;
        transition: all 0.2s; white-space: nowrap;
    }
    .careers-nav-icon {
        width: 0.9375rem; height: 0.9375rem; opacity: 0.85;
    }
    .careers-nav a:hover {
        color: #fff; background: rgba(255,255,255,0.1);
    }
    .careers-nav a.active {
        color: #fff;
        background: linear-gradient(135deg, rgba(59,130,246,0.55), rgba(37,99,235,0.45));
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.12), 0 4px 12px rgba(37,99,235,0.25);
    }
    @media (max-width: 1023px) {
        .careers-header-inner {
            flex-direction: column;
            align-items: stretch;
            gap: 0;
        }
        .careers-header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            width: 100%;
        }
        .careers-brand {
            flex: 1;
            min-width: 0;
        }
        .careers-site-nav-toggle { display: inline-flex; flex-shrink: 0; }
        .careers-header-nav-panel {
            display: none;
            width: 100%;
            flex-direction: column;
            align-items: stretch;
            gap: 0.75rem;
            padding: 0.75rem 0 0;
            max-height: calc(100vh - 4.5rem);
            overflow-y: auto;
        }
        .careers-header-inner.is-nav-open .careers-header-nav-panel { display: flex; }
        .careers-site-nav {
            flex-direction: column; align-items: stretch; gap: 0.25rem;
        }
        .careers-site-nav > a,
        .careers-site-nav-trigger {
            width: 100%; justify-content: space-between;
            font-size: 0.75rem; padding: 0.625rem 0.75rem;
        }
        .careers-site-nav-menu {
            position: static; display: none;
            box-shadow: none; border: none;
            background: rgba(255,255,255,0.05);
            margin-top: 0.25rem; padding: 0.25rem;
        }
        .careers-site-nav-menu a {
            color: rgba(255,255,255,0.88);
            font-size: 0.8125rem;
        }
        .careers-site-nav-menu a:hover {
            background: rgba(255,255,255,0.08); color: #fff;
        }
        .careers-site-nav-dropdown.is-open .careers-site-nav-menu { display: block; }
        .careers-site-nav-dropdown.is-open .careers-site-nav-chevron { transform: rotate(180deg); }
        .careers-nav-local { width: 100%; justify-content: stretch; }
        .careers-nav-local a { flex: 1; justify-content: center; }
    }
    @media (max-width: 640px) {
        :root { --portal-gutter: 1rem; }
        .careers-header-inner { padding: 0.75rem var(--portal-gutter); }
        .careers-brand { gap: 0.625rem; }
        .careers-brand-label { font-size: 1.125rem; }
        .careers-brand-logo-img { height: 1.875rem; max-width: 7rem; }
        .careers-nav a { padding: 0.4375rem 0.625rem; font-size: 0.75rem; }
        .careers-nav-icon { display: none; }
        .portal-section { padding-block: 1.5rem; }
    }
    .careers-main {
        max-width: var(--portal-max-width);
        margin: 0 auto;
        padding: 1.5rem var(--portal-gutter) 2rem;
        width: 100%;
        flex: 1;
    }
    .careers-main-fluid { max-width: none; padding: 0; width: 100%; }
    .careers-main-fluid .careers-alert {
        max-width: var(--portal-max-width);
        margin-inline: auto;
        width: calc(100% - (var(--portal-gutter) * 2));
    }

    .portal-requirement-hero {
        padding: clamp(2.5rem, 8vw, 4rem) var(--portal-gutter);
        text-align: center;
    }
    .portal-requirement-hero h1 {
        font-size: clamp(1.625rem, 5vw, 2.25rem);
        line-height: 1.2;
    }
    .portal-requirement-hero p {
        font-size: clamp(0.8125rem, 2.5vw, 0.875rem);
    }

    .careers-hero {
        margin: -1.5rem calc(-1 * var(--portal-gutter)) 2rem;
        padding: 3rem var(--portal-gutter) 4.5rem;
        background: linear-gradient(145deg, #071526 0%, #0f2744 35%, #1a3a6e 70%, #1d4ed8 100%);
        position: relative; overflow: hidden;
    }
    .careers-hero-bg {
        position: absolute; inset: 0; pointer-events: none;
    }
    .careers-hero-orb {
        position: absolute; border-radius: 50%;
        filter: blur(60px); opacity: 0.5;
    }
    .careers-hero-orb-1 {
        width: 22rem; height: 22rem; top: -6rem; right: -4rem;
        background: rgba(59, 130, 246, 0.45);
        animation: careers-orb-float 8s ease-in-out infinite;
    }
    .careers-hero-orb-2 {
        width: 16rem; height: 16rem; bottom: -4rem; left: 10%;
        background: rgba(96, 165, 250, 0.35);
        animation: careers-orb-float 10s ease-in-out infinite reverse;
    }
    .careers-hero-orb-3 {
        width: 10rem; height: 10rem; top: 40%; left: 55%;
        background: rgba(147, 197, 253, 0.2);
        animation: careers-orb-float 12s ease-in-out infinite;
    }
    @keyframes careers-orb-float {
        0%, 100% { transform: translate(0, 0) scale(1); }
        50% { transform: translate(1rem, -1.25rem) scale(1.05); }
    }
    .careers-hero-grid {
        position: absolute; inset: 0;
        background-image:
            linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
        background-size: 48px 48px;
        mask-image: radial-gradient(ellipse 80% 70% at 50% 40%, black 20%, transparent 75%);
    }
    .careers-hero-inner {
        position: relative; z-index: 1;
        max-width: var(--portal-max-width); margin: 0 auto;
        padding-inline: var(--portal-gutter);
    }
    .careers-hero-content { max-width: 38rem; }
    .careers-hero-badge {
        display: inline-flex; align-items: center; gap: 0.5rem;
        padding: 0.375rem 0.875rem 0.375rem 0.625rem;
        border-radius: 9999px;
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.14);
        color: rgba(255,255,255,0.9);
        font-size: 0.75rem; font-weight: 600;
        letter-spacing: 0.02em;
        backdrop-filter: blur(8px);
        margin-bottom: 1.25rem;
    }
    .careers-hero-badge-dot {
        width: 0.5rem; height: 0.5rem; border-radius: 50%;
        background: #4ade80;
        box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.25);
        animation: careers-pulse 2s ease-in-out infinite;
    }
    @keyframes careers-pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.55; }
    }
    .careers-hero h1 {
        font-size: clamp(2rem, 5vw, 3.25rem); font-weight: 800; color: #fff;
        letter-spacing: -0.04em; line-height: 1.08;
    }
    .careers-hero-highlight {
        background: linear-gradient(135deg, #93c5fd 0%, #60a5fa 45%, #ffffff 100%);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }
    .careers-hero p {
        color: rgba(255,255,255,0.78); margin-top: 1rem;
        font-size: 1.0625rem; line-height: 1.65; max-width: 32rem;
    }
    .careers-hero-actions {
        display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 1.75rem;
    }
    .careers-btn-hero-primary {
        background: #fff; color: var(--careers-navy);
        box-shadow: 0 8px 24px rgba(0,0,0,0.18);
        padding: 0.75rem 1.375rem;
    }
    .careers-btn-hero-primary:hover {
        background: #f8fafc; transform: translateY(-1px);
        box-shadow: 0 12px 28px rgba(0,0,0,0.22);
    }
    .careers-btn-hero-ghost {
        background: rgba(255,255,255,0.08);
        color: #fff;
        border: 1px solid rgba(255,255,255,0.22);
        backdrop-filter: blur(8px);
        padding: 0.75rem 1.375rem;
    }
    .careers-btn-hero-ghost:hover {
        background: rgba(255,255,255,0.14);
        border-color: rgba(255,255,255,0.35);
    }
    @media (max-width: 639px) {
        .careers-hero { padding: 2.5rem 0 4rem; margin: -1.5rem 0 2rem; }
        .careers-hero-actions { flex-direction: column; }
        .careers-hero-actions .careers-btn { width: 100%; justify-content: center; }
        .careers-search { margin-top: -2.5rem; padding: 1rem; }
        .careers-search-grid > * { flex: 1 1 100% !important; min-width: 0 !important; width: 100% !important; }
        .careers-search-grid .careers-btn { width: 100%; justify-content: center; }
        .careers-job-grid { grid-template-columns: 1fr; }
        .careers-job-actions { flex-direction: column; }
        .careers-job-actions .careers-btn { width: 100%; }
    }

    .careers-search {
        background: var(--careers-surface); border-radius: 1rem;
        box-shadow: 0 4px 24px rgba(15,39,68,0.08), 0 1px 3px rgba(0,0,0,0.04);
        padding: 1.25rem; margin-top: -3rem; position: relative; z-index: 10;
        border: 1px solid var(--careers-border);
    }
    .careers-search-grid { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: flex-end; }
    .careers-field {
        display: block;
        font-size: 0.6875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--careers-muted);
        margin-bottom: 0.25rem;
    }
    .careers-input:not(textarea):not([type="file"]) {
        height: 28px;
        min-height: 28px;
        padding: 0 0.5rem;
        font-size: 0.75rem;
        line-height: 1;
        border-radius: 0.125rem;
    }
    .careers-input {
        width: 100%;
        border: 1px solid var(--careers-border);
        background: #fff;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .careers-input:is(textarea) {
        height: auto;
        min-height: 3.5rem;
        padding: 0.375rem 0.5rem;
        font-size: 0.75rem;
        line-height: 1.4;
        border-radius: 0.125rem;
    }
    .careers-input[type="file"] {
        height: auto;
        min-height: 28px;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        border-radius: 0.125rem;
        background: #f8fafc;
    }
    .careers-input:focus {
        outline: none; border-color: var(--careers-accent);
        box-shadow: 0 0 0 3px rgba(59,130,246,0.15); background: #fff;
    }
    .careers-btn {
        display: inline-flex; align-items: center; justify-content: center; gap: 0.375rem;
        padding: 0.625rem 1.25rem; font-size: 0.8125rem; font-weight: 600;
        border-radius: 0.625rem; transition: all 0.2s; cursor: pointer; border: none;
    }
    .careers-btn-primary {
        background: var(--careers-gradient); color: #fff;
        box-shadow: 0 2px 8px rgba(30,77,140,0.35);
    }
    .careers-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(30,77,140,0.4); }
    .careers-btn-secondary {
        background: #fff; color: var(--careers-navy); border: 1px solid var(--careers-border);
    }
    .careers-btn-secondary:hover { background: #f8fafc; border-color: #cbd5e1; }
    .careers-btn-ghost { background: rgba(255,255,255,0.15); color: #fff; border: 1px solid rgba(255,255,255,0.25); }
    .careers-btn-ghost:hover { background: rgba(255,255,255,0.25); }

    .careers-job-grid { display: grid; gap: 1rem; }
    @media (min-width: 768px) { .careers-job-grid { grid-template-columns: repeat(2, 1fr); } }

    .careers-job-card {
        background: var(--careers-surface); border: 1px solid var(--careers-border);
        border-radius: 1rem; padding: 1.375rem; transition: all 0.25s ease;
        display: flex; flex-direction: column; gap: 1rem;
    }
    .careers-job-card:hover {
        border-color: #93c5fd; box-shadow: 0 8px 32px rgba(30,77,140,0.1);
        transform: translateY(-2px);
    }
    .careers-job-title { font-size: 1.0625rem; font-weight: 700; color: var(--careers-navy); letter-spacing: -0.02em; }
    .careers-job-meta { font-size: 0.8125rem; color: var(--careers-muted); display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
    .careers-job-meta-dot::before { content: '·'; margin-right: 0.5rem; color: #cbd5e1; }
    .careers-tag {
        display: inline-flex; align-items: center; font-size: 0.6875rem; font-weight: 600;
        padding: 0.25rem 0.625rem; border-radius: 9999px;
    }
    .careers-tag-open { background: #dcfce7; color: #166534; }
    .careers-tag-factory { background: #eff6ff; color: #1d4ed8; }
    .careers-job-desc { font-size: 0.8125rem; color: #475569; line-height: 1.6; flex: 1; }
    .careers-job-actions { display: flex; gap: 0.5rem; margin-top: auto; }

    .careers-card {
        background: var(--careers-surface); border: 1px solid var(--careers-border);
        border-radius: 1rem; overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .careers-card-head {
        padding: 1.5rem; background: var(--careers-gradient); color: #fff;
    }
    .careers-card-body { padding: 1.5rem; }
    .careers-section-title {
        font-size: 0.6875rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.08em; color: var(--careers-muted); margin-bottom: 0.5rem;
    }
    .careers-empty {
        text-align: center; padding: 4rem 2rem; background: var(--careers-surface);
        border-radius: 1rem; border: 1px dashed var(--careers-border);
    }
    .careers-empty-icon { font-size: 2.5rem; margin-bottom: 1rem; opacity: 0.4; }
    .careers-alert {
        padding: 0.875rem 1rem; border-radius: 0.625rem; font-size: 0.8125rem; margin-bottom: 1rem;
    }
    .careers-alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
    .careers-alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }

    .careers-back {
        display: inline-flex; align-items: center; gap: 0.375rem;
        font-size: 0.8125rem; color: var(--careers-blue); font-weight: 500;
        margin-bottom: 1.25rem; transition: color 0.2s;
    }
    .careers-back:hover { color: var(--careers-navy); }

    .careers-form-section {
        background: var(--careers-surface); border: 1px solid var(--careers-border);
        border-radius: 1rem; margin-bottom: 1rem; overflow: hidden;
    }
    .careers-form-section-head {
        padding: 0.875rem 1.25rem; background: #f8fafc;
        border-bottom: 1px solid var(--careers-border);
        font-size: 0.6875rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.06em; color: var(--careers-navy);
    }
    .careers-form-section-body { padding: 1.25rem; }

    .careers-pipeline { display: flex; flex-wrap: wrap; gap: 0.5rem; margin: 1.5rem 0; }
    .careers-pipeline-step {
        flex: 1; min-width: 4rem; text-align: center; position: relative;
    }
    .careers-pipeline-dot {
        width: 2rem; height: 2rem; border-radius: 50%; margin: 0 auto 0.375rem;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.6875rem; font-weight: 700; border: 2px solid #e2e8f0;
        background: #fff; color: #94a3b8;
    }
    .careers-pipeline-step.done .careers-pipeline-dot { background: #dcfce7; border-color: #22c55e; color: #166534; }
    .careers-pipeline-step.current .careers-pipeline-dot { background: #dbeafe; border-color: #3b82f6; color: #1d4ed8; box-shadow: 0 0 0 4px rgba(59,130,246,0.2); }
    .careers-pipeline-label { font-size: 0.625rem; color: var(--careers-muted); font-weight: 500; }

    .careers-footer {
        margin-top: auto;
        border-top: 1px solid var(--careers-border);
        padding: 1rem 0;
        text-align: center;
        font-size: 0.75rem;
        color: var(--careers-muted);
        background: #fff;
        flex-shrink: 0;
    }

    .careers-centered { max-width: 42rem; margin-left: auto; margin-right: auto; width: 100%; }
    .careers-otp-row { display: flex; gap: 0.5rem; align-items: center; }
    .careers-otp-row .careers-input { flex: 1; }
    .careers-otp-row .careers-btn { height: 28px; min-height: 28px; padding: 0 0.75rem; border-radius: 0.125rem; font-size: 0.75rem; }

    /* Job detail — hero + content layout */
    .careers-job-hero {
        margin: -1.5rem calc(-1 * var(--portal-gutter)) 0;
        padding: 2rem var(--portal-gutter) 3.5rem;
    }
    .careers-job-back {
        display: inline-flex; align-items: center; gap: 0.375rem;
        font-size: 0.8125rem; font-weight: 500; color: rgba(255,255,255,0.72);
        margin-bottom: 1.5rem; transition: color 0.2s; text-decoration: none;
    }
    .careers-job-back:hover { color: #fff; }
    .careers-job-hero-content { max-width: 48rem; }
    .careers-job-hero-title {
        font-size: clamp(1.5rem, 4vw, 2.625rem); font-weight: 800; color: #fff;
        letter-spacing: -0.035em; line-height: 1.15; margin-top: 0.25rem;
    }
    .careers-job-hero .careers-job-detail-tags {
        display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 1.125rem;
    }
    .careers-job-hero .careers-tag {
        border: 1px solid transparent;
        backdrop-filter: blur(8px);
    }
    .careers-job-hero .careers-tag-open {
        background: rgba(74, 222, 128, 0.14); color: #86efac;
        border-color: rgba(74, 222, 128, 0.28);
    }
    .careers-job-hero .careers-tag-factory {
        background: rgba(96, 165, 250, 0.14); color: #bfdbfe;
        border-color: rgba(96, 165, 250, 0.28);
    }
    .careers-job-hero .careers-tag-role {
        background: rgba(255, 255, 255, 0.08); color: rgba(255,255,255,0.88);
        border-color: rgba(255,255,255,0.16);
    }
    .careers-job-hero-deadline {
        margin-top: 1rem; font-size: 0.875rem; color: rgba(255,255,255,0.72);
    }
    .careers-job-hero-deadline strong { color: #fcd34d; font-weight: 700; }
    .careers-job-hero-deadline-note { color: rgba(255,255,255,0.55); font-size: 0.8125rem; }
    .careers-job-hero-subtitle {
        margin-top: 0.375rem; font-size: 1.125rem; font-weight: 500;
        color: rgba(255,255,255,0.82); line-height: 1.4;
    }
    .careers-job-hero-salary {
        margin-top: 1rem; display: inline-flex; align-items: center;
        font-size: 0.9375rem; font-weight: 700; color: #bbf7d0;
        background: rgba(34, 197, 94, 0.12); border: 1px solid rgba(74, 222, 128, 0.28);
        padding: 0.4375rem 0.875rem; border-radius: 9999px;
    }
    .careers-job-hero-actions {
        margin-top: 1.5rem; display: flex; flex-wrap: wrap; gap: 0.625rem;
    }

    .careers-job-facts {
        display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.75rem;
        margin: -1.25rem 0 1.5rem; position: relative; z-index: 11;
    }
    @media (min-width: 768px) {
        .careers-job-facts { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }
    .careers-job-fact {
        background: var(--careers-surface); border: 1px solid var(--careers-border);
        border-radius: 0.875rem; padding: 0.875rem 1rem;
        box-shadow: 0 4px 16px rgba(15,39,68,0.06);
    }
    .careers-job-fact-label {
        display: block; font-size: 0.625rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.06em;
        color: var(--careers-muted); margin-bottom: 0.25rem;
    }
    .careers-job-fact strong { font-size: 0.875rem; color: var(--careers-navy); line-height: 1.35; }

    .careers-job-panel-title {
        font-size: 1.0625rem; font-weight: 700; color: var(--careers-navy);
        margin-bottom: 1rem; letter-spacing: -0.02em;
    }
    .careers-job-share {
        margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--careers-border);
    }
    .careers-job-share-label {
        display: block; font-size: 0.6875rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.06em;
        color: var(--careers-muted); margin-bottom: 0.5rem;
    }
    .careers-sidebar-meta-row-stack { grid-template-columns: 1fr; gap: 0.25rem; }
    .careers-sidebar-meta-row-stack dd { font-weight: 500; line-height: 1.5; }

    .careers-job-apply-bar {
        display: none; position: fixed; left: 0; right: 0; bottom: 0; z-index: 40;
        padding: 0.75rem 1rem calc(0.75rem + env(safe-area-inset-bottom, 0px));
        background: rgba(255,255,255,0.96); border-top: 1px solid var(--careers-border);
        box-shadow: 0 -8px 24px rgba(15,39,68,0.1);
        backdrop-filter: blur(10px);
    }
    @media (max-width: 1023px) {
        .careers-job-apply-bar { display: block; }
        .careers-main { padding-bottom: 5.5rem; }
    }
    .careers-job-apply-bar-inner {
        max-width: var(--portal-max-width); margin: 0 auto;
        padding-inline: var(--portal-gutter);
        display: flex; align-items: center; justify-content: space-between; gap: 1rem;
    }
    .careers-job-apply-bar-text {
        min-width: 0; display: flex; flex-direction: column; gap: 0.125rem;
    }
    .careers-job-apply-bar-text strong {
        font-size: 0.8125rem; color: var(--careers-navy);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .careers-job-apply-bar-text span { font-size: 0.6875rem; color: var(--careers-muted); font-weight: 600; }

    .careers-job-detail {
        position: relative; z-index: 10;
    }
    .careers-job-detail-grid {
        display: grid; gap: 1.25rem;
    }
    @media (min-width: 1024px) {
        .careers-job-detail-grid { grid-template-columns: 1fr 300px; align-items: start; }
    }

    .careers-job-detail-main {
        background: var(--careers-surface);
        border: 1px solid var(--careers-border);
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(15,39,68,0.08);
    }
    .careers-job-content { padding: 1.5rem; }

    .careers-job-tabs { display: flex; flex-direction: column; }
    .careers-job-tab-list {
        display: flex; flex-wrap: wrap; gap: 0;
        border-bottom: 1px solid var(--careers-border);
        background: #f8fafc;
        overflow-x: auto;
    }
    .careers-job-tab {
        flex-shrink: 0;
        padding: 0.875rem 1.125rem;
        font-size: 0.8125rem; font-weight: 600;
        color: var(--careers-muted);
        background: transparent; border: none;
        border-bottom: 2px solid transparent;
        cursor: pointer; white-space: nowrap;
        transition: color 0.15s, border-color 0.15s, background 0.15s;
    }
    .careers-job-tab:hover { color: var(--careers-navy); background: #fff; }
    .careers-job-tab.is-active {
        color: var(--careers-blue);
        background: var(--careers-surface);
        border-bottom-color: var(--careers-blue);
    }
    .careers-job-tab-panel { display: none; }
    .careers-job-tab-panel.is-active { display: block; }
    .careers-tab-empty {
        font-size: 0.875rem; color: var(--careers-muted);
        text-align: center; padding: 2.5rem 1rem;
    }

    .careers-job-section { margin-bottom: 2rem; }
    .careers-job-section:last-child { margin-bottom: 0; }
    .careers-job-section-title {
        font-size: 1rem; font-weight: 700; color: var(--careers-navy);
        margin-bottom: 0.875rem; padding-bottom: 0.5rem;
        border-bottom: 1px solid var(--careers-border);
    }
    .careers-salary-line {
        font-size: 0.9375rem; font-weight: 600; color: #15803d;
        background: #f0fdf4; border: 1px solid #bbf7d0;
        padding: 0.75rem 1rem; border-radius: 0.625rem;
    }

    .careers-prose {
        font-size: 0.875rem; line-height: 1.75; color: #334155;
    }
    .careers-prose p { margin-bottom: 0.75rem; }
    .careers-prose p:last-child { margin-bottom: 0; }
    .careers-prose ul, .careers-prose ol { margin: 0.5rem 0 0.75rem 1.25rem; }
    .careers-prose li { margin-bottom: 0.375rem; }
    .careers-prose h3, .careers-prose h4 {
        font-weight: 700; color: var(--careers-navy);
        margin: 1rem 0 0.5rem;
    }
    .careers-prose strong, .careers-prose b { font-weight: 600; color: #1e293b; }
    .careers-prose a { color: var(--careers-blue); text-decoration: underline; }
    .careers-prose blockquote {
        border-left: 3px solid #cbd5e1; padding-left: 1rem; margin: 1rem 0;
        color: #64748b; font-style: italic;
    }
    .careers-prose table { width: 100%; border-collapse: collapse; margin: 1rem 0; font-size: 0.8125rem; }
    .careers-prose th, .careers-prose td { border: 1px solid var(--careers-border); padding: 0.5rem 0.75rem; text-align: left; }
    .careers-prose th { background: #f8fafc; font-weight: 600; }

    .careers-job-sidebar-card-float {
        box-shadow: 0 12px 40px rgba(15,39,68,0.12);
    }
    .careers-job-sidebar { display: flex; flex-direction: column; gap: 1rem; }
    @media (max-width: 639px) {
        .careers-job-hero { padding: 1.75rem var(--portal-gutter) 3rem; margin: -1.5rem 0 0; }
        .careers-job-hero-actions .careers-btn { flex: 1; min-width: calc(50% - 0.375rem); }
    }
    @media (min-width: 1024px) {
        .careers-job-sidebar { position: sticky; top: 5rem; }
    }
    .careers-job-sidebar-card {
        background: var(--careers-surface);
        border: 1px solid var(--careers-border);
        border-radius: 1rem;
        padding: 1.25rem;
        box-shadow: 0 4px 16px rgba(15,39,68,0.06);
    }
    .careers-sidebar-deadline {
        text-align: center; padding-bottom: 1rem; margin-bottom: 1rem;
        border-bottom: 1px solid var(--careers-border);
    }
    .careers-sidebar-deadline-label {
        display: block; font-size: 0.6875rem; font-weight: 600;
        text-transform: uppercase; letter-spacing: 0.06em;
        color: var(--careers-muted); margin-bottom: 0.25rem;
    }
    .careers-sidebar-deadline strong { font-size: 1.0625rem; color: #b45309; }

    .careers-sidebar-meta { margin-top: 1.25rem; }
    .careers-sidebar-meta-row {
        display: grid; grid-template-columns: 5.5rem 1fr; gap: 0.5rem;
        padding: 0.625rem 0; border-bottom: 1px solid #f1f5f9;
        font-size: 0.8125rem;
    }
    .careers-sidebar-meta-row:last-child { border-bottom: none; }
    .careers-sidebar-meta-row dt { color: var(--careers-muted); font-weight: 500; }
    .careers-sidebar-meta-row dd { color: #1e293b; font-weight: 600; margin: 0; }

    .careers-company-title {
        font-size: 0.6875rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: 0.06em; color: var(--careers-muted); margin-bottom: 0.75rem;
    }
    .careers-company-name { font-size: 0.9375rem; font-weight: 700; color: var(--careers-navy); }
    .careers-company-address, .careers-company-phone {
        font-size: 0.8125rem; color: #64748b; margin-top: 0.5rem; line-height: 1.5;
    }
</style>
