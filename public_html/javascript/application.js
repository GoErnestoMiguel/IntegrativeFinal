const intake = document.querySelector('#application-form');
if (intake) {
    const steps = [...intake.querySelectorAll('.application-step')];
    const guided = intake.dataset.mode === 'simple';
    const back = document.querySelector('#application-back');
    const next = document.querySelector('#application-next');
    const submit = document.querySelector('#application-submit');
    let step = 0;
    function review() {
        const list = document.querySelector('#application-review');
        list.replaceChildren();
        intake.querySelectorAll('.field').forEach(label => {
            const input = label.querySelector('input, select, textarea');
            if (!input || label.hidden || input.name === 'email_otp' || input.type === 'password') return;
            const row = document.createElement('div');
            const title = document.createElement('dt');
            const value = document.createElement('dd');
            title.textContent = [...label.childNodes].filter(n => n.nodeType === 3).map(n => n.textContent).join('').trim() || label.querySelector('span')?.textContent;
            value.textContent = input.tagName === 'SELECT' ? input.selectedOptions[0]?.textContent : (input.type === 'file' ? [...input.files].map(file=>file.name).join(', ') : input.value) || 'Not provided';
            row.append(title, value); list.append(row);
        });
    }
    function show(focus = true) {
        steps.forEach((section, index) => section.hidden = guided && index !== step);
        back.hidden = !guided || step === 0;
        next.hidden = !guided || step === steps.length - 1;
        submit.hidden = guided && step !== steps.length - 1;
        document.querySelector('#application-count').textContent = `Step ${step + 1} of ${steps.length}`;
        document.querySelector('#application-bar').value = step + 1;
        review();
        if (focus) { const title = steps[step].querySelector('legend'); title.tabIndex = -1; title.focus(); }
    }
    function validate(section) {
        const invalid = [...section.querySelectorAll('input,select,textarea')].find(input => !input.checkValidity());
        if (invalid) { invalid.reportValidity(); return false; }
        return true;
    }
    if (guided) {
        intake.noValidate = true;
        document.querySelector('#application-progress').hidden = false;
        next.addEventListener('click', () => { if (validate(steps[step])) { step++; show(); } });
        back.addEventListener('click', () => { step--; show(); });
        intake.addEventListener('submit', event => {
            if (step < steps.length - 1) { event.preventDefault(); next.click(); return; }
            for (let index = 0; index < steps.length; index++) {
                if ([...steps[index].querySelectorAll('input,select,textarea')].some(input => !input.checkValidity())) {
                    event.preventDefault(); step = index; show(); validate(steps[index]); return;
                }
            }
        });
    }
    intake.addEventListener('input', review);
    intake.addEventListener('change', review);
    show(false);
    const errors=document.querySelector('#form-errors');
    if(errors) { errors.focus(); errors.scrollIntoView({block:'start'}); }
    // No names, contact details, OTPs or uploaded files are saved to browser storage.
    let changed=false;
    intake.addEventListener('input',()=>changed=true);
    intake.addEventListener('submit',event=>{ if(!event.defaultPrevented && intake.checkValidity()) changed=false; });
    window.addEventListener('beforeunload',event=>{ if(changed){ event.preventDefault(); event.returnValue=''; } });
}

