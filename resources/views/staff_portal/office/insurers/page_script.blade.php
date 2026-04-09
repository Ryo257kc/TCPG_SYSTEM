<script>
function initializeInsurersPage() {
    var LABEL_EDIT = '編集';
    var LABEL_SAVE = '保存';
    var LABEL_CANCEL = '取消';
    var LABEL_DELETE = '削除';
    var INSURANCE_TYPE_OPTIONS = ['区外国保', '一般国保', '協会けんぽ', '健保組合', '共済', '63退職', '67退職', '後期高齢', '医療助成'];
    var SUBSIDY_TYPE_OPTIONS = ['障害', '福祉', '母子', '子ども', '乳幼児', '水俣', '原爆', '一人親'];

    function getRows() {
        return document.querySelectorAll('[data-insurer-row]');
    }

    function getTemplateRow() {
        return document.querySelector('[data-insurer-template]');
    }

    function getTbody() {
        return document.querySelector('.data-table tbody');
    }

    function getInputs(row) {
        return row.querySelectorAll('[data-insurer-input]');
    }

    function getDisplays(row) {
        return row.querySelectorAll('[data-insurer-display]');
    }

    function getDisplayValue(input) {
        if (!input) {
            return '';
        }

        if (input.tagName === 'SELECT') {
            return input.options[input.selectedIndex] ? input.options[input.selectedIndex].text : '';
        }

        return input.value || '';
    }

    function snapshotRow(row) {
        var inputs = getInputs(row);
        var values = [];
        var i;

        for (i = 0; i < inputs.length; i += 1) {
            if (inputs[i].tagName === 'SELECT') {
                values.push(String(inputs[i].selectedIndex));
            } else {
                values.push(inputs[i].value || '');
            }
        }

        row.setAttribute('data-insurer-snapshot', JSON.stringify(values));
    }

    function restoreRow(row) {
        var raw = row.getAttribute('data-insurer-snapshot') || '[]';
        var values = [];
        var inputs = getInputs(row);
        var i;

        try {
            values = JSON.parse(raw);
        } catch (error) {
            values = [];
        }

        for (i = 0; i < inputs.length; i += 1) {
            if (inputs[i].tagName === 'SELECT') {
                inputs[i].selectedIndex = Number(values[i] || 0);
            } else {
                inputs[i].value = values[i] || '';
            }
        }
    }

    function syncRowDisplays(row) {
        var displays = getDisplays(row);
        var inputs = getInputs(row);
        var i;

        for (i = 0; i < inputs.length; i += 1) {
            if (displays[i]) {
                displays[i].textContent = getDisplayValue(inputs[i]);
            }
        }
    }

    function setEditing(row, isEditing) {
        var displays = getDisplays(row);
        var inputs = getInputs(row);
        var i;

        row.setAttribute('data-is-editing', isEditing ? '1' : '0');

        for (i = 0; i < displays.length; i += 1) {
            displays[i].style.display = isEditing ? 'none' : '';
        }

        for (i = 0; i < inputs.length; i += 1) {
            inputs[i].style.display = isEditing ? 'block' : 'none';
        }

        var editButton = row.querySelector('[data-insurer-edit]');
        var saveButton = row.querySelector('[data-insurer-save]');
        var cancelButton = row.querySelector('[data-insurer-cancel]');
        var deleteButton = row.querySelector('[data-insurer-delete]');

        if (editButton) {
            editButton.style.display = isEditing ? 'none' : '';
        }
        if (saveButton) {
            saveButton.style.display = isEditing ? '' : 'none';
        }
        if (cancelButton) {
            cancelButton.style.display = isEditing ? '' : 'none';
        }
        if (deleteButton) {
            deleteButton.style.display = isEditing ? '' : 'none';
        }
    }

    function buildActionButtons(row) {
        var actionCell = row.lastElementChild;
        var wrapper;
        var editButton;
        var saveButton;
        var cancelButton;
        var deleteButton;

        if (!actionCell) {
            return;
        }

        actionCell.innerHTML = '';

        wrapper = document.createElement('div');
        wrapper.className = 'insurer-row-actions';

        editButton = document.createElement('button');
        editButton.type = 'button';
        editButton.className = 'btn';
        editButton.setAttribute('data-insurer-edit', '1');
        editButton.textContent = LABEL_EDIT;

        saveButton = document.createElement('button');
        saveButton.type = 'button';
        saveButton.className = 'btn btn-primary';
        saveButton.setAttribute('data-insurer-save', '1');
        saveButton.textContent = LABEL_SAVE;

        cancelButton = document.createElement('button');
        cancelButton.type = 'button';
        cancelButton.className = 'btn';
        cancelButton.setAttribute('data-insurer-cancel', '1');
        cancelButton.textContent = LABEL_CANCEL;

        deleteButton = document.createElement('button');
        deleteButton.type = 'button';
        deleteButton.className = 'btn';
        deleteButton.setAttribute('data-insurer-delete', '1');
        deleteButton.textContent = LABEL_DELETE;

        wrapper.appendChild(editButton);
        wrapper.appendChild(saveButton);
        wrapper.appendChild(cancelButton);
        wrapper.appendChild(deleteButton);
        actionCell.appendChild(wrapper);
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function buildSelectHtml(options) {
        var html = '<option value=""></option>';
        var i;

        for (i = 0; i < options.length; i += 1) {
            html += '<option value="' + escapeHtml(options[i]) + '">' + escapeHtml(options[i]) + '</option>';
        }

        return html;
    }

    function initializeRow(row) {
        var shouldEdit;

        if (!row || row.hasAttribute('data-insurer-template')) {
            return;
        }

        shouldEdit = row.getAttribute('data-is-editing') === '1';
        buildActionButtons(row);
        syncRowDisplays(row);
        snapshotRow(row);
        setEditing(row, shouldEdit);
    }

    function createNewRow() {
        var tbody = getTbody();
        var newRow;
        var sourceRow;
        var firstRow;
        var emptyRow;
        var displays;
        var inputs;
        var i;

        if (!tbody) {
            return false;
        }

        firstRow = tbody.querySelector('[data-insurer-row]');
        sourceRow = firstRow || getTemplateRow();
        if (!sourceRow) {
            return false;
        }

        newRow = sourceRow.cloneNode(true);
        newRow.removeAttribute('hidden');
        newRow.removeAttribute('data-insurer-template');
        newRow.setAttribute('data-insurer-row', '');
        newRow.setAttribute('data-is-new', '1');

        displays = getDisplays(newRow);
        for (i = 0; i < displays.length; i += 1) {
            displays[i].textContent = '';
            displays[i].style.display = 'none';
        }

        inputs = getInputs(newRow);
        for (i = 0; i < inputs.length; i += 1) {
            inputs[i].hidden = false;
            inputs[i].style.display = 'block';
            if (inputs[i].tagName === 'SELECT') {
                inputs[i].selectedIndex = 0;
            } else {
                inputs[i].value = '';
            }
        }

        if (firstRow) {
            tbody.insertBefore(newRow, firstRow);
        } else {
            tbody.appendChild(newRow);
        }

        buildActionButtons(newRow);
        snapshotRow(newRow);
        newRow.setAttribute('data-is-editing', '1');
        var editButton = newRow.querySelector('[data-insurer-edit]');
        var saveButton = newRow.querySelector('[data-insurer-save]');
        var cancelButton = newRow.querySelector('[data-insurer-cancel]');
        var deleteButton = newRow.querySelector('[data-insurer-delete]');
        if (editButton) editButton.style.display = 'none';
        if (saveButton) saveButton.style.display = '';
        if (cancelButton) cancelButton.style.display = '';
        if (deleteButton) deleteButton.style.display = '';
        return false;
    }

    function findButtonRow(target, attrName) {
        var button = target.closest('[' + attrName + ']');
        if (!button) {
            return null;
        }

        return button.closest('[data-insurer-row]');
    }

    function getPageRoot() {
        return document.querySelector('[data-insurer-save-url]');
    }

    function submitRow(row, url, originalRequired) {
        var root = getPageRoot();
        var inputs = getInputs(row);
        var form;
        var csrfInput;
        var originalInput;
        var fieldNames = [
            'insurer_number',
            'insurer_name',
            'scheduled_payment_name',
            'scheduled_payment_name_2',
            'insurance_type',
            'subsidy_type'
        ];
        var i;
        var hiddenInput;

        if (!root || !url) {
            return;
        }

        form = document.createElement('form');
        form.method = 'post';
        form.action = url;
        form.style.display = 'none';

        csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = root.getAttribute('data-insurer-csrf') || '';
        form.appendChild(csrfInput);

        originalInput = document.createElement('input');
        originalInput.type = 'hidden';
        originalInput.name = 'original_insurer_number';
        originalInput.value = row.getAttribute('data-original-insurer-number') || '';
        form.appendChild(originalInput);

        if (originalRequired && !originalInput.value) {
            return;
        }

        for (i = 0; i < fieldNames.length; i += 1) {
            hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = fieldNames[i];
            hiddenInput.value = inputs[i] ? (inputs[i].value || '') : '';
            form.appendChild(hiddenInput);
        }

        document.body.appendChild(form);
        form.submit();
    }

    Array.prototype.forEach.call(getRows(), function (row) {
        initializeRow(row);
    });

    document.addEventListener('click', function (event) {
        var root = getPageRoot();
        var addButton = event.target.closest('[data-insurer-add]');
        if (addButton) {
            event.preventDefault();
            createNewRow();
            return;
        }

        var editRow = findButtonRow(event.target, 'data-insurer-edit');
        if (editRow) {
            event.preventDefault();
            snapshotRow(editRow);
            setEditing(editRow, true);
            return;
        }

        var saveRow = findButtonRow(event.target, 'data-insurer-save');
        if (saveRow) {
            event.preventDefault();
            submitRow(saveRow, root ? root.getAttribute('data-insurer-save-url') : '', false);
            return;
        }

        var cancelRow = findButtonRow(event.target, 'data-insurer-cancel');
        if (cancelRow) {
            event.preventDefault();
            if (cancelRow.getAttribute('data-is-new') === '1') {
                cancelRow.remove();
                return;
            }

            restoreRow(cancelRow);
            syncRowDisplays(cancelRow);
            setEditing(cancelRow, false);
            return;
        }

        var deleteRow = findButtonRow(event.target, 'data-insurer-delete');
        if (deleteRow) {
            event.preventDefault();
            if (deleteRow.getAttribute('data-is-new') === '1') {
                deleteRow.remove();
                return;
            }

            submitRow(deleteRow, root ? root.getAttribute('data-insurer-delete-url') : '', true);
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeInsurersPage);
} else {
    initializeInsurersPage();
}
</script>
