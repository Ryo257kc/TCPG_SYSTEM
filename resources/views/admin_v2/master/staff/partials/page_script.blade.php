<script>
function toggleStaffInfoEdit(editing) {
  const form = document.getElementById('staff-info-form');
  if (!form) return;

  form.classList.toggle('editing', editing);

  const view = form.querySelector('.staff-info-view');
  const edit = form.querySelector('.staff-info-edit');
  if (view) {
    view.hidden = editing;
    view.style.display = editing ? 'none' : 'block';
  }
  if (edit) {
    edit.hidden = !editing;
    edit.style.display = editing ? 'block' : 'none';
  }
}

document.addEventListener('DOMContentLoaded', () => {
  toggleStaffInfoEdit(false);
});
</script>
