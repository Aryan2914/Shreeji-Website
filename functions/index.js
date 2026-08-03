const { onRequest } = require("firebase-functions/v2/https");
const admin = require("firebase-admin");

if (!admin.apps.length) {
  admin.initializeApp();
}

const SYSTEM_INSTRUCTION = `
You are Shreeji AI, the official virtual assistant for Shreeji Infotech (Ahmedabad's premier IT & laptop store for 15+ years).
Address: Sakar Complex, 5, Jawahar Chowk, Maninagar, Ahmedabad, Gujarat 380008.
Phone: +91 93777 04344
Email: sales@shreejiinfo.in
Hours: Monday to Saturday, 10:00 AM – 8:00 PM (Closed Sundays).

STRICT OPERATIONAL GUIDELINES:
1. TOPIC BOUNDARY: Answer ONLY queries related to Shreeji Infotech products (Laptops: HP, Dell, Lenovo, ASUS, Acer, Apple; RAM/SSD upgrades, Printers), repair services (screen/keyboard replacement, motherboard fix, speed boost), CCTV installations, Enterprise AMC, and store contact info. If asked about unrelated topics (e.g. coding, cooking, sports), politely say: "I can only assist with Shreeji Infotech products, IT repairs, CCTV setups, and business services. How can I help with your hardware needs today?"
2. FOCUS ON LATEST QUERY: Answer strictly the latest user message. Keep replies short (max 2-3 sentences), professional, direct, and business-focused.
3. INTENT DETECTED RESPONSE:
   - SALES: Recommend top brands (HP, Dell, Lenovo, Mac) or RAM/SSD storage upgrades.
   - REPAIR: Mention 3-month repair warranty, free diagnosis at Maninagar showroom.
   - CCTV: Highlight site survey & custom HD/IP camera installation.
   - ENTERPRISE: Mention GST invoices, corporate AMC & bulk pricing.
   - CONTACT: Share phone +91 93777 04344 and Maninagar location.
4. LEAD CAPTURE: Whenever a user wants a quote, callback, or purchase, ask for their Name and Phone Number so a sales executive can contact them immediately.
`;

exports.chat = onRequest({ cors: true, maxInstances: 10 }, async (req, res) => {
  if (req.method !== "POST") {
    return res.status(405).json({ error: "Method not allowed" });
  }

  try {
    const { message, history = [], intent = "GENERAL" } = req.body || {};
    if (!message || typeof message !== "string") {
      return res.status(400).json({ error: "Message is required" });
    }

    // Access API key securely from environment variable or fallback configuration
    const apiKey = process.env.GEMINI_API_KEY || "AIzaSyCwYhjnjRkQWEM187WaZKgdDaBxSI9s38o";
    const geminiUrl = `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=${apiKey}`;

    // Limit history payload to last 2-4 entries to stay focused on latest query
    const limitedHistory = (Array.isArray(history) ? history : []).slice(-4);

    const requestBody = {
      contents: [
        ...limitedHistory,
        { role: "user", parts: [{ text: `[Intent: ${intent}] Latest User Query: ${message}` }] }
      ],
      systemInstruction: {
        parts: [{ text: SYSTEM_INSTRUCTION }]
      },
      generationConfig: {
        temperature: 0.2,
        topP: 0.8,
        maxOutputTokens: 250
      }
    };

    const response = await fetch(geminiUrl, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(requestBody)
    });

    if (!response.ok) {
      throw new Error(`Gemini API error ${response.status}`);
    }

    const data = await response.json();
    const replyText = data.candidates?.[0]?.content?.parts?.[0]?.text || "Thank you for reaching out to Shreeji Infotech! Call us at +91 93777 04344 or visit our Maninagar showroom for instant assistance.";

    return res.status(200).json({ reply: replyText });
  } catch (err) {
    console.error("Cloud Function Chat Error:", err);
    return res.status(500).json({ error: "Service unavailable", details: err.message });
  }
});
