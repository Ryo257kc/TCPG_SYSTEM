<script>
function toggleCompanyEdit(editing) {
  toggleCompanyInfoEdit('company-info-form', editing);
}

function toggleCompanyInfoEdit(formId, editing) {
  const form = document.getElementById(formId);
  if (!form) return;
  form.classList.toggle('editing', editing);

  const view = form.querySelector('.company-info-view');
  const edit = form.querySelector('.company-info-edit');
  if (view) {
    view.hidden = editing;
    view.style.display = editing ? 'none' : 'block';
  }
  if (edit) {
    edit.hidden = !editing;
    edit.style.display = editing ? 'block' : 'none';
  }
}

function setCompanyTab(tabName) {
  document.querySelectorAll('.company-info-form').forEach((form) => {
    toggleCompanyInfoEdit(form.id, false);
  });

  const url = new URL(window.location.href);
  url.searchParams.set('tab', tabName);
  window.location.href = url.toString();
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

function toggleMayorRowEdit(rowId, editing) {
  const row = document.getElementById(rowId);
  if (!row) return;
  row.classList.toggle('editing', editing);
}

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.company-info-form').forEach((form) => {
    toggleCompanyInfoEdit(form.id, false);
  });
});
</script>
