<script>
function toggleStoreEdit(editing) {
  const panel = document.getElementById('store-detail-panel');
  if (!panel) return;
  panel.classList.toggle('editing', editing);
}

function selectFreeeDepartment(value) {
  toggleStoreEdit(true);
  const input = document.querySelector('input[name="freee_department_name"]');
  if (!input) return;
  input.value = value;
  input.focus();
}
</script>
