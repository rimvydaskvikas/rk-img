// ===== RAKTUTECH: galerijos modulis v3 (vienintelis snippet'as) =====
// Darbas (rk_galerija) = pavadinimas + kategorija + kelios nuotraukos/video (meta rk_media). Kortele rodo virseli,
// paspaudus atsidaro perziura ir slankiojama per to darbo nuotraukas/video. Kategorijos rodomos kliento nustatyta tvarka (term meta rk_order).
// Trumpiniai: [rk_galerija] (puslapio kategorijos darbai, slankiklis), [rk_galerija_rodykles], [rk_galerija_visa] (visi darbai su filtrais).
// Valdymas: wp-admin -> Galerija. CSS/JS liecia TIK .rkg-* / .rkga-* / .rk-lb klases ir ikeliami tik puslapiuose su trumpiniu.

add_action('init', function () {
    register_taxonomy('rk_gal_kat', 'rk_galerija', array(
        'labels' => array('name' => 'Kategorijos', 'singular_name' => 'Kategorija', 'menu_name' => 'Kategorijos'),
        'public' => false, 'show_ui' => true, 'show_admin_column' => true, 'hierarchical' => true, 'show_in_rest' => true,
    ));
    register_post_type('rk_galerija', array(
        'labels' => array('name' => 'Galerija', 'singular_name' => 'Darbas', 'menu_name' => 'Galerija', 'add_new' => 'Naujas darbas', 'add_new_item' => 'Naujas darbas', 'edit_item' => 'Redaguoti darba'),
        'public' => false, 'show_ui' => true, 'show_in_menu' => false, 'menu_icon' => 'dashicons-format-gallery',
        'supports' => array('title', 'thumbnail', 'page-attributes', 'custom-fields'), 'show_in_rest' => true, 'taxonomies' => array('rk_gal_kat'),
    ));
    $auth = function () { return current_user_can('edit_posts'); };
    register_post_meta('rk_galerija', 'rk_media', array('type' => 'string', 'single' => true, 'show_in_rest' => true, 'auth_callback' => $auth, 'sanitize_callback' => 'wp_kses_post'));
    register_term_meta('rk_gal_kat', 'rk_page', array('type' => 'integer', 'single' => true, 'show_in_rest' => true, 'auth_callback' => $auth));
    register_term_meta('rk_gal_kat', 'rk_order', array('type' => 'integer', 'single' => true, 'show_in_rest' => true, 'auth_callback' => $auth));
}, 5);

// Leidziame video failus (mp4/mov) ir REST perduodama rusiavima pagal rk_order
add_filter('upload_mimes', function ($m) { $m['mp4'] = 'video/mp4'; $m['mov'] = 'video/quicktime'; return $m; });
add_filter('rest_rk_gal_kat_collection_params', function ($p) { $p['orderby']['enum'][] = 'meta_value_num'; return $p; });

// Admin: numatytus WP ekranus nukreipiame i valdymo skydeli
add_action('admin_menu', function () {
    add_menu_page('Galerija', 'Galerija', 'edit_posts', 'rk-galerija', 'rk_galui_page', 'dashicons-format-gallery', 25);
}, 20);
add_action('admin_init', function () {
    global $pagenow;
    $pt = isset($_GET['post_type']) ? $_GET['post_type'] : ''; $tx = isset($_GET['taxonomy']) ? $_GET['taxonomy'] : '';
    if (($pagenow === 'edit.php' && $pt === 'rk_galerija') || ($pagenow === 'edit-tags.php' && $tx === 'rk_gal_kat') || ($pagenow === 'post-new.php' && $pt === 'rk_galerija')) { wp_safe_redirect(admin_url('admin.php?page=rk-galerija')); exit; }
});
add_action('admin_head', function () {
    $s = get_current_screen();
    if ($s && $s->id === 'toplevel_page_rk-galerija') echo '<style>.update-nag,.notice:not(.rkg-keep),#wpfooter{display:none!important}#wpbody-content{padding-bottom:0}</style>';
});
// Isvalome Elementor kesa puslapiams, kuriuose rodoma galerija
function rk_galui_purge() {
    foreach (get_terms(array('taxonomy' => 'rk_gal_kat', 'hide_empty' => false)) as $t) { $p = (int) get_term_meta($t->term_id, 'rk_page', true); if ($p) delete_post_meta($p, '_elementor_element_cache'); }
    foreach (array('atlikti-darbai', 'galerija') as $slug) { $g = get_page_by_path($slug); if ($g) delete_post_meta($g->ID, '_elementor_element_cache'); }
}
foreach (array('save_post_rk_galerija', 'deleted_post', 'trashed_post', 'untrashed_post', 'edited_rk_gal_kat', 'created_rk_gal_kat', 'delete_rk_gal_kat') as $h) add_action($h, 'rk_galui_purge');

