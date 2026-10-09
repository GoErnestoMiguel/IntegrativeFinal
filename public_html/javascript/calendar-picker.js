// Keep native date inputs for typing, validation and form submission.
(() => {
 const fields = document.querySelectorAll('input[type="date"]');
 if (!fields.length || !window.HTMLDialogElement) return;
 const dialog = document.createElement('dialog');
 dialog.className = 'date-calendar';
 dialog.setAttribute('aria-labelledby', 'date-calendar-title');
 dialog.innerHTML = `<div class="date-calendar-title"><h2 id="date-calendar-title">Choose a date</h2><button type="button" class="date-close" aria-label="Close calendar">×</button></div><div class="date-calendar-controls"><button type="button" class="date-prev" aria-label="Previous month">‹</button><label>Month<select class="date-month"></select></label><label>Year<input class="date-year" type="number" min="1" max="9999"></label><button type="button" class="date-next" aria-label="Next month">›</button></div><p class="date-month-label" aria-live="polite"></p><div class="date-weekdays" aria-hidden="true"><span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span></div><div class="date-days" role="group" aria-label="Days of the month"></div><div class="date-calendar-footer"><button type="button" class="date-today">Today</button><button type="button" class="date-clear">Clear date</button></div>`;
 document.body.append(dialog);
 const month = dialog.querySelector('.date-month'), year = dialog.querySelector('.date-year'), days = dialog.querySelector('.date-days');
 const names = Array.from({length:12}, (_,i)=>new Date(2020,i,1).toLocaleString('en',{month:'long'}));
 names.forEach((name,i)=>month.add(new Option(name,i)));
 let active, trigger, shown;
 const iso = d => `${String(d.getFullYear()).padStart(4,'0')}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
 const allowed = value => (!active.min || value >= active.min) && (!active.max || value <= active.max);
 const choose = value => { active.value=value; active.dispatchEvent(new Event('input',{bubbles:true})); active.dispatchEvent(new Event('change',{bubbles:true})); dialog.close(); };
 function render() {
  month.value=shown.getMonth(); year.value=shown.getFullYear();
  dialog.querySelector('.date-month-label').textContent=`${names[shown.getMonth()]} ${shown.getFullYear()}`;
  days.replaceChildren();
  for(let i=0;i<shown.getDay();i++) days.append(document.createElement('span'));
  const count=new Date(shown.getFullYear(),shown.getMonth()+1,0).getDate();
  for(let n=1;n<=count;n++) {
   const date=new Date(shown.getFullYear(),shown.getMonth(),n), value=iso(date), button=document.createElement('button');
   button.type='button'; button.textContent=n; button.disabled=!allowed(value);
   button.setAttribute('aria-label',date.toLocaleDateString('en',{month:'long',day:'numeric',year:'numeric'}));
   button.setAttribute('aria-pressed',String(value===active.value));
   if(value===iso(new Date())) button.setAttribute('aria-current','date');
   button.addEventListener('click',()=>choose(value)); days.append(button);
  }
  dialog.querySelector('.date-today').disabled=!allowed(iso(new Date()));
 }
 fields.forEach(field=>{
  const wrap=document.createElement('span'); wrap.className='date-input-wrap'; field.before(wrap);wrap.append(field);
  const button=document.createElement('button');button.type='button';button.className='date-open';button.setAttribute('aria-label','Open calendar');button.setAttribute('aria-haspopup','dialog');button.disabled=field.disabled||field.readOnly;
  button.innerHTML='<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M16 3v4M8 3v4M3 11h18"/></svg>';wrap.append(button);
  button.addEventListener('click',()=>{
   active=field;trigger=button;
   let value=field.value || iso(new Date()); if(field.max && value>field.max)value=field.max;if(field.min && value<field.min)value=field.min;
   shown=new Date(value+'T12:00:00');shown.setDate(1);render();dialog.showModal();
   (days.querySelector('[aria-pressed="true"]')||days.querySelector('button:not(:disabled)')||month).focus();
  });
 });
 dialog.querySelector('.date-close').onclick=()=>dialog.close();
 dialog.addEventListener('close',()=>trigger?.focus());
 dialog.addEventListener('click',e=>{if(e.target===dialog){const r=dialog.getBoundingClientRect();if(e.clientX<r.left||e.clientX>r.right||e.clientY<r.top||e.clientY>r.bottom)dialog.close();}});
 month.onchange=()=>{shown.setMonth(Number(month.value));render();};
 year.onchange=()=>{if(year.checkValidity() && year.value){shown.setFullYear(Number(year.value));render();}};
 const move=offset=>{const next=new Date(shown);next.setMonth(next.getMonth()+offset);if(next.getFullYear()>0&&next.getFullYear()<10000){shown=next;render();}};
 dialog.querySelector('.date-prev').onclick=()=>move(-1);dialog.querySelector('.date-next').onclick=()=>move(1);
 dialog.querySelector('.date-today').onclick=()=>choose(iso(new Date()));dialog.querySelector('.date-clear').onclick=()=>choose('');
 days.addEventListener('keydown',e=>{
  const offsets={ArrowLeft:-1,ArrowRight:1,ArrowUp:-7,ArrowDown:7};if(!(e.key in offsets))return;
  const buttons=[...days.querySelectorAll('button')], index=buttons.indexOf(document.activeElement), target=buttons[index+offsets[e.key]];
  if(target&&!target.disabled){e.preventDefault();target.focus();}
 });
})();
