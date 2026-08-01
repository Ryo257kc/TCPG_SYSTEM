<script>
    // 取消時に編集前へ戻すため、行ごとの初期値を保持する。
    var highMedicalSnapshots = new WeakMap();

    // 一覧行と、その直後にある開閉詳細行をセットで扱う。
    function getHighMedicalPair(row) {
        var detailRow = row?.nextElementSibling;
        if (!row || !detailRow || !detailRow.hasAttribute('data-receipt-detail')) {
            return null;
        }

        return { row: row, detailRow: detailRow };
    }

    // 保存先URLとCSRFを持つページルートを取得する。
    function getHighMedicalRoot() {
        return document.querySelector('[data-high-medical-save-url]');
    }

    // 詳細行を開いて、編集用inputを表示する。
    function openHighMedicalRow(row, detailRow) {
        var toggle = row.querySelector('[data-receipt-toggle]');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'true');
            toggle.textContent = '▴';
        }

        row.classList.add('is-editing');
        detailRow.hidden = false;
    }

    // 詳細行を閉じて、表示状態に戻す。
    function closeHighMedicalRow(row, detailRow) {
        var toggle = row.querySelector('[data-receipt-toggle]');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
            toggle.textContent = '▾';
        }

        row.classList.remove('is-editing');
        detailRow.hidden = true;
    }

    // 取消で戻せるように、編集inputと詳細表示値を保存する。
    function captureHighMedicalSnapshot(row, detailRow) {
        return {
            controls: Array.prototype.map.call(
                row.querySelectorAll('[data-high-medical-field]'),
                function (control) {
                    return control.type === 'checkbox' ? control.checked : control.value;
                }
            ),
            detailValues: Array.prototype.map.call(
                detailRow.querySelectorAll('.high-medical-detail-value'),
                function (value) {
                    return value.textContent;
                }
            ),
        };
    }

    // 行の現在値を初期スナップショットとして登録する。
    function storeHighMedicalSnapshot(row, detailRow) {
        highMedicalSnapshots.set(row, captureHighMedicalSnapshot(row, detailRow));
    }

    // 取消ボタン押下時に、保存しておいた初期値へ戻す。
    function restoreHighMedicalSnapshot(row, detailRow) {
        var snapshot = highMedicalSnapshots.get(row);
        if (!snapshot) {
            return;
        }

        Array.prototype.forEach.call(row.querySelectorAll('[data-high-medical-field]'), function (control, index) {
            var value = snapshot.controls[index];
            if (control.type === 'checkbox') {
                control.checked = !!value;
            } else {
                control.value = value ?? '';
            }
        });

        Array.prototype.forEach.call(detailRow.querySelectorAll('.high-medical-detail-value'), function (value, index) {
            value.textContent = snapshot.detailValues[index] ?? '';
        });
    }

    // JSで作るPOSTフォームにhidden項目を追加する。
    function appendHighMedicalField(form, name, value) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.appendChild(input);
    }

    // 編集中の1行だけをPOSTで保存する。
    function submitHighMedicalRow(row) {
        var root = getHighMedicalRoot();
        var form;

        if (!root || !row) {
            return;
        }

        form = document.createElement('form');
        form.method = 'post';
        form.action = root.getAttribute('data-high-medical-save-url') || '';
        form.style.display = 'none';

        appendHighMedicalField(form, '_token', root.getAttribute('data-receipt-csrf') || '');
        appendHighMedicalField(form, 'insurance_claim_detail_id', row.getAttribute('data-receipt-id') || '');
        appendHighMedicalField(form, 'store_name', document.getElementById('receipt-store')?.value ?? '');
        appendHighMedicalField(form, 'payment_status', document.getElementById('high-medical-payment-status')?.value ?? 'unpaid');
        appendHighMedicalField(form, 'filter_subject_name', document.getElementById('high-medical-subject-name')?.value ?? '');
        appendHighMedicalField(form, 'filter_bank_deposit_date', document.getElementById('high-medical-bank-deposit-date')?.value ?? '');

        row.querySelectorAll('[data-high-medical-field]').forEach(function (control) {
            if (control.type === 'checkbox') {
                appendHighMedicalField(form, control.getAttribute('data-high-medical-field'), control.checked ? '1' : '0');
                return;
            }

            appendHighMedicalField(form, control.getAttribute('data-high-medical-field'), control.value);
        });

        document.body.appendChild(form);
        form.submit();
    }

    // 初期表示時に、全行の取消用スナップショットを作る。
    document.querySelectorAll('[data-receipt-row]').forEach(function (row) {
        var pair = getHighMedicalPair(row);
        if (!pair) {
            return;
        }

        storeHighMedicalSnapshot(pair.row, pair.detailRow);
    });

    // 保存・取消・開閉ボタンのクリックをまとめて処理する。
    document.addEventListener('click', function (event) {
        var saveButton = event.target.closest('[data-high-medical-save]');
        var cancelButton = event.target.closest('[data-high-medical-cancel]');
        var toggleButton = event.target.closest('[data-receipt-toggle]');
        var detailRow;
        var row;
        var pair;

        if (saveButton) {
            detailRow = saveButton.closest('[data-receipt-detail]');
            row = detailRow?.previousElementSibling;
            pair = getHighMedicalPair(row);
            if (!pair) {
                return;
            }

            submitHighMedicalRow(pair.row);
            return;
        }

        if (cancelButton) {
            detailRow = cancelButton.closest('[data-receipt-detail]');
            row = detailRow?.previousElementSibling;
            pair = getHighMedicalPair(row);
            if (!pair) {
                return;
            }

            restoreHighMedicalSnapshot(pair.row, pair.detailRow);
            closeHighMedicalRow(pair.row, pair.detailRow);
            return;
        }

        if (!toggleButton) {
            return;
        }

        row = toggleButton.closest('[data-receipt-row]');
        pair = getHighMedicalPair(row);
        if (!pair) {
            return;
        }

        if (toggleButton.getAttribute('aria-expanded') === 'true') {
            closeHighMedicalRow(pair.row, pair.detailRow);
            return;
        }

        openHighMedicalRow(pair.row, pair.detailRow);
    });

</script>
