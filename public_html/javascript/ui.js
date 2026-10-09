// Small, progressive enhancements. Navigation and forms work without animation.
(() => {
 const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
 const animate = element => {
  if (!reduced.matches && element.animate) element.animate([{opacity:0,transform:'translateY(6px)'},{opacity:1,transform:'none'}],{duration:180,easing:'ease-out'});
 };
 document.querySelectorAll('.application-step,.check-question').forEach(step => {
  new MutationObserver(() => { if (!step.hidden) animate(step); }).observe(step,{attributes:true,attributeFilter:['hidden']});
 });
 document.querySelectorAll('details').forEach(detail => detail.addEventListener('toggle',() => { if(detail.open) detail.querySelectorAll('p,dl').forEach(animate); }));
 const nav=document.querySelector('#main-nav'), menu=document.querySelector('.menu-button');
 const close=()=>{nav?.classList.remove('open');menu?.setAttribute('aria-expanded','false');};
 document.addEventListener('keydown',event=>{if(event.key==='Escape' && nav?.classList.contains('open')){close();menu.focus();}});
 window.matchMedia('(min-width:851px)').addEventListener('change',close);
 nav?.querySelectorAll('a').forEach(link=>link.addEventListener('click',close));
})();
