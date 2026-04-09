(() => {
  const trigger = document.getElementById('print-trigger');
  if (!trigger) {
    return;
  }

  trigger.addEventListener('click', (event) => {
    event.preventDefault();
    if (typeof window.print === 'function') {
      window.print();
      return;
    }
    window.alert('この端末では印刷機能が利用できません。');
  });
})();

