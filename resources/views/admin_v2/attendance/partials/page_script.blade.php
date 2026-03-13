<script>
(function(){
  var root = document.getElementById('attendance-main');
  if (!root) return;

  var shiftCreateBtn = document.getElementById('shift-create-btn');
  var inline = document.getElementById('attendance-shift-inline');
  var list = document.getElementById('attendance-shift-list');
  var empty = document.getElementById('attendance-shift-empty');
  var createBtn = document.getElementById('attendance-shift-create-submit-btn');
  var deleteBtn = document.getElementById('attendance-shift-delete-submit-btn');
  var selectAllBtn = document.getElementById('attendance-shift-select-all-btn');
  var clearBtn = document.getElementById('attendance-shift-clear-btn');
  var closeBtn = document.getElementById('attendance-shift-close-btn');

  var csrf = @json(csrf_token());
  var month = @json($selectedMonth);
  var companyId = @json((string) ($selectedCompanyId ?? ''));
  var candidatesUrl = @json(route('admin.attendance.shift-candidates'));
  var createUrl = @json(route('admin.attendance.create-shifts'));
  var deleteUrl = @json(route('admin.attendance.delete-shifts'));

  function openInline(){ if (inline) inline.hidden = false; }
  function closeInline(){ if (inline) inline.hidden = true; }
  function resetCandidates(){
    if (!list || !empty) return;
    list.innerHTML = '';
    list.hidden = true;
    empty.hidden = true;
  }

  function renderCandidates(candidates){
    if (!list || !empty) return;
    list.innerHTML = '';
    empty.hidden = candidates.length !== 0;
    list.hidden = candidates.length === 0;

    candidates.forEach(function(candidate){
      var wrapper = document.createElement('label');
      wrapper.className = 'create-inline-item' + (candidate.existing ? ' existing' : '');

      var check = document.createElement('input');
      check.type = 'checkbox';
      check.value = candidate.staff_id;
      check.className = 'attendance-shift-check';

      var main = document.createElement('div');
      main.className = 'create-inline-item-main';

      var title = document.createElement('div');
      title.className = 'create-inline-item-title';
      title.textContent = candidate.staff_id + ' ' + candidate.staff_name;

      main.appendChild(title);

      wrapper.appendChild(check);
      wrapper.appendChild(main);
      list.appendChild(wrapper);
    });
  }

  function selectedIds(){
    return Array.prototype.slice.call(document.querySelectorAll('.attendance-shift-check:checked')).map(function(check){
      return check.value;
    });
  }

  function loadCandidates(){
    fetch(candidatesUrl + '?month=' + encodeURIComponent(month) + '&company_id=' + encodeURIComponent(companyId), {
      headers: { 'Accept': 'application/json' }
    })
    .then(function(res){ return res.json(); })
    .then(function(json){
      if (!json || json.ok !== true) {
        throw new Error('candidates failed');
      }
      renderCandidates(json.candidates || []);
    })
    .catch(function(){
      alert('\u30b7\u30d5\u30c8\u5019\u88dc\u306e\u53d6\u5f97\u306b\u5931\u6557\u3057\u307e\u3057\u305f\u3002');
    });
  }

  function postAction(url, confirmMessage, fallbackMessage){
    var ids = selectedIds();
    if (list && list.children.length === 0) {
      alert('\u30b7\u30d5\u30c8\u5019\u88dc\u304c\u307e\u3060\u8aad\u307f\u8fbc\u307e\u308c\u3066\u3044\u307e\u305b\u3093\u3002');
      return;
    }
    if (ids.length === 0) {
      alert('\u30b9\u30bf\u30c3\u30d5\u3092\u9078\u629e\u3057\u3066\u304f\u3060\u3055\u3044\u3002');
      return;
    }
    if (confirmMessage && !window.confirm(confirmMessage)) {
      return;
    }

    fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json'
      },
      body: JSON.stringify({ month: month, staff_ids: ids })
    })
    .then(function(res){
      return res.json().then(function(json){ return { status: res.status, json: json }; });
    })
    .then(function(payload){
      var json = payload.json || {};
      if (payload.status >= 400 || json.ok !== true) {
        throw new Error(json.message || 'action failed');
      }
      closeInline();
      location.reload();
    })
    .catch(function(err){
      alert((err && err.message) ? err.message : fallbackMessage);
    });
  }

  if (shiftCreateBtn) {
    shiftCreateBtn.addEventListener('click', function(){
      openInline();
      resetCandidates();
      loadCandidates();
    });
  }
  if (closeBtn) closeBtn.addEventListener('click', closeInline);
  if (selectAllBtn) {
    selectAllBtn.addEventListener('click', function(){
      document.querySelectorAll('.attendance-shift-check').forEach(function(check){ check.checked = true; });
    });
  }
  if (clearBtn) {
    clearBtn.addEventListener('click', function(){
      document.querySelectorAll('.attendance-shift-check').forEach(function(check){ check.checked = false; });
    });
  }
  if (createBtn) {
    createBtn.addEventListener('click', function(){
      postAction(createUrl, '', '\u30b7\u30d5\u30c8\u4f5c\u6210\u306b\u5931\u6557\u3057\u307e\u3057\u305f\u3002');
    });
  }
  if (deleteBtn) {
    deleteBtn.addEventListener('click', function(){
      postAction(deleteUrl, '\u9078\u629e\u3057\u305f\u6708\u6b21\u30b7\u30d5\u30c8\u3092\u524a\u9664\u3057\u307e\u3059\u3002\u3088\u308d\u3057\u3044\u3067\u3059\u304b\uff1f', '\u30b7\u30d5\u30c8\u524a\u9664\u306b\u5931\u6557\u3057\u307e\u3057\u305f\u3002');
    });
  }
})();
</script>
