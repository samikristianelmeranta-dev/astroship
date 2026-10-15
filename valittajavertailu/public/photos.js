// Kuvien lataus ja näyttäminen.
import { api, el } from '/forms.js';

const MAX_SIDE = 1600;
const QUALITY = 0.82;

// Pienentää kuvan ja tallentaa sen JPEGinä. Uudelleenpiirto canvasille
// poistaa samalla EXIF-tiedot, kuten kuvauspaikan GPS-sijainnin.
export async function resizeToJpeg(file) {
  const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
  const scale = Math.min(1, MAX_SIDE / Math.max(bitmap.width, bitmap.height));
  const canvas = document.createElement('canvas');
  canvas.width = Math.round(bitmap.width * scale);
  canvas.height = Math.round(bitmap.height * scale);
  canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
  bitmap.close?.();
  return canvas.toDataURL('image/jpeg', QUALITY);
}

// Kuvien valinta, esikatselu ja lataus lomakkeella.
export function createUploader({ input, dropzone, list, max = 15, onChange = () => {} }) {
  const items = []; // { key, dataUrl, id, status: 'ladataan' | 'valmis' | 'virhe', error }

  function render() {
    list.replaceChildren(
      ...items.map((item) =>
        el(
          'figure',
          { class: `thumb ${item.status}` },
          el('img', { src: item.dataUrl, alt: '' }),
          item.status === 'ladataan' ? el('span', { class: 'thumb-status' }, 'Ladataan…') : null,
          item.status === 'virhe' ? el('span', { class: 'thumb-status' }, item.error) : null,
          el('button', { type: 'button', class: 'thumb-remove', 'aria-label': 'Poista kuva', onclick: () => remove(item) }, '×'),
        ),
      ),
    );
    dropzone.hidden = items.length >= max;
    onChange();
  }

  function remove(item) {
    items.splice(items.indexOf(item), 1);
    render();
  }

  async function add(files) {
    for (const file of [...files].slice(0, max - items.length)) {
      if (!file.type.startsWith('image/')) continue;
      const item = { key: crypto.randomUUID(), dataUrl: '', status: 'ladataan' };
      try {
        item.dataUrl = await resizeToJpeg(file);
      } catch {
        continue; // Selain ei osaa lukea kuvaa (esim. HEIC muualla kuin Safarissa).
      }
      items.push(item);
      render();
      const { status, data } = await api('POST', '/api/photos', { dataUrl: item.dataUrl }).catch(() => ({ status: 0, data: {} }));
      if (status === 201) Object.assign(item, { id: data.id, status: 'valmis' });
      else Object.assign(item, { status: 'virhe', error: data.error ?? 'Lataus epäonnistui' });
      render();
    }
  }

  input.addEventListener('change', () => {
    add(input.files);
    input.value = '';
  });
  for (const type of ['dragenter', 'dragover']) {
    dropzone.addEventListener(type, (e) => {
      e.preventDefault();
      dropzone.classList.add('over');
    });
  }
  for (const type of ['dragleave', 'drop']) dropzone.addEventListener(type, () => dropzone.classList.remove('over'));
  dropzone.addEventListener('drop', (e) => {
    e.preventDefault();
    add(e.dataTransfer.files);
  });

  return {
    ids: () => items.filter((i) => i.status === 'valmis').map((i) => i.id),
    count: () => items.length,
    clear: () => {
      items.length = 0;
      render();
    },
    pending: () => items.some((i) => i.status === 'ladataan'),
    failed: () => items.some((i) => i.status === 'virhe'),
  };
}

// Hakee kuvan fetchillä, jotta sama koodi toimii evästeistunnolla ja kuluttajan tunnuksella.
export async function photoSrc(id, token) {
  const res = await fetch(`/api/photos/${id}${token ? `?t=${token}` : ''}`);
  if (!res.ok) return null;
  return URL.createObjectURL(await res.blob());
}

let lightbox;
function openLightbox(src) {
  if (!lightbox) {
    lightbox = el(
      'dialog',
      { class: 'lightbox', onclick: (e) => e.target === lightbox && lightbox.close() },
      el('img', { alt: 'Asunnon kuva' }),
      el('button', { type: 'button', class: 'lightbox-close', 'aria-label': 'Sulje', onclick: () => lightbox.close() }, '×'),
    );
    document.body.append(lightbox);
  }
  lightbox.querySelector('img').src = src;
  lightbox.showModal();
}

// Kuvagalleria näkymiin. Palauttaa elementin heti; kuvat täyttyvät taustalla.
export function photoGallery(ids = [], { token } = {}) {
  if (!ids?.length) return null;
  const grid = el('div', { class: 'gallery' });
  for (const id of ids) {
    const button = el('button', { type: 'button', class: 'gallery-item', 'aria-label': 'Avaa kuva' });
    grid.append(button);
    photoSrc(id, token).then((src) => {
      if (!src) return button.remove();
      button.append(el('img', { src, alt: '', loading: 'lazy' }));
      button.addEventListener('click', () => openLightbox(src));
    });
  }
  return el('div', {}, el('p', { class: 'eyebrow' }, `Kuvat (${ids.length})`), grid);
}

// Valmis kuvanlisäyslohko näkymiin (myyjän lisäkuvat ja vastaukset kuvapyyntöihin).
export function uploaderBlock({ id, max = 15, text = 'Lisää kuvia' }) {
  const input = el('input', { type: 'file', accept: 'image/*', multiple: true, class: 'visually-hidden', id });
  const dropzone = el('label', { class: 'dropzone', for: id }, el('strong', {}, text), el('span', {}, 'Valitse tai raahaa tähän.'));
  const list = el('div', { class: 'thumbs' });
  const uploader = createUploader({ input, dropzone, list, max });
  return { node: el('div', { class: 'photo-add' }, dropzone, input, list), uploader };
}
