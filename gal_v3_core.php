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
    if (is_array($list)) foreach ($list as $m) { if (!empty($m['id']) && get_post($m['id'])) $out[] = array('t' => (isset($m['t']) && $m['t'] === 'v') ? 'v' : 'i', 'id' => (int) $m['id']); }
    if (!$out) { $tid = get_post_thumbnail_id($post_id); if ($tid) $out[] = array('t' => 'i', 'id' => (int) $tid); }
    return $out;
}
// Mini aplankas: iki 4 mazyciu miniatiuru po virseliu (rodoma tik kai darbe > 1 failo)
function rk_gal_mini($media, $cover) {
    if (count($media) < 2) return '';
    $out = ''; $n = 0;
    foreach ($media as $m) {
        if ($n >= 3) break; $n++;
        if ($m['t'] === 'v') { $out .= '<span class="rkg-mi rkg-mi-v">&#9654;</span>'; continue; }
        $u = wp_get_attachment_image_url($m['id'], 'thumbnail');
        $out .= '<span class="rkg-mi"><img loading="lazy" src="' . esc_url($u) . '" alt=""></span>';
    }
    while ($n < 3) { $n++; $out .= '<span class="rkg-mi rkg-mi-empty"></span>'; }
    return '<span class="rkg-mini">' . $out . '</span>';
}
function rk_gal_card($p, $with_cat = false) {
    $media = rk_gal_media($p->ID); if (!$media) return '';
    $cover = get_post_thumbnail_id($p->ID);
    if (!$cover) foreach ($media as $m) { if ($m['t'] === 'i') { $cover = $m['id']; break; } }
    $cap = esc_html(get_the_title($p)); $items = array(); $ni = 0; $nv = 0;
    foreach ($media as $m) {
        if ($m['t'] === 'v') { $nv++; $items[] = array('t' => 'v', 'src' => wp_get_attachment_url($m['id']), 'poster' => $cover ? wp_get_attachment_image_url($cover, 'large') : ''); }
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
function rk_gal_needed() { if (is_admin()) return false; $id = get_queried_object_id(); if (!$id) return false; return strpos((string) get_post_meta($id, '_elementor_data', true), '[rk_galerija') !== false; }
