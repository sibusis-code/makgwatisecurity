/**
 * Makgwati Security — Main Script
 * Mobile nav | Scroll effects | Lead form submissions via WhatsApp
 */

(function () {
    'use strict';

    const WA_NUMBER = '27790260098';

    // ============================================================
    // MOBILE NAVIGATION
    // ============================================================
    function initNav() {
        const toggle = document.querySelector('.nav-toggle');
        const menu = document.querySelector('.nav-menu');
        if (!toggle || !menu) return;

        toggle.addEventListener('click', function () {
            menu.classList.toggle('active');
            toggle.classList.toggle('active');
        });

        // Close menu on nav link click
        document.querySelectorAll('.nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                menu.classList.remove('active');
                toggle.classList.remove('active');
            });
        });
    }

    // ============================================================
    // NAVBAR SCROLL EFFECT
    // ============================================================
    function initScrollNav() {
        const navbar = document.querySelector('.navbar');
        if (!navbar) return;

        window.addEventListener('scroll', function () {
            if (window.scrollY > 100) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }

    // ============================================================
    // SCROLL ANIMATIONS (Intersection Observer)
    // ============================================================
    function initAnimations() {
        const targets = document.querySelectorAll(
            '.service-card, .contact-card, .stat-card, .branch-card, .training-card, .project-card, .gallery-category'
        );
        if (!targets.length) return;

        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        targets.forEach(function (el) {
            el.classList.add('animate-ready');
            observer.observe(el);
        });
    }

    // ============================================================
    // LEAD FORM SUBMISSION via WhatsApp
    // formId: 'home' | 'services' | 'training' | 'contact' | 'vip'
    // ============================================================
    window.submitLeadForm = function (formId) {
        var prefix = formId.charAt(0); // h, s, t, c, v
        var name = (document.getElementById(prefix + '_name') || {}).value || '';
        var phone = (document.getElementById(prefix + '_phone') || {}).value || '';
        var email = (document.getElementById(prefix + '_email') || {}).value || '';
        var service = (document.getElementById(prefix + '_service') || {}).value || '';
        var message = (document.getElementById(prefix + '_message') || {}).value || '';

        // Validation
        if (!name.trim()) {
            alert('Please enter your full name.');
            return;
        }
        if (!phone.trim()) {
            alert('Please enter your phone number so we can contact you.');
            return;
        }

        // Build WhatsApp message
        var pageLabels = {
            home: 'Home Page Enquiry',
            services: 'Services Page Enquiry',
            training: 'Training Enrollment',
            contact: 'Contact Page Enquiry',
            vip: 'VIP Protection Enquiry'
        };

        var lines = [
            'Hi Makgwati Security!',
            '',
            '--- ' + (pageLabels[formId] || 'Website Enquiry') + ' ---',
            '',
            'Name: ' + name.trim(),
            'Phone: ' + phone.trim()
        ];

        if (email.trim()) lines.push('Email: ' + email.trim());
        if (service.trim()) lines.push('Service / Course: ' + service.trim());

        // Extra fields
        var location = (document.getElementById(prefix + '_location') || {}).value || '';
        if (location.trim()) lines.push('Location: ' + location.trim());

        var date = (document.getElementById(prefix + '_date') || {}).value || '';
        if (date.trim()) lines.push('Date: ' + date.trim());

        if (message.trim()) {
            lines.push('');
            lines.push('Message: ' + message.trim());
        }

        lines.push('');
        lines.push('Please get back to me. Thank you!');

        var text = encodeURIComponent(lines.join('\n'));
        window.open('https://wa.me/' + WA_NUMBER + '?text=' + text, '_blank');

        // Clear form
        var formEl = document.getElementById(formId + 'LeadForm');
        if (formEl) formEl.reset();
    };

    // ============================================================
    // INIT
    // ============================================================
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            initNav();
            initScrollNav();
            initAnimations();
        });
    } else {
        initNav();
        initScrollNav();
        initAnimations();
    }

})();
