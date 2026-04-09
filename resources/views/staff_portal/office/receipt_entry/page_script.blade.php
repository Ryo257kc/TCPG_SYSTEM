<script>
    var receiptInsurerLookupOptions = @json($insurerLookupOptions ?? []);
    var receiptTypeByStore = @json($receiptTypeByStore ?? []);
    var receiptInsurerNumberDatalist = document.getElementById('receipt-insurer-number-options');
    var receiptRowSnapshots = new WeakMap();
    var RECEIPT_CELL_INDEX = {
        summaryCategory: 2,
        totalSheets: 3,
        totalPartsCount: 4,
        totalAmount: 5,
        copaymentAmount: 6,
        claimAmount: 7,
        returnedAmount: 8,
        adjustmentAmount: 9,
        patientName: 10,
        remarks: 11,
    };
    var RECEIPT_DETAIL_INDEX = {
        treatmentMonth: 0,
        insuranceCategory: 1,
        depositName: 2,
        storeName: 3,
        paymentDate: 4,
    };

    function getReceiptPair(row) {
        var detailRow = row?.nextElementSibling;
        if (!row || !detailRow || !detailRow.hasAttribute('data-receipt-detail')) {
            return null;
        }

        return { row: row, detailRow: detailRow };
    }

    function getReceiptActionButtons(detailRow) {
        return Array.prototype.slice.call(detailRow.querySelectorAll('.receipt-entry-detail-actions > .btn'));
    }

    function getReceiptPageRoot() {
        return document.querySelector('[data-receipt-save-url]');
    }

    function getReceiptInlineErrorBox() {
        return document.querySelector('[data-receipt-inline-error]');
    }

    function showReceiptInlineError(message) {
        var box = getReceiptInlineErrorBox();
        if (!box) {
            return;
        }

        box.textContent = message;
        box.hidden = false;
    }

    function hideReceiptInlineError() {
        var box = getReceiptInlineErrorBox();
        if (!box) {
            return;
        }

        box.textContent = '';
        box.hidden = true;
    }

    function submitReceiptRow(row, detailRow, url) {
        var root = getReceiptPageRoot();
        var form;
        var fields;

        if (!root || !url || !row || !detailRow) {
            return;
        }

        form = document.createElement('form');
        form.method = 'post';
        form.action = url;
        form.style.display = 'none';

        fields = buildReceiptSubmitFields(row, detailRow, root);

        Object.keys(fields).forEach(function (name) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = fields[name];
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
    }

    function buildReceiptSubmitFields(row, detailRow, root) {
        return {
            _token: root.getAttribute('data-receipt-csrf') || '',
            insurance_claim_detail_id: row.getAttribute('data-receipt-id') || '',
            insurer_number: getReceiptEditableControl(row, 0)?.value || '',
            summary_category: getReceiptEditableControl(row, RECEIPT_CELL_INDEX.summaryCategory)?.value || '',
            total_sheets: getReceiptEditableControl(row, RECEIPT_CELL_INDEX.totalSheets)?.value || '',
            total_parts_count: getReceiptEditableControl(row, RECEIPT_CELL_INDEX.totalPartsCount)?.value || '',
            total_amount: getReceiptEditableControl(row, RECEIPT_CELL_INDEX.totalAmount)?.value || '',
            copayment_amount: getReceiptEditableControl(row, RECEIPT_CELL_INDEX.copaymentAmount)?.value || '',
            claim_amount: getReceiptEditableControl(row, RECEIPT_CELL_INDEX.claimAmount)?.value || '',
            returned_amount: getReceiptEditableControl(row, RECEIPT_CELL_INDEX.returnedAmount)?.value || '',
            adjustment_amount: getReceiptEditableControl(row, RECEIPT_CELL_INDEX.adjustmentAmount)?.value || '',
            patient_name: getReceiptEditableControl(row, RECEIPT_CELL_INDEX.patientName)?.value || '',
            remarks: getReceiptEditableControl(row, RECEIPT_CELL_INDEX.remarks)?.value || '',
            target_month: getReceiptToolbarMonth(),
            store_name: getReceiptDetailValue(detailRow, RECEIPT_DETAIL_INDEX.storeName)?.textContent?.trim() || getReceiptToolbarStore(),
            deposit_name: getReceiptDetailValue(detailRow, RECEIPT_DETAIL_INDEX.depositName)?.textContent?.trim() || '',
        };
    }

    function saveReceiptRowAsync(row, detailRow, url) {
        var root = getReceiptPageRoot();
        var fields;

        if (!root || !url || !row || !detailRow) {
            return Promise.resolve(false);
        }

        fields = buildReceiptSubmitFields(row, detailRow, root);

        return fetch(url, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(fields),
        }).then(function (response) {
            if (!response.ok) {
                return false;
            }

            return response.json().then(function (payload) {
                if (payload && payload.insurance_claim_detail_id) {
                    row.setAttribute('data-receipt-id', String(payload.insurance_claim_detail_id));
                }

                row.dataset.receiptDraft = '0';
                storeReceiptSnapshot(row, detailRow);
                return true;
            }).catch(function () {
                return false;
            });
        }).catch(function () {
            return false;
        });
    }

    function captureReceiptSnapshot(row, detailRow) {
        return {
            controls: Array.prototype.map.call(
                row.querySelectorAll('.receipt-entry-edit-control'),
                function (control) {
                    return control.value;
                }
            ),
            displays: Array.prototype.map.call(
                row.querySelectorAll('[data-receipt-editable-display]'),
                function (display) {
                    return display.textContent;
                }
            ),
            detailValues: Array.prototype.map.call(
                detailRow.querySelectorAll('.receipt-entry-detail-value'),
                function (value) {
                    return value.textContent;
                }
            ),
        };
    }

    function storeReceiptSnapshot(row, detailRow) {
        receiptRowSnapshots.set(row, captureReceiptSnapshot(row, detailRow));
        row.dataset.receiptDraft = '0';
    }

    function restoreReceiptSnapshot(row, detailRow) {
        var snapshot = receiptRowSnapshots.get(row);
        if (!snapshot) {
            return;
        }

        Array.prototype.forEach.call(row.querySelectorAll('.receipt-entry-edit-control'), function (control, index) {
            control.value = snapshot.controls[index] ?? '';
        });

        Array.prototype.forEach.call(row.querySelectorAll('[data-receipt-editable-display]'), function (display, index) {
            display.textContent = snapshot.displays[index] ?? '';
        });

        Array.prototype.forEach.call(detailRow.querySelectorAll('.receipt-entry-detail-value'), function (value, index) {
            value.textContent = snapshot.detailValues[index] ?? '';
        });
    }

    function removeReceiptPair(row, detailRow) {
        if (detailRow?.parentNode) {
            detailRow.parentNode.removeChild(detailRow);
        }

        if (row?.parentNode) {
            row.parentNode.removeChild(row);
        }
    }

    function getReceiptToolbarMonth() {
        return document.getElementById('receipt-target-month')?.value ?? '';
    }

    function getReceiptToolbarStore() {
        return document.getElementById('receipt-store')?.value ?? '';
    }

    function formatReceiptMonthEnd(monthValue) {
        if (!monthValue || monthValue.indexOf('-') === -1) {
            return '';
        }

        var parts = monthValue.split('-');
        var year = Number(parts[0]);
        var month = Number(parts[1]);
        if (!year || !month) {
            return '';
        }

        var lastDate = new Date(year, month, 0);
        return [
            lastDate.getFullYear(),
            String(lastDate.getMonth() + 1).padStart(2, '0'),
            String(lastDate.getDate()).padStart(2, '0')
        ].join('/');
    }

    function parseReceiptMoney(value) {
        var normalized = String(value || '').replace(/,/g, '').trim();
        if (normalized === '') {
            return 0;
        }

        var parsed = Number(normalized);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function formatReceiptMoney(value) {
        if (!Number.isFinite(value)) {
            return '';
        }

        return value.toLocaleString('ja-JP');
    }

    function getReceiptEditableControl(row, cellIndex) {
        return row?.children?.[cellIndex]?.querySelector('.receipt-entry-edit-control') ?? null;
    }

    function getReceiptDetailValue(detailRow, detailIndex) {
        return detailRow?.querySelectorAll('.receipt-entry-detail-value')?.[detailIndex] ?? null;
    }

    function setReceiptRemarks(row, text) {
        var remarksControl = getReceiptEditableControl(row, RECEIPT_CELL_INDEX.remarks);
        var remarksDisplay = row?.children?.[RECEIPT_CELL_INDEX.remarks]?.querySelector('[data-receipt-editable-display]');
        if (remarksControl) {
            remarksControl.value = text;
        }
        if (remarksDisplay) {
            remarksDisplay.textContent = text;
        }
    }

    function syncReceiptClaimAmount(row) {
        if (!row || row.dataset.receiptClaimManual === '1') {
            return;
        }

        var totalAmountControl = getReceiptEditableControl(row, RECEIPT_CELL_INDEX.totalAmount);
        var copaymentAmountControl = getReceiptEditableControl(row, RECEIPT_CELL_INDEX.copaymentAmount);
        var claimAmountControl = getReceiptEditableControl(row, RECEIPT_CELL_INDEX.claimAmount);
        var claimAmountDisplay = row?.children?.[RECEIPT_CELL_INDEX.claimAmount]?.querySelector('[data-receipt-editable-display]');
        if (!totalAmountControl || !copaymentAmountControl || !claimAmountControl) {
            return;
        }

        var calculated = parseReceiptMoney(totalAmountControl.value) - parseReceiptMoney(copaymentAmountControl.value);
        claimAmountControl.value = formatReceiptMoney(calculated);
        if (claimAmountDisplay) {
            claimAmountDisplay.textContent = claimAmountControl.value;
        }
    }

    function buildDuplicateReceiptRemark(row, detailRow) {
        var treatmentMonth = getReceiptDetailValue(detailRow, RECEIPT_DETAIL_INDEX.treatmentMonth)?.textContent?.trim() ?? '';
        var claimAmount = parseReceiptMoney(getReceiptEditableControl(row, RECEIPT_CELL_INDEX.claimAmount)?.value);
        var adjustmentAmount = parseReceiptMoney(getReceiptEditableControl(row, RECEIPT_CELL_INDEX.adjustmentAmount)?.value);
        var totalAmount = claimAmount + adjustmentAmount;

        if (treatmentMonth === '' && totalAmount === 0) {
            return '';
        }

        var monthLabel = treatmentMonth.replace(/\/\d{2}$/, '');
        return monthLabel + '　請求額' + formatReceiptMoney(totalAmount);
    }

    function appendReceiptRemark(row, remarkText) {
        var remarksControl = getReceiptEditableControl(row, RECEIPT_CELL_INDEX.remarks);
        var currentText = remarksControl?.value?.trim() ?? '';
        var nextText = String(remarkText || '').trim();

        if (nextText === '') {
            return;
        }

        if (currentText !== '') {
            nextText = currentText + '\n' + nextText;
        }

        setReceiptRemarks(row, nextText);
    }

    function updateReceiptInsurerNumberOptions(inputValue) {
        if (!receiptInsurerNumberDatalist) {
            return;
        }

        var keyword = String(inputValue || '').trim();
        receiptInsurerNumberDatalist.innerHTML = '';
        if (keyword === '') {
            return;
        }

        receiptInsurerLookupOptions
            .filter(function (option) {
                return option.insurer_number.indexOf(keyword) === 0;
            })
            .slice(0, 20)
            .forEach(function (option) {
                var el = document.createElement('option');
                el.value = option.insurer_number;
                el.label = [option.insurer_name, option.insurance_category].filter(Boolean).join(' / ');
                receiptInsurerNumberDatalist.appendChild(el);
            });
    }

    function findReceiptInsurerByNumber(insurerNumber) {
        var keyword = String(insurerNumber || '').trim();
        if (keyword === '') {
            return null;
        }

        return receiptInsurerLookupOptions.find(function (option) {
            return option.insurer_number === keyword;
        }) ?? null;
    }

    function syncReceiptInsurerDisplays(input) {
        var row = input?.closest('[data-receipt-row]');
        var detailRow = row?.nextElementSibling;
        if (!row || !detailRow || !detailRow.hasAttribute('data-receipt-detail')) {
            return;
        }

        var insurer = findReceiptInsurerByNumber(input.value);
        var insurerNameDisplay = row.children?.[1]?.querySelector('.receipt-entry-readonly');
        var insuranceCategoryDisplay = getReceiptDetailValue(detailRow, RECEIPT_DETAIL_INDEX.insuranceCategory);
        var depositNameDisplay = getReceiptDetailValue(detailRow, RECEIPT_DETAIL_INDEX.depositName);
        var storeName = getReceiptDetailValue(detailRow, RECEIPT_DETAIL_INDEX.storeName)?.textContent?.trim() ?? '';
        var activeStoreName = storeName !== '' ? storeName : getReceiptToolbarStore();
        var receiptType = String(receiptTypeByStore[activeStoreName] || '').trim();
        var depositName = '';
        var displayInsurerNumber = row.children?.[0]?.querySelector('[data-receipt-editable-display]');

        if (!insurer) {
            if (displayInsurerNumber) {
                displayInsurerNumber.textContent = '';
            }
            if (insurerNameDisplay) {
                insurerNameDisplay.textContent = '';
            }
            if (insuranceCategoryDisplay) {
                insuranceCategoryDisplay.textContent = '';
            }
            if (depositNameDisplay) {
                depositNameDisplay.textContent = '';
            }
            return;
        }

        if (insurerNameDisplay) {
            insurerNameDisplay.textContent = insurer?.insurer_name ?? '';
        }

        if (insuranceCategoryDisplay) {
            insuranceCategoryDisplay.textContent = insurer?.insurance_category ?? '';
        }

        if (insurer) {
            if (receiptType.indexOf('柔整') !== -1) {
                depositName = insurer.scheduled_payment_name || '';
            } else if (receiptType.indexOf('鍼灸') !== -1) {
                depositName = insurer.scheduled_payment_name_2 || '';
            }
        }

        if (depositNameDisplay) {
            depositNameDisplay.textContent = depositName;
        }

        if (displayInsurerNumber) {
            displayInsurerNumber.textContent = insurer.insurer_number ?? '';
        }
    }

    function bindInsurerLookup(input) {
        if (!input) {
            return;
        }

        var row = input.closest('[data-receipt-row]');

        input.addEventListener('input', function () {
            hideReceiptInlineError();
            updateReceiptInsurerNumberOptions(input.value);
            syncReceiptInsurerDisplays(input);
            if (row) {
                syncReceiptClaimAmount(row);
            }
        });

        input.addEventListener('focus', function () {
            updateReceiptInsurerNumberOptions(input.value);
        });

        input.addEventListener('change', function () {
            hideReceiptInlineError();
            syncReceiptInsurerDisplays(input);
            if (row) {
                syncReceiptClaimAmount(row);
            }
        });

        input.addEventListener('blur', function () {
            var insurer = findReceiptInsurerByNumber(input.value);
            if (!insurer) {
                input.value = '';
                syncReceiptInsurerDisplays(input);
            }
        });
    }

    function bindReceiptRowCalculation(row) {
        if (!row) {
            return;
        }

        var totalAmountControl = getReceiptEditableControl(row, RECEIPT_CELL_INDEX.totalAmount);
        var copaymentAmountControl = getReceiptEditableControl(row, RECEIPT_CELL_INDEX.copaymentAmount);
        var claimAmountControl = getReceiptEditableControl(row, RECEIPT_CELL_INDEX.claimAmount);

        [totalAmountControl, copaymentAmountControl].forEach(function (control) {
            if (!control) {
                return;
            }

            control.addEventListener('input', function () {
                syncReceiptClaimAmount(row);
            });

            control.addEventListener('change', function () {
                syncReceiptClaimAmount(row);
            });
        });

        if (claimAmountControl) {
            claimAmountControl.addEventListener('input', function () {
                row.dataset.receiptClaimManual = '1';
            });

            claimAmountControl.addEventListener('change', function () {
                row.dataset.receiptClaimManual = '1';
            });
        }
    }

    function openReceiptRow(row, detailRow) {
        var toggle = row.querySelector('[data-receipt-toggle]');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'true');
            toggle.textContent = '▴';
        }

        row.classList.add('is-editing');
        detailRow.hidden = false;
    }

    function closeReceiptRow(row, detailRow) {
        var toggle = row.querySelector('[data-receipt-toggle]');
        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
            toggle.textContent = '▾';
        }

        row.classList.remove('is-editing');
        detailRow.hidden = true;
    }

    function syncEditableDisplay(cell, mode) {
        var display = cell.querySelector('[data-receipt-editable-display]');
        var control = cell.querySelector('.receipt-entry-edit-control');
        if (!display || !control) {
            return;
        }

        if (mode === 'blank') {
            if (control.tagName === 'SELECT') {
                control.selectedIndex = 0;
            } else {
                control.value = '';
            }

            display.textContent = '';
            return;
        }

        if (control.tagName === 'SELECT') {
            display.textContent = control.value === '' ? '' : (control.options[control.selectedIndex]?.text ?? '');
            return;
        }

        display.textContent = control.value;
    }

    function clearDetailValues(detailRow) {
        detailRow.querySelectorAll('.receipt-entry-detail-value').forEach(function (el) {
            el.textContent = '';
        });
    }

    function duplicateReceiptPair(sourceRow) {
        var pair = getReceiptPair(sourceRow);
        if (!pair) {
            return;
        }

        var clonedRow = pair.row.cloneNode(true);
        var clonedDetailRow = pair.detailRow.cloneNode(true);

        pair.detailRow.parentNode.insertBefore(clonedRow, pair.detailRow.nextElementSibling);
        pair.detailRow.parentNode.insertBefore(clonedDetailRow, clonedRow.nextElementSibling);

        clonedRow.querySelectorAll('td').forEach(function (cell) {
            syncEditableDisplay(cell, 'clone');
        });

        clonedRow.setAttribute('data-receipt-id', '');
        var duplicateRemark = buildDuplicateReceiptRemark(pair.row, pair.detailRow);
        appendReceiptRemark(pair.row, duplicateRemark);
        appendReceiptRemark(clonedRow, duplicateRemark);
        pair.row.querySelectorAll('td').forEach(function (cell) {
            syncEditableDisplay(cell, 'save');
        });
        clonedRow.dataset.receiptDraft = '1';
        clonedRow.dataset.receiptClaimManual = '0';
        bindInsurerLookup(clonedRow.querySelector('[data-insurer-number-input]'));
        bindReceiptRowCalculation(clonedRow);
        syncReceiptClaimAmount(clonedRow);
        saveReceiptRowAsync(pair.row, pair.detailRow, getReceiptPageRoot()?.getAttribute('data-receipt-save-url')).then(function (saved) {
            if (saved) {
                closeReceiptRow(pair.row, pair.detailRow);
                openReceiptRow(clonedRow, clonedDetailRow);
            } else {
                removeReceiptPair(clonedRow, clonedDetailRow);
                showReceiptInlineError('複製元の備考保存に失敗しました。元行を保存してから再度複製してください。');
            }
        });
    }

    function addBlankReceiptPair() {
        var tbody = document.querySelector('.data-table tbody');
        var templateRow = tbody?.querySelector('[data-receipt-template]') || tbody?.querySelector('[data-receipt-row]');
        var templateDetailRow = templateRow?.nextElementSibling;
        var emptyRow = tbody?.querySelector('[data-receipt-empty]');
        if (!tbody || !templateRow || !templateDetailRow || !templateDetailRow.hasAttribute('data-receipt-detail')) {
            return;
        }

        var newRow = templateRow.cloneNode(true);
        var newDetailRow = templateDetailRow.cloneNode(true);

        newRow.removeAttribute('data-receipt-template');
        newRow.hidden = false;
        newDetailRow.removeAttribute('data-receipt-template');
        newDetailRow.hidden = false;

        newRow.querySelectorAll('td').forEach(function (cell) {
            var displayOnly = cell.querySelector('.receipt-entry-readonly');
            if (displayOnly && !cell.querySelector('[data-receipt-editable-display]')) {
                displayOnly.textContent = '';
            }

            syncEditableDisplay(cell, 'blank');
        });

        clearDetailValues(newDetailRow);
        var treatmentMonthValue = getReceiptDetailValue(newDetailRow, RECEIPT_DETAIL_INDEX.treatmentMonth);
        if (treatmentMonthValue) {
            treatmentMonthValue.textContent = formatReceiptMonthEnd(getReceiptToolbarMonth());
        }

        var storeNameValue = getReceiptDetailValue(newDetailRow, RECEIPT_DETAIL_INDEX.storeName);
        if (storeNameValue) {
            storeNameValue.textContent = getReceiptToolbarStore();
        }

        if (emptyRow && emptyRow.parentNode) {
            emptyRow.parentNode.removeChild(emptyRow);
        }

        newRow.dataset.receiptDraft = '1';
        newRow.dataset.receiptClaimManual = '0';

        tbody.insertBefore(newDetailRow, tbody.firstChild);
        tbody.insertBefore(newRow, newDetailRow);

        bindInsurerLookup(newRow.querySelector('[data-insurer-number-input]'));
        bindReceiptRowCalculation(newRow);
        syncReceiptClaimAmount(newRow);
        syncReceiptInsurerDisplays(newRow.querySelector('[data-insurer-number-input]'));
        openReceiptRow(newRow, newDetailRow);
    }

    document.querySelectorAll('[data-insurer-number-input]').forEach(function (input) {
        bindInsurerLookup(input);
    });

    document.querySelectorAll('[data-receipt-row]').forEach(function (row) {
        var pair = getReceiptPair(row);
        if (!pair) {
            return;
        }

        storeReceiptSnapshot(pair.row, pair.detailRow);
        row.dataset.receiptClaimManual = '0';
        bindReceiptRowCalculation(row);
    });

    document.addEventListener('click', function (event) {
        var addButton = event.target.closest('[data-receipt-add]');
        if (addButton) {
            addBlankReceiptPair();
            return;
        }

        var duplicateButton = event.target.closest('[data-receipt-duplicate]');
        if (duplicateButton) {
            var detailRow = duplicateButton.closest('[data-receipt-detail]');
            duplicateReceiptPair(detailRow?.previousElementSibling);
            return;
        }

        var saveButton = event.target.closest('.receipt-entry-detail-actions > .btn.btn-primary');
        if (saveButton) {
            var saveDetailRow = saveButton.closest('[data-receipt-detail]');
            var saveRow = saveDetailRow?.previousElementSibling;
            var savePair = getReceiptPair(saveRow);
            var insurerNumberInput = saveRow?.querySelector('[data-insurer-number-input]');
            if (!savePair) {
                return;
            }

            if (!findReceiptInsurerByNumber(insurerNumberInput?.value ?? '')) {
                showReceiptInlineError('保険者番号は候補から選択してください');
                openReceiptRow(savePair.row, savePair.detailRow);
                return;
            }

            hideReceiptInlineError();
            submitReceiptRow(savePair.row, savePair.detailRow, getReceiptPageRoot()?.getAttribute('data-receipt-save-url'));
            return;
        }

        var detailActionButton = event.target.closest('.receipt-entry-detail-actions > .btn');
        if (detailActionButton) {
            var actionDetailRow = detailActionButton.closest('[data-receipt-detail]');
            var actionRow = actionDetailRow?.previousElementSibling;
            var actionPair = getReceiptPair(actionRow);
            if (!actionPair) {
                return;
            }

            var buttons = getReceiptActionButtons(actionPair.detailRow);
            var buttonIndex = buttons.indexOf(detailActionButton);

            if (buttonIndex === 2) {
                if (actionPair.row.dataset.receiptDraft === '1') {
                    removeReceiptPair(actionPair.row, actionPair.detailRow);
                    return;
                }

                restoreReceiptSnapshot(actionPair.row, actionPair.detailRow);
                closeReceiptRow(actionPair.row, actionPair.detailRow);
                return;
            }

            if (buttonIndex === 3) {
                if (actionPair.row.dataset.receiptDraft === '1' || !(actionPair.row.getAttribute('data-receipt-id') || '')) {
                    removeReceiptPair(actionPair.row, actionPair.detailRow);
                    return;
                }

                submitReceiptRow(actionPair.row, actionPair.detailRow, getReceiptPageRoot()?.getAttribute('data-receipt-delete-url'));
                return;
            }
        }

        var toggleButton = event.target.closest('[data-receipt-toggle]');
        if (!toggleButton) {
            return;
        }

        var row = toggleButton.closest('[data-receipt-row]');
        var detailRow = row?.nextElementSibling;
        if (!row || !detailRow || !detailRow.hasAttribute('data-receipt-detail')) {
            return;
        }

        var isOpen = toggleButton.getAttribute('aria-expanded') === 'true';
        if (isOpen) {
            closeReceiptRow(row, detailRow);
        } else {
            openReceiptRow(row, detailRow);
        }
    });
</script>
