<script>
    document.addEventListener('DOMContentLoaded', () => {
        const addPanel = document.querySelector('.history-add-panel');
        const addButton = document.querySelector('.js-history-add');
        const addCancelButton = document.querySelector('.js-history-add-cancel');

        const closeAllEdits = () => {
            document.querySelectorAll('[data-history-edit-row]').forEach((row) => {
                row.hidden = true;
            });
        };

        const closeAdd = () => {
            if (addPanel) {
                addPanel.hidden = true;
            }
        };

        document.querySelectorAll('.js-history-edit').forEach((button) => {
            button.addEventListener('click', () => {
                const id = button.dataset.historyEdit;
                const target = document.querySelector('[data-history-edit-row="' + id + '"]');
                if (!target) {
                    return;
                }

                const willOpen = target.hidden;
                closeAllEdits();
                closeAdd();
                target.hidden = !willOpen;
            });
        });

        document.querySelectorAll('.js-history-cancel').forEach((button) => {
            button.addEventListener('click', () => {
                const id = button.dataset.historyCancel;
                const target = document.querySelector('[data-history-edit-row="' + id + '"]');
                if (!target) {
                    return;
                }

                target.hidden = true;
            });
        });

        if (addButton && addPanel) {
            addButton.addEventListener('click', () => {
                const willOpen = addPanel.hidden;
                closeAllEdits();
                addPanel.hidden = !willOpen;
            });
        }

        if (addCancelButton) {
            addCancelButton.addEventListener('click', () => {
                closeAdd();
            });
        }
    });
</script>
