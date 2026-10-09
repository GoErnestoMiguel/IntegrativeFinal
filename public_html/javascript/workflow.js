document.querySelectorAll('[data-select-resident]').forEach(button => button.addEventListener('click', () => {
 const field=document.querySelector('#review-resident-id'); field.value=button.dataset.selectResident;
 field.focus(); field.scrollIntoView({block:'center'});
}));
