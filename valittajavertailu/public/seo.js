// Kaupunkisivujen toimintakehotukset esitäyttävät lomakkeen postinumeron.
document.addEventListener('click', (e) => {
  const link = e.target.closest('[data-quickstart-postal]');
  if (!link) return;
  try {
    sessionStorage.setItem('vv-quickstart', JSON.stringify({ postalCode: link.dataset.quickstartPostal || '' }));
  } catch {}
});
