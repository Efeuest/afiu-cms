(() => {
  const sidebar=document.querySelector('#sidebar'),backdrop=document.querySelector('[data-sidebar-backdrop]');
  const setOpen=v=>{document.body.classList.toggle('sidebar-open',v);};
  document.querySelector('[data-open-sidebar]')?.addEventListener('click',()=>setOpen(true));
  document.querySelector('[data-close-sidebar]')?.addEventListener('click',()=>setOpen(false));backdrop?.addEventListener('click',()=>setOpen(false));
  document.querySelectorAll('form[data-confirm]').forEach(f=>f.addEventListener('submit',e=>{if(!confirm(f.dataset.confirm||'Are you sure?'))e.preventDefault();}));
  document.querySelectorAll('[data-copy]').forEach(el=>el.addEventListener('click',async()=>{try{await navigator.clipboard.writeText(location.origin+el.dataset.copy);el.classList.add('copied');setTimeout(()=>el.classList.remove('copied'),900);}catch{}}));
  const title=document.querySelector('[data-title-input]'),slug=document.querySelector('[data-slug-input]');let slugTouched=!!slug?.value;
  slug?.addEventListener('input',()=>slugTouched=true);title?.addEventListener('input',()=>{if(!slug||slugTouched)return;slug.value=title.value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');});
  document.querySelectorAll('[data-count-target]').forEach(el=>{const out=document.querySelector(`[data-count-for="${el.dataset.countTarget}"]`);const max=el.dataset.countTarget==='seo_title'?60:160;const update=()=>{if(out)out.textContent=`${el.value.length}/${max}`;};el.addEventListener('input',update);update();});
  const body=document.querySelector('[data-editor-body]');document.querySelectorAll('[data-editor-toolbar] button').forEach(btn=>btn.addEventListener('click',()=>{if(!body)return;const s=body.selectionStart,e=body.selectionEnd,text=body.value.slice(s,e)||'text';let insert=text;if(btn.dataset.tag)insert=`<${btn.dataset.tag}>${text}</${btn.dataset.tag}>`;if(btn.dataset.wrap)insert=`<${btn.dataset.wrap}>${text}</${btn.dataset.wrap}>`;if(btn.hasAttribute('data-link')){const url=prompt('Link URL','https://');if(!url)return;insert=`<a href="${url}">${text}</a>`;}body.setRangeText(insert,s,e,'end');body.focus();}));
})();
