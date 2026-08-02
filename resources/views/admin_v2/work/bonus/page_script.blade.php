<script>
  (function() {
    var root = document.getElementById('bonus-main');
    if (!root) return;

    var saveUrl = @json(route('admin.bonus.update'));
    var recalculateUrl = @json(route('admin.bonus.recalculate'));
    var createCandidatesUrl = @json(route('admin.bonus.create-candidates'));
    var createUrl = @json(route('admin.bonus.create'));
    var deleteUrl = @json(route('admin.bonus.delete'));
    var csrf = @json(csrf_token());
    var month = @json($selectedMonth);
    var paymentDate = @json($selectedPaymentDate);
    var staffId = @json((string)(($selectedRow['staff_id'] ?? '')));
    var selectedCompanyId = @json((string)($selectedCompanyId ?? ''));
    var isBonusConfirmed = @json(((int)($summary['edit_lock'] ?? 0)) === 1);
    var confirmUrl = @json(route('admin.bonus.confirm'));

    var editBtn = document.getElementById('edit-toggle-btn');
    var recalculateBtn = document.getElementById('bonus-recalculate-btn');
    var createBtn = document.getElementById('bonus-create-btn');
    var createInline = document.getElementById('bonus-create-inline');
    var createList = document.getElementById('bonus-create-list');
    var createEmpty = document.getElementById('bonus-create-empty');
    var createPaymentDate = document.getElementById('bonus-create-payment-date');
    var createShowBtn = document.getElementById('bonus-create-show-btn');
    var createSubmitBtn = document.getElementById('bonus-create-submit-btn');
    var deleteSubmitBtn = document.getElementById('bonus-delete-submit-btn');
    var selectAllBtn = document.getElementById('bonus-create-select-all-btn');
    var clearBtn = document.getElementById('bonus-create-clear-btn');
    var closeBtn = document.getElementById('bonus-create-close-btn');


    // 確定ボタン
    var confirmBtn = document.getElementById('confirm-btn');

    var setBonusLockedUi = function() {
      [editBtn, recalculateBtn].forEach(function(btn) {
        if (btn) btn.disabled = true;
      });
      if (confirmBtn) confirmBtn.disabled = false;
    };

    if (isBonusConfirmed) {
      setBonusLockedUi();
    }

    if (confirmBtn) {
      confirmBtn.addEventListener('click', function() {
        if (!staffId) return;

        confirmBtn.disabled = true;

        fetch(confirmUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json'
            },
            body: JSON.stringify({
              staff_id: staffId,
              month: month,
              payment_date: paymentDate,
              company_id: selectedCompanyId
            })
          })
          .then(function(res) {
            return res.json();
          })
          .then(function(json) {
            if (!json || json.ok !== true) throw new Error('confirm failed');
            location.reload();
          })
          .catch(function() {
            alert('賞与確定状態の更新に失敗しました。');
          })
          .finally(function() {
            confirmBtn.disabled = false;
          });
      });
    }


    if (editBtn) {
      editBtn.addEventListener('click', function() {
        if (isBonusConfirmed) return;
        if (!root.classList.contains('is-editing')) {
          root.classList.add('is-editing');
          editBtn.textContent = '保存';
          return;
        }

        editBtn.disabled = true;
        var values = {};
        root.querySelectorAll('.edit-input[data-key]').forEach(function(input) {
          var key = (input.getAttribute('data-key') || '').trim();
          if (!key) return;
          values[key] = input.classList.contains('text') ?
            input.value :
            String(input.value || '').replace(/,/g, '');
        });

        fetch(saveUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json'
            },
            body: JSON.stringify({
              staff_id: staffId,
              month: month,
              payment_date: paymentDate,
              values: values,
              company_id: selectedCompanyId
            })
          })
          .then(async function(res) {
            const text = await res.text();

            if (!res.ok) {
              alert('賞与データの保存に失敗しました。\n\n' + text);
              console.error(text);
              throw new Error('server error');
            }

            return JSON.parse(text);
          })
          .then(function(json) {
            if (!json || json.ok !== true) throw new Error('save failed');
            root.classList.remove('is-editing');
            editBtn.textContent = '編集';
            location.reload();
          })
          .catch(function() {
            alert('賞与データの保存に失敗しました。');
          })
          .finally(function() {
            editBtn.disabled = false;
          });
      });
    }

    if (recalculateBtn) {
      recalculateBtn.addEventListener('click', function() {
        if (!staffId || isBonusConfirmed) return;
        recalculateBtn.disabled = true;
        fetch(recalculateUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json'
            },
            body: JSON.stringify({
              staff_id: staffId,
              month: month,
              payment_date: paymentDate,
              company_id: selectedCompanyId
            })
          })
          .then(function(res) {
            return res.json().then(function(json) {
              return {
                status: res.status,
                json: json
              };
            });
          })
          .then(function(payload) {
            if (payload.status >= 400 || !payload.json || payload.json.ok !== true) {
              throw new Error((payload.json || {}).message || 'recalculate failed');
            }
            location.reload();
          })
          .catch(function(err) {
            console.error(err);
            alert((err && err.message) ? err.message : '賞与データの再計算に失敗しました。');
          })
          .finally(function() {
            recalculateBtn.disabled = false;
          });
      });
    }

    var resetCandidates = function() {
      if (!createList || !createEmpty) return;
      createList.innerHTML = '';
      createList.hidden = true;
      createEmpty.hidden = true;
    };

    var renderCandidates = function(candidates) {
      if (!createList || !createEmpty) return;
      createList.innerHTML = '';
      createEmpty.hidden = candidates.length !== 0;
      createList.hidden = candidates.length === 0;

      candidates.forEach(function(candidate) {
        var wrapper = document.createElement('label');
        wrapper.className = 'create-inline-item' + (candidate.existing ? ' existing' : '');

        var check = document.createElement('input');
        check.type = 'checkbox';
        check.value = candidate.staff_id;
        check.className = 'bonus-create-check';

        var main = document.createElement('div');
        main.className = 'create-inline-item-main';
        main.innerHTML = '<div class="create-inline-item-title">' + candidate.staff_id + ' ' + candidate.staff_name + '</div>' +
          '<div class="create-inline-item-meta">' + (candidate.company_name || '-') + ' / ' + (candidate.store_name || '-') + '</div>';

        wrapper.appendChild(check);
        wrapper.appendChild(main);
        createList.appendChild(wrapper);
      });
    };

    var openCreate = function() {
      if (createInline) createInline.hidden = false;
      resetCandidates();
    };

    var closeCreate = function() {
      if (createInline) createInline.hidden = true;
    };

    var loadCandidates = function() {
      var selectedPayment = createPaymentDate ? createPaymentDate.value : '';
      if (!selectedPayment) {
        alert('支給日を選択してください。');
        return;
      }
      if (createShowBtn) createShowBtn.disabled = true;
      var query = '?month=' + encodeURIComponent(month) + '&company_id=' + encodeURIComponent(selectedCompanyId) + '&payment_date=' + encodeURIComponent(selectedPayment);
      fetch(createCandidatesUrl + query, {
          headers: {
            'Accept': 'application/json'
          }
        })
        .then(function(res) {
          return res.json();
        })
        .then(function(json) {
          if (!json || json.ok !== true) throw new Error('candidates failed');
          renderCandidates(json.candidates || []);
        })
        .catch(function() {
          alert('賞与データ候補の取得に失敗しました。');
        })
        .finally(function() {
          if (createShowBtn) createShowBtn.disabled = false;
        });
    };

    if (createBtn) createBtn.addEventListener('click', openCreate);
    if (closeBtn) closeBtn.addEventListener('click', closeCreate);
    if (createShowBtn) createShowBtn.addEventListener('click', loadCandidates);
    if (selectAllBtn) selectAllBtn.addEventListener('click', function() {
      document.querySelectorAll('.bonus-create-check').forEach(function(check) {
        check.checked = true;
      });
    });
    if (clearBtn) clearBtn.addEventListener('click', function() {
      document.querySelectorAll('.bonus-create-check').forEach(function(check) {
        check.checked = false;
      });
    });

    if (createSubmitBtn) {
      createSubmitBtn.addEventListener('click', function() {
        var ids = Array.prototype.slice.call(document.querySelectorAll('.bonus-create-check:checked')).map(function(check) {
          return check.value;
        });
        var selectedPayment = createPaymentDate ? createPaymentDate.value : '';
        if (ids.length === 0) {
          alert('スタッフを選択してください。');
          return;
        }
        if (!selectedPayment) {
          alert('支給日を選択してください。');
          return;
        }

        createSubmitBtn.disabled = true;
        fetch(createUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json'
            },
            body: JSON.stringify({
              month: month,
              company_id: selectedCompanyId,
              payment_date: selectedPayment,
              staff_ids: ids
            })
          })
          .then(function(res) {
            return res.json().then(function(json) {
              return {
                status: res.status,
                json: json
              };
            });
          })
          .then(function(payload) {
            if (payload.status >= 400 || !payload.json || payload.json.ok !== true) throw new Error((payload.json || {}).message || 'create failed');
            closeCreate();
            location.reload();
          })
          .catch(function(err) {
            alert((err && err.message) ? err.message : '賞与データ作成に失敗しました。');
          })
          .finally(function() {
            createSubmitBtn.disabled = false;
          });
      });
    }

    if (deleteSubmitBtn) {
      deleteSubmitBtn.addEventListener('click', function() {
        var ids = Array.prototype.slice.call(document.querySelectorAll('.bonus-create-check:checked')).map(function(check) {
          return check.value;
        });
        var selectedPayment = createPaymentDate ? createPaymentDate.value : '';
        if (ids.length === 0) {
          alert('スタッフを選択してください。');
          return;
        }
        if (!selectedPayment) {
          alert('支給日を選択してください。');
          return;
        }
        if (!window.confirm('選択した賞与データを削除します。よろしいですか？')) return;

        deleteSubmitBtn.disabled = true;
        fetch(deleteUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json'
            },
            body: JSON.stringify({
              payment_date: selectedPayment,
              staff_ids: ids
            })
          })
          .then(function(res) {
            return res.json().then(function(json) {
              return {
                status: res.status,
                json: json
              };
            });
          })
          .then(function(payload) {
            if (payload.status >= 400 || !payload.json || payload.json.ok !== true) throw new Error((payload.json || {}).message || 'delete failed');
            closeCreate();
            location.reload();
          })
          .catch(function(err) {
            alert((err && err.message) ? err.message : '賞与データ削除に失敗しました。');
          })
          .finally(function() {
            deleteSubmitBtn.disabled = false;
          });
      });
    }
  })();
</script>
