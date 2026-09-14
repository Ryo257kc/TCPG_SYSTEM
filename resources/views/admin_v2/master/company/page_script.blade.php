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

// 二重送信防止：市長税(特別徴収)の新規追加で、ボタン連打により同じ内容が
// 2件登録される実例が発生した（2026-09-14）。このページの全フォーム（社保・老保・
// 市長税の新規追加/編集/削除）に共通で、submit時にボタンを即座に無効化する。
document.addEventListener('submit', (event) => {
  const form = event.target;
  if (!(form instanceof HTMLFormElement)) return;

  form.querySelectorAll('button[type="submit"]').forEach((button) => {
    button.disabled = true;
  });
});
</script>
