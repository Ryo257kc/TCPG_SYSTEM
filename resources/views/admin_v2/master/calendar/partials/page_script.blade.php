<script>
document.querySelectorAll('.calendar-row').forEach((row) => {
    const editButton = row.querySelector('[data-action="edit"]');
    const cancelButton = row.querySelector('[data-action="cancel"]');
    const fields = row.querySelectorAll('[data-original]');

    if (editButton) {
        editButton.addEventListener('click', () => {
            row.classList.add('is-editing');
        });
    }

    if (cancelButton) {
        cancelButton.addEventListener('click', () => {
            fields.forEach((field) => {
                field.value = field.dataset.original ?? '';
            });
            row.classList.remove('is-editing');
        });
    }
});
</script>