// ---------- Priekis: slankiklis, filtrai, perziura (lightbox su nuotraukomis ir video) ----------
add_action('wp_footer', function () {
    if (!rk_gal_needed()) return;
    echo '<script>(function(){
var M=document.createElement("div");M.className="rk-lb";M.setAttribute("role","dialog");M.setAttribute("aria-modal","true");M.setAttribute("aria-label","Darbo perziura");
M.innerHTML="<button type=button class=x aria-label=Uždaryti>✕</button><button type=button class=p aria-label=Atgal>‹</button><button type=button class=n aria-label=Pirmyn>›</button><figure><div class=rk-lb-stage></div><figcaption></figcaption></figure><div class=rk-lb-thumbs></div>";
document.body.appendChild(M);var items=[],i=0,title="",last=null;
function stage(k){i=(k+items.length)%items.length;var it=items[i],s=M.querySelector(".rk-lb-stage");s.innerHTML="";
 if(it.t==="v"){var v=document.createElement("video");v.src=it.src;v.controls=true;v.playsInline=true;v.autoplay=true;v.preload="metadata";if(it.poster)v.poster=it.poster;s.appendChild(v);}
 else{var im=document.createElement("img");im.src=it.src;im.alt=title;s.appendChild(im);}
 M.querySelector("figcaption").textContent=(items.length>1?(i+1)+" / "+items.length+" · ":"")+title;
 M.querySelectorAll(".rk-lb-thumbs button").forEach(function(b,k){b.classList.toggle("on",k===i)});
 M.querySelector(".p").style.display=M.querySelector(".n").style.display=items.length>1?"":"none";}
function open(a){items=JSON.parse(a.getAttribute("data-media")||"[]");if(!items.length)return;title=a.getAttribute("data-title")||"";last=a;
 var th=M.querySelector(".rk-lb-thumbs");th.innerHTML="";if(items.length>1)items.forEach(function(it,k){var b=document.createElement("button");b.type="button";b.setAttribute("aria-label",(k+1)+"");b.innerHTML=it.t==="v"?(it.thumb?"<img src=\""+it.thumb+"\" alt=\"\">":"")+"<span>▶</span>":"<img src=\""+(it.src)+"\" alt=\"\">";b.addEventListener("click",function(){stage(k)});th.appendChild(b)});
 M.classList.add("on");document.body.style.overflow="hidden";stage(0);M.querySelector(".x").focus();}
function close(){M.classList.remove("on");document.body.style.overflow="";M.querySelector(".rk-lb-stage").innerHTML="";if(last)last.focus();}
document.addEventListener("click",function(e){var a=e.target.closest(".rkg-it");if(a){e.preventDefault();open(a)}});
M.querySelector(".x").addEventListener("click",close);M.querySelector(".p").addEventListener("click",function(){stage(i-1)});M.querySelector(".n").addEventListener("click",function(){stage(i+1)});
M.addEventListener("click",function(e){if(e.target===M)close()});
document.addEventListener("keydown",function(e){if(!M.classList.contains("on"))return;if(e.key==="Escape")close();if(e.key==="ArrowLeft")stage(i-1);if(e.key==="ArrowRight")stage(i+1)});
var tx=0;M.addEventListener("touchstart",function(e){tx=e.touches[0].clientX},{passive:true});M.addEventListener("touchend",function(e){var d=e.changedTouches[0].clientX-tx;if(Math.abs(d)>50&&items.length>1)stage(d<0?i+1:i-1)});
document.querySelectorAll(".rkg-wrap").forEach(function(w){var g=w.querySelector(".rkg-grid");if(!g)return;
 var sec=w.closest("section,.e-con-boxed,.e-parent")||w.parentElement,nav=null;while(sec&&!nav){nav=sec.querySelector(".rkg-nav");if(!nav)sec=sec.parentElement;}
 if(!nav){nav=document.createElement("div");nav.className="rkg-nav rkg-nav-inline";nav.innerHTML="<button type=button class=rkg-arrow aria-label=Atgal>‹</button><button type=button class=rkg-arrow aria-label=Pirmyn>›</button>";w.insertBefore(nav,g);}
 var b=nav.querySelectorAll("button");function step(){var it=g.querySelector(".rkg-it");return it?it.getBoundingClientRect().width+14:300;}function per(){return Math.max(1,Math.round(g.clientWidth/step()));}
 b[0].addEventListener("click",function(){g.scrollBy({left:-step()*per(),behavior:"smooth"})});b[1].addEventListener("click",function(){g.scrollBy({left:step()*per(),behavior:"smooth"})});
 var n=g.querySelectorAll(".rkg-it").length,d=document.createElement("div");d.className="rkg-dots";g.after(d);
 function upd(){g.classList.toggle("rkg-few",n<=per());var off=g.scrollWidth<=g.clientWidth+5;nav.classList.toggle("rkg-nav-off",off);d.classList.toggle("rkg-nav-off",off);var first=Math.round(g.scrollLeft/step())+1,lastI=Math.min(n,first+per()-1);d.textContent=(first===lastI?first:first+"–"+lastI)+" iš "+n;b[0].disabled=g.scrollLeft<5;b[1].disabled=g.scrollLeft+g.clientWidth>=g.scrollWidth-5;}
 g.addEventListener("scroll",upd);window.addEventListener("resize",upd);window.addEventListener("load",upd);upd();requestAnimationFrame(upd);});
var s=document.querySelector(".rkg-all");if(s){var tabs=s.querySelectorAll(".rkga-tab"),its=s.querySelectorAll(".rkg-it"),LIM=24,lim=LIM,more=document.createElement("button");more.type="button";more.className="rkga-more";s.appendChild(more);
 function apply(){var on=s.querySelector(".rkga-tab.on"),c=on?on.getAttribute("data-cat"):"",k=0,total=0;its.forEach(function(a){var m=!c||a.getAttribute("data-cat")===c;if(m)total++;var show=m&&k<lim;if(m)k++;a.classList.toggle("rkga-off",!show)});var left=total-Math.min(lim,total);more.style.display=left>0?"":"none";more.textContent="Rodyti daugiau ("+left+")";}
 more.addEventListener("click",function(){lim+=LIM;apply()});apply();tabs.forEach(function(t){t.addEventListener("click",function(){tabs.forEach(function(x){x.classList.remove("on")});t.classList.add("on");lim=LIM;apply()})});}
})();</script>';
}, 99);
add_action('wp_head', function () {
    if (!rk_gal_needed()) return;
    $css = <<<'RKGALCSS'
.rkg-wrap,.rkg-nav,.rkg-all{font-family:Inter,sans-serif;color:#64748B;font-size:17px;line-height:1.7}.rkg-wrap *,.rkg-all *{box-sizing:border-box}
.rkg-nav{display:flex;gap:8px;justify-content:flex-end}.rkg-nav.rkg-nav-inline{margin:0 0 14px}
.rkg-nav button.rkg-arrow{flex:0 0 40px;width:40px;height:40px;padding:0;border-radius:50%;border:1px solid #E2E8F0;background:#fff;color:#166534;font-size:22px;line-height:1;font-weight:400;display:grid;place-items:center;box-shadow:none;cursor:pointer;transition:.2s;font-family:inherit}
.rkg-nav button.rkg-arrow:hover{background:#166534;color:#fff;border-color:#166534}.rkg-nav button.rkg-arrow:disabled{opacity:.35;cursor:default;background:#fff;color:#166534;border-color:#E2E8F0}
.rkg-grid{display:flex;gap:14px;overflow-x:auto;scroll-snap-type:x mandatory;scroll-behavior:smooth;padding:4px 2px 14px;scrollbar-width:none;align-items:stretch}.rkg-grid::-webkit-scrollbar{display:none}.rkg-grid.rkg-few{justify-content:center}
.rkg-it{flex:0 0 calc((100% - 42px)/4);scroll-snap-align:start;display:flex;flex-direction:column;border-radius:14px;overflow:hidden;border:1px solid #E2E8F0;background:#fff;transition:.2s;height:auto;cursor:pointer}
.rkg-it .rkg-ph{position:relative;display:block;aspect-ratio:4/3;background:#0f172a;overflow:hidden}.rkg-it img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .3s}
.rkg-it:hover{box-shadow:0 22px 44px -24px rgba(15,74,40,.35);transform:translateY(-3px)}.rkg-it:hover img{transform:scale(1.04)}a.rkg-it,a.rkg-it:hover,a.rkg-it:focus{text-decoration:none;color:#111827;outline:none}a.rkg-it:focus-visible{outline:2px solid #22C55E;outline-offset:2px}
.rkg-it .rkg-n{position:absolute;left:10px;bottom:10px;background:rgba(17,24,39,.72);color:#fff;font-size:11.5px;font-weight:600;line-height:1;padding:6px 9px;border-radius:999px;letter-spacing:.01em}
.rkg-it .rkg-play{position:absolute;top:50%;left:50%;width:48px;height:48px;margin:-24px 0 0 -24px;border-radius:50%;background:rgba(255,255,255,.92);color:#166534;display:grid;place-items:center;font-size:18px;padding-left:4px;box-shadow:0 8px 24px rgba(0,0,0,.25)}
.rkg-it .rkg-novid{position:absolute;inset:0;display:grid;place-items:center;color:#fff;font-size:40px;background:linear-gradient(135deg,#14532D,#0f172a)}
.rkg-mini{display:grid;grid-template-columns:repeat(3,1fr);gap:4px;padding:6px 8px 0;background:#fff}.rkg-mi{flex:1 1 0;aspect-ratio:4/3;border-radius:6px;overflow:hidden;background:#F1F5F9;display:grid;place-items:center;font-size:11px;font-weight:700;color:#166534;border:1px solid #E2E8F0;min-width:0}.rkg-mi img{width:100%;height:100%;object-fit:cover;display:block;transform:none!important}.rkg-mi-v{background:#14532D;color:#fff;font-size:10px;position:relative}.rkg-mi-v i{position:absolute;inset:0;display:grid;place-items:center;font-style:normal;color:#fff;text-shadow:0 1px 4px rgba(0,0,0,.7);font-size:11px}.rkg-mi-more{background:#F0FDF4}.rkg-mi-empty{visibility:hidden}
.rkg-it .rkg-cap{display:block;flex:1;padding:10px 12px 11px;font-size:12.5px;font-weight:600;color:#111827;line-height:1.35;background:#fff;border-top:1px solid #E2E8F0}
.rkg-dots{text-align:center;color:#64748B;font-size:12px;margin-top:2px}.rkg-nav-off,.rkg-dots.rkg-nav-off{display:none!important}
.rkg-all .rkga-tabs{display:flex;flex-wrap:wrap;justify-content:center;gap:8px;margin:0 0 22px}.rkg-all .rkga-tab{border:1px solid #E2E8F0;background:#fff;color:#166534;border-radius:999px;padding:9px 16px;max-width:100%;white-space:normal;text-align:center;line-height:1.3;font:600 13.5px/1.2 Inter,sans-serif;cursor:pointer;transition:.2s;box-shadow:none;text-transform:none;letter-spacing:normal}.rkg-all .rkga-tab:hover{border-color:#166534}.rkg-all .rkga-tab.on{background:#166534;color:#fff;border-color:#166534}.rkg-all .rkga-tab b{font-weight:600;opacity:.7;margin-left:2px}
.rkga-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}.rkga-grid .rkg-it{scroll-snap-align:none}.rkga-grid .rkg-it.rkga-off{display:none!important}
.rkg-all .rkga-more{display:block;margin:26px auto 0;border:1px solid #166534;background:#fff;color:#166534;border-radius:999px;padding:12px 26px;font:600 14px/1.2 Inter,sans-serif;cursor:pointer;box-shadow:none;text-transform:none;letter-spacing:normal}.rkg-all .rkga-more:hover{background:#166534;color:#fff}
@media(max-width:1024px){.rkg-it{flex-basis:calc((100% - 14px)/2)}.rkga-grid{grid-template-columns:1fr 1fr}.rkg-grid.rkg-few{justify-content:flex-start}}
@media(max-width:767px){.rkg-it{flex-basis:85%}.rkg-grid.rkg-few .rkg-it:only-child{flex-basis:100%}.rkga-grid{grid-template-columns:1fr}}
.rk-lb{position:fixed;inset:0;background:rgba(17,24,39,.94);display:none;grid-template-rows:1fr auto;place-items:center;z-index:99999;padding:56px 16px 12px}.rk-lb.on{display:grid}
.rk-lb figure{margin:0;max-width:94vw;text-align:center;display:flex;flex-direction:column;align-items:center;gap:12px;min-width:0}
.rk-lb .rk-lb-stage{display:grid;place-items:center;max-width:94vw}.rk-lb .rk-lb-stage img,.rk-lb .rk-lb-stage video{max-width:94vw;max-height:calc(100vh - 190px);border-radius:14px;box-shadow:0 30px 80px rgba(0,0,0,.5);background:#000}
.rk-lb figcaption{color:#fff;font-weight:600;font-size:15px;font-family:Inter,sans-serif}
.rk-lb .rk-lb-thumbs{display:flex;gap:8px;justify-content:center;flex-wrap:wrap;max-width:94vw;padding:8px 0 4px}.rk-lb .rk-lb-thumbs button{width:58px;height:44px;border-radius:8px;border:2px solid transparent;padding:0;overflow:hidden;background:#1e293b;cursor:pointer;opacity:.6;transition:.15s;display:grid;place-items:center;color:#fff;font-size:14px}.rk-lb .rk-lb-thumbs button{position:relative}.rk-lb .rk-lb-thumbs button img{width:100%;height:100%;object-fit:cover;display:block}.rk-lb .rk-lb-thumbs button span{position:absolute;inset:0;display:grid;place-items:center;text-shadow:0 1px 4px rgba(0,0,0,.8)}.rk-lb .rk-lb-thumbs button.on{border-color:#22C55E;opacity:1}.rk-lb .rk-lb-thumbs button:hover{opacity:1}
.rk-lb>button{position:absolute;background:rgba(255,255,255,.12);color:#fff;border:0;width:48px;height:48px;border-radius:50%;font-size:28px;cursor:pointer;display:grid;place-items:center;z-index:2}.rk-lb>button:hover{background:rgba(255,255,255,.28)}.rk-lb .x,.rk-lb .x:hover{background:transparent;color:#fff}.rk-lb .x{top:12px;right:12px;font-size:20px}.rk-lb .p{left:12px;top:50%;transform:translateY(-50%)}.rk-lb .n{right:12px;top:50%;transform:translateY(-50%)}
@media(max-width:600px){.rk-lb{padding:52px 8px 8px}.rk-lb .p,.rk-lb .n{top:auto;bottom:80px;transform:none;width:42px;height:42px}.rk-lb .rk-lb-thumbs button{width:46px;height:36px}}
RKGALCSS;
    echo '<style id="rk-gal">' . $css . '</style>';
}, 97);
