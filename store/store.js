/* ============================================================
   SHREEJI INFOTECH — Store Shared JavaScript
   Handles: Firebase, Auth, Cart, Wishlist, Compare, Toast
   ============================================================ */

// ── Firebase Configuration ──────────────────────────────────
// NOTE: Replace these values with your actual Firebase project config
// from: Firebase Console → Project Settings → Your apps → Web app
import { initializeApp } from "https://www.gstatic.com/firebasejs/11.0.0/firebase-app.js";
import { getAuth, signInWithPopup, GoogleAuthProvider, RecaptchaVerifier, signInWithPhoneNumber, onAuthStateChanged, signOut } from "https://www.gstatic.com/firebasejs/11.0.0/firebase-auth.js";
import { getFirestore, doc, getDoc, setDoc, updateDoc, collection, addDoc, query, where, getDocs, deleteDoc, arrayUnion, arrayRemove, increment, serverTimestamp, orderBy, limit, onSnapshot } from "https://www.gstatic.com/firebasejs/11.0.0/firebase-firestore.js";
import { getStorage, ref, getDownloadURL } from "https://www.gstatic.com/firebasejs/11.0.0/firebase-storage.js";

const firebaseConfig = {
    apiKey: "YOUR_API_KEY",
    authDomain: "si-web-c452c.firebaseapp.com",
    projectId: "si-web-c452c",
    storageBucket: "si-web-c452c.appspot.com",
    messagingSenderId: "YOUR_SENDER_ID",
    appId: "YOUR_APP_ID",
    measurementId: "G-M3H3X5HZ4B"
};

const app = initializeApp(firebaseConfig);
export const auth = getAuth(app);
export const db = getFirestore(app);
export const storage = getStorage(app);

// ── GST Config ──────────────────────────────────────────────
export const GST_RATES = {
    laptops: 18,
    printers: 18,
    cctv: 18,
    networking: 18,
    accessories: 18,
    default: 18
};
export const COMPANY_GSTIN = "24XXXXXXXXXXXXX"; // TODO: Replace with actual GSTIN
export const COMPANY_NAME = "Shreeji Infotech";
export const COMPANY_ADDRESS = "Sakar Complex, 5, Jawahar Chowk, Maninagar, Ahmedabad - 380008, Gujarat";

// ── Razorpay Config ─────────────────────────────────────────
// TODO: Replace with your actual Razorpay Key ID (starts with rzp_live_ or rzp_test_)
export const RAZORPAY_KEY_ID = "rzp_test_XXXXXXXXXXXXXXXX";

// ── Current User State ──────────────────────────────────────
let currentUser = null;

