// ── Shreeji Infotech AI Assistant script ──

document.addEventListener('DOMContentLoaded', () => {
    // UI elements
    const aiFloat = document.getElementById('aiFloat');
    const aiChatWindow = document.getElementById('aiChatWindow');
    const aiCloseBtn = document.getElementById('aiCloseBtn');
    const aiChatMessages = document.getElementById('aiChatMessages');
    const aiChatInput = document.getElementById('aiChatInput');
    const aiSendBtn = document.getElementById('aiSendBtn');
    const aiSuggestions = document.getElementById('aiSuggestions');

    // Toggle Chat window
    aiFloat.addEventListener('click', () => {
        aiChatWindow.classList.toggle('open');
        if (aiChatWindow.classList.contains('open')) {
            aiChatInput.focus();
        }
    });

    aiCloseBtn.addEventListener('click', () => {
        aiChatWindow.classList.remove('open');
    });

    // Handle suggestion chips
    document.querySelectorAll('.ai-suggest-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const queryText = btn.getAttribute('data-query');
            if (queryText) {
                sendMessage(queryText);
                aiSuggestions.style.display = 'none'; // hide chips after first action to clear space
            }
        });
    });

    // Handle Input events
    aiSendBtn.addEventListener('click', () => {
        handleUserInput();
    });

    aiChatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            handleUserInput();
        }
    });

    function handleUserInput() {
        const text = aiChatInput.value.trim();
        if (text) {
            sendMessage(text);
            aiChatInput.value = '';
        }
    }

    // Chat states
    let chatHistory = [
        {
            role: "user",
            parts: [{ text: "Introduce yourself as Shreeji AI, the official assistant for Shreeji Infotech." }]
        },
        {
            role: "model",
            parts: [{ text: "Hello! I am your Shreeji Infotech Assistant. How can I help you today? I can recommend laptops, estimate repair costs, or help you configure CCTV installations." }]
        }
    ];

    // System prompt setting store information
    const SYSTEM_INSTRUCTION = `
You are Shreeji AI, the virtual store manager of Shreeji Infotech.
Address: Sakar Complex, 5, Jawahar Chowk, Maninagar, Ahmedabad, Gujarat 380008.
Phone: +91 93777 04344
Email: sales@shreejiinfo.in
Open: Monday to Saturday, 10:00 AM to 8:00 PM. Closed on Sundays.
Offerings: Laptops (HP, Dell, Lenovo, ASUS, Acer, Apple Macbook), Printers, CCTV installations, and Expert hardware repairs.

Follow these strict rules:
1. Keep replies professional, short, and friendly.
2. Recommend major laptop brands based on user needs.
3. For repairs (cracked screens, slow systems, battery replacements) and CCTV setups, provide helpful general advice.
4. LEAD CAPTURING: If the customer wants to buy, get a repair quotation, book a visit, or request CCTV installation, kindly ask for their name and phone number so a sales executive can contact them immediately.
5. If the user shares their name and phone number, say: "Thank you [Name]! I have recorded your request. Our executive will call you shortly on [Phone]."
`;

    // Send a message
    function sendMessage(text) {
        // Render User Message
        appendMessage(text, 'user');
        
        // Render Typing Indicator
        const typingEl = appendTypingIndicator();
        aiChatMessages.scrollTop = aiChatMessages.scrollHeight;

        // Check if message contains a phone number and name to log it as a lead
        detectAndSaveLead(text);

        // Fetch from Gemini API
        fetchAIResponse(text, typingEl);
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
    let detectedName = '';
    let detectedPhone = '';

    function detectAndSaveLead(text) {
        // Match 10 digit Indian mobile numbers
        const phoneRegex = /(\+91[\-\s]?)?[6-9]\d{9}/g;
        const matches = text.match(phoneRegex);
        
        if (matches) {
            detectedPhone = matches[0];
            // Infer name: basic extraction or generic "AI Chat Customer"
            detectedName = text.replace(phoneRegex, '').replace(/my name is|i am|name/gi, '').trim().substring(0, 30) || "AI Chat Visitor";
            
            // Save to Firestore using exposed saveLead function
            if (window.saveLead) {
                window.saveLead({
                    type: "AI Chatbot",
                    name: detectedName,
                    phone: detectedPhone,
                    requirement: "Requested callback or recommendation via AI Chatbot",
                    source: "AI Assistant Widget"
                });
            }
        }
    }

    async function fetchAIResponse(userText, typingEl) {
        const apiKey = "AIzaSyCwYhjnjRkQWEM187WaZKgdDaBxSI9s38o"; // uses existing project API Key
        const url = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=${apiKey}`;

        // Build history with system instructions prepended or configured
        const requestPayload = {
            contents: [
                ...chatHistory,
                { role: "user", parts: [{ text: userText }] }
            ],
            systemInstruction: {
                parts: [{ text: SYSTEM_INSTRUCTION }]
            }
        };

        try {
            const response = await fetch(url, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(requestPayload)
            });

            if (!response.ok) throw new Error("API call failed");

            const data = await response.json();
            const botText = data.candidates[0].content.parts[0].text;

            // Remove typing indicator
            if (typingEl) typingEl.remove();

            // Append bot response
            appendMessage(botText, 'bot');

            // Save to chat history state
            chatHistory.push({ role: "user", parts: [{ text: userText }] });
            chatHistory.push({ role: "model", parts: [{ text: botText }] });

        } catch (error) {
            console.error("Gemini API Error:", error);
            if (typingEl) typingEl.remove();
            
            // Smart local keyword fallback responder
            const query = userText.toLowerCase();
            let fallbackText = "";

            if (query.includes("laptop") || query.includes("computer") || query.includes("dell") || query.includes("hp") || query.includes("lenovo") || query.includes("macbook") || query.includes("asus")) {
                fallbackText = "We offer premium laptops from HP, Dell, Lenovo, ASUS, Acer, and Apple. Please share your Name, Phone Number, and your budget so our sales manager can help you pick the best model!";
            } else if (query.includes("repair") || query.includes("service") || query.includes("broken") || query.includes("slow") || query.includes("screen") || query.includes("keyboard")) {
                fallbackText = "Our technicians repair laptops (screen replacements, keyboard replacement, chip-level service, and OS upgrades). Please share your Name, Phone Number, and laptop model, and we'll send a price estimate!";
            } else if (query.includes("cctv") || query.includes("camera") || query.includes("security")) {
                fallbackText = "We set up professional CCTV security systems for homes and corporate spaces in Ahmedabad. Share your Name and Phone Number, and we'll schedule a site survey & custom quote.";
            } else if (query.includes("location") || query.includes("where") || query.includes("address") || query.includes("map") || query.includes("address")) {
                fallbackText = "We are located at: Sakar Complex, 5, Jawahar Chowk, Maninagar, Ahmedabad, Gujarat 380008. Open Mon-Sat 10:00 AM to 8:00 PM.";
            } else if (query.includes("contact") || query.includes("phone") || query.includes("number") || query.includes("call")) {
                fallbackText = "You can call us directly at +91 93777 04344 or email sales@shreejiinfo.in. Alternatively, leave your phone number here and we'll call you!";
            } else {
                fallbackText = "Hello! We at Shreeji Infotech specialize in premium laptops, repairs, and CCTV systems. How can I help you today? Please share your requirements or leave your phone number so we can call you.";
            }

            appendMessage(fallbackText, 'bot');
        }
    }
});
