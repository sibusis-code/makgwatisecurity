/**
 * Makgwati Security — Smart FAQ Chatbot + Lead Capture
 * All leads delivered via WhatsApp to 27790260098 (Ally)
 */

(function () {
    'use strict';

    const WA_NUMBER = '27790260098';

    const faqs = [
        {
            keywords: ['service', 'offer', 'provide', 'what do', 'what you'],
            answer: '🛡️ <strong>Our Services Include:</strong><br>• VIP / Close Protection<br>• Building Security (armed & unarmed)<br>• Armed Response<br>• Event Security Management<br>• Riot Intervention<br>• CCTV Installation<br>• Access Control Systems<br>• Fire Alarm Installation<br>• Car Guarding<br>• Cash In Transit (CIT)<br><br>Visit our <a href="services.html" style="color:var(--gold);">Services page</a> to see all options.'
        },
        {
            keywords: ['price', 'cost', 'how much', 'fee', 'charge', 'rate', 'affordable'],
            answer: '💰 <strong>Training Prices:</strong><br>• Grade A — <strong>R1,300</strong><br>• Grade B — <strong>R1,200</strong><br>• EDC — <strong>R2,400</strong><br>• CIT — <strong>R1,400</strong><br>• Reaction Unit — <strong>R1,400</strong><br>• Handgun Private — <strong>R2,100</strong><br>• Handgun / Shotgun / Rifle Business — <strong>R2,600</strong><br><br>For security service quotes, please share your requirements — we\'ll tailor a package for you.'
        },
        {
            keywords: ['psira', 'registered', 'certified', 'accredited', 'licence', 'legitimate', 'legal', 'registration number'],
            answer: '✅ <strong>We Are Fully Certified:</strong><br>• PSIRA Reg: <strong>4464345</strong><br>• Training Number: <strong>4333959</strong><br>• SAPS Number: <strong>4001370</strong><br>• PFTC Number: <strong>T2311004</strong><br><br>All officers are PSIRA registered and fully vetted.'
        },
        {
            keywords: ['location', 'where', 'branch', 'office', 'area', 'province', 'limpopo', 'north west'],
            answer: '📍 <strong>Our Branch Locations:</strong><br>• <strong>Head Office</strong> — Ally: 079 026 0098<br>• <strong>Jane Furse</strong> — Beauty: 082 227 1165<br>• <strong>Driekop</strong> — Kgaugelo: 076 953 7244<br>• <strong>Monsterlus</strong> — Kamo: 082 072 4878<br>• <strong>Makeketela</strong> — Charity: 082 284 7799<br>• <strong>Mogwase</strong> — David: 070 624 7673 / 079 716 5314<br><br><a href="contact.html" style="color:var(--gold);">View all branch details →</a>'
        },
        {
            keywords: ['firearm', 'gun', 'handgun', 'rifle', 'shotgun', 'weapon', 'competency', 'pistol'],
            answer: '🔫 <strong>Firearm Competency Training:</strong><br>• Handgun — Private License: <strong>R2,100</strong><br>• Handgun — Business License: <strong>R2,600</strong><br>• Shotgun — Business License: <strong>R2,600</strong><br>• Rifle — Business License: <strong>R2,600</strong><br><br>SAPS-aligned, conducted at our accredited range (PFTC: T2311004). Would you like to enroll?'
        },
        {
            keywords: ['vip', 'protection', 'bodyguard', 'close', 'escort', 'executive', 'celebrity'],
            answer: '⭐ <strong>VIP / Close Protection:</strong><br>We provide discreet, professional close protection for executives, politicians, celebrities, and high-net-worth individuals.<br><br><strong>Services include:</strong><br>• Personal close protection officers<br>• Secure transportation<br>• Advance security sweeps<br>• Multi-team coordination<br>• 24/7 coverage available<br><br>View our <a href="vipprotection.html" style="color:var(--gold);">VIP gallery and assignment videos →</a>'
        },
        {
            keywords: ['enroll', 'register', 'join', 'sign up', 'how to apply', 'start training', 'apply'],
            answer: '🎓 <strong>How to Enroll:</strong><br>1. Choose your course from our <a href="training.html" style="color:var(--gold);">Training page</a><br>2. Fill in the enrollment form, OR<br>3. Click <strong>Enroll Now</strong> on any course card<br>4. We\'ll confirm your enrollment via WhatsApp<br><br>Requirements: Valid ID, PSIRA-eligible. Courses run regularly — enroll early to secure your spot.'
        },
        {
            keywords: ['contact', 'call', 'phone', 'number', 'reach', 'email', 'speak', 'talk'],
            answer: '📞 <strong>Contact Our Head Office:</strong><br>• WhatsApp: <strong>079 026 0098</strong> (Ally)<br>• Phone: <strong>015 001 2295</strong><br><br>Or <a href="contact.html" style="color:var(--gold);">visit our Contact page</a> to find your nearest branch (6 locations).'
        },
        {
            keywords: ['cit', 'cash in transit', 'cash transit', 'money transport', 'cash'],
            answer: '💰 <strong>Cash In Transit (CIT):</strong><br>We offer professional CIT security services including:<br>• Armed escort teams<br>• Armoured vehicle coordination<br>• Route planning and threat assessment<br>• Emergency response protocols<br><br>CIT Training also available — R1,400. Enroll via our <a href="training.html" style="color:var(--gold);">Training page</a>.'
        },
        {
            keywords: ['event', 'crowd', 'concert', 'gathering', 'festival', 'function', 'corporate'],
            answer: '🎪 <strong>Event Security Management:</strong><br>We provide comprehensive event security including:<br>• Crowd control and management<br>• Access control at all entry points<br>• VIP area security<br>• Roving patrols<br>• Incident response teams<br>• Post-event sweep<br><br>We\'ve secured events ranging from 50 to 5,000+ attendees. Get a quote via WhatsApp.'
        },
        {
            keywords: ['cctv', 'camera', 'surveillance', 'monitoring', 'install', 'setup'],
            answer: '📹 <strong>CCTV Installation:</strong><br>We install and configure professional-grade CCTV systems:<br>• Indoor and outdoor cameras<br>• HD & night vision capability<br>• Remote monitoring integration<br>• DVR / NVR setup<br>• Cable routing and power backup<br><br>Contact us for a free site assessment and quote.'
        },
        {
            keywords: ['training', 'course', 'grade', 'certificate', 'study'],
            answer: '📚 <strong>All Training Courses:</strong><br>• Grade A — R1,300<br>• Grade B — R1,200<br>• EDC — R2,400<br>• CIT — R1,400<br>• Reaction Unit — R1,400<br>• Handgun Private — R2,100<br>• Handgun/Shotgun/Rifle Business — R2,600<br><br>All PSIRA accredited. Visit our <a href="training.html" style="color:var(--gold);">Training page</a> for full details.'
        }
    ];

    const defaultAnswer = '👋 Thanks for reaching out! I\'m not sure I understood your question. Try one of the quick buttons below, or describe what you need and I\'ll do my best to help.<br><br>Alternatively, WhatsApp us directly at <strong>079 026 0098</strong>.';

    let leadFormVisible = false;
    let badgeTimer;

    function getAnswer(userInput) {
        const input = userInput.toLowerCase();
        for (let i = 0; i < faqs.length; i++) {
            const faq = faqs[i];
            for (let j = 0; j < faq.keywords.length; j++) {
                if (input.includes(faq.keywords[j])) {
                    return faq.answer;
                }
            }
        }
        return defaultAnswer;
    }

    function appendMessage(html, type) {
        const messages = document.getElementById('chatbotMessages');
        if (!messages) return;
        const msg = document.createElement('div');
        msg.className = 'chat-msg ' + (type === 'user' ? 'chat-msg-user' : 'chat-msg-bot');
        msg.innerHTML = html;
        messages.appendChild(msg);
        messages.scrollTop = messages.scrollHeight;
    }

    function showLeadForm() {
        if (leadFormVisible) return;
        const form = document.getElementById('chatbotLeadForm');
        if (form) {
            form.style.display = 'block';
            leadFormVisible = true;
        }
    }

    function processInput(userText) {
        if (!userText.trim()) return;
        appendMessage(userText, 'user');
        const input = document.getElementById('chatbotTextInput');
        if (input) input.value = '';

        // Typing indicator
        const messages = document.getElementById('chatbotMessages');
        const indicator = document.createElement('div');
        indicator.className = 'chat-msg chat-msg-bot chat-typing';
        indicator.innerHTML = '<span class="dot"></span><span class="dot"></span><span class="dot"></span>';
        if (messages) messages.appendChild(indicator);
        if (messages) messages.scrollTop = messages.scrollHeight;

        setTimeout(function () {
            if (indicator.parentNode) indicator.parentNode.removeChild(indicator);
            const answer = getAnswer(userText);
            appendMessage(answer, 'bot');
            // Show lead form after 800ms
            setTimeout(showLeadForm, 800);
        }, 700);
    }

    function initChatbot() {
        const trigger = document.getElementById('chatbotTrigger');
        const window_ = document.getElementById('chatbotWindow');
        const closeBtn = document.getElementById('chatbotClose');
        const sendBtn = document.getElementById('chatbotSendBtn');
        const textInput = document.getElementById('chatbotTextInput');
        const submitBtn = document.getElementById('cbSubmit');
        const quickBtns = document.querySelectorAll('.quick-btn');

        if (!trigger || !window_) return;

        // Show greeting when opened
        function openChat() {
            window_.classList.add('open');
            trigger.classList.add('hide');
            localStorage.setItem('cbSeen', '1');
            clearTimeout(badgeTimer);
            const badge = trigger.querySelector('.chatbot-badge');
            if (badge) badge.style.display = 'none';

            if (!document.querySelector('.chat-msg')) {
                appendMessage(
                    '👋 Hello! I\'m the <strong>Makgwati Security AI Assistant</strong>.<br><br>I can help you with services, pricing, training courses, locations, and more.<br>What can I assist you with today?',
                    'bot'
                );
            }
        }

        trigger.addEventListener('click', openChat);
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                window_.classList.remove('open');
                trigger.classList.remove('hide');
            });
        }

        // Quick buttons
        quickBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                processInput(btn.getAttribute('data-query') || btn.textContent);
            });
        });

        // Send button
        if (sendBtn) {
            sendBtn.addEventListener('click', function () {
                const val = textInput ? textInput.value : '';
                processInput(val);
            });
        }

        // Enter key
        if (textInput) {
            textInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    processInput(textInput.value);
                }
            });
        }

        // Lead form submit
        if (submitBtn) {
            submitBtn.addEventListener('click', function () {
                const name = (document.getElementById('cbLeadName') || {}).value || '';
                const phone = (document.getElementById('cbLeadPhone') || {}).value || '';
                const service = (document.getElementById('cbLeadService') || {}).value || '';

                if (!name.trim() || !phone.trim()) {
                    appendMessage('⚠️ Please enter your <strong>name and phone number</strong> so we can contact you.', 'bot');
                    return;
                }

                const msg = 'Hi%20Makgwati%20Security!%0A%0AI%20chatted%20with%20your%20AI%20assistant%20and%20would%20like%20to%20learn%20more.%0A%0AName%3A%20' +
                    encodeURIComponent(name) +
                    '%0APhone%3A%20' +
                    encodeURIComponent(phone) +
                    '%0AInterest%3A%20' +
                    encodeURIComponent(service || 'General Enquiry') +
                    '%0A%0APlease%20get%20back%20to%20me.%20Thank%20you!';

                window.open('https://wa.me/' + WA_NUMBER + '?text=' + msg, '_blank');
                appendMessage('✅ Great! Opening WhatsApp now. Our team will respond to you shortly. Thank you, <strong>' + name.split(' ')[0] + '</strong>!', 'bot');
            });
        }

        // Badge notification after 8 seconds if chat not yet seen
        if (!localStorage.getItem('cbSeen')) {
            badgeTimer = setTimeout(function () {
                const badge = trigger.querySelector('.chatbot-badge');
                if (badge) badge.style.display = 'flex';
            }, 8000);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChatbot);
    } else {
        initChatbot();
    }
})();
