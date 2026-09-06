import './bootstrap';

import Alpine from 'alpinejs';
import { registerTjsSelect } from './tjs-select';
import { registerTjsRichText } from './tjs-richtext';

window.Alpine = Alpine;

document.addEventListener('alpine:init', () => {
    registerTjsSelect(Alpine);
    registerTjsRichText(Alpine);
});

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const nodes = document.querySelectorAll('.tjs-reveal');

    if (!nodes.length) {
        return;
    }

    if (reduceMotion || !('IntersectionObserver' in window)) {
        nodes.forEach((el) => el.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.14, rootMargin: '0px 0px -6% 0px' }
    );

    nodes.forEach((el) => observer.observe(el));
});
