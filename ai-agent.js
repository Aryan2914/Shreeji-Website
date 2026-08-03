// ── Shreeji Infotech AI Assistant Script (Optimized & Secure) ──

document.addEventListener('DOMContentLoaded', () => {
    // UI elements
    const aiFloat = document.getElementById('aiFloat');
    const aiChatWindow = document.getElementById('aiChatWindow');
    const aiCloseBtn = document.getElementById('aiCloseBtn');
    const aiChatMessages = document.getElementById('aiChatMessages');
    const aiChatInput = document.getElementById('aiChatInput');
    const aiSendBtn = document.getElementById('aiSendBtn');
    const aiSuggestions = document.getElementById('aiSuggestions');

    if (!aiFloat || !aiChatWindow) return;

    // Toggle Chat window
    aiFloat.addEventListener('click', () => {
        aiChatWindow.classList.toggle('open');
        if (aiChatWindow.classList.contains('open') && aiChatInput) {
            aiChatInput.focus();
        }
    });

    if (aiCloseBtn) {
        aiCloseBtn.addEventListener('click', () => {
            aiChatWindow.classList.remove('open');
        });
    }

    // Handle suggestion chips
    document.querySelectorAll('.ai-suggest-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const queryText = btn.getAttribute('data-query');
            if (queryText) {
                sendMessage(queryText);
                if (aiSuggestions) aiSuggestions.style.display = 'none';
            }
        });
    });

    // Handle Input events
    if (aiSendBtn) {
        aiSendBtn.addEventListener('click', handleUserInput);
    }

    if (aiChatInput) {
        aiChatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') handleUserInput();
        });
    }

    function handleUserInput() {
        if (!aiChatInput) return;
        const text = aiChatInput.value.trim();
        if (text) {
            sendMessage(text);
            aiChatInput.value = '';
        }
    }

    // Chat History State (Limited to max 4 items for conciseness & security)
    let chatHistory = [
        {
            role: "user",
            parts: [{ text: "Hello" }]
        },
        {
            role: "model",
            parts: [{ text: "Hello! I am Shreeji AI, official assistant for Shreeji Infotech. How can I assist you with Laptops, IT Repairs, CCTV setups, or Business Procurement today?" }]
        }
    ];

    // Intent Detection Function
    function detectIntent(text) {
        const q = text.toLowerCase();
        if (/\b(laptop|computer|macbook|dell|hp|lenovo|asus|acer|ram|ssd|printer|price|buy|cost|quote)\b/.test(q)) {
            return "SALES";
        }
        if (/\b(repair|service|fix|screen|keyboard|battery|slow|broken|water|hinge|motherboard|upgrade)\b/.test(q)) {
            return "REPAIR";
        }
        if (/\b(cctv|camera|security|surveillance|dvr|nvr|hikvision|cp plus)\b/.test(q)) {
            return "CCTV";
        }
        if (/\b(enterprise|business|corporate|bulk|amc|office|gst|invoice|procurement)\b/.test(q)) {
            return "ENTERPRISE";
        }
        if (/\b(address|location|where|map|contact|phone|number|call|hours|timing|email|maninagar)\b/.test(q)) {
            return "CONTACT";
        }
        return "GENERAL";
    }

    // Send Message
    function sendMessage(userText) {
        appendMessage(userText, 'user');
        
        const typingEl = appendTypingIndicator();
        aiChatMessages.scrollTop = aiChatMessages.scrollHeight;

        // Lead detection
        detectAndSaveLead(userText);

        const intent = detectIntent(userText);
        fetchAIResponse(userText, intent, typingEl);
    }

    function appendMessage(text, sender) {
        const msgDiv = document.createElement('div');
        msgDiv.className = `ai-message ai-${sender}-message`;
        msgDiv.innerHTML = `<p>${escapeHTML(text)}</p>`;
        aiChatMessages.appendChild(msgDiv);
        aiChatMessages.scrollTop = aiChatMessages.scrollHeight;
    }

    function appendTypingIndicator() {
        const typingDiv = document.createElement('div');
        typingDiv.className = 'ai-message ai-bot-message';
        typingDiv.id = 'aiTypingIndicator';
        typingDiv.innerHTML = `
            <div class="ai-typing">
                <span></span>
                <span></span>
                <span></span>
            </div>
        `;
        aiChatMessages.appendChild(typingDiv);
        return typingDiv;
    }

    function escapeHTML(str) {
        return str.replace(/[&<>'"]/g, 
            tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag] || tag)
        );
    }

    // Lead detection logic
    function detectAndSaveLead(text) {
        const phoneRegex = /(\+91[\-\s]?)?[6-9]\d{9}/g;
        const matches = text.match(phoneRegex);
        
        if (matches) {
            const phone = matches[0];
            const name = text.replace(phoneRegex, '').replace(/my name is|i am|name/gi, '').trim().substring(0, 30) || "AI Chat Visitor";
            
            if (window.saveLead) {
                window.saveLead({
                    type: "AI Chatbot",
                    name: name,
                    phone: phone,
                    requirement: "Requested callback via AI Chatbot",
                    source: "AI Assistant Widget"
                });
            }
        }
    }

    // Secure Backend API Call & Intent-driven Fallback
    async function fetchAIResponse(userText, intent, typingEl) {
        let botText = "";

        try {
            // Secure backend endpoint rewrite (/api/chat)
            const response = await fetch('/api/chat', {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    message: userText,
                    intent: intent,
                    history: chatHistory.slice(-4) // Keep history limited to last 4 items
                })
            });

            if (!response.ok) throw new Error("Backend API unavailable");

            const data = await response.json();
            botText = data.reply;

        } catch (error) {
            // Professional Intent-Based Fallback Responder (Zero key exposure)
            botText = getFallbackResponse(userText, intent);
        }

        if (typingEl) typingEl.remove();
        appendMessage(botText, 'bot');

        // Update limited history state
        chatHistory.push({ role: "user", parts: [{ text: userText }] });
        chatHistory.push({ role: "model", parts: [{ text: botText }] });
        if (chatHistory.length > 6) chatHistory = chatHistory.slice(-4);
    }

    // High quality Intent Fallback Responder
    function getFallbackResponse(userText, intent) {
        switch (intent) {
            case "SALES":
                return "We stock genuine laptops (HP, Dell, Lenovo, ASUS, Acer, Apple) and RAM/SSD storage upgrades with GST invoices. Please share your Name & Phone Number, or call +91 93777 04344 for instant pricing!";
            
            case "REPAIR":
                return "Shreeji Infotech provides expert laptop repairs (screen replacement, motherboard fix, speed upgrades & battery changes) with 3-month service warranty. Visit our Maninagar showroom or call +91 93777 04344!";
            
            case "CCTV":
                return "We design and install professional HD/IP CCTV security camera setups for home & corporate spaces in Ahmedabad. Share your Name & Phone Number to schedule a site survey & free quotation.";
            
            case "ENTERPRISE":
                return "We offer corporate IT procurement, GST invoicing, and Annual Maintenance Contracts (AMC) for businesses. Contact our enterprise desk at +91 93777 04344 or email sales@shreejiinfo.in.";
            
            case "CONTACT":
                return "📍 Location: Sakar Complex, 5, Jawahar Chowk, Maninagar, Ahmedabad - 380008.\n📞 Phone: +91 93777 04344\n⏰ Hours: Mon-Sat 10:00 AM – 8:00 PM.";
            
            default:
                return "Thank you for contacting Shreeji Infotech! We specialize in Laptops, Storage Upgrades, IT Repairs, and CCTV Security Systems. Please share your requirement or call us at +91 93777 04344.";
        }
    }
});
