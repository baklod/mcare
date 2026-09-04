export const attachLandingChat = () => {
    const root = document.querySelector('[data-landing-chat]');
    if (!root) return;

    const chatUrl = root.dataset.chatUrl;
    const toggle = root.querySelector('[data-landing-chat-toggle]');
    const panel = root.querySelector('[data-landing-chat-panel]');
    const thread = root.querySelector('[data-landing-chat-thread]');
    const form = root.querySelector('[data-landing-chat-form]');
    const input = root.querySelector('[data-landing-chat-input]');
    const sendButton = root.querySelector('[data-landing-chat-send]');
    const closeButton = root.querySelector('[data-landing-chat-close]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    if (!chatUrl || !toggle || !panel || !thread || !form || !input || !sendButton || !closeButton || !csrfToken) {
        return;
    }

    const history = [];
    let pending = false;
    let introduced = false;

    const setOpen = (open) => {
        panel.hidden = !open;
        root.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.hidden = open;
        toggle.title = 'Open MCARE assistant';
        if (open) {
            if (!introduced) {
                appendMessage('assistant', 'Hello. I can help with questions about this MCARE Hub website only. You can ask in English, Filipino, or another language.');
                introduced = true;
            }
            window.requestAnimationFrame(() => input.focus());
        }
    };

    const appendMessage = (role, text) => {
        const bubble = document.createElement('div');
        bubble.className = `landing-chat-bubble is-${role}`;
        bubble.textContent = text;
        thread.appendChild(bubble);
        thread.scrollTop = thread.scrollHeight;
        return bubble;
    };

    const setBusy = (busy) => {
        pending = busy;
        sendButton.disabled = busy;
        input.disabled = busy;
        form.classList.toggle('is-busy', busy);
    };

    const sendMessage = async (rawMessage) => {
        const message = rawMessage.trim();
        if (!message || pending) return;

        appendMessage('user', message);
        input.value = '';
        setBusy(true);
        const waiting = appendMessage('assistant', 'Thinking…');
        waiting.classList.add('is-pending');

        try {
            const response = await fetch(chatUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    message,
                    history,
                }),
            });

            const payload = await response.json().catch(() => ({}));
            const reply = typeof payload.reply === 'string' && payload.reply.trim() !== ''
                ? payload.reply.trim()
                : (typeof payload.message === 'string' && payload.message.trim() !== ''
                    ? payload.message.trim()
                    : 'The MCARE assistant could not reply. Please try again.');

            waiting.textContent = reply;
            waiting.classList.remove('is-pending');

            if (response.ok) {
                history.push({ role: 'user', content: message });
                history.push({ role: 'assistant', content: reply });
                if (history.length > 8) {
                    history.splice(0, history.length - 8);
                }
            }
        } catch (error) {
            waiting.textContent = 'The MCARE assistant could not be reached. Please try again.';
            waiting.classList.remove('is-pending');
        } finally {
            setBusy(false);
            input.focus();
            thread.scrollTop = thread.scrollHeight;
        }
    };

    toggle.addEventListener('click', () => {
        setOpen(true);
    });

    closeButton.addEventListener('click', () => {
        setOpen(false);
        toggle.focus();
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        sendMessage(input.value);
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            sendMessage(input.value);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !panel.hidden) {
            setOpen(false);
            toggle.focus();
        }
    });
};
