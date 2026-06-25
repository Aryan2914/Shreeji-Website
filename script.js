document.addEventListener('DOMContentLoaded', () => {

    /* ══════════════════════════════════════════
       1. Navbar scroll state
       ══════════════════════════════════════════ */
    const navbar = document.getElementById('navbar');
    const onScroll = () => {
        navbar.classList.toggle('scrolled', window.scrollY > 40);
    };
    window.addEventListener('scroll', onScroll, { passive: true });

    /* ══════════════════════════════════════════
       2. Mobile hamburger menu
       ══════════════════════════════════════════ */
    const hamburger     = document.getElementById('hamburger');
    const mobileDrawer  = document.getElementById('mobileDrawer');
    const drawerOverlay = document.getElementById('drawerOverlay');

    const openDrawer = () => {
        hamburger.classList.add('open');
        mobileDrawer.classList.add('open');
        drawerOverlay.style.display = 'block';
        document.body.style.overflow = 'hidden';
    };
    const closeDrawer = () => {
        hamburger.classList.remove('open');
        mobileDrawer.classList.remove('open');
        drawerOverlay.style.display = 'none';
        document.body.style.overflow = '';
    };

    hamburger.addEventListener('click', () => {
        mobileDrawer.classList.contains('open') ? closeDrawer() : openDrawer();
    });
    drawerOverlay.addEventListener('click', closeDrawer);

    document.querySelectorAll('.drawer-link').forEach(link => {
        link.addEventListener('click', closeDrawer);
    });

    /* ══════════════════════════════════════════
       3. Smooth anchor scrolling
       ══════════════════════════════════════════ */
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', e => {
            const id = anchor.getAttribute('href');
            if (id === '#') return;
            const target = document.querySelector(id);
            if (target) {
                e.preventDefault();
                closeDrawer();
                const offset = navbar.offsetHeight + 8;
                const top = target.getBoundingClientRect().top + window.scrollY - offset;
                window.scrollTo({ top, behavior: 'smooth' });
            }
        });
    });

    /* ══════════════════════════════════════════
       4. Active nav link highlight
       ══════════════════════════════════════════ */
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.nav-link');

    const highlightNav = () => {
        let current = '';
        sections.forEach(sec => {
            if (window.scrollY >= sec.offsetTop - 120) current = sec.id;
        });
        navLinks.forEach(link => {
            link.classList.toggle('active', link.getAttribute('href') === `#${current}`);
        });
    };
    window.addEventListener('scroll', highlightNav, { passive: true });

    /* ══════════════════════════════════════════
       5. Scroll Reveal (Intersection Observer)
       ══════════════════════════════════════════ */
    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12 });

    document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

    /* ══════════════════════════════════════════
       6. Hero parallax
       ══════════════════════════════════════════ */
    const heroBg = document.querySelector('.hero-bg-img');
    const heroSection = document.querySelector('.hero');

    if (heroBg && heroSection) {
        window.addEventListener('scroll', () => {
            const scrolled = window.scrollY;
            if (scrolled <= heroSection.offsetHeight) {
                heroBg.style.transform = `scale(1.04) translateY(${scrolled * 0.18}px)`;
            }
        }, { passive: true });

        setTimeout(() => {
            heroBg.style.transform = 'scale(1) translateY(0)';
        }, 100);
    }

    /* ══════════════════════════════════════════
       6.5 Component Floating Parallax (clamped)
       ══════════════════════════════════════════ */
    const floatingIcons = document.querySelectorAll('.trust-icon, .product-icon, .cta-icon, .author-avatar');
    const MAX_PARALLAX = 12; // max px offset — prevents icons escaping cards
    window.addEventListener('scroll', () => {
        floatingIcons.forEach((icon, index) => {
            const rect = icon.getBoundingClientRect();
            if (rect.top < window.innerHeight && rect.bottom > 0) {
                const speed = 0.04 + (index % 3) * 0.02;
                let offset = (window.innerHeight - rect.top) * speed * -1;
                // Clamp to prevent overflow
                offset = Math.max(-MAX_PARALLAX, Math.min(MAX_PARALLAX, offset));
                icon.style.transform = `translate3d(0, ${offset}px, 0)`;
            }
        });
    }, { passive: true });

    /* ══════════════════════════════════════════
       7. Animated number counters
       ══════════════════════════════════════════ */
    const counters = document.querySelectorAll('.stat-num[data-count]');
    let countersAnimated = false;

    const animateCounters = () => {
        if (countersAnimated) return;
        countersAnimated = true;

        counters.forEach(counter => {
            const target = parseInt(counter.dataset.count, 10);
            const duration = 2000;
            const startTime = performance.now();

            const update = (now) => {
                const elapsed = now - startTime;
                const progress = Math.min(elapsed / duration, 1);
                // Ease-out cubic
                const eased = 1 - Math.pow(1 - progress, 3);
                const current = Math.round(eased * target);
                counter.textContent = current.toLocaleString() + '+';

                if (progress < 1) {
                    requestAnimationFrame(update);
                }
            };
            requestAnimationFrame(update);
        });
    };

    if (counters.length > 0) {
        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounters();
                    statsObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });

        counters.forEach(c => statsObserver.observe(c));
    }

    /* ══════════════════════════════════════════
       8. Enquiry Form → WhatsApp
       ══════════════════════════════════════════ */
    const enquiryForm = document.getElementById('enquiryForm');

    if (enquiryForm) {
        enquiryForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const name    = document.getElementById('formName').value.trim();
            const phone   = document.getElementById('formPhone').value.trim();
            const product = document.getElementById('formProduct').value;
            const message = document.getElementById('formMessage').value.trim();

            if (!name || !phone || !product) {
                showToast('Please fill in all required fields.', 'error');
                return;
            }

            // Build WhatsApp message
            let text = `Hi Shreeji Infotech! 👋\n\n`;
            text += `*Name:* ${name}\n`;
            text += `*Phone:* ${phone}\n`;
            text += `*Product Interest:* ${product}\n`;
            if (message) {
                text += `*Message:* ${message}\n`;
            }
            text += `\nI'd love to get more details and pricing. Thank you!`;

            const encoded = encodeURIComponent(text);
            const waURL = `https://wa.me/919377704344?text=${encoded}`;

            // Track the event in GA4
            if (typeof gtag === 'function') {
                gtag('event', 'enquiry_submit', {
                    event_category: 'engagement',
                    event_label: product,
                    value: 1
                });
            }

            showToast('Opening WhatsApp...', 'success');

            setTimeout(() => {
                window.open(waURL, '_blank');
            }, 600);

            enquiryForm.reset();
        });
    }

    /* ══════════════════════════════════════════
       8.5 B2B Enquiry Form → WhatsApp
       ══════════════════════════════════════════ */
    const b2bForm = document.getElementById('b2bEnquiryForm');

    if (b2bForm) {
        b2bForm.addEventListener('submit', (e) => {
            e.preventDefault();

            const name    = document.getElementById('b2bName').value.trim();
            const company = document.getElementById('b2bCompany').value.trim();
            const phone   = document.getElementById('b2bPhone').value.trim();
            const email   = document.getElementById('b2bEmail').value.trim() || 'N/A';
            const interest= document.getElementById('b2bInterest').value;
            const message = document.getElementById('b2bMessage').value.trim();

            if (!name || !company || !phone || !interest || !message) {
                showToast('Please fill in all required B2B fields.', 'error');
                return;
            }

            // Build B2B Corporate WhatsApp message
            let text = `*🚨 [New Corporate Lead]*\n\n`;
            text += `*Company:* ${company}\n`;
            text += `*Contact Person:* ${name}\n`;
            text += `*Phone:* ${phone}\n`;
            if (email !== 'N/A') text += `*Email:* ${email}\n`;
            text += `*Requirement:* ${interest}\n\n`;
            text += `*Project Details:*\n${message}\n\n`;
            text += `_Sent via B2B Inquiry Form_`;

            const encoded = encodeURIComponent(text);
            const waURL = `https://wa.me/919377704344?text=${encoded}`;

            if (typeof gtag === 'function') {
                gtag('event', 'b2b_lead_submit', {
                    event_category: 'engagement',
                    event_label: interest,
                    value: 1
                });
            }

            showToast('Opening B2B Channel...', 'success');

            setTimeout(() => {
                window.open(waURL, '_blank');
            }, 600);

            b2bForm.reset();
        });
    }

    /* ══════════════════════════════════════════
       9. Product card Enquire Now → Form Pre-fill & GA tracking
       ══════════════════════════════════════════ */
    document.querySelectorAll('.product-card').forEach(card => {
        card.addEventListener('click', (e) => {
            if (e.target.closest('.product-link')) return; // Avoid double triggering
            const link = card.querySelector('.product-link');
            if (link) link.click();
        });
    });

    document.querySelectorAll('.product-link[data-product]').forEach(link => {
        link.addEventListener('click', () => {
            const productName = link.dataset.product;
            
            // Pre-select the product in the enquiry form
            const formProductSelect = document.getElementById('formProduct');
            if (formProductSelect) {
                formProductSelect.value = productName;
            }

            if (typeof gtag === 'function') {
                gtag('event', 'product_enquiry_click', {
                    event_category: 'engagement',
                    event_label: productName,
                    value: 1
                });
            }
        });
    });

    /* ══════════════════════════════════════════
       10. Toast notification system
       ══════════════════════════════════════════ */
    function showToast(message, type = 'info') {
        // Remove existing toast
        const existing = document.querySelector('.toast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `<i class="ph-fill ph-${type === 'success' ? 'check-circle' : type === 'error' ? 'warning-circle' : 'info'}"></i> ${message}`;
        document.body.appendChild(toast);

        // Trigger animation
        requestAnimationFrame(() => {
            toast.classList.add('show');
        });

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, 3000);
    }

    /* ══════════════════════════════════════════
       11. Track outbound link clicks (GA4)
       ══════════════════════════════════════════ */
    document.querySelectorAll('a[href^="tel:"], a[href^="mailto:"]').forEach(link => {
        link.addEventListener('click', () => {
            if (typeof gtag === 'function') {
                const isPhone = link.href.startsWith('tel:');
                gtag('event', isPhone ? 'phone_call_click' : 'email_click', {
                    event_category: 'contact',
                    event_label: link.href,
                    value: 1
                });
            }
        });
    });

});
