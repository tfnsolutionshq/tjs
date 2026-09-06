<style>
    .jp {
        max-width: 72rem;
        margin: 0 auto;
        padding: 2rem 1rem 3rem;
    }
    @media (min-width: 640px) {
        .jp { padding: 2.5rem 1.5rem 3.5rem; }
    }

    .jp-intro {
        display: grid;
        gap: 1rem;
        margin-bottom: 1.75rem;
    }
    @media (min-width: 768px) {
        .jp-intro {
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: end;
            gap: 1.5rem;
        }
    }

    .jp-lead {
        margin: 0;
        font-size: 1rem;
        line-height: 1.65;
        color: var(--j-muted);
        max-width: 42rem;
    }

    .jp-stat {
        display: inline-flex;
        align-items: center;
        gap: .55rem;
        padding: .55rem .85rem;
        border-radius: 999px;
        background: color-mix(in srgb, var(--j-accent) 10%, white);
        border: 1px solid color-mix(in srgb, var(--j-accent) 22%, transparent);
        color: var(--j-accent);
        font-size: .78rem;
        font-weight: 700;
        letter-spacing: .02em;
        white-space: nowrap;
    }
    .jp-stat svg {
        width: 1rem;
        height: 1rem;
        flex-shrink: 0;
    }

    .jp-grid {
        display: grid;
        gap: 1rem;
        grid-template-columns: minmax(0, 1fr);
    }
    @media (min-width: 640px) {
        .jp-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (min-width: 1024px) {
        .jp-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }

    .jp-grid--2 {
        grid-template-columns: minmax(0, 1fr);
    }
    @media (min-width: 900px) {
        .jp-grid--2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    .jp-card {
        display: flex;
        flex-direction: column;
        gap: .85rem;
        height: 100%;
        padding: 1.15rem 1.15rem 1.2rem;
        background: var(--j-surface);
        border: 1px solid color-mix(in srgb, var(--j-text) 8%, transparent);
        border-radius: 1rem;
        box-shadow: 0 10px 28px color-mix(in srgb, var(--j-text) 4%, transparent);
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }
    .jp-card:hover {
        transform: translateY(-2px);
        border-color: color-mix(in srgb, var(--j-accent) 28%, transparent);
        box-shadow: 0 16px 36px color-mix(in srgb, var(--j-text) 8%, transparent);
    }

    .jp-card__head {
        display: flex;
        align-items: flex-start;
        gap: .85rem;
        min-width: 0;
    }

    .jp-avatar {
        flex-shrink: 0;
        width: 3.1rem;
        height: 3.1rem;
        border-radius: .95rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .95rem;
        font-weight: 800;
        letter-spacing: .03em;
        color: #fff;
        overflow: hidden;
        background: linear-gradient(145deg, var(--j-accent), color-mix(in srgb, var(--j-accent) 65%, #0f172a));
        box-shadow: 0 8px 18px color-mix(in srgb, var(--j-accent) 28%, transparent);
    }

    .jp-card__who { min-width: 0; flex: 1; }

    .jp-name {
        margin: 0;
        font-family: var(--j-font-display, 'Libre Baskerville', Georgia, serif);
        font-size: 1.05rem;
        font-weight: 700;
        line-height: 1.35;
        color: var(--j-text);
        word-break: break-word;
    }

    .jp-role {
        margin: .28rem 0 0;
        font-size: .82rem;
        font-weight: 650;
        line-height: 1.45;
        color: var(--j-accent);
    }

    .jp-affiliation {
        margin: .2rem 0 0;
        font-size: .8rem;
        line-height: 1.45;
        color: var(--j-muted);
    }

    .jp-bio {
        margin: 0;
        font-size: .84rem;
        line-height: 1.55;
        color: var(--j-muted);
        display: -webkit-box;
        -webkit-line-clamp: 4;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .jp-card__foot {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
        margin-top: auto;
        padding-top: .15rem;
    }

    .jp-tag {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .22rem .55rem;
        border-radius: 999px;
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        background: color-mix(in srgb, var(--j-text) 5%, transparent);
        color: var(--j-muted);
    }

    .jp-orcid {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        font-size: .76rem;
        font-weight: 650;
        color: var(--j-accent);
        text-decoration: none;
    }
    .jp-orcid:hover { text-decoration: underline; }

    .jp-empty {
        grid-column: 1 / -1;
        text-align: center;
        padding: 3rem 1.5rem;
        background: var(--j-surface);
        border: 1px dashed color-mix(in srgb, var(--j-text) 14%, transparent);
        border-radius: 1.1rem;
    }
    .jp-empty__icon {
        width: 3.25rem;
        height: 3.25rem;
        margin: 0 auto;
        border-radius: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: color-mix(in srgb, var(--j-accent) 12%, white);
        color: var(--j-accent);
    }
    .jp-empty__icon svg { width: 1.45rem; height: 1.45rem; }
    .jp-empty__title {
        margin: 1rem 0 0;
        font-family: var(--j-font-display, 'Libre Baskerville', Georgia, serif);
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--j-text);
    }
    .jp-empty__text {
        margin: .45rem auto 0;
        max-width: 26rem;
        font-size: .88rem;
        line-height: 1.55;
        color: var(--j-muted);
    }

    .jp-split {
        display: grid;
        gap: 1rem;
    }
    @media (min-width: 900px) {
        .jp-split { grid-template-columns: minmax(0, 1.4fr) minmax(16rem, .85fr); align-items: start; }
    }

    .jp-panel {
        padding: 1.35rem 1.4rem;
        background: var(--j-surface);
        border: 1px solid color-mix(in srgb, var(--j-text) 8%, transparent);
        border-radius: 1rem;
        box-shadow: 0 10px 28px color-mix(in srgb, var(--j-text) 4%, transparent);
    }

    .jp-panel__title {
        margin: 0 0 .85rem;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: var(--j-muted);
    }

    .jp-prose {
        margin: 0;
        font-size: .95rem;
        line-height: 1.7;
        color: var(--j-text);
    }
    .jp-prose.tjs-prose p { margin: 0 0 .85em; }
    .jp-prose.tjs-prose ul,
    .jp-prose.tjs-prose ol { margin: .35em 0 .85em; padding-left: 1.35rem; }
    .jp-prose.tjs-prose ul { list-style-type: disc; }
    .jp-prose.tjs-prose ol { list-style-type: decimal; }
    .jp-prose.tjs-prose ul ul { list-style-type: circle; }
    .jp-prose.tjs-prose ol ol { list-style-type: lower-alpha; }
    .jp-prose.tjs-prose li { display: list-item; margin: .2em 0; }
    .jp-prose.tjs-prose a { color: var(--j-accent, #1d4ed8); }

    .jp-meta-list {
        display: grid;
        gap: .85rem;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .jp-meta-item {
        padding-bottom: .85rem;
        border-bottom: 1px solid color-mix(in srgb, var(--j-text) 8%, transparent);
    }
    .jp-meta-item:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .jp-meta-label {
        display: block;
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: var(--j-muted);
    }

    .jp-meta-value {
        display: block;
        margin-top: .28rem;
        font-size: .92rem;
        font-weight: 650;
        line-height: 1.45;
        color: var(--j-text);
    }

    .jp-volume {
        display: grid;
        gap: 1rem;
        height: 100%;
    }
    @media (min-width: 520px) {
        .jp-volume { grid-template-columns: 5.5rem minmax(0, 1fr); align-items: start; }
    }

    .jp-volume__cover {
        display: block;
        width: 5.5rem;
        height: 7.25rem;
        border-radius: .75rem;
        overflow: hidden;
        border: 1px solid color-mix(in srgb, var(--j-text) 12%, transparent);
        background: color-mix(in srgb, var(--j-accent) 8%, white);
        box-shadow: 0 8px 20px color-mix(in srgb, var(--j-text) 6%, transparent);
    }
    .jp-volume__cover img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .jp-volume__cover--placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: var(--j-font-display, 'Libre Baskerville', Georgia, serif);
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--j-accent);
    }

    .jp-volume__title {
        margin: 0;
        font-family: var(--j-font-display, 'Libre Baskerville', Georgia, serif);
        font-size: 1.12rem;
        font-weight: 700;
        line-height: 1.35;
        color: var(--j-text);
    }

    .jp-volume__subtitle {
        margin: .3rem 0 0;
        font-size: .84rem;
        line-height: 1.45;
        color: var(--j-muted);
    }

    .jp-issues {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
        margin-top: .85rem;
        padding: 0;
        list-style: none;
    }

    .jp-issue-link {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .4rem .7rem;
        border-radius: 999px;
        font-size: .78rem;
        font-weight: 650;
        text-decoration: none;
        color: var(--j-accent);
        background: color-mix(in srgb, var(--j-accent) 10%, white);
        border: 1px solid color-mix(in srgb, var(--j-accent) 22%, transparent);
        transition: background .15s ease, border-color .15s ease;
    }
    .jp-issue-link:hover {
        background: color-mix(in srgb, var(--j-accent) 16%, white);
        border-color: color-mix(in srgb, var(--j-accent) 35%, transparent);
        text-decoration: none;
    }

    .jp-list-controls {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        margin-bottom: 1.25rem;
    }
    .jp-list-controls__meta {
        font-size: .78rem;
        color: var(--j-muted);
        font-weight: 650;
    }
    .jp-layout-toggle {
        display: inline-flex;
        border: 1px solid color-mix(in srgb, var(--j-text) 10%, transparent);
        border-radius: .7rem;
        overflow: hidden;
        background: var(--j-surface);
    }
    .jp-layout-toggle__btn {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .5rem .75rem;
        font-size: .76rem;
        font-weight: 700;
        color: var(--j-muted);
        text-decoration: none;
        border-right: 1px solid color-mix(in srgb, var(--j-text) 8%, transparent);
    }
    .jp-layout-toggle__btn:last-child { border-right: 0; }
    .jp-layout-toggle__btn svg { width: .95rem; height: .95rem; }
    .jp-layout-toggle__btn.is-active {
        background: color-mix(in srgb, var(--j-accent) 12%, white);
        color: var(--j-accent);
    }
    .jp-layout-toggle__btn:hover { color: var(--j-text); }

    .jp-grid.is-list,
    .jp-grid--2.is-list {
        grid-template-columns: minmax(0, 1fr);
    }
    .jp-grid.is-list .jp-card,
    .jp-grid--2.is-list .jp-card {
        flex-direction: row;
        align-items: stretch;
        gap: 1rem;
    }
    @media (max-width: 720px) {
        .jp-grid.is-list .jp-card,
        .jp-grid--2.is-list .jp-card { flex-direction: column; }
    }
    .jp-grid.is-list .jp-card__head,
    .jp-grid.is-list .jp-card__who { flex: 1; min-width: 0; }
    .jp-grid.is-list .jp-volume { grid-template-columns: 7rem minmax(0, 1fr); }
    @media (max-width: 640px) {
        .jp-grid.is-list .jp-volume { grid-template-columns: 1fr; }
    }

    .jp-list-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: 1rem 1.05rem;
        background: var(--j-surface);
        border: 1px solid color-mix(in srgb, var(--j-text) 8%, transparent);
        border-radius: 1rem;
    }

    .jp-pagination {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: center;
        gap: .45rem;
        margin-top: 1.5rem;
    }
    .jp-pagination__btn,
    .jp-pagination__page {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 2.35rem;
        height: 2.35rem;
        padding: 0 .65rem;
        border-radius: .65rem;
        font-size: .78rem;
        font-weight: 700;
        text-decoration: none;
        color: var(--j-text);
        border: 1px solid color-mix(in srgb, var(--j-text) 10%, transparent);
        background: var(--j-surface);
    }
    .jp-pagination__btn:hover,
    .jp-pagination__page:hover {
        border-color: color-mix(in srgb, var(--j-accent) 35%, transparent);
        color: var(--j-accent);
    }
    .jp-pagination__btn.is-disabled,
    .jp-pagination__page.is-active {
        opacity: 1;
    }
    .jp-pagination__btn.is-disabled {
        opacity: .45;
        pointer-events: none;
    }
    .jp-pagination__page.is-active {
        background: var(--j-accent);
        border-color: var(--j-accent);
        color: #fff;
    }
    .jp-pagination__pages {
        display: inline-flex;
        flex-wrap: wrap;
        gap: .35rem;
    }
    .jp-pagination__ellipsis {
        display: inline-flex;
        align-items: center;
        padding: 0 .25rem;
        color: var(--j-muted);
        font-size: .78rem;
    }
</style>
