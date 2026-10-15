import { loadCatalog, formValues, clearErrors, clearErrorOnInput, showErrors, postJson } from '/forms.js';

const form = document.getElementById('agent-form');
const alertBox = document.getElementById('form-alert');
const catalog = await loadCatalog();

function checkboxes(container, name, entries) {
  for (const [value, label] of entries) {
    const wrap = document.createElement('label');
    wrap.className = 'check';
    const input = document.createElement('input');
    input.type = 'checkbox';
    input.name = name;
    input.value = value;
    const span = document.createElement('span');
    span.textContent = label;
    wrap.append(input, span);
    container.append(wrap);
  }
}

checkboxes(document.getElementById('regions'), 'regions', catalog.regions.map((r) => [r, r]));

clearErrorOnInput(form);

form.addEventListener('submit', async (event) => {
  event.preventDefault();
  clearErrors(form);
  alertBox.hidden = true;
  const button = form.querySelector('button[type=submit]');
  button.disabled = true;
  try {
    const { status, data } = await postJson('/api/agents', formValues(form));
    if (status === 201) {
      location.href = '/valittaja';
      return;
    }
    if (data.errors) showErrors(form, data.errors);
    else {
      alertBox.textContent = data.error ?? 'Lähetys epäonnistui. Yritä uudelleen.';
      alertBox.hidden = false;
    }
  } catch {
    alertBox.textContent = 'Yhteysvirhe. Yritä uudelleen.';
    alertBox.hidden = false;
  } finally {
    button.disabled = false;
  }
});
