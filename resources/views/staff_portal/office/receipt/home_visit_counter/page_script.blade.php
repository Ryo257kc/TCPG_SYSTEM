<script>
    document.addEventListener('DOMContentLoaded', function() {
        var body = document.querySelector('[data-home-visit-body]');
        var saveForm = document.getElementById('home-visit-save-form');
        var deleteForm = document.getElementById('home-visit-delete-form');
        var rowTemplate = document.getElementById('home-visit-row-template');
        var detailTemplate = document.getElementById('home-visit-detail-template');
        var targetMonthInput = document.getElementById('home-visit-target-month');
        var storeSelect = document.getElementById('receipt-store');
        var patientNameInput = document.getElementById('home-visit-patient-name');

        if (!body || !saveForm || !deleteForm || !rowTemplate || !detailTemplate) {
            return;
        }

        function formatTargetMonthEnd(value) {
            if (!/^\d{4}-\d{2}$/.test(value || '')) {
                return '';
            }

            var parts = value.split('-');
            var year = Number(parts[0]);
            var month = Number(parts[1]);
            var lastDate = new Date(year, month, 0);
            return String(lastDate.getFullYear()) + '/' + String(lastDate.getMonth() + 1).padStart(2, '0') + '/' + String(lastDate.getDate()).padStart(2, '0');
        }

        function getRowPair(row) {
            var detailRow = row ? row.nextElementSibling : null;
            if (!row || !detailRow || !detailRow.hasAttribute('data-home-visit-detail')) {
                return null;
            }

            return {
                row: row,
                detailRow: detailRow
            };
        }

        function getField(row, fieldName) {
            return row.querySelector('[data-home-visit-field="' + fieldName + '"]');
        }

        function getDisplay(row, fieldName) {
            return row.querySelector('[data-home-visit-display="' + fieldName + '"]');
        }

        function getDetailDisplay(detailRow, fieldName) {
            return detailRow.querySelector('[data-home-visit-detail-display="' + fieldName + '"]');
        }

        function getFieldValue(row, fieldName) {
            var field = getField(row, fieldName);
            return field ? (field.value || '') : '';
        }

        function getFieldText(row, fieldName) {
            var field = getField(row, fieldName);
            if (!field) {
                return '';
            }

            if (field.tagName === 'SELECT') {
                return field.options[field.selectedIndex] ? field.options[field.selectedIndex].text : '';
            }

            return field.value || '';
        }

        function snapshotRow(row) {
            row.dataset.homeVisitSnapshot = JSON.stringify({
                deposit_name: getFieldValue(row, 'deposit_name'),
                patient_name: getFieldValue(row, 'patient_name'),
                payment_date_text: getFieldValue(row, 'payment_date_text'),
                payment_amount: getFieldValue(row, 'payment_amount'),
                cash_collected_amount: getFieldValue(row, 'cash_collected_amount'),
                staff_name: getFieldValue(row, 'staff_name'),
                remarks: getFieldValue(row, 'remarks')
            });
        }

        function restoreRow(row) {
            if (!row.dataset.homeVisitSnapshot) {
                return;
            }

            var snapshot = JSON.parse(row.dataset.homeVisitSnapshot);
            Object.keys(snapshot).forEach(function(fieldName) {
                var field = getField(row, fieldName);
                if (field) {
                    field.value = snapshot[fieldName] || '';
                }
            });
        }

        function syncRowDisplays(row) {
            var depositDisplay = getDisplay(row, 'deposit_name');
            if (depositDisplay) {
                depositDisplay.textContent = getFieldText(row, 'deposit_name');
            }

            var patientDisplay = getDisplay(row, 'patient_name');
            if (patientDisplay) {
                patientDisplay.textContent = getFieldText(row, 'patient_name');
            }

            var payment_date_text_display = getDisplay(row, 'payment_date_text');
            var payment_date_text_field = getField(row, 'payment_date_text');
            if (payment_date_text_display && payment_date_text_field) {
                payment_date_text_display.textContent = getFieldText(row, 'payment_date_text');
            }

            var payment_amount_display = getDisplay(row, 'payment_amount');
            var payment_amount_field = getField(row, 'payment_amount');
            if (payment_amount_display && payment_amount_field) {
                payment_amount_display.textContent = getFieldText(row, 'payment_amount');
            }

            var cashDisplay = getDisplay(row, 'cash_collected_amount');
            if (cashDisplay) {
                cashDisplay.textContent = getFieldText(row, 'cash_collected_amount');
            }

            var staffDisplay = getDisplay(row, 'staff_name');
            if (staffDisplay) {
                staffDisplay.textContent = getFieldText(row, 'staff_name');
            }

            var remarksDisplay = getDisplay(row, 'remarks');
            if (remarksDisplay) {
                remarksDisplay.textContent = getFieldText(row, 'remarks');
            }
        }

        function setEditing(row, editing) {
            var pair = getRowPair(row);
            if (!pair) {
                return;
            }

            var toggle = row.querySelector('[data-home-visit-toggle]');
            row.classList.toggle('is-editing', editing);
            pair.detailRow.hidden = !editing;
            if (toggle) {
                toggle.setAttribute('aria-expanded', editing ? 'true' : 'false');
                toggle.textContent = editing ? '▴' : '▾';
            }
        }

        function removeEmptyRow() {
            var emptyRow = body.querySelector('[data-home-visit-empty]');
            if (emptyRow) {
                emptyRow.remove();
            }
        }

        function ensureEmptyRow() {
            if (body.querySelector('[data-home-visit-row]') || body.querySelector('[data-home-visit-empty]')) {
                return;
            }

            var emptyRow = document.createElement('tr');
            emptyRow.setAttribute('data-home-visit-empty', '');
            emptyRow.innerHTML = '<td colspan="10">対象データがありません。</td>';
            body.appendChild(emptyRow);
        }

        function populateSaveForm(row) {
            saveForm.querySelector('[name="insurance_claim_detail_id"]').value = row.dataset.homeVisitId || '';
            saveForm.querySelector('[name="target_month"]').value = targetMonthInput ? targetMonthInput.value : '';
            if (saveForm.querySelector('[name="row_treatment_month"]')) {
                saveForm.querySelector('[name="row_treatment_month"]').value = row.dataset.homeVisitTreatmentMonth || '';
            }
            saveForm.querySelector('[name="store_name"]').value = row.dataset.homeVisitStore || (storeSelect ? storeSelect.value : '');
            if (saveForm.querySelector('[name="filter_store_name"]')) {
                saveForm.querySelector('[name="filter_store_name"]').value = storeSelect ? storeSelect.value : '';
            }
            if (saveForm.querySelector('[name="filter_patient_name"]')) {
                saveForm.querySelector('[name="filter_patient_name"]').value = patientNameInput ? patientNameInput.value : '';
            }
            saveForm.querySelector('[name="deposit_name"]').value = getFieldValue(row, 'deposit_name');
            saveForm.querySelector('[name="patient_name"]').value = getFieldValue(row, 'patient_name');
            if (saveForm.querySelector('[name="payment_date_text"]')) {
                saveForm.querySelector('[name="payment_date_text"]').value = getFieldValue(row, 'payment_date_text');
            }
            if (saveForm.querySelector('[name="payment_amount"]')) {
                saveForm.querySelector('[name="payment_amount"]').value = getFieldValue(row, 'payment_amount');
            }
            saveForm.querySelector('[name="cash_collected_amount"]').value = getFieldValue(row, 'cash_collected_amount');
            saveForm.querySelector('[name="staff_name"]').value = getFieldValue(row, 'staff_name');
            saveForm.querySelector('[name="remarks"]').value = getFieldValue(row, 'remarks');
        }

        function populateDeleteForm(row) {
            deleteForm.querySelector('[name="insurance_claim_detail_id"]').value = row.dataset.homeVisitId || '';
            deleteForm.querySelector('[name="target_month"]').value = targetMonthInput ? targetMonthInput.value : '';
            deleteForm.querySelector('[name="store_name"]').value = row.dataset.homeVisitStore || (storeSelect ? storeSelect.value : '');
            if (deleteForm.querySelector('[name="filter_store_name"]')) {
                deleteForm.querySelector('[name="filter_store_name"]').value = storeSelect ? storeSelect.value : '';
            }
            if (deleteForm.querySelector('[name="filter_patient_name"]')) {
                deleteForm.querySelector('[name="filter_patient_name"]').value = patientNameInput ? patientNameInput.value : '';
            }
        }

        function createNewRow() {
            removeEmptyRow();

            var newRow = rowTemplate.content.firstElementChild.cloneNode(true);
            var newDetailRow = detailTemplate.content.firstElementChild.cloneNode(true);
            var storeName = storeSelect ? storeSelect.value : '';
            var treatmentMonth = formatTargetMonthEnd(targetMonthInput ? targetMonthInput.value : '');

            newRow.dataset.homeVisitStore = storeName;
            newRow.dataset.homeVisitTreatmentMonth = treatmentMonth;

            var storeDisplay = getDisplay(newRow, 'store_name');
            if (storeDisplay) {
                storeDisplay.textContent = storeName;
            }

            var treatmentMonthDisplay = getDetailDisplay(newDetailRow, 'treatment_month');
            if (treatmentMonthDisplay) {
                treatmentMonthDisplay.textContent = treatmentMonth;
            }

            body.insertBefore(newDetailRow, body.firstChild);
            body.insertBefore(newRow, newDetailRow);

            snapshotRow(newRow);
            setEditing(newRow, true);
        }

        document.addEventListener('click', function(event) {
            var addButton = event.target.closest('[data-home-visit-add]');
            if (addButton) {
                event.preventDefault();
                createNewRow();
                return;
            }

            var toggleButton = event.target.closest('[data-home-visit-toggle]');
            if (toggleButton) {
                event.preventDefault();
                var toggleRow = toggleButton.closest('[data-home-visit-row]');
                if (!toggleRow) {
                    return;
                }

                if (toggleRow.classList.contains('is-editing')) {
                    if (toggleRow.dataset.homeVisitDraft === '1') {
                        var draftPair = getRowPair(toggleRow);
                        if (draftPair) {
                            draftPair.detailRow.remove();
                        }
                        toggleRow.remove();
                        ensureEmptyRow();
                        return;
                    }

                    restoreRow(toggleRow);
                    syncRowDisplays(toggleRow);
                    setEditing(toggleRow, false);
                    return;
                }

                snapshotRow(toggleRow);
                setEditing(toggleRow, true);
                return;
            }

            var saveButton = event.target.closest('[data-home-visit-save]');
            if (saveButton) {
                event.preventDefault();
                var saveDetailRow = saveButton.closest('[data-home-visit-detail]');
                var saveRow = saveDetailRow ? saveDetailRow.previousElementSibling : null;
                if (!saveRow || !saveRow.hasAttribute('data-home-visit-row')) {
                    return;
                }

                populateSaveForm(saveRow);
                saveForm.submit();
                return;
            }

            var cancelButton = event.target.closest('[data-home-visit-cancel]');
            if (cancelButton) {
                event.preventDefault();
                var cancelDetailRow = cancelButton.closest('[data-home-visit-detail]');
                var cancelRow = cancelDetailRow ? cancelDetailRow.previousElementSibling : null;
                if (!cancelRow || !cancelRow.hasAttribute('data-home-visit-row')) {
                    return;
                }

                if (cancelRow.dataset.homeVisitDraft === '1') {
                    cancelDetailRow.remove();
                    cancelRow.remove();
                    ensureEmptyRow();
                    return;
                }

                restoreRow(cancelRow);
                syncRowDisplays(cancelRow);
                setEditing(cancelRow, false);
                return;
            }

            var deleteButton = event.target.closest('[data-home-visit-delete]');
            if (deleteButton) {
                event.preventDefault();
                var deleteDetailRow = deleteButton.closest('[data-home-visit-detail]');
                var deleteRow = deleteDetailRow ? deleteDetailRow.previousElementSibling : null;
                if (!deleteRow || !deleteRow.hasAttribute('data-home-visit-row')) {
                    return;
                }

                if (!window.confirm('削除しますか？')) {
                    return;
                }

                if (deleteRow.dataset.homeVisitDraft === '1') {
                    deleteDetailRow.remove();
                    deleteRow.remove();
                    ensureEmptyRow();
                    return;
                }

                populateDeleteForm(deleteRow);
                deleteForm.submit();
            }
        });
    });
</script>
