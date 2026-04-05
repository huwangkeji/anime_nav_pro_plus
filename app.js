/* ===== Anime Nav Pro Plus - 特效引擎 v3 ===== */
(function(){
  'use strict';

  /* 浮动粒子 */
  function makeParticles(){
    var c=['#ff6b6b','#feca57','#48dbfb','#ff9ff3','#a29bfe','#00d2d3','#1dd1a1'];
    var n=window.innerWidth<600?8:14;
    for(var i=0;i<n;i++){
      var p=document.createElement('div');
      p.className='particle';
      var s=Math.random()*4+2;
      var cl=c[Math.floor(Math.random()*c.length)];
      p.style.cssText=
        'width:'+s+'px;height:'+s+'px;'+
        'left:'+(Math.random()*100)+'%;'+
        'background:'+cl+';box-shadow:0 0 '+(s*3)+'px '+cl+';'+
        'animation-duration:'+(Math.random()*15+10)+'s;'+
        'animation-delay:'+(Math.random()*12)+'s;'+
        'opacity:'+(Math.random()*0.5+0.2);
      document.body.appendChild(p);
    }
  }

  /* 鼠标跟随光晕（仅宽屏）*/
  function cursorGlow(){
    if(window.innerWidth<768)return;
    var g=document.createElement('div');
    g.style.cssText=
      'position:fixed;width:220px;height:220px;border-radius:50%;'+
      'background:radial-gradient(circle,rgba(74,144,217,0.07) 0%,transparent 70%);'+
      'pointer-events:none;z-index:0;transform:translate(-50%,-50%);'+
      'transition:left .25s ease,top .25s ease;';
    document.body.appendChild(g);
    document.onmousemove=function(e){
      g.style.left=e.clientX+'px';g.style.top=e.clientY+'px';
    };
  }

  /* 卡片入场动画 */
  function scrollReveal(){
    if(!('IntersectionObserver' in window))return;
    var ob=new IntersectionObserver(function(es){
      es.forEach(function(e){
        if(e.isIntersecting){
          var cs=e.target.querySelectorAll('.card');
          for(var i=0;i<cs.length;i++)(function(el,idx){
            el.style.opacity='0';el.style.transform='translateY(18px)';
            setTimeout(function(){
              el.style.transition='opacity .45s,transform .45s';
              el.style.opacity='1';el.style.transform='translateY(0)';
            },idx*70);
          })(cs[i],i);
          ob.unobserve(e.target);
        }
      });
    },{threshold:0.08});
    document.querySelectorAll('.g-nav,.g-feat').forEach(function(e){ob.observe(e);});
  }

  /* 搜索框聚焦光效脉冲 */
  function searchPulse(){
    var wrap=document.querySelector('.bing-input-wrap');
    if(!wrap)return;
    wrap.addEventListener('focusin',function(){this.classList.add('focused');});
    wrap.addEventListener('focusout',function(){this.classList.remove('focused');});
  }

  /* 初始化 */
  if(document.readyState==='loading'){
    document.addEventListener('DOMContentLoaded',init);
  }else{init();}
  function init(){
    makeParticles();
    cursorGlow();
    scrollReveal();
    searchPulse();
  }
})();
