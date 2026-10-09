// Small enhancements only. Forms and page links also work without JavaScript.
document.documentElement.classList.add('js');

// Show the initial checklist one question at a time. PHP validates all answers.
const qualificationForm = document.querySelector('#qualification-form');
if (qualificationForm) {
    const questions = [...qualificationForm.querySelectorAll('.check-question')];
    const back = document.querySelector('#check-back');
    const next = document.querySelector('#check-next');
    const submit = document.querySelector('#check-submit');
    let step = 0;
    qualificationForm.noValidate = true;
    document.querySelector('#check-progress').hidden = false;

    function showQuestion(focus = true) {
        questions.forEach((question, index) => question.hidden = index !== step);
        back.hidden = step === 0;
        next.hidden = step === questions.length - 1;
        submit.hidden = step !== questions.length - 1;
        document.querySelector('#check-count').textContent = `Question ${step + 1} of ${questions.length}`;
        document.querySelector('#check-bar').value = step + 1;
        if (focus) {
            const legend = questions[step].querySelector('legend');
            legend.tabIndex = -1;
            legend.focus();
        }
    }

    function answered() {
        const input = questions[step].querySelector('input');
        if (!input.checkValidity()) { input.reportValidity(); return false; }
        return true;
    }
    next.addEventListener('click', () => { if (answered()) { step++; showQuestion(); } });
    back.addEventListener('click', () => { step--; showQuestion(); });
    qualificationForm.addEventListener('submit', event => {
        if (!answered()) { event.preventDefault(); return; }
        if (step < questions.length - 1) { event.preventDefault(); step++; showQuestion(); }
    });
    showQuestion(false);
}

const menuButton = document.querySelector('.menu-button');
menuButton?.addEventListener('click', function () {
    const isOpen = document.querySelector('#main-nav').classList.toggle('open');
    menuButton.setAttribute('aria-expanded', String(isOpen));
});

document.querySelectorAll('[data-print]').forEach(function (button) {
    button.addEventListener('click', function () {
        window.print();
    });
});

// Adjust the contact field to the selected update method.
const contactMethod = document.querySelector('#contact-method');
if (contactMethod) {
    const input = document.querySelector('#contact');
    const label = document.querySelector('#contact-label');
    const accountEmail = document.querySelector('#application-form input[name="email"]');
    let mobileNumber = contactMethod.value === 'sms' ? input.value : '';
    let previousMethod = contactMethod.value;

    function updateContact() {
        const method = contactMethod.value;
        if (previousMethod === 'sms') mobileNumber = input.value;
        input.readOnly = method === 'email';
        input.value = method === 'email' ? accountEmail.value : method === 'sms' ? mobileNumber : '';
        previousMethod = method;
        input.required = method !== 'none';
        input.type = method === 'email' ? 'email' : 'tel';
        input.placeholder = method === 'email' ? 'sample@example.com' : '09XXXXXXXXX';
        label.textContent = method === 'email' ? 'Email address' : 'Mobile number';
        document.querySelector('#contact-field').hidden = method === 'none';
    }

    contactMethod.addEventListener('change', updateContact);
    accountEmail.addEventListener('input', () => {
        if (contactMethod.value === 'email') input.value = accountEmail.value;
    });
    updateContact();
}