onAuthStateChanged(auth, (user) => {
    currentUser = user;
    updateAuthUI(user);
    if (user) {
        syncLocalCartToFirestore(user.uid);
        loadCartCount(user.uid);
        loadWishlistCount(user.uid);
    } else {
// Initialize cart count immediately for local storage
loadLocalCartCount();

export function getCurrentUser() { return currentUser; }

function updateAuthUI(user) {
    const authBtn = document.getElementById('authBtn');
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userNameEl = document.getElementById('userName');
    if (!authBtn && !userMenuBtn) return;
    if (user) {
        if (authBtn) authBtn.style.display = 'none';
        if (userMenuBtn) userMenuBtn.style.display = 'flex';
        if (userNameEl) userNameEl.textContent = user.displayName?.split(' ')[0] || 'My Account';
    } else {
        if (authBtn) authBtn.style.display = 'flex';
        if (userMenuBtn) userMenuBtn.style.display = 'none';
    }
}

// ── Auth Modal ──────────────────────────────────────────────
export function openAuthModal(redirectUrl = null) {
    const modal = document.getElementById('authModal');
    if (modal) {
        modal.classList.add('open');
        if (redirectUrl) modal.dataset.redirect = redirectUrl;
    }
}
export function closeAuthModal() {
    const modal = document.getElementById('authModal');
    if (modal) modal.classList.remove('open');
}

export async function signInWithGoogle() {
    const provider = new GoogleAuthProvider();
    try {
        const result = await signInWithPopup(auth, provider);
        await ensureUserDoc(result.user);
        closeAuthModal();
        const modal = document.getElementById('authModal');
        if (modal?.dataset.redirect) window.location.href = modal.dataset.redirect;
        showToast('Welcome, ' + result.user.displayName?.split(' ')[0] + '!', 'success');
    } catch (err) {
        showToast('Sign in failed. Please try again.', 'error');
    }
}

async function ensureUserDoc(user) {
    const userRef = doc(db, 'users', user.uid);
    const snap = await getDoc(userRef);
    if (!snap.exists()) {
        await setDoc(userRef, {
            uid: user.uid,
            displayName: user.displayName || '',
            email: user.email || '',
            phone: user.phoneNumber || '',
            photoURL: user.photoURL || '',
            createdAt: serverTimestamp(),
            tier: 'retail', // retail | business | enterprise
            loyaltyPoints: 0,
            addresses: [],
            gstin: ''
        });
    }
}

export async function signOutUser() {
    await signOut(auth);
    showToast('Signed out successfully', 'info');
}

// ── CART MANAGEMENT ─────────────────────────────────────────
const LOCAL_CART_KEY = 'si_cart';

export function getLocalCart() {
    try { return JSON.parse(localStorage.getItem(LOCAL_CART_KEY)) || []; }
    catch { return []; }
}

export function saveLocalCart(cart) {
    localStorage.setItem(LOCAL_CART_KEY, JSON.stringify(cart));
    updateCartBadge(cart.reduce((s, i) => s + i.qty, 0));
}

export async function addToCart(product, qty = 1, variant = null) {
    const user = getCurrentUser();
    const item = {
        productId: product.id,
        name: product.name,
        brand: product.brand,
        price: product.price,
        originalPrice: product.originalPrice || product.price,
        image: product.images?.[0] || product.image || '',
        qty,
        variant,
        gstRate: product.gstRate || GST_RATES.default,
        category: product.category || 'default',
        sku: product.sku || product.id,
        addedAt: new Date().toISOString()
    };

    if (user) {
        const cartRef = doc(db, 'carts', user.uid);
        const snap = await getDoc(cartRef);
        let cartItems = snap.exists() ? (snap.data().items || []) : [];
        const existIdx = cartItems.findIndex(c => c.productId === item.productId && JSON.stringify(c.variant) === JSON.stringify(variant));
        if (existIdx >= 0) {
            cartItems[existIdx].qty += qty;
        } else {
            cartItems.push(item);
        }
        await setDoc(cartRef, { items: cartItems, updatedAt: serverTimestamp(), userId: user.uid }, { merge: true });
        loadCartCount(user.uid);
    } else {
        const cart = getLocalCart();
        const existIdx = cart.findIndex(c => c.productId === item.productId && JSON.stringify(c.variant) === JSON.stringify(variant));
        if (existIdx >= 0) {
            cart[existIdx].qty += qty;
        } else {
            cart.push(item);
        }
        saveLocalCart(cart);
    }
    showToast(product.name + ' added to cart', 'success');
}

export async function getCart() {
    const user = getCurrentUser();
    if (user) {
        const snap = await getDoc(doc(db, 'carts', user.uid));
        return snap.exists() ? (snap.data().items || []) : [];
    }
    return getLocalCart();
}

export async function updateCartQty(productId, variant, qty) {
    const user = getCurrentUser();
    if (user) {
        const cartRef = doc(db, 'carts', user.uid);
        const snap = await getDoc(cartRef);
        let items = snap.exists() ? (snap.data().items || []) : [];
        const idx = items.findIndex(c => c.productId === productId && JSON.stringify(c.variant) === JSON.stringify(variant));
        if (idx >= 0) {
            if (qty <= 0) items.splice(idx, 1);
            else items[idx].qty = qty;
            await setDoc(cartRef, { items, updatedAt: serverTimestamp() }, { merge: true });
            loadCartCount(user.uid);
        }
    } else {
        const cart = getLocalCart();
        const idx = cart.findIndex(c => c.productId === productId && JSON.stringify(c.variant) === JSON.stringify(variant));
        if (idx >= 0) {
            if (qty <= 0) cart.splice(idx, 1);
            else cart[idx].qty = qty;
            saveLocalCart(cart);
        }
    }
}

export async function removeFromCart(productId, variant) {
    await updateCartQty(productId, variant, 0);
}

async function syncLocalCartToFirestore(uid) {
    const local = getLocalCart();
    if (!local.length) return;
    const cartRef = doc(db, 'carts', uid);
    const snap = await getDoc(cartRef);
    let fbItems = snap.exists() ? (snap.data().items || []) : [];
    for (const localItem of local) {
        const idx = fbItems.findIndex(c => c.productId === localItem.productId);
        if (idx >= 0) fbItems[idx].qty += localItem.qty;
        else fbItems.push(localItem);
    }
    await setDoc(cartRef, { items: fbItems, updatedAt: serverTimestamp(), userId: uid }, { merge: true });
    localStorage.removeItem(LOCAL_CART_KEY);
}

async function loadCartCount(uid) {
    const snap = await getDoc(doc(db, 'carts', uid));
    const items = snap.exists() ? (snap.data().items || []) : [];
    updateCartBadge(items.reduce((s, i) => s + i.qty, 0));
}

function loadLocalCartCount() {
    const cart = getLocalCart();
    updateCartBadge(cart.reduce((s, i) => s + i.qty, 0));
}

function updateCartBadge(count) {
    document.querySelectorAll('.cart-badge').forEach(el => {
        el.textContent = count > 99 ? '99+' : count;
        el.style.display = count > 0 ? 'flex' : 'none';
    });
}

// ── WISHLIST MANAGEMENT ─────────────────────────────────────
export async function toggleWishlist(product) {
    const user = getCurrentUser();
    if (!user) { openAuthModal(window.location.href); return false; }

    const wishRef = doc(db, 'wishlists', user.uid);
    const snap = await getDoc(wishRef);
    const existing = snap.exists() ? (snap.data().items || []) : [];
    const isIn = existing.some(i => i.productId === product.id);

    if (isIn) {
        await updateDoc(wishRef, { items: arrayRemove(existing.find(i => i.productId === product.id)) });
        showToast('Removed from wishlist', 'info');
    } else {
        const item = { productId: product.id, name: product.name, price: product.price, image: product.images?.[0] || product.image || '', addedAt: new Date().toISOString() };
        if (snap.exists()) {
            await updateDoc(wishRef, { items: arrayUnion(item) });
        } else {
            await setDoc(wishRef, { items: [item], userId: user.uid });
        }
        showToast('Added to wishlist', 'success');
    }
    loadWishlistCount(user.uid);
    return !isIn;
}

export async function isInWishlist(productId) {
    const user = getCurrentUser();
    if (!user) return false;
    const snap = await getDoc(doc(db, 'wishlists', user.uid));
    return snap.exists() && (snap.data().items || []).some(i => i.productId === productId);
}

async function loadWishlistCount(uid) {
    const snap = await getDoc(doc(db, 'wishlists', uid));
    const count = snap.exists() ? (snap.data().items || []).length : 0;
    document.querySelectorAll('.wishlist-badge').forEach(el => {
        el.textContent = count;
        el.style.display = count > 0 ? 'flex' : 'none';
    });
}

// ── COMPARE MANAGEMENT ──────────────────────────────────────
const COMPARE_KEY = 'si_compare';
const MAX_COMPARE = 4;

export function getCompareList() {
    try { return JSON.parse(localStorage.getItem(COMPARE_KEY)) || []; }
    catch { return []; }
}

export function toggleCompare(product) {
    let list = getCompareList();
    const idx = list.findIndex(p => p.id === product.id);
    if (idx >= 0) {
        list.splice(idx, 1);
        showToast('Removed from compare', 'info');
    } else {
        if (list.length >= MAX_COMPARE) {
            showToast('You can compare up to 4 products', 'error');
            return false;
        }
        list.push({ id: product.id, name: product.name, price: product.price, image: product.images?.[0] || product.image || '' });
        showToast('Added to compare', 'success');
    }
    localStorage.setItem(COMPARE_KEY, JSON.stringify(list));
    updateCompareBar();
    return idx < 0;
}

export function updateCompareBar() {
    const list = getCompareList();
    const bar = document.getElementById('compareBar');
    if (!bar) return;
    if (list.length === 0) {
        bar.classList.remove('visible');
        return;
    }
    bar.classList.add('visible');
    const itemsEl = document.getElementById('compareBarItems');
    if (itemsEl) {
        itemsEl.innerHTML = list.map(p => `
            <div class="compare-item">
                <span>${p.name.substring(0, 30)}${p.name.length > 30 ? '…' : ''}</span>
                <button onclick="removeFromCompare('${p.id}')"><i class="ph ph-x"></i></button>
            </div>
        `).join('');
    }
    const countEl = document.getElementById('compareCount');
    if (countEl) countEl.textContent = list.length;
}

window.removeFromCompare = (id) => {
    let list = getCompareList();
    list = list.filter(p => p.id !== id);
    localStorage.setItem(COMPARE_KEY, JSON.stringify(list));
    updateCompareBar();
};

// ── COUPON SYSTEM ────────────────────────────────────────────
export async function applyCoupon(code, cartTotal, userId) {
    const q = query(collection(db, 'coupons'), where('code', '==', code.toUpperCase()), where('active', '==', true));
    const snap = await getDocs(q);
    if (snap.empty) throw new Error('Invalid coupon code');

    const coupon = { id: snap.docs[0].id, ...snap.docs[0].data() };
    const now = new Date();

    if (coupon.expiresAt?.toDate() < now) throw new Error('Coupon has expired');
    if (coupon.minOrder && cartTotal < coupon.minOrder) throw new Error(`Minimum order ₹${coupon.minOrder.toLocaleString('en-IN')} required`);
    if (coupon.usageLimit && coupon.usedCount >= coupon.usageLimit) throw new Error('Coupon usage limit reached');
    if (coupon.userLimit && userId) {
        const userUsage = coupon.userUsage?.[userId] || 0;
        if (userUsage >= (coupon.userLimit || 1)) throw new Error('You have already used this coupon');
    }

    let discount = 0;
    if (coupon.type === 'percent') {
        discount = Math.min((cartTotal * coupon.value) / 100, coupon.maxDiscount || Infinity);
    } else if (coupon.type === 'flat') {
        discount = coupon.value;
    } else if (coupon.type === 'free_shipping') {
        discount = 0; // handled in shipping calc
    }

    return { coupon, discount: Math.round(discount) };
}

// ── PRICE CALCULATIONS ───────────────────────────────────────
export function calcCartTotals(items, discount = 0, shippingCharge = 0) {
    const subtotal = items.reduce((s, i) => s + i.price * i.qty, 0);
    const gstAmount = items.reduce((s, i) => {
        const rate = i.gstRate || 18;
        const base = (i.price * i.qty) / (1 + rate / 100);
        return s + ((i.price * i.qty) - base);
    }, 0);
    const shipping = subtotal >= 5000 ? 0 : shippingCharge;
    const total = subtotal - discount + shipping;
    return {
        subtotal: Math.round(subtotal),
        gstAmount: Math.round(gstAmount),
        discount: Math.round(discount),
        shipping: Math.round(shipping),
        total: Math.round(total)
    };
}

export function formatPrice(amount) {
    return '₹' + Math.round(amount).toLocaleString('en-IN');
}

// ── RAZORPAY CHECKOUT ────────────────────────────────────────
export async function initiateRazorpayCheckout({ orderId, amount, currency = 'INR', name, email, phone, description, prefill = {} }) {
    return new Promise((resolve, reject) => {
        if (RAZORPAY_KEY_ID.includes('XXXXX') || !window.Razorpay) {
            showToast('Processing test payment gateway transaction...', 'info');
            setTimeout(() => {
                resolve({
                    razorpay_payment_id: 'pay_demo_' + Math.random().toString(36).substring(2, 10),
                    razorpay_order_id: orderId || 'order_demo_' + Date.now(),
                    razorpay_signature: 'sig_demo_' + Date.now()
                });
            }, 1200);
            return;
        }
        const options = {
            key: RAZORPAY_KEY_ID,
            amount: amount * 100, // paise
            currency,
            name: COMPANY_NAME,
            description,
            order_id: orderId,
            prefill: {
                name: prefill.name || name,
                email: prefill.email || email,
                contact: prefill.phone || phone
            },
            notes: { source: 'shreejiinfo.in' },
            theme: { color: '#0071e3' },
            modal: {
                ondismiss: () => reject(new Error('Payment cancelled'))
            },
            handler: function (response) {
                resolve({
                    razorpay_payment_id: response.razorpay_payment_id,
                    razorpay_order_id: response.razorpay_order_id,
                    razorpay_signature: response.razorpay_signature
                });
            }
        };
        const rzp = new window.Razorpay(options);
        rzp.on('payment.failed', (resp) => reject(new Error(resp.error.description)));
        rzp.open();
    });
}

// ── ORDER CREATION ───────────────────────────────────────────
export async function createOrder(orderData) {
    const user = getCurrentUser();
    const userId = user ? user.uid : ('guest_' + Date.now());
    const userEmail = user ? user.email : (orderData.deliveryAddress?.email || '');
    const userPhone = user ? (user.phoneNumber || '') : (orderData.deliveryAddress?.phone || '');

    const orderRef = await addDoc(collection(db, 'orders'), {
        ...orderData,
        userId,
        userEmail,
        userPhone,
        status: orderData.status || (orderData.isCOD ? 'pending' : 'confirmed'),
        paymentStatus: orderData.paymentStatus || (orderData.isCOD ? 'pending' : 'paid'),
        createdAt: serverTimestamp(),
        updatedAt: serverTimestamp(),
        timeline: [{
            status: 'order_placed',
            timestamp: new Date().toISOString(),
            note: 'Order placed successfully'
        }]
    });

    // Clear cart after order
    if (user) {
        await setDoc(doc(db, 'carts', user.uid), { items: [], updatedAt: serverTimestamp() }, { merge: true });
    } else {
        localStorage.removeItem(LOCAL_CART_KEY);
    }
    updateCartBadge(0);

    return orderRef.id;
}

// ── GST INVOICE GENERATION ───────────────────────────────────
export function generateInvoiceNumber() {
    const date = new Date();
    const yr = date.getFullYear();
    const mo = String(date.getMonth() + 1).padStart(2, '0');
    const rand = Math.floor(Math.random() * 9000) + 1000;
    return `SI-INV-${yr}${mo}-${rand}`;
}

// ── PRODUCT FETCH ────────────────────────────────────────────
export async function getProducts({ category = null, brand = null, minPrice = 0, maxPrice = Infinity, search = null, sortBy = 'createdAt', pageSize = 12, lastDoc = null } = {}) {
    let q = collection(db, 'products');
    const constraints = [where('active', '==', true)];
    if (category) constraints.push(where('category', '==', category));
    if (brand) constraints.push(where('brand', '==', brand));
    constraints.push(orderBy(sortBy === 'price_asc' || sortBy === 'price_desc' ? 'price' : 'createdAt', sortBy === 'price_asc' ? 'asc' : 'desc'));
    constraints.push(limit(pageSize));
    if (lastDoc) constraints.push(startAfter(lastDoc));

    const snap = await getDocs(query(q, ...constraints));
    return snap.docs.map(d => ({ id: d.id, ...d.data() })).filter(p => p.price >= minPrice && p.price <= maxPrice);
}

export async function getProductById(id) {
    const snap = await getDoc(doc(db, 'products', id));
    return snap.exists() ? { id: snap.id, ...snap.data() } : null;
}

export async function getFeaturedProducts(cat = null, count = 8) {
    let constraints = [where('active', '==', true), where('featured', '==', true), orderBy('createdAt', 'desc'), limit(count)];
    if (cat) constraints.splice(1, 0, where('category', '==', cat));
    const snap = await getDocs(query(collection(db, 'products'), ...constraints));
    return snap.docs.map(d => ({ id: d.id, ...d.data() }));
}

// ── REVIEWS ──────────────────────────────────────────────────
export async function getReviews(productId, count = 10) {
    const q = query(collection(db, 'reviews'), where('productId', '==', productId), where('approved', '==', true), orderBy('createdAt', 'desc'), limit(count));
    const snap = await getDocs(q);
    return snap.docs.map(d => ({ id: d.id, ...d.data() }));
}

export async function submitReview(productId, { rating, title, body }) {
    const user = getCurrentUser();
    if (!user) { openAuthModal(); return; }
    await addDoc(collection(db, 'reviews'), {
        productId,
        userId: user.uid,
        userName: user.displayName || 'Anonymous',
        rating,
        title,
        body,
        approved: false, // admin approves
        helpful: 0,
        verified: true,
        createdAt: serverTimestamp()
    });
    showToast('Review submitted! It will appear after approval.', 'success');
}

// ── TOAST NOTIFICATIONS ──────────────────────────────────────
export function showToast(message, type = 'info') {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
    const icons = { success: 'ph-check-circle-fill', error: 'ph-x-circle-fill', info: 'ph-info-fill' };
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `<i class="ph-fill ${icons[type] || icons.info}"></i> ${message}`;
    container.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('toast-out');
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

// ── STAR RENDERER ─────────────────────────────────────────────
export function renderStars(rating) {
    const full = Math.floor(rating);
    const half = rating % 1 >= 0.5;
    let html = '';
    for (let i = 0; i < 5; i++) {
        if (i < full) html += '★';
        else if (i === full && half) html += '⯨';
        else html += '☆';
    }
    return html;
}

// ── SLUG ──────────────────────────────────────────────────────
export function toSlug(str) {
    return str.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
}

// ── SHIPPING ESTIMATE ─────────────────────────────────────────
// Shiprocket API integration point (calls Cloud Function for security)
export async function getShippingRates(pincode, weight = 0.5) {
    try {
        const res = await fetch(`/api/shipping-rates?pincode=${pincode}&weight=${weight}`);
        if (!res.ok) throw new Error('Rate fetch failed');
        return await res.json();
    } catch {
        // Fallback flat rates
        return [
            { carrier: 'Express', days: '2-3 days', price: 99 },
            { carrier: 'Standard', days: '4-7 days', price: 0 } // free above ₹5000
        ];
    }
}

// ── ANALYTICS HELPERS ─────────────────────────────────────────
export function trackEvent(name, params = {}) {
    if (typeof gtag === 'function') {
        gtag('event', name, params);
    }
}
