document.addEventListener('click', function (event) {
  const trigger = event.target.closest('[data-confirm]');
  if (trigger && !window.confirm(trigger.dataset.confirm || 'Are you sure?')) event.preventDefault();
});
document.addEventListener('submit', function (event) {
  const form = event.target.closest('[data-confirm-submit]');
  if (form && !window.confirm(form.dataset.confirmSubmit || 'Continue with this action?')) event.preventDefault();
});
