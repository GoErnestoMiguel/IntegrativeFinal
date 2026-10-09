document.querySelectorAll('[data-preview-scroll]').forEach(button => {
 button.addEventListener('click', () => {
  const preview = document.querySelector('.import-preview-scroll');
  if (!preview) return;
  preview.scrollTop = button.dataset.previewScroll === 'last' ? preview.scrollHeight : 0;
  preview.focus({preventScroll:true});
 });
});
