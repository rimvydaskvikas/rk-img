// ===== rk_gal_sc: trumpiniai (shortcode) - modulis piesia TIK nuotraukas, visa kita (sekcija, antraste, tarpai) yra Elementor =====
// [rk_galerija]                 - dabartinio puslapio kategorijos nuotraukos (slankiklis). Galima nurodyti kategorija="spynos"
// [rk_galerija_rodykles]        - slankiklio rodykles (dedamos salia antrastes; valdo artimiausia [rk_galerija] toje pacioje sekcijoje)
// [rk_galerija_visa]            - visos nuotraukos su kategoriju filtrais ir "Rodyti daugiau"
function rk_gal_items_html($posts, $with_cat = false) {
    $items = '';
    foreach ($posts as $p) {
        $tid = get_post_thumbnail_id($p->ID); if (!$tid) continue;
        $full = wp_get_attachment_image_url($tid, 'large'); $mid = wp_get_attachment_image_url($tid, 'medium_large') ?: wp_get_attachment_image_url($tid, 'large');
        $srcset = wp_get_attachment_image_srcset($tid, 'medium_large'); $srcset = $srcset ? ' srcset="' . esc_attr($srcset) . '" sizes="(max-width:767px) 85vw, (max-width:1024px) 45vw, 280px"' : '';
        $cap = esc_html(get_the_title($p)); $cat = '';
        if ($with_cat) { $tt = get_the_terms($p->ID, 'rk_gal_kat'); $cat = ($tt && !is_wp_error($tt)) ? $tt[0]->slug : ''; }
        $items .= '<a class="rkg-it"' . ($with_cat ? ' data-cat="' . esc_attr($cat) . '"' : '') . ' href="' . esc_url($full) . '" target="_blank" rel="noopener"><img loading="lazy" decoding="async" src="' . esc_url($mid) . '"' . $srcset . ' alt="' . $cap . '"><span class="rkg-cap">' . $cap . '</span></a>';
    }
    return $items;
}
add_shortcode('rk_galerija', function ($atts) {
    $atts = shortcode_atts(array('kategorija' => ''), $atts);
    if ($atts['kategorija']) { $term = get_term_by('slug', $atts['kategorija'], 'rk_gal_kat'); }
    else { $terms = get_terms(array('taxonomy' => 'rk_gal_kat', 'hide_empty' => false, 'meta_key' => 'rk_page', 'meta_value' => (int) get_the_ID())); $term = (!is_wp_error($terms) && $terms) ? $terms[0] : null; }
    if (!$term) return '<div class="rkg-wrap rkg-empty">Šiai kategorijai nuotraukų dar nėra – įkelkite jas skiltyje „Galerija“.</div>';
    $q = new WP_Query(array('post_type' => 'rk_galerija', 'posts_per_page' => -1, 'orderby' => array('menu_order' => 'ASC', 'date' => 'DESC'), 'tax_query' => array(array('taxonomy' => 'rk_gal_kat', 'terms' => $term->term_id)), 'no_found_rows' => true));
    $items = rk_gal_items_html($q->posts);
    if (!$items) return '<div class="rkg-wrap rkg-empty">Šiai kategorijai nuotraukų dar nėra – įkelkite jas skiltyje „Galerija“.</div>';
    return '<div class="rkg-wrap"><div class="rkg-grid">' . $items . '</div></div>';
});
add_shortcode('rk_galerija_rodykles', function () {
    return '<div class="rkg-nav"><button type="button" class="rkg-arrow" aria-label="Atgal">&#8249;</button><button type="button" class="rkg-arrow" aria-label="Pirmyn">&#8250;</button></div>';
});
add_shortcode('rk_galerija_visa', function () {
    $terms = get_terms(array('taxonomy' => 'rk_gal_kat', 'hide_empty' => true, 'orderby' => 'name'));
    if (is_wp_error($terms) || !$terms) return '';
    $order = array(); foreach ($terms as $t) { $order[$t->term_id] = (int) get_term_meta($t->term_id, 'rk_page', true); }
    usort($terms, function ($a, $b) use ($order) { return ($order[$a->term_id] ?: 999) - ($order[$b->term_id] ?: 999); });
    $q = new WP_Query(array('post_type' => 'rk_galerija', 'posts_per_page' => -1, 'orderby' => array('menu_order' => 'ASC', 'date' => 'DESC'), 'no_found_rows' => true));
    $items = rk_gal_items_html($q->posts, true); if (!$items) return '';
    $cnt = array(); foreach ($q->posts as $p) { $tt = get_the_terms($p->ID, 'rk_gal_kat'); $s = ($tt && !is_wp_error($tt)) ? $tt[0]->slug : ''; $cnt[$s] = isset($cnt[$s]) ? $cnt[$s] + 1 : 1; }
    $tabs = '<button type="button" class="rkga-tab on" data-cat="">Visi <b>' . count($q->posts) . '</b></button>';
    foreach ($terms as $t) { if (empty($cnt[$t->slug])) continue; $tabs .= '<button type="button" class="rkga-tab" data-cat="' . esc_attr($t->slug) . '">' . esc_html($t->name) . ' <b>' . $cnt[$t->slug] . '</b></button>'; }
    return '<div class="rkg-all"><div class="rkga-tabs">' . $tabs . '</div><div class="rkga-grid">' . $items . '</div></div>';
});
add_action('wp_footer', function () { // rk_gal_js: slankiklis + filtrai
    echo '<script>(function(){
document.querySelectorAll(".rkg-wrap").forEach(function(w){var g=w.querySelector(".rkg-grid");if(!g)return;
 var sec=w.closest("section,.e-con-boxed,.e-parent")||w.parentElement;var nav=null;while(sec&&!nav){nav=sec.querySelector(".rkg-nav");if(!nav)sec=sec.parentElement;}
 if(!nav){nav=document.createElement("div");nav.className="rkg-nav rkg-nav-inline";nav.innerHTML="<button type=button class=rkg-arrow aria-label=Atgal>‹</button><button type=button class=rkg-arrow aria-label=Pirmyn>›</button>";w.insertBefore(nav,g);}
 var b=nav.querySelectorAll("button");function step(){var it=g.querySelector(".rkg-it");return it?it.getBoundingClientRect().width+14:300;}
 function per(){return Math.max(1,Math.round(g.clientWidth/step()));}b[0].addEventListener("click",function(){g.scrollBy({left:-step()*per(),behavior:"smooth"});});b[1].addEventListener("click",function(){g.scrollBy({left:step()*per(),behavior:"smooth"});});
 var n=g.querySelectorAll(".rkg-it").length;if(n<=4)g.classList.add("rkg-few");var d=document.createElement("div");d.className="rkg-dots";g.after(d);
 function upd(){var off=g.scrollWidth<=g.clientWidth+5;nav.classList.toggle("rkg-nav-off",off);d.classList.toggle("rkg-nav-off",off);var first=Math.round(g.scrollLeft/step())+1;var last=Math.min(n,first+per()-1);d.textContent=(first===last?first:first+"–"+last)+" iš "+n;b[0].disabled=g.scrollLeft<5;b[1].disabled=g.scrollLeft+g.clientWidth>=g.scrollWidth-5;}
 g.addEventListener("scroll",upd);window.addEventListener("resize",upd);window.addEventListener("load",upd);upd();requestAnimationFrame(upd);});
var s=document.querySelector(".rkg-all");if(s){var tabs=s.querySelectorAll(".rkga-tab"),its=s.querySelectorAll(".rkg-it");var LIM=24,lim=LIM;var more=document.createElement("button");more.type="button";more.className="rkga-more";s.appendChild(more);
 function apply(){var on=s.querySelector(".rkga-tab.on");var c=on?on.getAttribute("data-cat"):"";var k=0,total=0;its.forEach(function(a){var m=!c||a.getAttribute("data-cat")===c;if(m)total++;var show=m&&k<lim;if(m)k++;a.classList.toggle("rkga-off",!show)});var left=total-Math.min(lim,total);more.style.display=left>0?"":"none";more.textContent="Rodyti daugiau ("+left+")";}
 more.addEventListener("click",function(){lim+=LIM;apply()});apply();tabs.forEach(function(t){t.addEventListener("click",function(){tabs.forEach(function(x){x.classList.remove("on")});t.classList.add("on");lim=LIM;apply();});});}
})();</script>';
}, 99);
