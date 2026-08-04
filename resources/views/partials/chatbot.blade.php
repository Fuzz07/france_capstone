<!-- Interactive Floating Chatbot Widget -->
<div class="chatbot-widget-container" id="chatbotWidget">
    <!-- Floating Action Trigger Button -->
    <button class="chatbot-fab" id="chatbotFab" aria-label="Open Chatbot Assistant" title="Need help? Chat with our AI Assistant!">
        <div class="chatbot-fab-icon-wrap">
            <svg class="chatbot-fab-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
            <span class="chatbot-fab-badge">AI</span>
        </div>
        <span class="chatbot-fab-label">Chat Assistant</span>
    </button>

    <!-- Chatbot Window Popover -->
    <div class="chatbot-window" id="chatbotWindow" aria-hidden="true">
        <!-- Header -->
        <div class="chatbot-header">
            <div class="chatbot-header-info">
                <div class="chatbot-avatar">🤖</div>
                <div>
                    <h3 class="chatbot-title">Mera's Support Assistant</h3>
                    <div class="chatbot-status">
                        <span class="chatbot-status-dot"></span> Online &bull; Instant Answers
                    </div>
                </div>
            </div>
            <button type="button" class="chatbot-close-btn" id="chatbotCloseBtn" aria-label="Close Chatbot">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <!-- Chat History -->
        <div class="chatbot-messages" id="chatbotMessages">
            <div class="chatbot-msg chatbot-msg-bot">
                <div class="chatbot-msg-avatar">🤖</div>
                <div class="chatbot-msg-content">
                    <div class="chatbot-bubble">
                        Hello! 👋 Welcome to <strong>Mera's General Merchandise</strong>! How can I assist you today?
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Suggestion Chips -->
        <div class="chatbot-chips-wrapper" id="chatbotChips">
            <span class="chatbot-chips-title">Suggested Questions:</span>
            <div class="chatbot-chips-list">
                <button type="button" class="chatbot-chip" onclick="sendChatbotChip('What are your store hours?')">Store Hours 🕒</button>
                <button type="button" class="chatbot-chip" onclick="sendChatbotChip('Where is your store located?')">Location 📍</button>
                <button type="button" class="chatbot-chip" onclick="sendChatbotChip('What products do you have in stock?')">Products 🛍️</button>
                <button type="button" class="chatbot-chip" onclick="sendChatbotChip('What payment methods do you accept?')">Payment 💳</button>
            </div>
        </div>

        <!-- Input Form -->
        <form id="chatbotForm" class="chatbot-input-form" onsubmit="handleChatbotSubmit(event)">
            <input type="text" id="chatbotInput" class="chatbot-input" placeholder="Type your question..." autocomplete="off" required>
            <button type="submit" class="chatbot-send-btn" title="Send Question">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"></line>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                </svg>
            </button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const fab = document.getElementById('chatbotFab');
        const win = document.getElementById('chatbotWindow');
        const closeBtn = document.getElementById('chatbotCloseBtn');
        const input = document.getElementById('chatbotInput');

        if (!fab || !win) return;

        function toggleChatbot() {
            const isOpen = win.classList.contains('active');
            if (isOpen) {
                win.classList.remove('active');
                win.setAttribute('aria-hidden', 'true');
            } else {
                win.classList.add('active');
                win.setAttribute('aria-hidden', 'false');
                setTimeout(() => input && input.focus(), 150);
            }
        }

        fab.addEventListener('click', toggleChatbot);
        if (closeBtn) closeBtn.addEventListener('click', toggleChatbot);
    });

    function escapeHtml(text) {
        return (text || '')
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function scrollChatbotToBottom() {
        const box = document.getElementById('chatbotMessages');
        if (box) box.scrollTop = box.scrollHeight;
    }

    function sendChatbotChip(queryText) {
        const input = document.getElementById('chatbotInput');
        if (input) {
            input.value = queryText;
            document.getElementById('chatbotForm').dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
        }
    }

    function appendUserMessage(text) {
        const box = document.getElementById('chatbotMessages');
        if (!box) return;

        const msgDiv = document.createElement('div');
        msgDiv.className = 'chatbot-msg chatbot-msg-user';
        msgDiv.innerHTML = `
            <div class="chatbot-msg-content">
                <div class="chatbot-bubble chatbot-bubble-user">${escapeHtml(text)}</div>
            </div>
            <div class="chatbot-msg-avatar">👤</div>
        `;
        box.appendChild(msgDiv);
        scrollChatbotToBottom();
    }

    function showTypingIndicator() {
        const box = document.getElementById('chatbotMessages');
        if (!box) return null;

        const typingDiv = document.createElement('div');
        typingDiv.className = 'chatbot-msg chatbot-msg-bot chatbot-typing-row';
        typingDiv.id = 'chatbotTypingIndicator';
        typingDiv.innerHTML = `
            <div class="chatbot-msg-avatar">🤖</div>
            <div class="chatbot-msg-content">
                <div class="chatbot-bubble chatbot-typing">
                    <span class="dot"></span><span class="dot"></span><span class="dot"></span>
                </div>
            </div>
        `;
        box.appendChild(typingDiv);
        scrollChatbotToBottom();
        return typingDiv;
    }

    function removeTypingIndicator() {
        const indicator = document.getElementById('chatbotTypingIndicator');
        if (indicator) indicator.remove();
    }

    function appendBotMessage(replyText, products = [], suggestions = []) {
        removeTypingIndicator();

        const box = document.getElementById('chatbotMessages');
        if (!box) return;

        let formattedReply = escapeHtml(replyText).replace(/\n/g, '<br>');

        let productHtml = '';
        if (products && products.length > 0) {
            productHtml = '<div class="chatbot-products-grid">';
            products.forEach(p => {
                productHtml += `
                    <div class="chatbot-prod-card">
                        <div class="chatbot-prod-title">${escapeHtml(p.name)}</div>
                        <div class="chatbot-prod-meta">
                            <span class="chatbot-prod-price">${escapeHtml(p.price)}</span>
                            <span class="chatbot-prod-status">${escapeHtml(p.status)}</span>
                        </div>
                    </div>
                `;
            });
            productHtml += '</div>';
        }

        const msgDiv = document.createElement('div');
        msgDiv.className = 'chatbot-msg chatbot-msg-bot';
        msgDiv.innerHTML = `
            <div class="chatbot-msg-avatar">🤖</div>
            <div class="chatbot-msg-content">
                <div class="chatbot-bubble">${formattedReply}${productHtml}</div>
            </div>
        `;
        box.appendChild(msgDiv);

        // Update chips if suggestions returned
        if (suggestions && suggestions.length > 0) {
            const chipsList = document.querySelector('#chatbotChips .chatbot-chips-list');
            if (chipsList) {
                chipsList.innerHTML = suggestions.map(s => `
                    <button type="button" class="chatbot-chip" onclick="sendChatbotChip('${escapeHtml(s)}')">${escapeHtml(s)}</button>
                `).join('');
            }
        }

        scrollChatbotToBottom();
    }

    function handleChatbotSubmit(e) {
        e.preventDefault();
        const input = document.getElementById('chatbotInput');
        if (!input) return;

        const text = input.value.trim();
        if (!text) return;

        appendUserMessage(text);
        input.value = '';
        showTypingIndicator();

        fetch("{{ route('chat.bot-response') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ message: text })
        })
        .then(res => res.json())
        .then(data => {
            if (data && data.reply) {
                appendBotMessage(data.reply, data.products, data.suggestions);
            } else {
                appendBotMessage("I'm sorry, I couldn't process your request right now. Please try again or submit an inquiry.");
            }
        })
        .catch(err => {
            console.error('Chatbot error:', err);
            appendBotMessage("Connecting... Please make sure you are connected to the network or check back shortly.");
        });
    }
</script>
