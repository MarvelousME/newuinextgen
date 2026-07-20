
/* NEXTGEN-BEYOND-INFINITY UI System — progressive enhancement only. */
(function(){
  'use strict';
  var root=document.documentElement;
  root.classList.add('ngbi-ui-system');
  var reduce=window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  function qsa(s,ctx){return Array.prototype.slice.call((ctx||document).querySelectorAll(s));}
  function addReveal(){qsa('.tutor-card,.ngt-tutor-card,.bi-card,.dashboard-card,.feature-card,.pricing-card,.testimonial-card,.entry-content > section').forEach(function(el,i){el.classList.add('ngbi-reveal');el.style.setProperty('--ngbi-index',i%10);});}
  function magnetic(){if(reduce)return;qsa('.button,.btn,.wp-block-button__link,.bi-btn,.ngt-btn,.vc_btn3').forEach(function(btn){btn.addEventListener('pointermove',function(e){var r=btn.getBoundingClientRect();btn.style.setProperty('--mx',((e.clientX-r.left)/r.width*100).toFixed(2)+'%');btn.style.setProperty('--my',((e.clientY-r.top)/r.height*100).toFixed(2)+'%');});});}
  function tilt(){if(reduce)return;qsa('.tutor-card,.ngt-tutor-card,.bi-tutor-card').forEach(function(card){card.addEventListener('pointermove',function(e){var r=card.getBoundingClientRect(),x=(e.clientX-r.left)/r.width-.5,y=(e.clientY-r.top)/r.height-.5;card.style.transform='translateY(-6px) rotateX('+(-y*3)+'deg) rotateY('+(x*3)+'deg)';});card.addEventListener('pointerleave',function(){card.style.transform='';});});}
  function commandHint(){qsa('.wrap h1,.ngcpm-header h1,.ngc-ai-wrap h1,.rhi-wrap h1').forEach(function(h){if(h.dataset.ngbiDone)return;h.dataset.ngbiDone='1';var badge=document.createElement('span');badge.className='ngbi-agent-badge';badge.textContent='NEXTGEN-BEYOND-INFINITY UI';h.appendChild(badge);});}
  document.addEventListener('DOMContentLoaded',function(){addReveal();magnetic();tilt();commandHint();});
})();
