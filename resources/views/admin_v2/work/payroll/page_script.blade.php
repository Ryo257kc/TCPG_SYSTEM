<script>
  (function() {
    var editBtn = document.getElementById('edit-toggle-btn');
    var root = document.getElementById('payroll-main');
    if (!root) return;

    var saveUrl = @json(route('admin.payroll.update'));
    var attendanceReflectUrl = @json(route('admin.payroll.attendance-reflect'));
    var calcRecalculateUrl = @json(route('admin.payroll.recalculate'));
    var calcHomeVisitAllowanceUrl = @json(route('admin.payroll.calc-home-visit-allowance'));
    var calcKoyouUrl = @json(route('admin.payroll.calc-koyou'));
    var calcOvertimeDeductionUrl = @json(route('admin.payroll.calc-overtime-deduction'));
    var calcIncomeTaxUrl = @json(route('admin.payroll.calc-income-tax'));
    var confirmUrl = @json(route('admin.payroll.confirm'));
    var createCandidatesUrl = @json(route('admin.payroll.create-candidates'));
    var createUrl = @json(route('admin.payroll.create'));
    var deleteUrl = @json(route('admin.payroll.delete'));
    var salesSummaryUrl = @json(route('admin.payroll.sales-summary'));
    var salesReflectUrl = @json(route('admin.payroll.sales-reflect'));
    var csrf = @json(csrf_token());
    var month = @json($selectedMonth);
    var paymentDate = @json($selectedPaymentDate);
    var staffId = @json((string)($selectedRow['staff_id'] ?? ''));
    var selectedCompanyId = @json((string)($selectedCompanyId ?? ''));
    var hasAttendanceRecords = @json((bool)($selectedRow['attendance_record_exists'] ?? false));
    var isAttendanceConfirmed = @json(((int)($summary['attendance_checked'] ?? 0)) === 1);
    var isPayrollConfirmed = @json(((int)($summary['edit_lock'] ?? 0)) === 1);
    var attendanceConfirmNote = document.getElementById('attendance-confirm-note');
    var attendanceReflectBtn = document.getElementById('attendance-reflect-btn');
    var confirmBtn = document.getElementById('confirm-btn');
    var recalcBtn = document.getElementById('recalc-btn');
    var homeVisitAllowanceBtn = document.getElementById('calc-home-visit-allowance-btn');
    var payrollCreateBtn = document.getElementById('payroll-create-btn');
    var payrollCreateInline = document.getElementById('payroll-create-inline');
    var payrollCreateList = document.getElementById('payroll-create-list');
    var payrollCreateEmpty = document.getElementById('payroll-create-empty');
    var payrollCreatePaymentDate = document.getElementById('payroll-create-payment-date');
    var payrollCreateShowBtn = document.getElementById('payroll-create-show-btn');
    var payrollCreateSubmitBtn = document.getElementById('payroll-create-submit-btn');
    var payrollDeleteSubmitBtn = document.getElementById('payroll-delete-submit-btn');
    var payrollCreateSelectAllBtn = document.getElementById('payroll-create-select-all-btn');
    var payrollCreateClearBtn = document.getElementById('payroll-create-clear-btn');
    var payrollCreateCloseBtn = document.getElementById('payroll-create-close-btn');
    var payrollSalesBtn = document.getElementById('payroll-sales-btn');
    var payrollSalesInline = document.getElementById('payroll-sales-inline');
    var payrollSalesList = document.getElementById('payroll-sales-list');
    var payrollSalesEmpty = document.getElementById('payroll-sales-empty');
    var payrollSalesCloseBtn = document.getElementById('payroll-sales-close-btn');
    var payrollSalesReflectAllBtn = document.getElementById('payroll-sales-reflect-all-btn');
    var koyouBtn = document.getElementById('calc-koyou-btn');
    var overtimeDeductionBtn = document.getElementById('calc-overtime-deduction-btn');
    var incomeTaxBtn = document.getElementById('calc-income-tax-btn');

    var setConfirmStateUi = function(confirmed) {
      [editBtn, attendanceReflectBtn, recalcBtn, homeVisitAllowanceBtn, koyouBtn, overtimeDeductionBtn, incomeTaxBtn].forEach(function(btn) {
        if (btn) btn.disabled = true;
      });
      if (confirmBtn) {
        confirmBtn.disabled = false;
        confirmBtn.textContent = confirmed ? '\u672a\u78ba\u5b9a\u306b\u623b\u3059' : '\u78ba\u5b9a';
      }
    };

    var unlockButtons = function() {
      [editBtn, attendanceReflectBtn, recalcBtn, homeVisitAllowanceBtn, koyouBtn, overtimeDeductionBtn, incomeTaxBtn].forEach(function(btn) {
        if (btn) btn.disabled = false;
      });
      if (confirmBtn) {
        confirmBtn.disabled = false;
        confirmBtn.textContent = '\u78ba\u5b9a';
      }
    };

    if (editBtn) {
      editBtn.addEventListener('click', function() {
        if (!root.classList.contains('is-editing')) {
          root.classList.add('is-editing');
          editBtn.textContent = '\u4fdd\u5b58';
          return;
        }

        editBtn.disabled = true;
        var values = {};
        root.querySelectorAll('.edit-input[data-key]').forEach(function(input) {
          var key = (input.getAttribute('data-key') || '').trim();
          if (!key) return;
          values[key] = input.value;
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
              values: values,
              company_id: selectedCompanyId
            })
          })
          .then(function(res) {
            return res.json();
          })
          .then(function(json) {
            if (!json || json.ok !== true) {
              throw new Error('save failed');
            }
            root.classList.remove('is-editing');
            editBtn.textContent = '\u7de8\u96c6';
            location.reload();
          })
          .catch(function() {
            alert('\u4fdd\u5b58\u306b\u5931\u6557\u3057\u307e\u3057\u305f\u3002');
          })
          .finally(function() {
            editBtn.disabled = false;
          });
      });
    }

    var closePayrollCreateInline = function() {
      if (payrollCreateInline) payrollCreateInline.hidden = true;
    };

    var openPayrollCreateInline = function() {
      if (payrollCreateInline) payrollCreateInline.hidden = false;
    };

    var formatSalesNumber = function(value) {
      var number = Number(value || 0);
      return number.toLocaleString('ja-JP');
    };

    var salesPayloadFields = ['peple_num', 'km', 'kitazaike', 'higashi_kakogawa', 'tsubasa_harima', 'sakura_hari', 'orita_hari', 'miyamoto_hari', 'yokoi_hari', 'own_cost', 'unpaid_amo'];
    // peple_num(人数)・km(距離)は金額ではないため合計から除く。PayrollV2SalesImportService::homeVisitSalesTotal()と同じ式
    var salesMoneyFields = ['kitazaike', 'higashi_kakogawa', 'tsubasa_harima', 'sakura_hari', 'orita_hari', 'miyamoto_hari', 'yokoi_hari', 'own_cost', 'unpaid_amo'];

    var salesRowTotal = function(sales) {
      return salesMoneyFields.reduce(function(sum, field) {
        return sum + Number((sales || {})[field] || 0);
      }, 0);
    };

    var closePayrollSalesInline = function() {
      if (payrollSalesInline) payrollSalesInline.hidden = true;
    };

    var openPayrollSalesInline = function() {
      if (payrollSalesInline) payrollSalesInline.hidden = false;
    };

    var setPayrollSalesEmpty = function(message) {
      if (payrollSalesList) payrollSalesList.innerHTML = '';
      if (payrollSalesEmpty) {
        payrollSalesEmpty.textContent = message || '\u5bfe\u8c61\u30c7\u30fc\u30bf\u306f\u3042\u308a\u307e\u305b\u3093\u3002';
        payrollSalesEmpty.hidden = false;
      }
    };

    var reflectPayrollSales = function(ids, button) {
      if (!ids || ids.length === 0) {
        alert('\u53d6\u8fbc\u5bfe\u8c61\u304c\u3042\u308a\u307e\u305b\u3093\u3002');
        return;
      }

      if (button) button.disabled = true;
      fetch(salesReflectUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            month: month,
            payment_date: paymentDate,
            company_id: selectedCompanyId,
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
          var json = payload.json || {};
          if (payload.status >= 400 || json.ok !== true) {
            throw new Error(json.message || 'sales reflect failed');
          }

          alert(
            '\u58f2\u4e0a\u3092\u53d6\u308a\u8fbc\u307f\u307e\u3057\u305f\u3002' +
            '\n\u53d6\u8fbc: ' + Number(json.updated || 0) + '\u4ef6' +
            '\n\u78ba\u5b9a\u6e08: ' + Number(json.locked || 0) + '\u4ef6' +
            '\n\u58f2\u4e0a\u306a\u3057: ' + Number(json.no_data || 0) + '\u4ef6'
          );
          location.reload();
        })
        .catch(function(err) {
          alert((err && err.message) ? err.message : '\u58f2\u4e0a\u53d6\u8fbc\u306b\u5931\u6557\u3057\u307e\u3057\u305f\u3002');
        })
        .finally(function() {
          if (button) button.disabled = false;
        });
    };

    var renderPayrollSalesRows = function(rows) {
      if (!payrollSalesList || !payrollSalesEmpty) return;

      payrollSalesList.innerHTML = '';
      payrollSalesEmpty.hidden = rows.length !== 0;

      rows.forEach(function(row) {
        var sales = row.sales || {};
        var tr = document.createElement('tr');
        tr.dataset.staffId = row.staff_id || '';
        if (!row.sales) tr.className = 'sales-inline-no-data';
        if (row.has_payroll === false) tr.className = (tr.className + ' sales-inline-no-payroll').trim();
        if (Number(row.edit_lock || 0) === 1) tr.className = (tr.className + ' sales-inline-locked').trim();

        var name = document.createElement('td');
        name.className = 'sales-name-cell';
        name.textContent = (row.staff_id || '') + ' ' + (row.staff_name || '');
        tr.appendChild(name);

        var total = document.createElement('td');
        total.className = 'num sales-total-cell';
        total.textContent = row.sales ? formatSalesNumber(salesRowTotal(sales)) : '';
        tr.appendChild(total);

        var action = document.createElement('td');
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn';
        button.textContent = row.has_payroll === false ? '\u7d66\u4e0e\u306a\u3057' : (Number(row.edit_lock || 0) === 1 ? '\u78ba\u5b9a\u6e08' : '\u53d6\u8fbc');
        button.disabled = row.has_payroll === false || !row.sales || Number(row.edit_lock || 0) === 1;
        button.addEventListener('click', function() {
          reflectPayrollSales([row.staff_id], button);
        });
        action.appendChild(button);
        tr.appendChild(action);

        salesPayloadFields.forEach(function(field) {
          var td = document.createElement('td');
          td.className = 'num';
          td.textContent = row.sales ? formatSalesNumber(sales[field]) : '';
          tr.appendChild(td);
        });

        payrollSalesList.appendChild(tr);
      });
    };

    var loadPayrollSalesRows = function() {
      if (!payrollSalesList || !payrollSalesEmpty) return;
      openPayrollSalesInline();
      setPayrollSalesEmpty('\u8aad\u307f\u8fbc\u307f\u4e2d...');

      var query = '?month=' + encodeURIComponent(month) + '&payment_date=' + encodeURIComponent(paymentDate) + '&company_id=' + encodeURIComponent(selectedCompanyId);
      fetch(salesSummaryUrl + query, {
          headers: {
            'Accept': 'application/json'
          }
        })
        .then(function(res) {
          return res.json();
        })
        .then(function(json) {
          if (!json || json.ok !== true) {
            throw new Error('sales summary failed');
          }
          renderPayrollSalesRows(json.rows || []);
        })
        .catch(function() {
          setPayrollSalesEmpty('\u58f2\u4e0a\u96c6\u8a08\u306e\u53d6\u5f97\u306b\u5931\u6557\u3057\u307e\u3057\u305f\u3002');
        });
    };

    if (attendanceConfirmNote && (!hasAttendanceRecords || isAttendanceConfirmed)) {
      attendanceConfirmNote.hidden = true;
    }

    if (attendanceReflectBtn) {
      attendanceReflectBtn.addEventListener('click', function() {
        attendanceReflectBtn.disabled = true;

        fetch(attendanceReflectUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json'
            },
            body: JSON.stringify({
              staff_id: staffId,
              month: month
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
              throw new Error((payload.json || {}).message || 'attendance reflect failed');
            }

            alert((payload.json.message || '勤怠を反映しました。'));
            location.reload();
          })
          .catch(function(err) {
            alert((err && err.message) ? err.message : '勤怠反映に失敗しました。');
          })
          .finally(function() {
            attendanceReflectBtn.disabled = false;
          });
      });
    }

    var resetPayrollCreateCandidates = function() {
      if (!payrollCreateList || !payrollCreateEmpty) return;
      payrollCreateList.innerHTML = '';
      payrollCreateList.hidden = true;
      payrollCreateEmpty.hidden = true;
    };

    var renderPayrollCreateCandidates = function(candidates) {
      if (!payrollCreateList || !payrollCreateEmpty) return;
      payrollCreateList.innerHTML = '';
      payrollCreateEmpty.hidden = candidates.length !== 0;
      payrollCreateList.hidden = candidates.length === 0;

      candidates.forEach(function(candidate) {
        var wrapper = document.createElement('label');
        wrapper.className = 'create-inline-item' + (candidate.existing ? ' existing' : '');

        var check = document.createElement('input');
        check.type = 'checkbox';
        check.value = candidate.staff_id;
        check.className = 'payroll-create-check';

        var main = document.createElement('div');
        main.className = 'create-inline-item-main';
        main.innerHTML =
          '<div class="create-inline-item-title">' + candidate.staff_id + ' ' + candidate.staff_name + '</div>';

        var badges = document.createElement('div');
        badges.className = 'create-inline-badges';

        if (candidate.candidate_type === 'retired_prev_month') {
          var status = document.createElement('span');
          status.className = 'create-inline-badge retired';
          status.textContent = '\u524d\u6708\u9000\u8077';
          badges.appendChild(status);
        }

        if (candidate.retire_date) {
          var retire = document.createElement('span');
          retire.className = 'create-inline-badge';
          retire.textContent = candidate.retire_date;
          badges.appendChild(retire);
        }

        main.appendChild(badges);
        wrapper.appendChild(check);
        wrapper.appendChild(main);
        payrollCreateList.appendChild(wrapper);
      });
    };

    var loadPayrollCreateCandidates = function() {
      if (!payrollCreatePaymentDate) return;
      var paymentDate = payrollCreatePaymentDate.value;
      if (!paymentDate) {
        alert('\u4f5c\u6210\u65e5\u3092\u5165\u529b\u3057\u3066\u304f\u3060\u3055\u3044\u3002');
        payrollCreatePaymentDate.focus();
        return;
      }

      if (payrollCreateShowBtn) payrollCreateShowBtn.disabled = true;
      var query = '?month=' + encodeURIComponent(month) + '&company_id=' + encodeURIComponent(selectedCompanyId) + '&payment_date=' + encodeURIComponent(paymentDate);
      fetch(createCandidatesUrl + query, {
          headers: {
            'Accept': 'application/json'
          }
        })
        .then(function(res) {
          return res.json();
        })
        .then(function(json) {
          if (!json || json.ok !== true) {
            throw new Error('candidates failed');
          }
          renderPayrollCreateCandidates(json.candidates || []);
        })
        .catch(function() {
          alert('\u7d66\u4e0e\u30c7\u30fc\u30bf\u4f5c\u6210\u5019\u88dc\u306e\u53d6\u5f97\u306b\u5931\u6557\u3057\u307e\u3057\u305f\u3002');
        })
        .finally(function() {
          if (payrollCreateShowBtn) payrollCreateShowBtn.disabled = false;
        });
    };

    if (payrollCreateBtn) {
      payrollCreateBtn.addEventListener('click', function() {
        openPayrollCreateInline();
        resetPayrollCreateCandidates();
      });
    }

    if (payrollCreateShowBtn) {
      payrollCreateShowBtn.addEventListener('click', loadPayrollCreateCandidates);
    }

    if (payrollCreateCloseBtn) {
      payrollCreateCloseBtn.addEventListener('click', closePayrollCreateInline);
    }

    if (payrollSalesBtn) {
      payrollSalesBtn.addEventListener('click', loadPayrollSalesRows);
    }

    if (payrollSalesCloseBtn) {
      payrollSalesCloseBtn.addEventListener('click', closePayrollSalesInline);
    }

    if (payrollSalesReflectAllBtn) {
      payrollSalesReflectAllBtn.addEventListener('click', function() {
        var ids = Array.prototype.slice.call(document.querySelectorAll('#payroll-sales-list tr')).filter(function(row) {
          return !row.classList.contains('sales-inline-no-data') && !row.classList.contains('sales-inline-no-payroll') && !row.classList.contains('sales-inline-locked');
        }).map(function(row) {
          return row.dataset.staffId || '';
        }).filter(function(id) {
          return id !== '';
        });

        reflectPayrollSales(ids, payrollSalesReflectAllBtn);
      });
    }


    if (payrollCreateSelectAllBtn) {
      payrollCreateSelectAllBtn.addEventListener('click', function() {
        document.querySelectorAll('.payroll-create-check').forEach(function(check) {
          check.checked = true;
        });
      });
    }

    if (payrollCreateClearBtn) {
      payrollCreateClearBtn.addEventListener('click', function() {
        document.querySelectorAll('.payroll-create-check').forEach(function(check) {
          check.checked = false;
        });
      });
    }

    var reportType = document.getElementById('report_type');
    var transferListUrl = @json(route('admin.payroll.transfer-list'));
    var wageLedgerUrl = @json(route('admin.payroll.wage-ledger'));
    var personalWageLedgerUrl = @json(route('admin.payroll.personal-wage-ledger'));
    var outsourceRewardLedgerPrintUrl = @json(route('admin.payroll.outsource-reward-ledger-print'));
    var companyBurdenPrintUrl = @json(route('admin.payroll.company-burden-print'));
    var homeVisitSalesPrintUrl = @json(route('admin.payroll.home-visit-sales-print'));
    var outsourceMenuSalesPrintUrl = @json(route('admin.payroll.outsource-menu-sales-print'));
    var homeVisitSalesDetailPrintUrl = @json(route('admin.payroll.home-visit-sales-detail-print'));

    if (reportType) {
      reportType.addEventListener('change', function() {
        var value = reportType.value;
        if (!value) return;

        var baseUrl = value === 'transfer-list'
          ? transferListUrl
          : (value === 'wage-ledger'
            ? wageLedgerUrl
            : (value === 'personal-wage-ledger'
              ? personalWageLedgerUrl
              : (value === 'outsource-reward-ledger'
                ? outsourceRewardLedgerPrintUrl
                : (value === 'company-burden'
                  ? companyBurdenPrintUrl
                  : (value === 'home-visit-sales'
                    ? homeVisitSalesPrintUrl
                    : (value === 'outsource-menu-sales'
                      ? outsourceMenuSalesPrintUrl
                      : homeVisitSalesDetailPrintUrl))))));
        var params = new URLSearchParams();
        if (selectedCompanyId) params.set('company_id', selectedCompanyId);
        if (document.getElementById('payment_date') && document.getElementById('payment_date').value) {
          params.set('payment_date', document.getElementById('payment_date').value);
        }
        if (value === 'personal-wage-ledger') {
          var staffSelect = document.getElementById('staff_id');
          var selectedStaffId = staffSelect && staffSelect.value ? staffSelect.value : '';
          if (!selectedStaffId) {
            alert('スタッフを選択してください。');
            reportType.value = '';
            return;
          }
          params.set('staff_id', selectedStaffId);
        }

        window.open(baseUrl + '?' + params.toString(), '_blank', 'noopener');
        reportType.value = '';
      });
    }


    // 給与データ一括作成
    if (payrollCreateSubmitBtn) {
      payrollCreateSubmitBtn.addEventListener('click', function() {
        var ids = Array.prototype.slice.call(document.querySelectorAll('.payroll-create-check:checked')).map(function(check) {
          return check.value;
        });
        var paymentDate = payrollCreatePaymentDate ? payrollCreatePaymentDate.value : '';

        if (payrollCreateList && payrollCreateList.children.length === 0) {
          alert('表示を押して候補を確認してください。');
          return;
        }
        if (ids.length === 0) {
          alert('スタッフを選択してください。');
          return;
        }
        if (!paymentDate) {
          alert('作成日を入力してください。');
          return;
        }

        payrollCreateSubmitBtn.disabled = true;
        payrollCreateSubmitBtn.textContent = '作成中...';

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
              payment_date: paymentDate,
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
            var json = payload.json || {};
            if (payload.status >= 400 || json.ok !== true) {
              throw new Error(json.message || 'create failed');
            }

            var created = Number(json.created || json.inserted || json.updated || 0);
            var skipped = Number(json.skipped || json.existing || 0);

            var message = created > 0 ?
              '給与データを ' + created + ' 件作成しました。' :
              '作成対象はありませんでした。';

            if (skipped > 0) {
              message += '\n作成済み ' + skipped + ' 件は除外しました。';
            }

            alert(message);
            closePayrollCreateInline();
            location.reload();
          })
          .catch(function(err) {
            alert((err && err.message) ? err.message : '給与データ作成に失敗しました。');
          })
          .then(function() {
            payrollCreateSubmitBtn.disabled = false;
            payrollCreateSubmitBtn.textContent = '作成';
          });
      });
    }

    if (payrollDeleteSubmitBtn) {
      payrollDeleteSubmitBtn.addEventListener('click', function() {
        var ids = Array.prototype.slice.call(document.querySelectorAll('.payroll-create-check:checked')).map(function(check) {
          return check.value;
        });
        var paymentDate = payrollCreatePaymentDate ? payrollCreatePaymentDate.value : '';

        if (payrollCreateList && payrollCreateList.children.length === 0) {
          alert('表示を押して候補を確認してください。');
          return;
        }
        if (ids.length === 0) {
          alert('スタッフを選択してください。');
          return;
        }
        if (!paymentDate) {
          alert('作成日を入力してください。');
          return;
        }
        if (!window.confirm('選択した給与データを削除します。よろしいですか？')) {
          return;
        }


        payrollDeleteSubmitBtn.disabled = true;
        fetch(deleteUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json'
            },
            body: JSON.stringify({
              payment_date: paymentDate,
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
            var json = payload.json || {};
            if (payload.status >= 400 || json.ok !== true) {
              throw new Error(json.message || 'delete failed');
            }
            closePayrollCreateInline();
            location.reload();
          })
          .catch(function(err) {
            alert((err && err.message) ? err.message : '\u7d66\u4e0e\u30c7\u30fc\u30bf\u524a\u9664\u306b\u5931\u6557\u3057\u307e\u3057\u305f\u3002');
          })
          .finally(function() {
            payrollDeleteSubmitBtn.disabled = false;
          });
      });
    }



    // 再計算
    if (recalcBtn && staffId !== '') {
      recalcBtn.addEventListener('click', function() {
        recalcBtn.disabled = true;

        fetch(calcRecalculateUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json'
            },
            body: JSON.stringify({
              staff_id: staffId,
              month: month,
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
            var json = payload.json || {};

            // ★ここがポイント：サーバーのメッセージをそのまま出す
            if (payload.status >= 400 || json.ok !== true) {
              throw new Error(json.message || 'recalc failed');
            }

            location.reload();
          })
          .catch(function(err) {
            alert(err && err.message ? err.message : '再計算に失敗しました');
          })
          .finally(function() {
            recalcBtn.disabled = false;
          });
      });
    }

    if (homeVisitAllowanceBtn && staffId !== '') {
      homeVisitAllowanceBtn.addEventListener('click', function() {
        homeVisitAllowanceBtn.disabled = true;

        fetch(calcHomeVisitAllowanceUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json'
            },
            body: JSON.stringify({
              staff_id: staffId,
              month: month,
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
            var json = payload.json || {};
            if (payload.status >= 400 || json.ok !== true) {
              throw new Error(json.message || 'calc failed');
            }

            location.reload();
          })
          .catch(function(err) {
            alert(err && err.message ? err.message : '往診手当の計算に失敗しました。');
          })
          .finally(function() {
            homeVisitAllowanceBtn.disabled = false;
          });
      });
    }

    if (overtimeDeductionBtn && staffId !== '') {
      overtimeDeductionBtn.addEventListener('click', function() {
        overtimeDeductionBtn.disabled = true;
        fetch(calcOvertimeDeductionUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json'
            },
            body: JSON.stringify({
              staff_id: staffId,
              month: month,
              company_id: selectedCompanyId
            })
          })
          .then(function(res) {
            return res.json();
          })
          .then(function(json) {
            if (!json || json.ok !== true) {
              throw new Error('calc failed');
            }
            location.reload();
          })
          .catch(function() {
            alert('\u5272\u5897\u30fb\u63a7\u9664\u8a08\u7b97\u306b\u5931\u6557\u3057\u307e\u3057\u305f\u3002');
          })
          .finally(function() {
            overtimeDeductionBtn.disabled = false;
          });
      });
    }

    if (koyouBtn && staffId !== '') {
      koyouBtn.addEventListener('click', function() {
        koyouBtn.disabled = true;
        fetch(calcKoyouUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json'
            },
            body: JSON.stringify({
              staff_id: staffId,
              month: month
            })
          })
          .then(function(res) {
            return res.json();
          })
          .then(function(json) {
            if (!json || json.ok !== true) {
              throw new Error('calc failed');
            }
            location.reload();
          })
          .catch(function() {
            alert('\u96c7\u7528\u4fdd\u967a\u306e\u8a08\u7b97\u306b\u5931\u6557\u3057\u307e\u3057\u305f\u3002');
          })
          .finally(function() {
            koyouBtn.disabled = false;
          });
      });
    }

    if (incomeTaxBtn && staffId !== '') {
      incomeTaxBtn.addEventListener('click', function() {
        incomeTaxBtn.disabled = true;
        fetch(calcIncomeTaxUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json'
            },
            body: JSON.stringify({
              staff_id: staffId,
              month: month
            })
          })
          .then(function(res) {
            return res.json();
          })
          .then(function(json) {
            if (!json || json.ok !== true) {
              throw new Error('calc failed');
            }
            location.reload();
          })
          .catch(function() {
            alert('\u6240\u5f97\u7a0e\u306e\u8a08\u7b97\u306b\u5931\u6557\u3057\u307e\u3057\u305f\u3002');
          })
          .finally(function() {
            incomeTaxBtn.disabled = false;
          });
      });
    }

    if (confirmBtn && staffId !== '') {
      confirmBtn.addEventListener('click', function() {
        if (hasAttendanceRecords && !isPayrollConfirmed && !isAttendanceConfirmed) {
          alert('\u52e4\u6020\u672a\u78ba\u5b9a\u306e\u305f\u3081\u7d66\u4e0e\u78ba\u5b9a\u3067\u304d\u307e\u305b\u3093\u3002');
          return;
        }
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
              checked: isPayrollConfirmed ? 0 : 1
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
            var json = payload.json || {};
            if (payload.status >= 400 || json.ok !== true) {
              throw new Error(json.message || 'confirm failed');
            }
            isPayrollConfirmed = !!json.checked;
            location.reload();
          })
          .catch(function(err) {
            alert((err && err.message) ? err.message : '\u78ba\u5b9a\u306b\u5931\u6557\u3057\u307e\u3057\u305f\u3002');
          })
          .finally(function() {
            confirmBtn.disabled = false;
          });
      });
    }

    if (isPayrollConfirmed) {
      setConfirmStateUi(true);
    } else {
      unlockButtons();
    }
  })();
</script>
