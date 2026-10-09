document.querySelectorAll('[data-applicant-picker]').forEach(picker=>{
 const search=picker.querySelector('[data-picker-search]'),select=picker.querySelector('[data-picker-select]'),count=picker.querySelector('[data-picker-count]');
 const choices=[...select.options].slice(1).map(option=>({value:option.value,text:option.text}));
 picker.querySelector('.picker-search').hidden=false;
 search.addEventListener('input',()=>{
  const previous=select.value,query=search.value.trim().toLocaleLowerCase();
  const matches=choices.filter(option=>option.text.toLocaleLowerCase().includes(query));
  select.replaceChildren(new Option(matches.length?'Select a person':'No matching people',''));
  matches.forEach(option=>select.add(new Option(option.text,option.value)));
  select.value=matches.some(option=>option.value===previous)?previous:'';
  count.textContent=`${matches.length} ${matches.length===1?'person':'people'} found.`;
 });
});
