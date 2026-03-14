<script>
function toggleCompanyEdit(editing) {
  const panel = document.getElementById('company-detail-panel');
  if (!panel) return;
  panel.classList.toggle('editing', editing);
}

function setCompanyTab(tabName) {
  document.querySelectorAll('[data-company-tab]').forEach((button) => {
    button.classList.toggle('is-active', button.getAttribute('data-company-tab') === tabName);
  });

  document.querySelectorAll('[data-company-tab-panel]').forEach((panel) => {
    panel.classList.toggle('is-active', panel.getAttribute('data-company-tab-panel') === tabName);
  });
}

function toggleCurrentRateMode(cardId, mode) {
  const card = document.getElementById(cardId);
  if (!card) return;
  card.classList.remove('editing', 'creating');
  if (mode === 'editing' || mode === 'creating') {
    card.classList.add(mode);
  }
}

function toggleCurrentRateEdit(cardId, editing) {
  toggleCurrentRateMode(cardId, editing ? 'editing' : '');
}
</script>
