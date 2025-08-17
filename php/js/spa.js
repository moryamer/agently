// js/spa.js
// SPA navigation for internal UI pages. Add class="spa-link" to <a> links you want to hijack.
(function(){
  function executeScripts(root){
    const scripts = Array.from(root.querySelectorAll('script'));
    for (const old of scripts){
      const s = document.createElement('script');
      if (old.src){
        s.src = old.src;
        if (old.type) s.type = old.type;
        if (old.defer) s.defer = true;
        if (old.async) s.async = true;
      } else {
        s.textContent = old.textContent;
      }
      old.parentNode && old.parentNode.removeChild(old);
      document.body.appendChild(s);
    }
  }

  async function navigateTo(url, replaceState=false){
    try{
      const res = await fetch(url, { credentials: 'include' });
      const html = await res.text();
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, 'text/html');
      document.body.innerHTML = doc.body.innerHTML;
      executeScripts(document.body);
      bindLinks();
      if (replaceState) history.replaceState({spa:true}, '', url);
      else history.pushState({spa:true}, '', url);
      window.scrollTo(0,0);
    }catch(err){
      console.error('[SPA] navigation failed', err);
      location.href = url;
    }
  }

  function onLinkClick(e){
    const a = e.target.closest('a.spa-link');
    if (!a) return;
    const href = a.getAttribute('href');
    if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
    e.preventDefault();
    navigateTo(href);
  }

  function bindLinks(){
    document.removeEventListener('click', onLinkClick, true);
    document.addEventListener('click', onLinkClick, true);
  }

  window.addEventListener('popstate', (e)=>{
    if (e.state && e.state.spa){
      navigateTo(location.href, true);
    }
  });

  window.spaNavigate = navigateTo;
  bindLinks();
})();