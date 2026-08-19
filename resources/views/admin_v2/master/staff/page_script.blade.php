<script>
function toggleStaffInfoEdit(editing) {
  const form = document.getElementById('staff-info-form');
  if (!form) return;

  form.classList.toggle('editing', editing);

  const view = form.querySelector('.staff-info-view');
  const edit = form.querySelector('.staff-info-edit');
  if (view) {
    view.hidden = editing;
    view.style.display = editing ? 'none' : 'block';
  }
  if (edit) {
    edit.hidden = !editing;
    edit.style.display = editing ? 'block' : 'none';
  }

  document.querySelectorAll('.staff-tab-edit-only').forEach((button) => {
    button.hidden = editing;
    button.style.display = editing ? 'none' : 'inline-flex';
  });
  document.querySelectorAll('.staff-tab-save-only').forEach((button) => {
    button.hidden = !editing;
    button.style.display = editing ? 'inline-flex' : 'none';
  });
}

function staffDateEraYear(value) {
  const parts = String(value || '').split('-');
  if (parts.length < 3) return '';

  const year = Number(parts[0]);
  const month = Number(parts[1]);
  const day = Number(parts[2]);
  if (!year || !month || !day) return '';

  const ymd = (year * 10000) + (month * 100) + day;
  const eras = [
    { start: 20190501, mark: 'R', base: 2018 },
    { start: 19890108, mark: 'H', base: 1988 },
    { start: 19261225, mark: 'S', base: 1925 },
    { start: 19120730, mark: 'T', base: 1911 },
    { start: 18680125, mark: 'M', base: 1867 },
  ];

  for (const era of eras) {
    if (ymd >= era.start) {
      return era.mark + String(year - era.base);
    }
  }

  return String(year);
}

function setupStaffDateEraHints() {
  const root = document.querySelector('.staff-detail-panel');
  if (!root) return;

  root.querySelectorAll('input[type="date"]').forEach((input) => {
    if (input.dataset.eraHintReady === '1') return;
    input.dataset.eraHintReady = '1';

    const wrapper = document.createElement('span');
    wrapper.className = 'staff-date-era-field';
    input.insertAdjacentElement('beforebegin', wrapper);

    const hint = document.createElement('span');
    hint.className = 'staff-date-era-hint';
    wrapper.appendChild(hint);
    wrapper.appendChild(input);

    const update = () => {
      hint.textContent = staffDateEraYear(input.value);
      hint.hidden = hint.textContent === '';
    };

    input.addEventListener('input', update);
    input.addEventListener('change', update);
    update();
  });
}

// 給与マスタ・社保・住民税・扶養タブ共通：「最新」ブロックの読み取り⇔入力欄の切り替えと、
// 「新規登録」の折りたたみ開閉（2026-08-18、給与マスタから展開）。
function setupPayrollStyleToggles() {
  document.querySelectorAll('[data-edit-target]').forEach((btn) => {
    if (btn.dataset.toggleReady === '1') return;
    btn.dataset.toggleReady = '1';
    btn.addEventListener('click', () => {
      const target = document.getElementById(btn.dataset.editTarget);
      if (!target) return;
      const editing = target.classList.toggle('is-editing');
      btn.textContent = editing ? '閉じる' : '編集';
    });
  });

  document.querySelectorAll('[data-toggle-target]').forEach((btn) => {
    if (btn.dataset.toggleReady === '1') return;
    btn.dataset.toggleReady = '1';
    const openLabel = btn.textContent;
    btn.addEventListener('click', () => {
      const target = document.getElementById(btn.dataset.toggleTarget);
      if (!target) return;
      const open = target.classList.toggle('is-open');
      btn.textContent = open ? '－閉じる' : openLabel;
    });
  });
}

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('staff-info-form');
  toggleStaffInfoEdit(form && form.dataset.initialEditing === '1');
  setupStaffDateEraHints();
  setupPayrollStyleToggles();
});
</script>
