export const reply = (status, body, extra = {}) => ({ status, body, ...extra });
export const notFound = () => reply(404, { error: 'Ei löytynyt' });
export const unauthorized = () => reply(401, { error: 'Kirjaudu sisään.' });

export function safeEqual(a, b) {
  if (typeof a !== 'string' || typeof b !== 'string' || a.length !== b.length) return false;
  let diff = 0;
  for (let i = 0; i < a.length; i++) diff |= a.charCodeAt(i) ^ b.charCodeAt(i);
  return diff === 0;
}

export const JPEG_DATA_URL = /^data:image\/jpeg;base64,(\/9j\/[A-Za-z0-9+/]+=*)$/;
