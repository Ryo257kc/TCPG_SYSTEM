<script>
document.addEventListener('DOMContentLoaded', function () {
    var paymentRowSnapshots = new WeakMap();

    // 下表の列位置。保存・取消・入金日切替で同じセルを参照するため固定。
    var PAYMENT_CELL_INDEX = {
        adjustmentAmount: 4,
        returnedAmount: 5,
        highCostAmount: 6,
        unrecoverableAmount: 7,
        paymentDate: 8,
        paymentAmount: 9,
        remainingAmount: 10,
        patientName: 11,
        remarks: 12
    };

    // 保存先URL/CSRFを持つページルートを取得。
    function getPaymentRoot() {
        return document.querySelector('[data-payment-save-url]');
    }

    // 指定列のセルを取得。
    function getPaymentCell(row, index) {
        return row && row.cells ? row.cells[index] : null;
    }

    // 表示モード用の値を取得。
    function getPaymentReadonly(row, index) {
        var cell = getPaymentCell(row, index);
        return cell ? cell.querySelector('.payment-confirmation-readonly') : null;
    }

    // 編集モード用の入力値を取得。
    function getPaymentControl(row, index) {
        var cell = getPaymentCell(row, index);
        return cell ? cell.querySelector('.payment-confirmation-edit-control') : null;
    }

    // 入金日は表示値と保存キーが別なので、hiddenの保存キーを取得。
    function getPaymentDateKey(row) {
        var cell = getPaymentCell(row, PAYMENT_CELL_INDEX.paymentDate);
        return cell ? cell.querySelector('[data-payment-date-key]') : null;
    }

    // 開閉行内の「入金日を入れる/外す」ボタンを取得。
    function getPaymentDateButton(detailRow) {
        return detailRow ? detailRow.querySelector('[data-payment-date-toggle]') : null;
    }

    // 複製時の備考追記金額を表示用に整形。
    function formatPaymentMoney(value) {
        var normalized = String(value || '').replace(/,/g, '').trim();
        var numeric = Number(normalized);

        if (!normalized || !isFinite(numeric)) {
            return '';
        }

        return numeric.toLocaleString('ja-JP');
    }

    // 複製時の備考計算用に金額文字列を数値化。
    function parsePaymentMoney(value) {
        var normalized = String(value || '').replace(/,/g, '').trim();
        var numeric = Number(normalized);

        return isFinite(numeric) ? numeric : 0;
    }

    // 備考を表示側と編集側の両方へ反映。
    function setPaymentRemarks(row, nextText) {
        var readonly = getPaymentReadonly(row, PAYMENT_CELL_INDEX.remarks);
        var control = getPaymentControl(row, PAYMENT_CELL_INDEX.remarks);
        var text = String(nextText || '').trim();

        if (readonly) {
            readonly.textContent = text;
        }

        if (control) {
            control.value = text;
        }
    }

    // 複製時に元行・複製行へ追記する備考文を組み立てる。
    function buildDuplicatePaymentRemark(row) {
        var treatmentMonthCell = getPaymentCell(row, 0);
        var claimAmountCell = getPaymentCell(row, 3);
        var treatmentMonth = ((treatmentMonthCell ? treatmentMonthCell.textContent : '') || '').trim();
        var claimAmount = parsePaymentMoney((((claimAmountCell ? claimAmountCell.textContent : '') || '')).trim());
        var adjustmentAmount = parsePaymentMoney((getPaymentControl(row, PAYMENT_CELL_INDEX.adjustmentAmount) || {}).value || '');
        var totalAmount = claimAmount + adjustmentAmount;

        if (treatmentMonth === '' && totalAmount === 0) {
            return '';
        }

        return treatmentMonth + '　確認額 ' + formatPaymentMoney(totalAmount);
    }

    // 複製行は元行で確認した金額の残りになるよう修正額を反転する。
    function applyDuplicatePaymentAdjustment(sourceRow, clonedRow) {
        var claimAmountCell = getPaymentCell(sourceRow, 3);
        var claimAmount = parsePaymentMoney((((claimAmountCell ? claimAmountCell.textContent : '') || '')).trim());
        var adjustmentAmount = parsePaymentMoney((getPaymentControl(sourceRow, PAYMENT_CELL_INDEX.adjustmentAmount) || {}).value || '');
        var sourceConfirmedAmount = claimAmount + adjustmentAmount;
        var nextAdjustmentAmount = sourceConfirmedAmount === 0 ? '' : '-' + formatPaymentMoney(sourceConfirmedAmount);
        var adjustmentReadonly = getPaymentReadonly(clonedRow, PAYMENT_CELL_INDEX.adjustmentAmount);
        var adjustmentControl = getPaymentControl(clonedRow, PAYMENT_CELL_INDEX.adjustmentAmount);

        if (adjustmentReadonly) {
            adjustmentReadonly.textContent = nextAdjustmentAmount;
        }
        if (adjustmentControl) {
            adjustmentControl.value = nextAdjustmentAmount;
        }
    }

    // 既存備考を消さずに複製用備考を追記。
    function appendPaymentRemark(row, remarkText) {
        var currentText = ((getPaymentControl(row, PAYMENT_CELL_INDEX.remarks) || {}).value || '').trim();
        var nextText = String(remarkText || '').trim();

        if (nextText === '') {
            return;
        }

        if (currentText !== '') {
            nextText = currentText + '\n' + nextText;
        }

        setPaymentRemarks(row, nextText);
    }

    // +ボタンで行を編集モードにし、詳細行を開く。
    function openPaymentRow(row, detailRow) {
        var toggle = row ? row.querySelector('[data-payment-candidate-toggle]') : null;
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'true');
            toggle.textContent = '-';
        }

        row.classList.add('is-editing');
        detailRow.hidden = false;
    }

    // 保存・取消後に行を表示モードへ戻し、詳細行を閉じる。
    function closePaymentRow(row, detailRow) {
        var toggle = row ? row.querySelector('[data-payment-candidate-toggle]') : null;
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
            toggle.textContent = '+';
        }

        row.classList.remove('is-editing');
        detailRow.hidden = true;
    }

    // 取消で戻せるよう、編集開始時点の値を保存。
    function capturePaymentSnapshot(row, detailRow) {
        var adjustmentReadonly = getPaymentReadonly(row, PAYMENT_CELL_INDEX.adjustmentAmount);
        var adjustmentControl = getPaymentControl(row, PAYMENT_CELL_INDEX.adjustmentAmount);
        var returnedReadonly = getPaymentReadonly(row, PAYMENT_CELL_INDEX.returnedAmount);
        var returnedControl = getPaymentControl(row, PAYMENT_CELL_INDEX.returnedAmount);
        var highCostReadonly = getPaymentReadonly(row, PAYMENT_CELL_INDEX.highCostAmount);
        var highCostControl = getPaymentControl(row, PAYMENT_CELL_INDEX.highCostAmount);
        var unrecoverableReadonly = getPaymentReadonly(row, PAYMENT_CELL_INDEX.unrecoverableAmount);
        var unrecoverableControl = getPaymentControl(row, PAYMENT_CELL_INDEX.unrecoverableAmount);
        var paymentDateReadonly = getPaymentReadonly(row, PAYMENT_CELL_INDEX.paymentDate);
        var paymentDateKey = getPaymentDateKey(row);
        var paymentAmountReadonly = getPaymentReadonly(row, PAYMENT_CELL_INDEX.paymentAmount);
        var paymentAmountControl = getPaymentControl(row, PAYMENT_CELL_INDEX.paymentAmount);
        var patientNameReadonly = getPaymentReadonly(row, PAYMENT_CELL_INDEX.patientName);
        var patientNameControl = getPaymentControl(row, PAYMENT_CELL_INDEX.patientName);
        var remarksReadonly = getPaymentReadonly(row, PAYMENT_CELL_INDEX.remarks);
        var remarksControl = getPaymentControl(row, PAYMENT_CELL_INDEX.remarks);
        var paymentDateButton = getPaymentDateButton(detailRow);

        return {
            adjustmentDisplay: adjustmentReadonly ? adjustmentReadonly.textContent : '',
            adjustmentValue: adjustmentControl ? adjustmentControl.value : '',
            returnedDisplay: returnedReadonly ? returnedReadonly.textContent : '',
            returnedValue: returnedControl ? returnedControl.value : '',
            highCostDisplay: highCostReadonly ? !!highCostReadonly.checked : false,
            highCostChecked: highCostControl ? !!highCostControl.checked : false,
            unrecoverableDisplay: unrecoverableReadonly ? !!unrecoverableReadonly.checked : false,
            unrecoverableChecked: unrecoverableControl ? !!unrecoverableControl.checked : false,
            paymentDateDisplay: paymentDateReadonly ? paymentDateReadonly.textContent : '',
            paymentDateKey: paymentDateKey ? paymentDateKey.value : '',
            paymentAmountDisplay: paymentAmountReadonly ? paymentAmountReadonly.textContent : '',
            paymentAmountValue: paymentAmountControl ? paymentAmountControl.value : '',
            patientNameDisplay: patientNameReadonly ? patientNameReadonly.textContent : '',
            patientNameValue: patientNameControl ? patientNameControl.value : '',
            remarksDisplay: remarksReadonly ? remarksReadonly.textContent : '',
            remarksValue: remarksControl ? remarksControl.value : '',
            paymentDateButtonText: paymentDateButton ? paymentDateButton.textContent : ''
        };
    }

    // 行ごとの取消用スナップショットを保持。
    function storePaymentSnapshot(row, detailRow) {
        paymentRowSnapshots.set(row, capturePaymentSnapshot(row, detailRow));
    }

    // 取消時にスナップショットの値へ戻す。
    function applyPaymentSnapshot(row, detailRow, snapshot) {
        if (!snapshot) {
            return;
        }

        getPaymentReadonly(row, PAYMENT_CELL_INDEX.adjustmentAmount).textContent = snapshot.adjustmentDisplay;
        getPaymentControl(row, PAYMENT_CELL_INDEX.adjustmentAmount).value = snapshot.adjustmentValue;
        getPaymentReadonly(row, PAYMENT_CELL_INDEX.returnedAmount).textContent = snapshot.returnedDisplay;
        getPaymentControl(row, PAYMENT_CELL_INDEX.returnedAmount).value = snapshot.returnedValue;
        getPaymentReadonly(row, PAYMENT_CELL_INDEX.highCostAmount).checked = snapshot.highCostDisplay;
        getPaymentControl(row, PAYMENT_CELL_INDEX.highCostAmount).checked = snapshot.highCostChecked;
        getPaymentReadonly(row, PAYMENT_CELL_INDEX.unrecoverableAmount).checked = snapshot.unrecoverableDisplay;
        getPaymentControl(row, PAYMENT_CELL_INDEX.unrecoverableAmount).checked = snapshot.unrecoverableChecked;
        getPaymentReadonly(row, PAYMENT_CELL_INDEX.paymentDate).textContent = snapshot.paymentDateDisplay;
        getPaymentControl(row, PAYMENT_CELL_INDEX.paymentDate).textContent = snapshot.paymentDateDisplay;
        getPaymentDateKey(row).value = snapshot.paymentDateKey;
        getPaymentReadonly(row, PAYMENT_CELL_INDEX.paymentAmount).textContent = snapshot.paymentAmountDisplay;
        getPaymentControl(row, PAYMENT_CELL_INDEX.paymentAmount).value = snapshot.paymentAmountValue;
        getPaymentReadonly(row, PAYMENT_CELL_INDEX.patientName).textContent = snapshot.patientNameDisplay;
        getPaymentControl(row, PAYMENT_CELL_INDEX.patientName).value = snapshot.patientNameValue;
        getPaymentReadonly(row, PAYMENT_CELL_INDEX.remarks).textContent = snapshot.remarksDisplay;
        getPaymentControl(row, PAYMENT_CELL_INDEX.remarks).value = snapshot.remarksValue;

        var paymentDateButton = getPaymentDateButton(detailRow);
        if (paymentDateButton) {
            paymentDateButton.textContent = snapshot.paymentDateButtonText || (snapshot.paymentDateDisplay ? '入金日を外す' : '入金日を入れる');
        }
    }

    // controllerへ送る保存payloadをDBカラム名ベースで組み立てる。
    function buildPaymentSaveFields(row, root) {
        return {
            _token: root.getAttribute('data-payment-csrf') || '',
            journal_entry_id: root.getAttribute('data-payment-journal-entry-id') || '',
            insurance_claim_detail_id: row.getAttribute('data-payment-candidate-id') || '',
            source_insurance_claim_detail_id: row.getAttribute('data-payment-source-id') || row.getAttribute('data-payment-candidate-id') || '',
            adjustment_amount: (getPaymentControl(row, PAYMENT_CELL_INDEX.adjustmentAmount) || {}).value || '',
            returned_amount: (getPaymentControl(row, PAYMENT_CELL_INDEX.returnedAmount) || {}).value || '',
            is_high_cost_medical: (getPaymentControl(row, PAYMENT_CELL_INDEX.highCostAmount) || {}).checked ? 1 : 0,
            is_uncollectible: (getPaymentControl(row, PAYMENT_CELL_INDEX.unrecoverableAmount) || {}).checked ? 1 : 0,
            payment_date_text: (getPaymentDateKey(row) || {}).value || '',
            payment_amount: (getPaymentControl(row, PAYMENT_CELL_INDEX.paymentAmount) || {}).value || '',
            patient_name: (getPaymentControl(row, PAYMENT_CELL_INDEX.patientName) || {}).value || '',
            remarks: (getPaymentControl(row, PAYMENT_CELL_INDEX.remarks) || {}).value || ''
        };
    }

    // 保存後のJSONを画面の表示値・編集値へ反映。
    function applySavedPaymentRow(row, detailRow, payload) {
        getPaymentReadonly(row, PAYMENT_CELL_INDEX.adjustmentAmount).textContent = payload.adjustment_amount || '';
        getPaymentControl(row, PAYMENT_CELL_INDEX.adjustmentAmount).value = payload.adjustment_amount || '';
        getPaymentReadonly(row, PAYMENT_CELL_INDEX.returnedAmount).textContent = payload.returned_amount || '';
        getPaymentControl(row, PAYMENT_CELL_INDEX.returnedAmount).value = payload.returned_amount || '';
        getPaymentReadonly(row, PAYMENT_CELL_INDEX.highCostAmount).checked = !!payload.is_high_cost_medical;
        getPaymentControl(row, PAYMENT_CELL_INDEX.highCostAmount).checked = !!payload.is_high_cost_medical;
        getPaymentReadonly(row, PAYMENT_CELL_INDEX.unrecoverableAmount).checked = !!payload.is_uncollectible;
        getPaymentControl(row, PAYMENT_CELL_INDEX.unrecoverableAmount).checked = !!payload.is_uncollectible;
        getPaymentReadonly(row, PAYMENT_CELL_INDEX.paymentDate).textContent = payload.payment_date_display || '';
        getPaymentControl(row, PAYMENT_CELL_INDEX.paymentDate).textContent = payload.payment_date_display || '';
        getPaymentDateKey(row).value = payload.payment_date_text || '';
        getPaymentReadonly(row, PAYMENT_CELL_INDEX.paymentAmount).textContent = payload.payment_amount || '';
        getPaymentControl(row, PAYMENT_CELL_INDEX.paymentAmount).value = payload.payment_amount || '';
        var remainingReadonly = getPaymentReadonly(row, PAYMENT_CELL_INDEX.remainingAmount);
        if (remainingReadonly) {
            remainingReadonly.textContent = payload.remaining_amount || '';
        }
        if (payload.journal_entry_id) {
            document.querySelectorAll('[data-payment-entry-confirmed="' + payload.journal_entry_id + '"]').forEach(function (element) {
                element.textContent = payload.confirmed_amount || '';
            });
            document.querySelectorAll('[data-payment-entry-remaining="' + payload.journal_entry_id + '"]').forEach(function (element) {
                element.textContent = payload.entry_remaining_amount || '';
            });
        }
        getPaymentReadonly(row, PAYMENT_CELL_INDEX.patientName).textContent = payload.patient_name || '';
        getPaymentControl(row, PAYMENT_CELL_INDEX.patientName).value = payload.patient_name || '';
        getPaymentReadonly(row, PAYMENT_CELL_INDEX.remarks).textContent = payload.remarks || '';
        getPaymentControl(row, PAYMENT_CELL_INDEX.remarks).value = payload.remarks || '';

        var paymentDateButton = getPaymentDateButton(detailRow);
        if (paymentDateButton) {
            paymentDateButton.textContent = (payload.payment_date_display || '') !== '' ? '入金日を外す' : '入金日を入れる';
        }
    }

    // 下表の1行を非同期保存。
    function savePaymentRowAsync(row, detailRow) {
        var root = getPaymentRoot();
        var url = root ? root.getAttribute('data-payment-save-url') : '';
        var fields;

        if (!root || !url || !row || !detailRow) {
            return Promise.resolve(false);
        }

        fields = buildPaymentSaveFields(row, root);

        return fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(fields)
        }).then(function (response) {
            if (!response.ok) {
                return false;
            }

            return response.json().then(function (payload) {
                if (payload && payload.insurance_claim_detail_id) {
                    row.setAttribute('data-payment-candidate-id', String(payload.insurance_claim_detail_id));
                    row.setAttribute('data-payment-source-id', String(payload.insurance_claim_detail_id));
                }
                applySavedPaymentRow(row, detailRow, payload);
                storePaymentSnapshot(row, detailRow);
                return true;
            }).catch(function () {
                return false;
            });
        }).catch(function () {
            return false;
        });
    }

    // 複製ミスなどで不要になった下表1行を削除。
    function deletePaymentRowAsync(row) {
        var root = getPaymentRoot();
        var url = root ? root.getAttribute('data-payment-delete-url') : '';
        var detailId = row ? (row.getAttribute('data-payment-candidate-id') || '') : '';

        if (!root || !url || !detailId) {
            return Promise.resolve(false);
        }

        return fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                _token: root.getAttribute('data-payment-csrf') || '',
                insurance_claim_detail_id: detailId
            })
        }).then(function (response) {
            return response.ok;
        }).catch(function () {
            return false;
        });
    }

    // 表示行と開閉詳細行をセットで画面から除去。
    function removePaymentPair(row, detailRow) {
        if (detailRow && detailRow.parentNode) {
            detailRow.parentNode.removeChild(detailRow);
        }

        if (row && row.parentNode) {
            row.parentNode.removeChild(row);
        }
    }

    // 複製。元行へ備考追記して保存し、新しい行を編集状態で追加。
    function duplicatePaymentRow(sourceRow) {
        var detailRow = sourceRow ? sourceRow.nextElementSibling : null;
        var clonedRow;
        var clonedDetailRow;
        var duplicateRemark;

        if (!sourceRow || !detailRow || !detailRow.hasAttribute('data-payment-candidate-detail')) {
            return;
        }

        clonedRow = sourceRow.cloneNode(true);
        clonedDetailRow = detailRow.cloneNode(true);

        detailRow.parentNode.insertBefore(clonedRow, detailRow.nextElementSibling);
        detailRow.parentNode.insertBefore(clonedDetailRow, clonedRow.nextElementSibling);

        clonedRow.setAttribute('data-payment-candidate-id', '');
        clonedRow.setAttribute('data-payment-source-id', sourceRow.getAttribute('data-payment-candidate-id') || sourceRow.getAttribute('data-payment-source-id') || '');

        duplicateRemark = buildDuplicatePaymentRemark(sourceRow);
        applyDuplicatePaymentAdjustment(sourceRow, clonedRow);
        appendPaymentRemark(sourceRow, duplicateRemark);
        appendPaymentRemark(clonedRow, duplicateRemark);

        savePaymentRowAsync(sourceRow, detailRow).then(function (sourceSaved) {
            if (!sourceSaved) {
                clonedDetailRow.parentNode.removeChild(clonedDetailRow);
                clonedRow.parentNode.removeChild(clonedRow);
                alert('複製元の備考保存に失敗しました。元行を保存してから再度複製してください。');
                return;
            }

            savePaymentRowAsync(clonedRow, clonedDetailRow).then(function (cloneSaved) {
                if (!cloneSaved) {
                    clonedDetailRow.parentNode.removeChild(clonedDetailRow);
                    clonedRow.parentNode.removeChild(clonedRow);
                    alert('複製行の保存に失敗しました。');
                    return;
                }

                closePaymentRow(sourceRow, detailRow);
                openPaymentRow(clonedRow, clonedDetailRow);
                storePaymentSnapshot(clonedRow, clonedDetailRow);
            });
        });
    }

    // 下表の操作ボタンをまとめて処理。
    document.addEventListener('click', function (event) {
        var duplicateButton = event.target.closest('[data-payment-duplicate]');
        if (duplicateButton) {
            var detailRowForDuplicate = duplicateButton.closest('[data-payment-candidate-detail]');
            var rowForDuplicate = detailRowForDuplicate ? detailRowForDuplicate.previousElementSibling : null;
            duplicatePaymentRow(rowForDuplicate);
            return;
        }

        var deleteButton = event.target.closest('[data-payment-delete]');
        if (deleteButton) {
            var detailRowForDelete = deleteButton.closest('[data-payment-candidate-detail]');
            var rowForDelete = detailRowForDelete ? detailRowForDelete.previousElementSibling : null;
            var detailIdForDelete = rowForDelete ? (rowForDelete.getAttribute('data-payment-candidate-id') || '') : '';
            var sourceIdForDelete = rowForDelete ? (rowForDelete.getAttribute('data-payment-source-id') || '') : '';

            if (!rowForDelete || !detailRowForDelete) {
                return;
            }

            if (!window.confirm('削除しますか？')) {
                return;
            }

            if (detailIdForDelete === '' || detailIdForDelete !== sourceIdForDelete) {
                removePaymentPair(rowForDelete, detailRowForDelete);
                return;
            }

            deletePaymentRowAsync(rowForDelete).then(function (deleted) {
                if (!deleted) {
                    alert('削除に失敗しました。');
                    return;
                }

                removePaymentPair(rowForDelete, detailRowForDelete);
            });
            return;
        }

        var saveButton = event.target.closest('[data-payment-save]');
        if (saveButton) {
            var detailRowForSave = saveButton.closest('[data-payment-candidate-detail]');
            var rowForSave = detailRowForSave ? detailRowForSave.previousElementSibling : null;

            if (!rowForSave || !detailRowForSave) {
                return;
            }

            savePaymentRowAsync(rowForSave, detailRowForSave).then(function (saved) {
                if (!saved) {
                    alert('保存に失敗しました。');
                    return;
                }

                closePaymentRow(rowForSave, detailRowForSave);
            });
            return;
        }

        var cancelButton = event.target.closest('[data-payment-cancel]');
        if (cancelButton) {
            var detailRowForCancel = cancelButton.closest('[data-payment-candidate-detail]');
            var rowForCancel = detailRowForCancel ? detailRowForCancel.previousElementSibling : null;

            if (!rowForCancel || !detailRowForCancel) {
                return;
            }

            applyPaymentSnapshot(rowForCancel, detailRowForCancel, paymentRowSnapshots.get(rowForCancel));
            closePaymentRow(rowForCancel, detailRowForCancel);
            return;
        }

        var paymentDateButton = event.target.closest('[data-payment-date-toggle]');
        if (paymentDateButton) {
            var detailRowForDate = paymentDateButton.closest('[data-payment-candidate-detail]');
            var rowForDate = detailRowForDate ? detailRowForDate.previousElementSibling : null;
            if (!rowForDate || !rowForDate.hasAttribute('data-payment-candidate-row')) {
                return;
            }

            var paymentDateDisplays = rowForDate.querySelectorAll('[data-payment-date-display]');
            var paymentDateKey = rowForDate.querySelector('[data-payment-date-key]');
            var assignedDisplay = paymentDateButton.getAttribute('data-payment-date-display-value') || '';
            var assignedKey = paymentDateButton.getAttribute('data-payment-date-key-value') || '';
            var currentKey = paymentDateKey ? paymentDateKey.value.trim() : '';
            var shouldClear = currentKey !== '';
            var claimAmountCell = getPaymentCell(rowForDate, 3);
            var claimAmount = parsePaymentMoney(((claimAmountCell ? claimAmountCell.textContent : '') || '').trim());
            var adjustmentAmount = parsePaymentMoney((getPaymentControl(rowForDate, PAYMENT_CELL_INDEX.adjustmentAmount) || {}).value || '');
            var returnedAmount = parsePaymentMoney((getPaymentControl(rowForDate, PAYMENT_CELL_INDEX.returnedAmount) || {}).value || '');
            var nextPaymentAmount = shouldClear ? '0' : formatPaymentMoney(claimAmount + adjustmentAmount + returnedAmount);
            var paymentAmountReadonly = getPaymentReadonly(rowForDate, PAYMENT_CELL_INDEX.paymentAmount);
            var paymentAmountControl = getPaymentControl(rowForDate, PAYMENT_CELL_INDEX.paymentAmount);

            paymentDateDisplays.forEach(function (element) {
                element.textContent = shouldClear ? '' : assignedDisplay;
            });

            if (paymentDateKey) {
                paymentDateKey.value = shouldClear ? '' : assignedKey;
            }

            if (paymentAmountReadonly) {
                paymentAmountReadonly.textContent = nextPaymentAmount;
            }
            if (paymentAmountControl) {
                paymentAmountControl.value = nextPaymentAmount;
            }

            paymentDateButton.textContent = shouldClear ? '入金日を入れる' : '入金日を外す';
            return;
        }

        var toggleButton = event.target.closest('[data-payment-candidate-toggle]');
        if (!toggleButton) {
            return;
        }

        var row = toggleButton.closest('[data-payment-candidate-row]');
        var detailRow = row ? row.nextElementSibling : null;
        if (!row || !detailRow || !detailRow.hasAttribute('data-payment-candidate-detail')) {
            return;
        }

        var isOpen = toggleButton.getAttribute('aria-expanded') === 'true';
        if (!isOpen) {
            storePaymentSnapshot(row, detailRow);
        }
        if (isOpen) {
            closePaymentRow(row, detailRow);
        } else {
            openPaymentRow(row, detailRow);
        }
    });

    var params = new URLSearchParams(window.location.search);
    if (!params.get('journal_entry_id')) {
        return;
    }

    window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
});
</script>
