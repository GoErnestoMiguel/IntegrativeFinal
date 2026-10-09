(() => {
 const button=document.querySelector('#get-otp'); if(!button)return;
 const form=button.form, email=form.elements.email, code=form.elements.email_otp, status=document.querySelector('#otp-status');
 email.addEventListener('input',()=>{code.value='';status.textContent='After changing your email, request a new code.';});
 button.addEventListener('click',async()=>{
  if(!email.reportValidity())return;
  button.disabled=true;button.textContent='Sending…';status.textContent='Sending your verification code…';
  try {
   const response=await fetch('send-otp.php',{method:'POST',body:new URLSearchParams({email:email.value,form_token:form.elements.form_token.value})});
   const data=await response.json();status.textContent=data.message;
   if(response.ok){code.focus();let remaining=60;button.textContent=`Resend in ${remaining}s`;const timer=setInterval(()=>{remaining--;button.textContent=`Resend in ${remaining}s`;if(remaining<=0){clearInterval(timer);button.disabled=false;button.textContent='Get OTP';}},1000);return;}
  } catch {status.textContent='Unable to request a code. Check your connection and try again.';}
  button.disabled=false;button.textContent='Get OTP';
 });
})();
