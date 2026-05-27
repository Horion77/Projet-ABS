(function () {
  'use strict';

  var CFG = {
    staticCount:   200,
    staticMinSize: 1.5,
    staticMaxSize: 5.0,
    staticMinDur:  1.5,
    staticMaxDur:  4.0,
    staticMinDelay: 0,
    staticMaxDelay: 4.0,
    floatCount:    50,
    floatMinSize:  2.0,
    floatMaxSize:  5.5,
    floatMinDur:   6,
    floatMaxDur:   16,
    floatMinDelay: 0,
    floatMaxDelay: 8,
    colors: [
      'rgba(255,255,255,{a})',
      'rgba(238,238,255,{a})',
      'rgba(210,222,255,{a})',
      'rgba(230,248,255,{a})',
    ],
  };

  function rand(min, max)    { return min + Math.random() * (max - min); }
  function randInt(min, max) { return Math.floor(rand(min, max + 1)); }
  function color(a) {
    return CFG.colors[randInt(0, CFG.colors.length - 1)].replace('{a}', a.toFixed(2));
  }

  function injectCSS() {
    if (document.getElementById('abs-stars-css')) return;
    var css = [
      '@keyframes abs-twinkle {',
      '  0%   { opacity: var(--s0, 0.08); transform: scale(0.85); }',
      '  50%  { opacity: var(--s1, 1.00); transform: scale(1.50); }',
      '  100% { opacity: var(--s0, 0.08); transform: scale(0.85); }',
      '}',
      '@keyframes abs-d0{0%,100%{transform:translate(0,0)}50%{transform:translate(24px,-22px)}}',
      '@keyframes abs-d1{0%,100%{transform:translate(0,0)}50%{transform:translate(-26px,18px)}}',
      '@keyframes abs-d2{0%,100%{transform:translate(0,0)}50%{transform:translate(20px,24px)}}',
      '@keyframes abs-d3{0%,100%{transform:translate(0,0)}50%{transform:translate(-18px,-26px)}}',
      '@keyframes abs-d4{0%,100%{transform:translate(0,0)}50%{transform:translate(28px,10px)}}',
      '@keyframes abs-d5{0%,100%{transform:translate(0,0)}50%{transform:translate(-12px,28px)}}',
      '@keyframes abs-d6{0%,100%{transform:translate(0,0)}50%{transform:translate(14px,-30px)}}',
      '@keyframes abs-d7{0%,100%{transform:translate(0,0)}50%{transform:translate(-30px,7px)}}',
      '.abs-sl{position:fixed;inset:0;pointer-events:none;z-index:2;overflow:hidden;}',
      '.abs-s{position:absolute;border-radius:50%;will-change:opacity,transform;}',
    ].join('\n');
    var el = document.createElement('style');
    el.id = 'abs-stars-css';
    el.textContent = css;
    document.head.appendChild(el);
  }

  function mkStatic() {
    var sz  = rand(CFG.staticMinSize, CFG.staticMaxSize);
    var dur = rand(CFG.staticMinDur,  CFG.staticMaxDur).toFixed(2);
    var del = rand(CFG.staticMinDelay, CFG.staticMaxDelay).toFixed(2);
    var s1  = rand(0.40, 0.65).toFixed(2);
    var s0  = (parseFloat(s1) * rand(0.05, 0.15)).toFixed(2);
    var col = color(parseFloat(s1));
    var glow = (sz * 3).toFixed(1);
    var el = document.createElement('span');
    el.className = 'abs-s';
    el.style.cssText =
      'width:'  + sz.toFixed(1) + 'px;' +
      'height:' + sz.toFixed(1) + 'px;' +
      'top:'    + rand(0,100).toFixed(3) + '%;' +
      'left:'   + rand(0,100).toFixed(3) + '%;' +
      'background:' + col + ';' +
      'box-shadow:0 0 ' + glow + 'px ' + col + ',0 0 ' + (sz*1.5).toFixed(1) + 'px rgba(255,255,255,0.25);' +
      '--s0:' + s0 + ';--s1:' + s1 + ';' +
      'animation:abs-twinkle ' + dur + 's ' + del + 's ease-in-out infinite;';
    return el;
  }

  function mkFloat() {
    var sz  = rand(CFG.floatMinSize, CFG.floatMaxSize);
    var dur = rand(CFG.floatMinDur,  CFG.floatMaxDur).toFixed(2);
    var del = rand(CFG.floatMinDelay, CFG.floatMaxDelay).toFixed(2);
    var dir = randInt(0, 7);
    var opa = rand(0.20, 0.50).toFixed(2);
    var col = color(parseFloat(opa));
    var glow = (sz * 3.5).toFixed(1);
    var el = document.createElement('span');
    el.className = 'abs-s';
    el.style.cssText =
      'width:'  + sz.toFixed(1) + 'px;' +
      'height:' + sz.toFixed(1) + 'px;' +
      'top:'    + rand(0,100).toFixed(3) + '%;' +
      'left:'   + rand(0,100).toFixed(3) + '%;' +
      'background:' + col + ';' +
      'box-shadow:0 0 ' + glow + 'px ' + col + ';' +
      'opacity:' + opa + ';' +
      'animation:abs-d' + dir + ' ' + dur + 's ' + del + 's ease-in-out infinite;';
    return el;
  }

  function build() {
    var l1 = document.createElement('div');
    l1.className = 'abs-sl'; l1.id = 'abs-stars-static';
    l1.setAttribute('aria-hidden','true');
    var f1 = document.createDocumentFragment();
    for (var i = 0; i < CFG.staticCount; i++) f1.appendChild(mkStatic());
    l1.appendChild(f1);

    var l2 = document.createElement('div');
    l2.className = 'abs-sl'; l2.id = 'abs-stars-float';
    l2.setAttribute('aria-hidden','true');
    var f2 = document.createDocumentFragment();
    for (var j = 0; j < CFG.floatCount; j++) f2.appendChild(mkFloat());
    l2.appendChild(f2);

    var body = document.body;
    body.insertBefore(l2, body.firstChild);
    body.insertBefore(l1, body.firstChild);
  }

  function init() {
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion:reduce)').matches) return;
    injectCSS();
    build();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();