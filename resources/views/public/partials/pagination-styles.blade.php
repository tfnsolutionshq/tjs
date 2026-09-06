<style>
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
        color: #0f172a;
        border: 1px solid color-mix(in srgb, #0f172a 10%, transparent);
        background: #fff;
    }
    .jp-pagination__btn:hover,
    .jp-pagination__page:hover {
        border-color: color-mix(in srgb, var(--blue, #2563eb) 35%, transparent);
        color: var(--blue, #2563eb);
    }
    .jp-pagination__btn.is-disabled {
        opacity: .45;
        pointer-events: none;
    }
    .jp-pagination__page.is-active {
        background: var(--blue, #2563eb);
        border-color: var(--blue, #2563eb);
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
        color: #64748b;
        font-size: .78rem;
    }
</style>
