<script>
(function(){
  var key = 'allowance_active_no';
  var rows = Array.prototype.slice.call(document.querySelectorAll('.allow-row[data-allowance-no]'));
  if (!rows.length) return;

  function apply(no){
    rows.forEach(function(r){
      r.classList.toggle('allow-active', (r.getAttribute('data-allowance-no') || '') === String(no || ''));
    });
  }

  var saved = sessionStorage.getItem(key);
  if (saved) apply(saved);

  rows.forEach(function(r){
    r.addEventListener('click', function(){
      var no = r.getAttribute('data-allowance-no') || '';
      sessionStorage.setItem(key, no);
      apply(no);
    });
  });
})();
</script>