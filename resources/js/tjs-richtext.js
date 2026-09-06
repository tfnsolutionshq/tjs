function decodeEscapedMarkup(html) {
    if (!html || !html.includes('&lt;') || !/&lt;\/?[a-z][^&]*&gt;/i.test(html)) {
        return html;
    }

    const textarea = document.createElement('textarea');
    textarea.innerHTML = html;

    return textarea.value;
}

function looksLikeHtml(text) {
    return /<[a-z][\s\S]*>/i.test(text);
}

export function registerTjsRichText(Alpine) {
    Alpine.data('tjsRichText', (cfg = {}) => ({
        value: cfg.value || '',
        placeholder: cfg.placeholder || '',
        minHeight: cfg.minHeight || '9rem',
        focused: false,

        init() {
            this.$nextTick(() => {
                const editor = this.$refs.editor;
                if (!editor) return;
                const html = decodeEscapedMarkup(this.value || '');
                editor.innerHTML = html;
                if (!html) {
                    editor.innerHTML = '';
                }
                this.sync();
            });
        },

        sync() {
            const editor = this.$refs.editor;
            if (!editor) return;
            let html = editor.innerHTML.trim();
            if (html === '<br>' || html === '<div><br></div>' || html === '<p><br></p>') {
                html = '';
            }
            this.value = html;
            if (this.$refs.input) {
                this.$refs.input.value = html;
            }
        },

        cmd(command, value = null) {
            this.$refs.editor?.focus();
            document.execCommand(command, false, value);
            this.sync();
        },

        link() {
            const url = window.prompt('Link URL', 'https://');
            if (!url) return;
            this.cmd('createLink', url);
        },

        onPaste(event) {
            event.preventDefault();
            const clipboard = event.clipboardData || window.clipboardData;
            const html = clipboard?.getData('text/html') || '';
            const text = clipboard?.getData('text/plain') || '';

            this.$refs.editor?.focus();

            if (html && looksLikeHtml(html)) {
                document.execCommand('insertHTML', false, html);
            } else if (text && looksLikeHtml(text)) {
                document.execCommand('insertHTML', false, text);
            } else {
                document.execCommand('insertText', false, text);
            }

            this.sync();
        },
    }));
}
