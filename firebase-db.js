import { initializeApp } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-app.js";
import { getFirestore, collection, addDoc, serverTimestamp, query, where, getDocs, orderBy, limit } from "https://www.gstatic.com/firebasejs/10.8.0/firebase-firestore.js";

// Your web app's Firebase configuration from Google
const firebaseConfig = {
  apiKey: "AIzaSyCwYhjnjRkQWEM187WaZKgdDaBxSI9s38o",
  authDomain: "si-web-c452c.firebaseapp.com",
  projectId: "si-web-c452c",
  storageBucket: "si-web-c452c.firebasestorage.app",
  messagingSenderId: "258518005782",
  appId: "1:258518005782:web:be6ece2b0950730fba1d37",
  measurementId: "G-M3H3X5HZ4B"
};

// Initialize Firebase
const app = initializeApp(firebaseConfig);
const db = getFirestore(app);

// Helper function to save a lead to the cloud safely 
async function saveLead(data) {
    try {
        await addDoc(collection(db, "leads"), {
            ...data,
            timestamp: serverTimestamp(),
            status: "New"
        });
        console.log("Secure lead backup completed successfully to CRM.");
        return true;
    } catch (e) {
        console.error("Error backing up lead: ", e);
        return false;
    }
}
window.saveLead = saveLead;


// ── Intercept Retail Home Form ── //
const retailForm = document.getElementById('enquiryForm');
if (retailForm) {
    retailForm.addEventListener('submit', () => {
        const name    = document.getElementById('formName').value.trim();
        const phone   = document.getElementById('formPhone').value.trim();
        const product = document.getElementById('formProduct').value;
        const message = document.getElementById('formMessage').value.trim();

        if (name && phone && product) {
            saveLead({
                type: "Retail",
                name: name,
                company: "N/A",
                phone: phone,
                email: "N/A",
                requirement: product,
                message: message || "No custom message attached.",
                source: "Home Page Retail Form"
            });
        }
    });
}

// ── Intercept Enterprise Corporate Form ── //
const b2bForm = document.getElementById('b2bEnquiryForm');
if (b2bForm) {
    b2bForm.addEventListener('submit', () => {
        const name    = document.getElementById('b2bName').value.trim();
        const company = document.getElementById('b2bCompany').value.trim();
        const phone   = document.getElementById('b2bPhone').value.trim();
        const email   = document.getElementById('b2bEmail').value.trim() || 'N/A';
        const interest= document.getElementById('b2bInterest').value;
        const message = document.getElementById('b2bMessage').value.trim();

        if (name && company && phone && interest) {
            saveLead({
                type: "B2B/Corporate",
                name: name,
                company: company,
                phone: phone,
                email: email,
                requirement: interest,
                message: message || "No explicit project details provided.",
                source: "Enterprise B2B Division"
            });
        }
    });
}