// ---------- Duomenys ----------
function rk_gal_terms_ordered($hide_empty = true) {
    $terms = get_terms(array('taxonomy' => 'rk_gal_kat', 'hide_empty' => $hide_empty));
    if (is_wp_error($terms)) return array();
    usort($terms, function ($a, $b) { $x = (int) get_term_meta($a->term_id, 'rk_order', true) ?: 999; $y = (int) get_term_meta($b->term_id, 'rk_order', true) ?: 999; return $x === $y ? strcmp($a->name, $b->name) : $x - $y; });
    return $terms;
}
// Darbo medija: masyvas [ ['t'=>'i'|'v','id'=>int] ] ; jei meta tuscia - virselis
function rk_gal_media($post_id) {
    $raw = get_post_meta($post_id, 'rk_media', true); $list = $raw ? json_decode($raw, true) : null; $out = array();
    if (is_array($list)) foreach ($list as $m) { if (!empty($m['id']) && get_post($m['id'])) $out[] = array('t' => (isset($m['t']) && $m['t'] === 'v') ? 'v' : 'i', 'id' => (int) $m['id'], 'p' => !empty($m['p']) ? (int) $m['p'] : 0); }
    if (!$out) { $tid = get_post_thumbnail_id($post_id); if ($tid) $out[] = array('t' => 'i', 'id' => (int) $tid); }
    return $out;
}
// Mini aplankas: iki 4 mazyciu miniatiuru po virseliu (rodoma tik kai darbe > 1 failo)
function rk_gal_mini($media, $cover) {
    if (count($media) < 2) return '';
    $out = ''; $n = 0;
    foreach ($media as $m) {
        if ($n >= 3) break; $n++;
        if ($m['t'] === 'v') { $pu = !empty($m['p']) ? wp_get_attachment_image_url($m['p'], 'medium') : ''; $out .= '<span class="rkg-mi rkg-mi-v">' . ($pu ? '<img loading="lazy" src="' . esc_url($pu) . '" alt="">' : '') . '<i>&#9654;</i></span>'; continue; }
        $u = wp_get_attachment_image_url($m['id'], 'medium');
        $out .= '<span class="rkg-mi"><img loading="lazy" src="' . esc_url($u) . '" alt=""></span>';
    }
    while ($n < 3) { $n++; $out .= '<span class="rkg-mi rkg-mi-empty"></span>'; }
    return '<span class="rkg-mini">' . $out . '</span>';
}
function rk_gal_card($p, $with_cat = false) {
    $GLOBALS['rk_gal_used'] = true;
    $media = rk_gal_media($p->ID); if (!$media) return '';
    $cover = get_post_thumbnail_id($p->ID);
    if (!$cover) foreach ($media as $m) { if ($m['t'] === 'i') { $cover = $m['id']; break; } }
    $cap = esc_html(get_the_title($p)); $items = array(); $ni = 0; $nv = 0;
    foreach ($media as $m) {
        if ($m['t'] === 'v') { $nv++; $pid = !empty($m['p']) ? $m['p'] : $cover; $items[] = array('t' => 'v', 'src' => wp_get_attachment_url($m['id']), 'poster' => $pid ? wp_get_attachment_image_url($pid, 'large') : '', 'thumb' => $pid ? wp_get_attachment_image_url($pid, 'medium') : ''); }
        else { $ni++; $items[] = array('t' => 'i', 'src' => wp_get_attachment_image_url($m['id'], 'large'), 'full' => wp_get_attachment_image_url($m['id'], 'full')); }
    }
    $thumb = $cover ? wp_get_attachment_image_url($cover, 'medium_large') : ''; $srcset = $cover ? wp_get_attachment_image_srcset($cover, 'medium_large') : '';
    $badge = ($ni ? $ni . ' nuotr.' : '') . ($ni && $nv ? ' · ' : '') . ($nv ? $nv . ' video' : '');
    $cat = ''; if ($with_cat) { $tt = get_the_terms($p->ID, 'rk_gal_kat'); $cat = ($tt && !is_wp_error($tt)) ? $tt[0]->slug : ''; }
    return '<a class="rkg-it" href="#" role="button" aria-label="' . $cap . '"' . ($with_cat ? ' data-cat="' . esc_attr($cat) . '"' : '') . " data-media='" . esc_attr(wp_json_encode($items)) . "' data-title=\"" . $cap . '">'
        . '<span class="rkg-ph">' . ($thumb ? '<img loading="lazy" decoding="async" src="' . esc_url($thumb) . '"' . ($srcset ? ' srcset="' . esc_attr($srcset) . '" sizes="(max-width:767px) 85vw, (max-width:1024px) 45vw, 280px"' : '') . ' alt="' . $cap . '">' : '<span class="rkg-novid">&#9654;</span>')
        . (($nv && !$ni) ? '<span class="rkg-play">&#9654;</span>' : '') . '<span class="rkg-n">' . $badge . '</span></span>' . rk_gal_mini($media, $cover) . '<span class="rkg-cap">' . $cap . '</span></a>';
}
function rk_gal_query($term_id = 0) {
    $args = array('post_type' => 'rk_galerija', 'posts_per_page' => -1, 'orderby' => array('menu_order' => 'ASC', 'date' => 'DESC'), 'no_found_rows' => true);
    if ($term_id) $args['tax_query'] = array(array('taxonomy' => 'rk_gal_kat', 'terms' => $term_id));
    // pirma darbai su nuotraukomis, tik-video darbai - pabaigoje (isliekant menu_order tvarkai)
    $with = array(); $only = array();
    foreach (get_posts($args) as $p) { $has = false; foreach (rk_gal_media($p->ID) as $m) { if ($m['t'] === 'i') { $has = true; break; } } if ($has) $with[] = $p; else $only[] = $p; }
    return array_merge($with, $only);
}
add_shortcode('rk_galerija', function ($atts) {
    $atts = shortcode_atts(array('kategorija' => ''), $atts); $term = null;
    if ($atts['kategorija']) $term = get_term_by('slug', $atts['kategorija'], 'rk_gal_kat');
    else { $terms = get_terms(array('taxonomy' => 'rk_gal_kat', 'hide_empty' => false, 'meta_key' => 'rk_page', 'meta_value' => (int) get_the_ID())); $term = (!is_wp_error($terms) && $terms) ? $terms[0] : null; }
    if (!$term) return '';
    $html = ''; foreach (rk_gal_query($term->term_id) as $p) $html .= rk_gal_card($p);
    return $html ? '<div class="rkg-wrap"><div class="rkg-grid">' . $html . '</div></div>' : '';
});
add_shortcode('rk_galerija_home', function ($atts) { // naujausi darbai (titulinis), kiekis="8"
    $atts = shortcode_atts(array('kiekis' => 8), $atts, 'rk_galerija_home'); $limit = max(1, min(24, absint($atts['kiekis'])));
    // pirmenybe darbams su nuotraukomis (ne tik video), tvarka - kaip galerijoje (menu_order)
    $posts = array_slice(rk_gal_query(), 0, $limit);
    $html = ''; foreach ($posts as $p) $html .= rk_gal_card($p);
    return $html ? '<div class="rkg-wrap rkg-home"><div class="rkg-grid">' . $html . '</div></div>' : '';
});
add_shortcode('rk_galerija_rodykles', function () {
    return '<div class="rkg-nav"><button type="button" class="rkg-arrow" aria-label="Atgal">&#8249;</button><button type="button" class="rkg-arrow" aria-label="Pirmyn">&#8250;</button></div>';
});
add_shortcode('rk_galerija_visa', function () {
    $terms = rk_gal_terms_ordered(true); $posts = rk_gal_query(); if (!$posts) return '';
    $cnt = array(); $html = '';
    foreach ($posts as $p) { $tt = get_the_terms($p->ID, 'rk_gal_kat'); $s = ($tt && !is_wp_error($tt)) ? $tt[0]->slug : ''; $cnt[$s] = isset($cnt[$s]) ? $cnt[$s] + 1 : 1; $html .= rk_gal_card($p, true); }
    $tabs = '<button type="button" class="rkga-tab on" data-cat="">Visi <b>' . count($posts) . '</b></button>';
    foreach ($terms as $t) { if (empty($cnt[$t->slug])) continue; $tabs .= '<button type="button" class="rkga-tab" data-cat="' . esc_attr($t->slug) . '">' . esc_html($t->name) . ' <b>' . $cnt[$t->slug] . '</b></button>'; }
    return '<div class="rkg-all"><div class="rkga-tabs">' . $tabs . '</div><div class="rkga-grid">' . $html . '</div></div>';
});
function rk_gal_needed() { if (is_admin()) return false; if (!empty($GLOBALS['rk_gal_used'])) return true; $id = get_queried_object_id(); if (!$id) $id = (int) get_option('page_on_front'); if (!$id) return false; return strpos((string) get_post_meta($id, '_elementor_data', true), '[rk_galerija') !== false; }
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
function rk_gal_print_css() {
    if (!empty($GLOBALS['rk_gal_css_done'])) return; $GLOBALS['rk_gal_css_done'] = true;
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
.rkg-mini{display:grid;grid-template-columns:repeat(3,1fr);gap:3px;padding:3px 0 0;background:#fff}.rkg-mi{aspect-ratio:4/3;border-radius:0;overflow:hidden;background:#F1F5F9;display:grid;place-items:center;font-size:11px;font-weight:700;color:#166534;border:0;min-width:0}.rkg-mi{position:relative}.rkg-mini .rkg-mi img,.rkg-mini .rkg-mi-v img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block;transform:none!important;border-radius:0!important}.rkg-mi-v{background:#14532D;color:#fff;font-size:10px;position:relative}.rkg-mi-v img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}.rkg-mi-v i{position:absolute;inset:0;display:grid;place-items:center;font-style:normal;color:#fff;text-shadow:0 1px 4px rgba(0,0,0,.7);font-size:11px}.rkg-mi-more{background:#F0FDF4}.rkg-mi-empty{visibility:hidden}
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
body .rk-lb>button{position:absolute;background:rgba(255,255,255,.12)!important;color:#fff!important;border:0!important;box-shadow:none!important;padding:0!important;margin:0!important;width:48px;height:48px;min-height:0!important;border-radius:50%!important;font-size:28px!important;line-height:1!important;font-family:inherit!important;cursor:pointer;display:grid;place-items:center;z-index:2;text-transform:none!important;letter-spacing:0!important}body .rk-lb>button:hover,body .rk-lb>button:focus{background:rgba(255,255,255,.28)!important;color:#fff!important;outline:none}body .rk-lb>button:focus,body .rk-lb>button:focus-visible{outline:none!important}body .rk-lb>button.x,body .rk-lb>button.x:hover,body .rk-lb>button.x:focus{background:transparent!important;color:#fff!important}body .rk-lb .rk-lb-thumbs button{box-shadow:none!important;min-height:0!important;margin:0!important;padding:0!important;border-radius:8px!important;text-transform:none!important}.rk-lb .x{top:12px;right:12px;font-size:20px}.rk-lb .p{left:12px;top:50%;transform:translateY(-50%)}.rk-lb .n{right:12px;top:50%;transform:translateY(-50%)}
@media(max-width:600px){.rk-lb{padding:52px 8px 8px}body .rk-lb>button.p,body .rk-lb>button.n{top:50%;bottom:auto;transform:translateY(-50%);width:38px;height:38px;font-size:24px!important;background:rgba(17,24,39,.55)!important}body .rk-lb>button.p{left:6px}body .rk-lb>button.n{right:6px}.rk-lb .rk-lb-thumbs button{width:46px;height:36px}}
RKGALCSS;
    echo '<style id="rk-gal">' . $css . '</style>';
}
add_action('wp_head', function () { if (rk_gal_needed()) rk_gal_print_css(); }, 97);
add_action('wp_footer', function () { if (!empty($GLOBALS['rk_gal_used'])) rk_gal_print_css(); }, 1);
// ---------- Valdymo skydelis v3: darbai su keliomis nuotraukomis / video ----------
function rk_galui_page() {
    $nonce = wp_create_nonce('wp_rest');
    ?>
<div id="rkg-app" class="rkg-app">
  <style>
  #wpcontent{background:#F4F7F5}.rkg-app{--g:#166534;--gd:#14532D;--acc:#22C55E;--tx:#111827;--mut:#64748B;--line:#E2E8F0;font-family:Inter,"Segoe UI",system-ui,sans-serif;color:var(--tx);margin:0 20px 90px 0;max-width:1440px}
  .rkg-app *{box-sizing:border-box}.rkg-app button{font-family:inherit}
  .rkg-top{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:24px 0 16px;flex-wrap:wrap}
  .rkg-top h1{margin:0;font-size:26px;font-weight:800;letter-spacing:-.01em;display:flex;align-items:center;gap:12px}.rkg-top h1 .ic{width:42px;height:42px;border-radius:12px;background:var(--g);color:#fff;display:grid;place-items:center}
  .rkg-top p{margin:6px 0 0;color:var(--mut);font-size:13.5px}.rkg-top p b{color:var(--g)}
  .rkg-btn{display:inline-flex;align-items:center;gap:8px;padding:11px 18px;border-radius:11px;font-weight:700;font-size:13.5px;border:1.5px solid transparent;cursor:pointer;line-height:1;transition:.15s;white-space:nowrap}
  .rkg-btn.p{background:var(--acc);color:#052E16}.rkg-btn.p:hover{background:#16a34a}.rkg-btn.g{background:var(--g);color:#fff}.rkg-btn.g:hover{background:var(--gd)}.rkg-btn.o{background:#fff;color:var(--g);border-color:var(--line)}.rkg-btn.o:hover{border-color:var(--g)}.rkg-btn.d{background:#fff;color:#b91c1c;border-color:#fecaca}.rkg-btn.d:hover{background:#fef2f2}.rkg-btn.s{padding:8px 12px;font-size:12.5px;border-radius:9px}.rkg-btn:disabled{opacity:.5;cursor:default}
  .rkg-help{background:#fff;border:1px solid var(--line);border-radius:16px;padding:16px 18px;margin-bottom:16px;display:none}.rkg-help.on{display:block}.rkg-help h3{margin:0 0 10px;font-size:15px}.rkg-help ol{margin:0;padding:0;list-style:none;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px}.rkg-help li{display:flex;gap:10px;font-size:13px;color:#334155;line-height:1.5}.rkg-help li i{flex:0 0 26px;height:26px;border-radius:50%;background:var(--g);color:#fff;font-style:normal;font-weight:800;display:grid;place-items:center;font-size:12px}
  .rkg-lay{display:grid;grid-template-columns:270px 1fr;gap:16px;align-items:start}@media(max-width:1100px){.rkg-lay{grid-template-columns:1fr}}
  .rkg-side{background:#fff;border:1px solid var(--line);border-radius:16px;padding:12px;position:sticky;top:46px}.rkg-side h4{margin:4px 8px 8px;font-size:11.5px;letter-spacing:.1em;text-transform:uppercase;color:var(--mut)}
  .rkg-cat{display:flex;align-items:center;gap:8px;width:100%;text-align:left;padding:10px 12px;border-radius:11px;border:1.5px solid transparent;background:transparent;cursor:pointer;font-size:13.5px;font-weight:600;color:var(--tx);transition:.15s}.rkg-cat:hover{background:#F8FAFC}.rkg-cat.on{background:var(--g);color:#fff}.rkg-cat b{margin-left:auto;background:#F0FDF4;color:var(--g);border-radius:999px;padding:2px 8px;font-size:11.5px}.rkg-cat.on b{background:rgba(255,255,255,.2);color:#fff}
  .rkg-cat .mv{display:none;gap:2px}.rkg-cat:hover .mv{display:inline-flex}.rkg-cat .mv span{width:20px;height:20px;border-radius:6px;display:grid;place-items:center;font-size:10px;background:#F1F5F9;color:var(--tx)}.rkg-cat.on .mv span{background:rgba(255,255,255,.2);color:#fff}
  .rkg-cat.add{border:1.5px dashed var(--line);color:var(--mut);justify-content:center;margin-top:8px}.rkg-cat.add:hover{border-color:var(--g);color:var(--g)}
  .rkg-side .foot{border-top:1px solid var(--line);margin-top:10px;padding-top:10px;display:flex;flex-direction:column;gap:6px}
  .rkg-main{min-width:0}
  .rkg-head{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:12px}.rkg-head h2{margin:0;font-size:20px;font-weight:800;display:flex;align-items:center;gap:10px}.rkg-head h2 small{font-size:12.5px;color:var(--mut);font-weight:600}.rkg-head .r{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
  .rkg-head input[type=text]{border:1.5px solid var(--line);border-radius:9px;padding:9px 12px;font-size:13px;background:#fff;min-width:220px}
  .rkg-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:14px}
  .rkg-card{background:#fff;border:1px solid var(--line);border-radius:14px;overflow:hidden;position:relative;transition:.15s;display:flex;flex-direction:column}.rkg-card:hover{box-shadow:0 18px 40px -22px rgba(15,74,40,.35)}
  .rkg-card .ph{aspect-ratio:4/3;background:#0f172a center/cover no-repeat;cursor:pointer;position:relative}.rkg-card .ph .n{position:absolute;left:10px;bottom:10px;background:rgba(17,24,39,.72);color:#fff;font-size:11px;font-weight:700;padding:4px 8px;border-radius:999px}.rkg-card .ph .play{position:absolute;top:50%;left:50%;width:40px;height:40px;margin:-20px 0 0 -20px;border-radius:50%;background:rgba(255,255,255,.92);color:var(--g);display:grid;place-items:center;font-size:15px;padding-left:3px}
  .rkg-card .ph .mv{position:absolute;right:8px;top:8px;display:flex;gap:4px;opacity:0;transition:.15s}.rkg-card:hover .ph .mv{opacity:1}.rkg-card .ph .mv button{width:28px;height:28px;border-radius:8px;background:rgba(255,255,255,.95);border:0;color:var(--tx);cursor:pointer;font-size:12px;display:grid;place-items:center}.rkg-card .ph .mv button:disabled{opacity:.35}
  .rkg-card .bd{padding:10px 12px 12px;display:flex;flex-direction:column;gap:8px}.rkg-card .t{font-weight:600;font-size:13px;line-height:1.35;min-height:35px}.rkg-card .act{display:flex;gap:6px}.rkg-card .act .rkg-btn{flex:1;justify-content:center}
  .rkg-empty{background:#fff;border:1px dashed var(--line);border-radius:16px;padding:40px;text-align:center;color:var(--mut)}
  .rkg-modal{position:fixed;inset:0;background:rgba(17,24,39,.55);z-index:100000;display:none;align-items:flex-start;justify-content:center;padding:30px 16px;overflow:auto}.rkg-modal.on{display:flex}
  .rkg-modal .box{background:#fff;border-radius:18px;width:100%;max-width:920px;padding:22px;box-shadow:0 30px 80px rgba(0,0,0,.35)}.rkg-modal h3{margin:0 0 14px;font-size:19px;font-weight:800;display:flex;justify-content:space-between;align-items:center}.rkg-modal h3 button{border:0;background:#F1F5F9;width:34px;height:34px;border-radius:50%;cursor:pointer;font-size:16px}
  .rkg-modal label{display:block;font-size:12.5px;font-weight:700;color:#334155;margin:12px 0 5px}.rkg-modal input[type=text],.rkg-modal select,.rkg-modal textarea{width:100%;border:1.5px solid var(--line);border-radius:10px;padding:10px 12px;font-size:14px;font-family:inherit;background:#fff}.rkg-modal input:focus,.rkg-modal select:focus,.rkg-modal textarea:focus{border-color:var(--acc);outline:0}
  .rkg-modal .two{display:grid;grid-template-columns:1fr 260px;gap:14px}@media(max-width:700px){.rkg-modal .two{grid-template-columns:1fr}}
  .rkg-drop{border:2px dashed #BBF7D0;background:#F0FDF4;border-radius:14px;padding:18px;text-align:center;color:var(--g);font-weight:700;margin-top:12px;cursor:pointer;transition:.15s;font-size:13.5px}.rkg-drop:hover,.rkg-drop.over{background:#DCFCE7;border-color:var(--acc)}.rkg-drop small{display:block;color:var(--mut);font-weight:500;margin-top:4px;font-size:12px}
  .rkg-media{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:10px;margin-top:12px}
  .rkg-m{position:relative;border:1.5px solid var(--line);border-radius:10px;overflow:hidden;background:#0f172a;aspect-ratio:4/3}.rkg-m img,.rkg-m video{width:100%;height:100%;object-fit:cover;display:block}.rkg-m.cov{border-color:var(--acc);box-shadow:0 0 0 2px #BBF7D0}
  .rkg-m .tag{position:absolute;left:6px;top:6px;background:rgba(17,24,39,.75);color:#fff;font-size:10px;font-weight:700;padding:2px 6px;border-radius:999px}.rkg-m .tag.v{background:#b91c1c}
  .rkg-m .tools{position:absolute;inset:auto 0 0 0;display:flex;justify-content:space-between;padding:5px;background:linear-gradient(transparent,rgba(0,0,0,.6));opacity:0;transition:.15s}.rkg-m:hover .tools{opacity:1}.rkg-m .tools button{width:24px;height:24px;border-radius:6px;border:0;background:rgba(255,255,255,.95);cursor:pointer;font-size:11px;display:grid;place-items:center;color:var(--tx)}.rkg-m .tools button.x{color:#b91c1c}.rkg-m .tools button.c{color:var(--g)}
  .rkg-m .up{position:absolute;inset:0;background:rgba(17,24,39,.7);color:#fff;display:grid;place-items:center;font-size:12px;font-weight:700}
  .rkg-modal .foot{display:flex;justify-content:space-between;gap:10px;margin-top:18px;flex-wrap:wrap}
  #rkg-toast{position:fixed;left:50%;bottom:24px;transform:translateX(-50%);background:#111827;color:#fff;padding:12px 18px;border-radius:12px;font-size:13.5px;font-weight:600;z-index:100001;display:none;box-shadow:0 20px 50px rgba(0,0,0,.3)}#rkg-toast.on{display:block}#rkg-toast.err{background:#b91c1c}
  </style>
  <div class="rkg-top"><div><h1><span class="ic">&#9635;</span>Galerija – atlikti darbai</h1><p>Vienas darbas = pavadinimas (markė, modelis, kas atlikta) + kelios nuotraukos ir/arba video. Svetainėje rodoma viena kortelė, paspaudus – visos to darbo nuotraukos. <b id="rkg-sum"></b></p></div>
    <div style="display:flex;gap:8px;flex-wrap:wrap"><button class="rkg-btn o" id="rkg-helpb">? Kaip naudotis</button><button class="rkg-btn p" id="rkg-new">+ Naujas darbas</button></div></div>
  <div class="rkg-help" id="rkg-help"><h3>Kaip naudotis</h3><ol>
    <li><i>1</i>Kairėje pasirinkite kategoriją (paslaugą). Kategorijų eiliškumas (↑↓) = eiliškumas svetainėje.</li>
    <li><i>2</i>„+ Naujas darbas“ → įrašykite pavadinimą (pvz. „Audi Q5 2018 m. antro rakto gamyba“), pasirinkite kategoriją, įkelkite nuotraukas ir video (galima kelis iš karto).</li>
    <li><i>3</i>Nuotraukų tvarką keiskite rodyklėmis, ★ pažymėkite viršelį (rodomas kortelėje). Video rodomas su ▶ ženklu.</li>
    <li><i>4</i>„Išsaugoti“ – darbas iš karto matomas svetainėje. Kortelių eiliškumą keiskite rodyklėmis ant kortelės.</li>
  </ol></div>
  <div class="rkg-lay">
    <aside class="rkg-side"><h4>Kategorijos</h4><div id="rkg-cats"></div><button class="rkg-cat add" id="rkg-addcat">+ Nauja kategorija</button>
      <div class="foot"><button class="rkg-btn o s" id="rkg-editcat">Kategorijos nustatymai</button></div></aside>
    <main class="rkg-main"><div class="rkg-head"><h2 id="rkg-title">Visi darbai <small id="rkg-cnt"></small></h2><div class="r"><input type="text" id="rkg-q" placeholder="Ieškoti pagal pavadinimą…"></div></div><div id="rkg-list" class="rkg-grid"></div></main>
  </div>
  <div class="rkg-modal" id="rkg-ed"><div class="box"><h3><span id="rkg-ed-title">Naujas darbas</span><button type="button" id="rkg-ed-x">✕</button></h3>
    <div class="two"><div><label>Pavadinimas (markė, modelis, metai, kas atlikta)</label><input type="text" id="rkg-f-title" placeholder="pvz. Audi Q5 2018 m. antro rakto gamyba"></div>
      <div><label>Kategorija</label><select id="rkg-f-cat"></select></div></div>
    <div class="rkg-drop" id="rkg-drop">Įkelti nuotraukas / video<small>Spustelėkite arba nutempkite failus čia (JPG, PNG, MP4, MOV – galima kelis iš karto)</small></div><input type="file" id="rkg-file" multiple accept="image/*,video/mp4,video/quicktime" style="display:none">
    <div class="rkg-media" id="rkg-media"></div>
    <div class="foot"><button class="rkg-btn d" id="rkg-del" style="display:none">Ištrinti darbą</button><div style="display:flex;gap:8px;margin-left:auto"><button class="rkg-btn o" id="rkg-cancel">Atšaukti</button><button class="rkg-btn g" id="rkg-save">Išsaugoti</button></div></div>
  </div></div>
  <div class="rkg-modal" id="rkg-cm"><div class="box" style="max-width:560px"><h3><span id="rkg-cm-title">Kategorija</span><button type="button" id="rkg-cm-x">✕</button></h3>
    <label>Pavadinimas</label><input type="text" id="rkg-c-name"><label>Rodyti paslaugos puslapyje</label><select id="rkg-c-page"><option value="0">– nerodyti atskirame puslapyje (tik „Atlikti darbai“) –</option></select>
    <div class="foot"><button class="rkg-btn d" id="rkg-c-del">Ištrinti kategoriją</button><div style="display:flex;gap:8px;margin-left:auto"><button class="rkg-btn o" id="rkg-c-cancel">Atšaukti</button><button class="rkg-btn g" id="rkg-c-save">Išsaugoti</button></div></div>
  </div></div>
  <div id="rkg-toast"></div>
</div>
<script>
(function(){
var N='<?php echo esc_js($nonce); ?>',R='<?php echo esc_js(rest_url('wp/v2/')); ?>',$=function(s){return document.querySelector(s)};
var S={cats:[],pages:[],jobs:[],cat:0,q:'',edit:null,media:[],cover:0,busy:0};
function api(p,o){o=o||{};var h={'X-WP-Nonce':N};if(!(o.body instanceof FormData))h['Content-Type']='application/json';return fetch(R+p,{method:o.method||'GET',headers:h,body:o.body instanceof FormData?o.body:(o.body?JSON.stringify(o.body):undefined),credentials:'same-origin'}).then(function(r){return r.json().then(function(j){if(!r.ok)throw new Error(j.message||r.status);return j})})}
function toast(t,err){var e=$('#rkg-toast');e.textContent=t;e.className='on'+(err?' err':'');clearTimeout(e._t);e._t=setTimeout(function(){e.className=''},3200)}
function dec(s){var d=document.createElement('textarea');d.innerHTML=s;return d.value}
function esc(s){return String(s).replace(/[&<>"]/g,function(c){return{'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]})}
function load(){return Promise.all([api('rk_gal_kat?per_page=100&hide_empty=false&context=edit'),api('pages?per_page=100&_fields=id,title,parent&status=publish'),loadJobs()]).then(function(r){S.cats=r[0].map(function(c){return{id:c.id,name:dec(c.name),slug:c.slug,count:c.count,page:(c.meta&&c.meta.rk_page)||0,order:(c.meta&&c.meta.rk_order)||999}}).sort(function(a,b){return a.order-b.order||a.name.localeCompare(b.name)});S.pages=r[1];render()})}
function loadJobs(){var all=[],pg=1;function nx(){return api('rk_galerija?per_page=100&page='+pg+'&context=edit&orderby=menu_order&order=asc&_fields=id,title,menu_order,rk_gal_kat,featured_media,meta').then(function(j){all=all.concat(j);if(j.length===100){pg++;return nx()}})}return nx().then(function(){S.jobs=all.map(function(p){var m=[];try{m=JSON.parse((p.meta&&p.meta.rk_media)||'[]')}catch(e){}return{id:p.id,title:dec(p.title.raw||p.title.rendered),order:p.menu_order,cat:(p.rk_gal_kat||[])[0]||0,cover:p.featured_media,media:m}})})}
var thumbCache={};function thumb(id){if(thumbCache[id])return Promise.resolve(thumbCache[id]);return api('media/'+id+'?_fields=id,source_url,media_details,mime_type').then(function(m){var s=m.media_details&&m.media_details.sizes;var u=(s&&(s.medium_large||s.medium||s.large)&&(s.medium_large||s.medium||s.large).source_url)||m.source_url;thumbCache[id]={url:u,full:m.source_url,video:/^video/.test(m.mime_type)};return thumbCache[id]}).catch(function(){return{url:'',full:'',video:false}})}
function render(){var cs=$('#rkg-cats');var total=S.jobs.length;cs.innerHTML='<button class="rkg-cat'+(S.cat===0?' on':'')+'" data-id="0">Visi darbai<b>'+total+'</b></button>'+S.cats.map(function(c,i){var n=S.jobs.filter(function(j){return j.cat===c.id}).length;return'<button class="rkg-cat'+(S.cat===c.id?' on':'')+'" data-id="'+c.id+'"><span class="mv"><span data-mv="-1" title="Aukštyn">▲</span><span data-mv="1" title="Žemyn">▼</span></span>'+esc(c.name)+'<b>'+n+'</b></button>'}).join('');
 cs.querySelectorAll('.rkg-cat').forEach(function(b){b.addEventListener('click',function(e){var mv=e.target.getAttribute('data-mv');if(mv){e.stopPropagation();moveCat(+b.getAttribute('data-id'),+mv);return}S.cat=+b.getAttribute('data-id');render()})});
 var c=S.cats.filter(function(x){return x.id===S.cat})[0];$('#rkg-title').firstChild.textContent=(c?c.name:'Visi darbai')+' ';
 var list=S.jobs.filter(function(j){return(!S.cat||j.cat===S.cat)&&(!S.q||j.title.toLowerCase().indexOf(S.q)>=0)});$('#rkg-cnt').textContent=list.length+' darb.';
 var ni=S.jobs.reduce(function(a,j){return a+j.media.filter(function(m){return m.t!=='v'}).length},0),nv=S.jobs.reduce(function(a,j){return a+j.media.filter(function(m){return m.t==='v'}).length},0);$('#rkg-sum').textContent=total+' darbai · '+ni+' nuotr. · '+nv+' video';
 var L=$('#rkg-list');if(!list.length){L.innerHTML='<div class="rkg-empty" style="grid-column:1/-1">Šioje kategorijoje darbų dar nėra. Spauskite „+ Naujas darbas“.</div>';return}
 L.innerHTML=list.map(function(j,i){var ni=j.media.filter(function(m){return m.t!=='v'}).length,nv=j.media.filter(function(m){return m.t==='v'}).length;return'<div class="rkg-card" data-id="'+j.id+'"><div class="ph" data-th="'+(j.cover||(j.media[0]&&j.media[0].id)||0)+'">'+(nv?'<span class="play">▶</span>':'')+'<span class="n">'+(ni?ni+' nuotr.':'')+(ni&&nv?' · ':'')+(nv?nv+' video':'')+'</span><span class="mv"><button data-mv="-1" '+(i===0?'disabled':'')+' title="Ankstesnis">◀</button><button data-mv="1" '+(i===list.length-1?'disabled':'')+' title="Vėlesnis">▶</button></span></div><div class="bd"><div class="t">'+esc(j.title)+'</div><div class="act"><button class="rkg-btn o s" data-ed>Redaguoti</button></div></div></div>'}).join('');
 L.querySelectorAll('.rkg-card').forEach(function(card){var id=+card.getAttribute('data-id');var ph=card.querySelector('.ph');var th=+ph.getAttribute('data-th');if(th)thumb(th).then(function(t){if(t.url)ph.style.backgroundImage='url("'+t.url+'")'});ph.addEventListener('click',function(e){if(e.target.closest('[data-mv]'))return;openEdit(id)});card.querySelector('[data-ed]').addEventListener('click',function(){openEdit(id)});card.querySelectorAll('[data-mv]').forEach(function(b){b.addEventListener('click',function(){moveJob(id,+b.getAttribute('data-mv'),list)})})})}
function moveCat(id,d){var i=S.cats.findIndex(function(c){return c.id===id}),j=i+d;if(j<0||j>=S.cats.length)return;var a=S.cats[i];S.cats.splice(i,1);S.cats.splice(j,0,a);Promise.all(S.cats.map(function(c,k){c.order=k+1;return api('rk_gal_kat/'+c.id,{method:'POST',body:{meta:{rk_order:k+1}}})})).then(function(){render();toast('Kategorijų eiliškumas išsaugotas')}).catch(function(e){toast('Klaida: '+e.message,1)})}
function moveJob(id,d,list){var i=list.findIndex(function(j){return j.id===id}),j=i+d;if(j<0||j>=list.length)return;var a=list[i],b=list[j];var oa=a.order,ob=b.order;if(oa===ob)ob=oa+d;a.order=ob;b.order=oa;Promise.all([api('rk_galerija/'+a.id,{method:'POST',body:{menu_order:a.order}}),api('rk_galerija/'+b.id,{method:'POST',body:{menu_order:b.order}})]).then(function(){S.jobs.sort(function(x,y){return x.order-y.order||y.id-x.id});render()}).catch(function(e){toast('Klaida: '+e.message,1)})}
function openEdit(id){var j=S.jobs.filter(function(x){return x.id===id})[0];S.edit=j||null;S.media=j?j.media.map(function(m){var o={t:m.t,id:m.id};if(m.p)o.p=m.p;return o}):[];S.cover=j?j.cover:0;$('#rkg-ed-title').textContent=j?'Redaguoti darbą':'Naujas darbas';$('#rkg-f-title').value=j?j.title:'';var sel=$('#rkg-f-cat');sel.innerHTML=S.cats.map(function(c){return'<option value="'+c.id+'">'+esc(c.name)+'</option>'}).join('');sel.value=j?j.cat:(S.cat||(S.cats[0]&&S.cats[0].id));$('#rkg-del').style.display=j?'':'none';renderMedia();$('#rkg-ed').classList.add('on');$('#rkg-f-title').focus()}
function closeEdit(){$('#rkg-ed').classList.remove('on');S.edit=null;S.media=[]}
function renderMedia(){var M=$('#rkg-media');M.innerHTML=S.media.map(function(m,i){return'<div class="rkg-m'+(m.id===S.cover?' cov':'')+'" data-i="'+i+'"><span class="tag'+(m.t==='v'?' v':'')+'">'+(m.t==='v'?'VIDEO':(m.id===S.cover?'VIRŠELIS':'nuotr.'))+'</span>'+(m.up?'<div class="up">'+m.up+'</div>':'')+'<div class="tools"><span><button data-a="l" '+(i===0?'disabled':'')+'>◀</button><button data-a="r" '+(i===S.media.length-1?'disabled':'')+'>▶</button></span><span>'+(m.t!=='v'?'<button data-a="c" class="c" title="Viršelis">★</button>':'')+'<button data-a="x" class="x" title="Pašalinti">✕</button></span></div></div>'}).join('');
 M.querySelectorAll('.rkg-m').forEach(function(el){var i=+el.getAttribute('data-i'),m=S.media[i];if(m.id)thumb(m.p||m.id).then(function(t){if(m.t==='v'&&!m.p){var v=document.createElement('video');v.src=t.full;v.muted=true;v.preload='metadata';el.insertBefore(v,el.firstChild)}else if(t.url){var im=document.createElement('img');im.src=t.url;el.insertBefore(im,el.firstChild)}});el.querySelectorAll('[data-a]').forEach(function(b){b.addEventListener('click',function(){var a=b.getAttribute('data-a');if(a==='x'){S.media.splice(i,1);if(S.cover===m.id)S.cover=0}if(a==='c')S.cover=m.id;if(a==='l'){S.media.splice(i-1,0,S.media.splice(i,1)[0])}if(a==='r'){S.media.splice(i+1,0,S.media.splice(i,1)[0])}renderMedia()})})})}
function shrink(f){return new Promise(function(res){if(!/^image\/(jpeg|png|webp)$/.test(f.type)||f.size<600*1024)return res(f);var img=new Image(),u=URL.createObjectURL(f);img.onload=function(){var MAX=1800,w=img.width,h=img.height;if(Math.max(w,h)<=MAX&&f.size<2.5*1024*1024){URL.revokeObjectURL(u);return res(f)}var k=Math.min(1,MAX/Math.max(w,h)),cv=document.createElement('canvas');cv.width=Math.round(w*k);cv.height=Math.round(h*k);cv.getContext('2d').drawImage(img,0,0,cv.width,cv.height);cv.toBlob(function(b){URL.revokeObjectURL(u);if(!b)return res(f);var nf=new File([b],f.name.replace(/\.[^.]+$/,'')+'.jpg',{type:'image/jpeg'});res(nf.size<f.size?nf:f)},'image/jpeg',.86)};img.onerror=function(){URL.revokeObjectURL(u);res(f)};img.src=u})}
function capture(f){return new Promise(function(res){try{var v=document.createElement('video');v.muted=true;v.playsInline=true;v.preload='auto';var u=URL.createObjectURL(f);var done=false;function fin(b){if(done)return;done=true;URL.revokeObjectURL(u);res(b)}v.addEventListener('loadeddata',function(){try{v.currentTime=Math.min(1,(v.duration||2)/2)}catch(e){fin(null)}});v.addEventListener('seeked',function(){try{var cv=document.createElement('canvas');var k=Math.min(1,1200/Math.max(v.videoWidth,v.videoHeight));cv.width=Math.round(v.videoWidth*k);cv.height=Math.round(v.videoHeight*k);cv.getContext('2d').drawImage(v,0,0,cv.width,cv.height);cv.toBlob(function(b){fin(b)},'image/jpeg',.85)}catch(e){fin(null)}});v.addEventListener('error',function(){fin(null)});setTimeout(function(){fin(null)},15000);v.src=u}catch(e){res(null)}})}
function addFiles(files){Array.prototype.forEach.call(files,function(f){var isV=/^video\//.test(f.type)||/\.(mp4|mov)$/i.test(f.name);if(!isV&&!/^image\//.test(f.type))return;var m={t:isV?'v':'i',id:0,up:'Keliama…'};S.media.push(m);renderMedia();(isV?Promise.resolve(f):shrink(f)).then(function(ff){var fd=new FormData();fd.append('file',ff,ff.name);fd.append('title',$('#rkg-f-title').value||ff.name);return api('media',{method:'POST',body:fd})}).then(function(r){m.id=r.id;thumbCache[r.id]={url:(r.media_details&&r.media_details.sizes&&(r.media_details.sizes.medium_large||r.media_details.sizes.medium)&&(r.media_details.sizes.medium_large||r.media_details.sizes.medium).source_url)||r.source_url,full:r.source_url,video:isV};if(!S.cover&&!isV)S.cover=r.id;if(!isV){delete m.up;renderMedia();return}m.up='Kadras…';renderMedia();return capture(f).then(function(b){if(!b){delete m.up;renderMedia();return}var fd2=new FormData();fd2.append('file',b,f.name.replace(/\.[^.]+$/,'')+'-kadras.jpg');fd2.append('title',$('#rkg-f-title').value||f.name);return api('media',{method:'POST',body:fd2}).then(function(p){m.p=p.id;if(!S.cover)S.cover=p.id}).catch(function(){}).then(function(){delete m.up;renderMedia()})})}).catch(function(e){S.media.splice(S.media.indexOf(m),1);renderMedia();toast('Nepavyko įkelti '+f.name+': '+e.message,1)})})}
function save(){var t=$('#rkg-f-title').value.trim(),cat=+$('#rkg-f-cat').value;if(!t)return toast('Įrašykite pavadinimą',1);if(S.media.some(function(m){return m.up}))return toast('Palaukite, kol įkels failus',1);var med=S.media.filter(function(m){return m.id}).map(function(m){var o={t:m.t,id:m.id};if(m.p)o.p=m.p;return o});if(!med.length)return toast('Įkelkite bent vieną nuotrauką ar video',1);var cover=S.cover;if(!med.some(function(m){return m.id===cover||m.p===cover})){cover=0;med.forEach(function(m){if(!cover&&m.t==='i')cover=m.id});if(!cover)med.forEach(function(m){if(!cover&&m.p)cover=m.p})}
 var body={title:t,status:'publish',rk_gal_kat:[cat],featured_media:cover||0,meta:{rk_media:JSON.stringify(med)}};if(!S.edit)body.menu_order=(S.jobs.reduce(function(a,j){return Math.max(a,j.order)},0)+1);
 $('#rkg-save').disabled=true;api('rk_galerija'+(S.edit?'/'+S.edit.id:''),{method:'POST',body:body}).then(function(){closeEdit();toast('Išsaugota');return loadJobs()}).then(render).catch(function(e){toast('Klaida: '+e.message,1)}).then(function(){$('#rkg-save').disabled=false})}
function delJob(){if(!S.edit||!confirm('Ištrinti darbą „'+S.edit.title+'“? Nuotraukos liks medijos bibliotekoje.'))return;api('rk_galerija/'+S.edit.id,{method:'DELETE'}).then(function(){closeEdit();toast('Ištrinta');return loadJobs()}).then(render).catch(function(e){toast('Klaida: '+e.message,1)})}
var catEdit=null;function openCat(c){catEdit=c||null;$('#rkg-cm-title').textContent=c?'Kategorijos nustatymai':'Nauja kategorija';$('#rkg-c-name').value=c?c.name:'';var s=$('#rkg-c-page');s.innerHTML='<option value="0">– nerodyti atskirame puslapyje (tik „Atlikti darbai“) –</option>'+S.pages.map(function(p){return'<option value="'+p.id+'">'+esc(dec(p.title.rendered))+'</option>'}).join('');s.value=c?c.page:0;$('#rkg-c-del').style.display=c?'':'none';$('#rkg-cm').classList.add('on')}
function saveCat(){var n=$('#rkg-c-name').value.trim(),pg=+$('#rkg-c-page').value;if(!n)return toast('Įrašykite pavadinimą',1);var body={name:n,meta:{rk_page:pg}};if(!catEdit)body.meta.rk_order=S.cats.length+1;api('rk_gal_kat'+(catEdit?'/'+catEdit.id:''),{method:'POST',body:body}).then(function(){$('#rkg-cm').classList.remove('on');toast('Išsaugota');return load()}).catch(function(e){toast('Klaida: '+e.message,1)})}
function delCat(){if(!catEdit)return;var n=S.jobs.filter(function(j){return j.cat===catEdit.id}).length;if(n)return toast('Kategorijoje yra '+n+' darbai – pirma perkelkite juos į kitą kategoriją',1);if(!confirm('Ištrinti kategoriją „'+catEdit.name+'“?'))return;api('rk_gal_kat/'+catEdit.id+'?force=true',{method:'DELETE'}).then(function(){$('#rkg-cm').classList.remove('on');if(S.cat===catEdit.id)S.cat=0;return load()}).catch(function(e){toast('Klaida: '+e.message,1)})}
$('#rkg-helpb').addEventListener('click',function(){$('#rkg-help').classList.toggle('on')});$('#rkg-new').addEventListener('click',function(){openEdit(0)});$('#rkg-ed-x').addEventListener('click',closeEdit);$('#rkg-cancel').addEventListener('click',closeEdit);$('#rkg-save').addEventListener('click',save);$('#rkg-del').addEventListener('click',delJob);
$('#rkg-q').addEventListener('input',function(){S.q=this.value.trim().toLowerCase();render()});
var D=$('#rkg-drop'),F=$('#rkg-file');D.addEventListener('click',function(){F.click()});F.addEventListener('change',function(){addFiles(F.files);F.value=''});['dragenter','dragover'].forEach(function(ev){D.addEventListener(ev,function(e){e.preventDefault();D.classList.add('over')})});['dragleave','drop'].forEach(function(ev){D.addEventListener(ev,function(e){e.preventDefault();D.classList.remove('over')})});D.addEventListener('drop',function(e){addFiles(e.dataTransfer.files)});
$('#rkg-addcat').addEventListener('click',function(){openCat(null)});$('#rkg-editcat').addEventListener('click',function(){var c=S.cats.filter(function(x){return x.id===S.cat})[0];if(!c)return toast('Pasirinkite kategoriją kairėje',1);openCat(c)});$('#rkg-cm-x').addEventListener('click',function(){$('#rkg-cm').classList.remove('on')});$('#rkg-c-cancel').addEventListener('click',function(){$('#rkg-cm').classList.remove('on')});$('#rkg-c-save').addEventListener('click',saveCat);$('#rkg-c-del').addEventListener('click',delCat);
load().catch(function(e){toast('Nepavyko įkelti: '+e.message,1)});
})();
</script>
    <?php
}
