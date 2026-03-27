<script>
function toggleStoreEdit(editing) {
  const panel = document.getElementById('store-detail-panel');
  if (!panel) return;
  panel.classList.toggle('editing', editing);
}
</script>
