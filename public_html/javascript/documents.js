document.querySelectorAll('form').forEach(form => {
 const inputs=[...form.querySelectorAll('[data-document-upload]')];
 if(!inputs.length)return;
 const count=document.createElement('input');count.type='hidden';count.name='document_count';form.append(count);
 const summary=form.querySelector('[data-document-summary]');
 function update() {
  let photos=0,bytes=0;
  inputs.forEach(input=>{
   let error='';
   for(const file of input.files) {
    photos++;bytes+=file.size;
    if(!/\.(jpe?g|png)$/i.test(file.name))error='Choose JPG or PNG photos only. PDFs and HEIC are not accepted.';
    else if(!file.size || file.size>5*1024*1024)error='Each photo must be between 1 byte and 5 MB.';
   }
   input.setCustomValidity(error);
  });
  count.value=String(photos);
  const batchError=photos>8?'Select at most 8 photos per submission.':bytes>20*1024*1024?'Keep all selected photos together within 20 MB.':'';
  if(batchError)inputs[0].setCustomValidity(batchError);
  if(summary)summary.textContent=`${photos} photo(s) selected · ${(bytes/1024/1024).toFixed(1)} MB. ${batchError || 'Limit: 8 photos and 20 MB per submission; 40 photos per application. Add more through My Profile.'}`;
 }
 inputs.forEach(input=>input.addEventListener('change',()=>{update();if(!input.checkValidity())input.reportValidity();}));
 form.addEventListener('submit',event=>{update();if(!inputs.every(input=>input.checkValidity())){event.preventDefault();inputs.find(input=>!input.checkValidity()).reportValidity();}});
 update();
});
