import { $$, delegate, on, onceGlobal } from './dom.js';

/**
 * Client-side form validation.
 *
 * Mirrors the server rules so mistakes are caught before a round trip; the
 * server still validates everything, because this can be bypassed.
 * Password rules here must match config/verum_auth.php and Password::defaults().
 */

const PASSWORD_MIN = 10;

const RULES = {
    required: (value) => (value.trim() === '' ? 'This field is required.' : null),

    email: (value) =>
        value === '' || /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value)
            ? null
            : 'Enter a valid email address.',

    password: (value) => {
        if (value === '') return null;
        const problems = [];
        if (value.length < PASSWORD_MIN) problems.push(`${PASSWORD_MIN} characters`);
        if (!/[a-z]/.test(value) || !/[A-Z]/.test(value)) problems.push('upper and lower case');
        if (!/\d/.test(value)) problems.push('a number');
        if (!/[^\w\s]/.test(value)) problems.push('a symbol');
        return problems.length === 0 ? null : `Needs ${problems.join(', ')}.`;
    },

    confirmed: (value, field) => {
        const target = document.getElementById(field.dataset.confirms);
        if (!target || value === '') return null;
        return value === target.value ? null : 'The passwords do not match.';
    },

    minlength: (value, field) => {
        const min = Number(field.dataset.minlength);
        return value === '' || value.length >= min ? null : `At least ${min} characters.`;
    },

    maxlength: (value, field) => {
        const max = Number(field.dataset.maxlength);
        return value.length <= max ? null : `No more than ${max} characters.`;
    },
};

/** Which rules apply to a field, read from its type and data attributes. */
function rulesFor(field) {
    const rules = [];
    if (field.required) rules.push('required');
    if (field.type === 'email') rules.push('email');
    if (field.dataset.rule === 'password') rules.push('password');
    if (field.dataset.confirms) rules.push('confirmed');
    if (field.dataset.minlength) rules.push('minlength');
    if (field.dataset.maxlength) rules.push('maxlength');
    return rules;
}

function firstError(field) {
    for (const rule of rulesFor(field)) {
        const message = RULES[rule](field.value, field);
        if (message) return message;
    }
    return null;
}

function showError(field, message) {
    const wrap = field.closest('.field') ?? field.parentElement;
    if (!wrap) return;

    let slot = wrap.querySelector('.field__err');

    if (!slot) {
        slot = document.createElement('p');
        slot.className = 'field__err';
        wrap.appendChild(slot);
    }

    if (message) {
        slot.textContent = message;
        slot.setAttribute('role', 'alert');
        wrap.classList.add('has-error');
        field.setAttribute('aria-invalid', 'true');
        if (!slot.id) slot.id = `e-${field.id || field.name}`;
        field.setAttribute('aria-describedby', slot.id);
    } else {
        wrap.classList.remove('has-error');
        field.removeAttribute('aria-invalid');
        // Server-rendered errors are stale once the field is valid again.
        if (slot.dataset.server !== 'true') slot.textContent = '';
    }
}

function validateField(field) {
    const message = firstError(field);
    showError(field, message);
    return message === null;
}

/** Live strength meter for password fields. */
function attachStrength(field) {
    const wrap = field.closest('.field');
    if (!wrap || wrap.querySelector('.pwbar')) return;

    const bar = document.createElement('div');
    bar.className = 'pwbar';
    bar.innerHTML = '<i></i><span></span>';
    wrap.appendChild(bar);

    const fill = bar.querySelector('i');
    const label = bar.querySelector('span');

    const update = () => {
        const value = field.value;
        const checks = [
            value.length >= PASSWORD_MIN,
            /[a-z]/.test(value) && /[A-Z]/.test(value),
            /\d/.test(value),
            /[^\w\s]/.test(value),
        ];
        const score = checks.filter(Boolean).length;

        bar.dataset.score = String(value === '' ? 0 : score);
        fill.style.width = `${(score / checks.length) * 100}%`;
        label.textContent = value === ''
            ? ''
            : ['Too weak', 'Weak', 'Fair', 'Good', 'Strong'][score];
    };

    on(field, 'input', update);
    update();
}

export function initValidation() {
    const forms = $$('form[data-validate]');
    if (forms.length === 0) return;

    forms.forEach((form) => {
        // Let our messages through instead of the browser's bubbles.
        form.setAttribute('novalidate', '');

        $$('[data-rule="password"]', form).forEach(attachStrength);

        on(form, 'submit', (event) => {
            const fields = $$('input, textarea, select', form)
                .filter((field) => rulesFor(field).length > 0);

            const bad = fields.filter((field) => !validateField(field));

            if (bad.length > 0) {
                event.preventDefault();
                bad[0].focus();
                bad[0].scrollIntoView({ block: 'center', behavior: 'smooth' });
            }
        });
    });

    // blur does not bubble, so use its capturing counterpart.
    onceGlobal('validate-focusout', () => on(
        document,
        'focusout',
        (event) => {
            const field = event.target;
            if (!field.matches?.('form[data-validate] input, form[data-validate] textarea')) return;
            if (rulesFor(field).length > 0) validateField(field);
        },
        true,
    ));

    onceGlobal('validate-input', () => delegate(document, 'input', 'form[data-validate] .has-error input, form[data-validate] .has-error textarea', (event, field) => {
        if (rulesFor(field).length > 0) validateField(field);
    }));
}
