/* ============================================================
   ComplaintBox — Shared JavaScript
   Form validation, password toggles, mobile nav, dismissible alerts.
   Server-side PHP validation remains authoritative.
   ============================================================ */
'use strict';

const $  = (selector, scope = document) => scope.querySelector(selector);
const $$ = (selector, scope = document) => Array.from(scope.querySelectorAll(selector));

const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const KATHFORD_DOMAIN = '@kathford.edu.np';
const PHONE_REGEX = /^[9][78][0-9]{8}$/;
const NAME_REGEX  = /^[A-Za-z][A-Za-z .'-]+$/;

/* ---------- Mobile nav toggle ---------- */
function initNavToggle() {
  const toggle = $('#navToggle');
  const links = $('#navLinks');
  if (toggle && links) {
    toggle.addEventListener('click', () => {
      const open = links.classList.toggle('open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }
}

/* ---------- Dismissible alerts ---------- */
function initAlerts() {
  $$('.alert-close').forEach((btn) => {
    btn.addEventListener('click', () => {
      const alert = btn.closest('.alert');
      if (alert) alert.remove();
    });
  });
}

/* ---------- Password visibility toggles (can toggle any number of times) ---------- */
function initPasswordToggles() {
  $$('.toggle-eye').forEach((btn) => {
    btn.type = 'button'; // never submit the form when toggling
    btn.addEventListener('click', () => {
      const wrapper = btn.closest('.input-wrapper');
      const input = wrapper ? wrapper.querySelector('input') : null;
      if (!input) return;

      const isPassword = input.type === 'password';
      input.type = isPassword ? 'text' : 'password';

      const icon = btn.querySelector('i');
      if (icon) {
        icon.className = isPassword ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
      }
      btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
      input.focus({ preventScroll: true });
    });
  });
}

/* ---------- Individual field validation ---------- */
function validateField(group) {
  const input = group.querySelector('input, select, textarea');
  if (!input) return true;

  // File inputs are validated server-side (content type, size). Never block
  // submission because one is optional.
  if (input.type === 'file') {
    group.classList.remove('invalid');
    return true;
  }

  const rule = group.dataset.validate || '';
  const value = (input.value || '').trim();
  let valid = true;

  switch (rule) {
    case 'name':
      valid = value.length > 0 && NAME_REGEX.test(value);
      break;
    case 'email':
      valid = value.length > 0 && EMAIL_REGEX.test(value)
        && value.toLowerCase().endsWith(KATHFORD_DOMAIN);
      break;
    case 'phone':
      valid = PHONE_REGEX.test(value);
      break;
    case 'password':
      valid = value.length >= 8;
      break;
    case 'confirm': {
      const passwordInput = $('[name="password"]', group.closest('form'));
      valid = value.length > 0 && value === (passwordInput ? passwordInput.value : '');
      break;
    }
    case 'required':
      valid = value.length > 0;
      break;
    default:
      // For password confirmation in change-password form
      if (input.name === 'confirm_password') {
        const pw = group.closest('form').querySelector('#newPassword');
        valid = value.length > 0 && value === (pw ? pw.value : '');
      } else {
        valid = value.length > 0;
      }
  }

  group.classList.toggle('invalid', !valid);
  return valid;
}

/* ---------- Live validation ---------- */
function initValidation() {
  const forms = $$('form[data-validate-form]');
  forms.forEach((form) => {
    const groups = $$('.form-group', form);
    groups.forEach((group) => {
      const input = group.querySelector('input, select, textarea');
      if (!input) return;

      input.addEventListener('blur', () => {
        if (input.value.trim() !== '' || group.classList.contains('invalid')) {
          validateField(group);
        }
      });
      input.addEventListener('input', () => {
        if (group.classList.contains('invalid')) validateField(group);
      });
      input.addEventListener('change', () => validateField(group));
    });

    // Re-validate confirm field when password changes
    const passwordField = $('[name="password"]', form);
    if (passwordField) {
      passwordField.addEventListener('input', () => {
        const confirmGroup = form.querySelector('[data-validate="confirm"], [name="confirm_password"]');
        const target = confirmGroup ? confirmGroup.closest('.form-group') : null;
        if (target && target.classList.contains('invalid')) validateField(target);
      });
    }
  });
}

/* ---------- Form submit with client validation ---------- */
function initFormSubmit() {
  const forms = $$('form[data-validate-form]');
  forms.forEach((form) => {
    form.addEventListener('submit', (e) => {
      const groups = $$('.form-group', form);
      let isFormValid = true;
      groups.forEach((group) => {
        if (!validateField(group)) isFormValid = false;
      });

      if (!isFormValid) {
        e.preventDefault();
        const firstInvalid = $('.form-group.invalid', form);
        const field = firstInvalid ? firstInvalid.querySelector('input, select, textarea') : null;
        if (field) field.focus();
      }
    });
  });
}

/* ---------- File input: show selected file name ---------- */
function initFileInputs() {
  $$('input[type="file"]').forEach((input) => {
    input.addEventListener('change', () => {
      const group = input.closest('.form-group');
      if (!group) return;
      let hint = group.querySelector('.file-hint');
      if (input.files && input.files.length > 0) {
        const f = input.files[0];
        if (!hint) {
          hint = document.createElement('div');
          hint.className = 'file-hint';
          group.appendChild(hint);
        }
        hint.textContent = 'Selected: ' + f.name + ' (' + (f.size / (1024 * 1024)).toFixed(2) + ' MB)';
      } else if (hint) {
        hint.remove();
      }
    });
  });
}

/* ---------- Boot ---------- */
document.addEventListener('DOMContentLoaded', () => {
  initNavToggle();
  initAlerts();
  initPasswordToggles();
  initValidation();
  initFormSubmit();
  initFileInputs();
});
