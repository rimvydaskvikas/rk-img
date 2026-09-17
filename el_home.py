# -*- coding: utf-8 -*-
"""Titulinis rk.edvi.lt kaip grynas Elementor medis (konteineriai + standartiniai valdikliai, be custom CSS sluoksnio)."""
import json, hashlib, itertools

# ---------- konstantos ----------
G, GD, ACC, TX, MUT, LINE, GL = '#166534', '#14532D', '#22C55E', '#111827', '#64748B', '#E2E8F0', '#F0FDF4'
MAN, INT = 'Manrope', 'Inter'
UP = 'https://rk.edvi.lt/wp-content/uploads/2026/09/'
IMG = {'hero': 190, 'c1': 180, 'c2': 181, 'c3': 182, 'c4': 179, 'c5': 183, 'c6': 184, 'w1': 185, 'w2': 186, 'av_andrius': 178, 'av_rasa': 177}
IMGN = {'hero': 'hero.jpg', 'c1': 'rk-c1_atrakinimas.jpg', 'c2': 'rk-c2_gamyba.jpg', 'c3': 'rk-c3_programavimas.jpg', 'c4': 'rk-c4_spynos.jpg', 'c5': 'rk-c5_buitiniai.jpg', 'c6': 'rk-c6_galanterija.jpg', 'w1': 'rk-w1_bmw.jpg', 'w2': 'rk-w2_audi.jpg', 'av_andrius': 'rk-av_andrius.jpg', 'av_rasa': 'rk-av_rasa.jpg'}
ICO = {"btn-phone":703,"card-bag":704,"card-chip":705,"card-home":706,"card-key":707,"card-lock":708,"crow-alert":709,"crow-clock":710,"crow-mail":711,"crow-phone":712,"crow-pin":713,"feat-car":714,"feat-clock":715,"feat-key":716,"fld-car":717,"fld-phone":718,"fld-pin":719,"google":720,"logo-audi":721,"logo-bmw":722,"logo-ford":723,"logo-hyundai":724,"logo-mb":725,"logo-opel":726,"logo-subaru":727,"logo-toyota":728,"logo-volvo":729,"logo-vw":730,"safe-lock":731,"tab-car":732,"tab-key":733,"tab-lock":734,"tag-car":735,"trust-clock":736,"why-1":737,"why-2":738,"why-3":739,"why-4":740,"why-5":741}

FORM_GLOBALS = {'field_typography_typography': '', 'button_typography_typography': '', 'label_typography_typography': '', 'field_text_color': '', 'field_background_color': '', 'field_border_color': '', 'button_background_color': '', 'button_text_color': '', 'button_hover_background_color': '', 'button_hover_color': '', 'label_color': ''}
_c = itertools.count(1)
def nid(): return hashlib.md5(('rkhome%d' % next(_c)).encode()).hexdigest()[:7]

def px(v): return {'unit': 'px', 'size': v, 'sizes': []}
def pct(v): return {'unit': '%', 'size': v, 'sizes': []}
def em(v): return {'unit': 'em', 'size': v, 'sizes': []}
def box(t, r=None, b=None, l=None, unit='px'):
    if r is None: r = b = l = t
    return {'unit': unit, 'top': str(t), 'right': str(r), 'bottom': str(b), 'left': str(l), 'isLinked': t == r == b == l}
def gap(c, r=None):
    r = c if r is None else r
    return {'column': str(c), 'row': str(r), 'isLinked': c == r, 'unit': 'px', 'size': c}
def img(k): return {'url': UP + IMGN[k], 'id': IMG[k], 'size': '', 'alt': '', 'source': 'library'}
def svg(k): return {'value': {'url': UP + 'rk-ico-%s.svg' % k, 'id': ICO[k]}, 'library': 'svg'}
def shadow(h, v, blur, spread, color): return {'horizontal': h, 'vertical': v, 'blur': blur, 'spread': spread, 'color': color}

def typo(p='', fam=None, size=None, w=None, lh=None, ls=None, tr=None, style=None):
    d = {p + 'typography_typography': 'custom'}
    if fam: d[p + 'typography_font_family'] = fam
    if size is not None: d[p + 'typography_font_size'] = px(size)
    if w: d[p + 'typography_font_weight'] = str(w)
    if lh is not None: d[p + 'typography_line_height'] = em(lh) if isinstance(lh, float) and lh < 4 else px(lh)
    if ls is not None: d[p + 'typography_letter_spacing'] = ls if isinstance(ls, dict) else px(ls)
    if tr: d[p + 'typography_text_transform'] = tr
    if style: d[p + 'typography_font_style'] = style
    return d

# ---------- elementai ----------
def con(children=(), tag='div', width='full', boxed=None, dirn='column', jc=None, ai=None, g=0, wrap=False, pad=0, mh=None, w=None, bg=None, bgimg=None, bgpos=None, bgsize=None, overlay=None, border=None, radius=None, sh=None, link=None, extra=None, hide_mobile=False):
    s = {'content_width': width, 'flex_direction': dirn, 'flex_gap': gap(g) if not isinstance(g, tuple) else gap(*g), 'padding': box(pad) if not isinstance(pad, tuple) else box(*pad), 'margin': box(0), 'html_tag': tag}
    if boxed: s['boxed_width'] = px(boxed)
    if jc: s['flex_justify_content'] = jc
    if ai: s['flex_align_items'] = ai
    if wrap: s['flex_wrap'] = 'wrap'
    if mh is not None: s['min_height'] = px(mh) if not isinstance(mh, dict) else mh
    if w is not None:
        s['width'] = px(w) if not isinstance(w, dict) else w
        s['width_tablet'] = s['width']; s['width_mobile'] = s['width']
    if dirn == 'row':
        s['flex_direction_tablet'] = 'row'; s['flex_direction_mobile'] = 'row'
        if not wrap: s['flex_wrap'] = 'nowrap'; s['flex_wrap_tablet'] = 'nowrap'; s['flex_wrap_mobile'] = 'nowrap'
    if bg: s.update({'background_background': 'classic', 'background_color': bg})
    if bgimg:
        s.update({'background_background': 'classic', 'background_image': img(bgimg), 'background_position': bgpos or 'center center', 'background_size': bgsize or 'cover', 'background_repeat': 'no-repeat'})
    if overlay: s.update(overlay)
    if border:
        s.update({'border_border': 'solid', 'border_width': box(border[0]), 'border_color': border[1]})
    if radius is not None: s['border_radius'] = box(radius)
    if sh: s.update({'box_shadow_box_shadow_type': 'yes', 'box_shadow_box_shadow': sh})
    if link: s['link'] = {'url': link, 'is_external': '', 'nofollow': '', 'custom_attributes': ''}; s['html_tag'] = 'a'
    if hide_mobile: s['hide_mobile'] = 'hidden-mobile'
    if extra:
        extra = dict(extra)
        if '_margin' in extra: s['margin'] = extra.pop('_margin')
        s.update(extra)
    return {'id': nid(), 'elType': 'container', 'settings': s, 'elements': list(children), 'isInner': False}

def grid(children, cols, g=20, cols_t=None, cols_m=None, pad=0, extra=None, **kw):
    e = {'container_type': 'grid', 'grid_columns_grid': {'unit': 'custom', 'size': cols, 'sizes': []} if isinstance(cols, str) else {'unit': 'fr', 'size': cols, 'sizes': []},
         'grid_rows_grid': {'unit': 'fr', 'size': 1, 'sizes': []}, 'grid_auto_flow': 'row', 'grid_gaps': gap(g) if not isinstance(g, tuple) else gap(*g)}
    if cols_t is not None: e['grid_columns_grid_tablet'] = {'unit': 'fr', 'size': cols_t, 'sizes': []}
    if cols_m is not None: e['grid_columns_grid_mobile'] = {'unit': 'fr', 'size': cols_m, 'sizes': []}
    if extra: e.update(extra)
    return con(children, pad=pad, extra=e, **kw)

def wid(t, s):
    s = dict(s); s.setdefault('_margin', box(0))
    return {'id': nid(), 'elType': 'widget', 'widgetType': t, 'settings': s, 'elements': []}

def heading(text, tag='h2', color=TX, fam=MAN, size=17, w=700, lh=1.2, ls=None, tr=None, align=None, extra=None):
    s = {'title': text, 'header_size': tag, 'title_color': color, **typo('', fam, size, w, lh, ls if ls is not None else 0, tr), '__globals__': {'typography_typography': '', 'title_color': ''}}
    if align: s['align'] = align
    if extra: s.update(extra)
    return wid('heading', s)

def icon(k, size, align='center', extra=None):
    s = {'selected_icon': svg(k), 'size': px(size), 'view': 'default', 'align': align}
    if extra: s.update(extra)
    return wid('icon', s)

def button(text, url, bg, color, border=None, ico=None, ico_align='left', ico_gap=9, full=False, extra=None, size=15):
    s = {'text': text, 'link': {'url': url, 'is_external': '', 'nofollow': '', 'custom_attributes': ''}, 'size': 'sm', 'align': 'justify' if full else 'left',
         'button_text_color': color, 'hover_color': color, 'border_radius': box(12), 'text_padding': box(14, 24, 14, 24), **typo('', INT, size, 600, 1.0),
         'background_color': bg, 'button_background_hover_color': bg, 'border_border': 'solid', 'border_width': box(0), 'border_color': bg, 'typography_letter_spacing': px(0), '__globals__': {'typography_typography': '', 'button_text_color': '', 'background_color': '', 'hover_color': '', 'button_background_hover_color': '', 'border_color': '', 'hover_border_color': ''}}
    if border: s.update({'border_border': 'solid', 'border_width': box(border[0]), 'border_color': border[1], 'hover_border_color': border[1]})
    if bg in (None, 'transparent'): s['background_color'] = 'rgba(0,0,0,0)'; s['button_background_hover_color'] = 'rgba(0,0,0,0)'
    if ico: s.update({'selected_icon': svg(ico) if isinstance(ico, str) and ico in ICO else {'value': ico, 'library': 'fa-solid'}, 'icon_align': ico_align, 'icon_indent': px(ico_gap)})
    if not full: s['_element_width'] = 'auto'
    if extra: s.update(extra)
    return wid('button', s)

def image(k, extra=None):
    s = {'image': img(k), 'image_size': 'full'}
    if extra: s.update(extra)
    return wid('image', s)

# ---------- HERO ----------
def hero():
    feats = []
    for k, t in [('feat-car', 'Visiems automobilių modeliams'), ('feat-key', 'Raktų gamyba ir programavimas'), ('feat-clock', 'Avarinė pagalba 24/7')]:
        ic = con([icon(k, 20)], w=42, mh=42, jc='center', ai='center', bg='rgba(0,0,0,0.18)', border=(1, 'rgba(37,194,116,0.85)'), radius=11, extra={'_flex_size': 'none'})
        feats.append(con([ic, heading(t, 'div', '#e9f4ee', INT, 13.5, 400, 1.3)], dirn='row', ai='center', g=11, extra={'width': px(200), 'width_tablet': px(200), 'width_mobile': px(200), '_flex_size': 'none'}))
    left = con([
        heading('Automobilių raktų gamyba ir avarinis <span style="color:#22C55E">atrakinimas</span>', 'h1', '#fff', MAN, 50, 800, 1.06, {'unit': 'em', 'size': -0.01, 'sizes': []}, extra={'_margin': box(14, 0, 16, 0), 'typography_font_size_tablet': px(42), 'typography_font_size_mobile': px(36)}),
        heading('Raktų gamyba, programavimas, spynų remontas ir avarinis atrakinimas. Greita, profesionali pagalba Jums patogioje vietoje.', 'p', '#d3e6da', INT, 17, 400, 28.9, extra={'_margin': box(0, 0, 30, 0), '_element_width': 'initial', '_element_custom_width': px(520)}),
        con(feats, dirn='row', wrap=True, g=22, extra={'_margin': box(0, 0, 34, 0)}),
        con([button('Skambinti dabar', 'tel:+37060012345', ACC, '#052E16', ico='btn-phone'),
             button('Sužinoti daugiau', '/paslaugos/', 'transparent', '#fff', border=(1.5, 'rgba(255,255,255,0.35)'), ico='fas fa-arrow-right', ico_align='right', ico_gap=9, extra={'icon_size': px(13)})],
            dirn='row', wrap=True, g=14, ai='center'),
    ], g=0)
    # forma
    form = wid('form', hero_form())
    card = con([
        heading('Reikia pagalbos dabar?', 'h3', TX, MAN, 21, 800, 1.2, align='center', extra={'_margin': box(0, 0, 4, 0)}),
        heading('Užpildykite formą – susisieksime per kelias minutes.', 'p', MUT, INT, 13.5, 400, 28.9, align='center', extra={'_margin': box(0, 0, 16, 0)}),
        form,
        con([icon('safe-lock', 13, extra={'_element_width': 'auto'}), heading('Jūsų duomenys yra saugūs.', 'div', MUT, INT, 12.5, 400, 28.9, extra={'_element_width': 'auto'})], dirn='row', jc='center', ai='center', g=6, extra={'_margin': box(16, 0, 0, 0)}),
        con([icon('trust-clock', 20, extra={'_element_width': 'auto', '_flex_size': 'none'}),
             heading('<b style="color:#166534">Susisiekiame per 5 min.</b><br>Dirbame Kaune ir aplinkiniuose miestuose.', 'div', '#3a463f', INT, 13, 400, 28.9)],
            dirn='row', ai='center', g=10, pad=(11, 13, 11, 13), bg=GL, border=(1, '#d0edda'), radius=12, extra={'_margin': box(12, 0, 0, 0)}),
    ], pad=(26, 26, 22, 26), bg='#fff', radius=20, sh=shadow(0, 34, 70, -24, 'rgba(0,0,0,0.55)'), extra={'color': TX})
    wrap = grid([left, card], '1.15fr .85fr', g=46, cols_t=1, cols_m=1, extra={'grid_gaps_tablet': gap(32)})
    return con([wrap], tag='section', width='boxed', boxed=1136, pad=(66, 22, 78, 22), bgimg='hero', bgpos='center right', bgsize='cover',
               overlay={'background_overlay_background': 'gradient', 'background_overlay_color': 'rgba(8,32,20,0.96)', 'background_overlay_color_b': 'rgba(8,32,20,0.3)',
                        'background_overlay_gradient_type': 'linear', 'background_overlay_gradient_angle': {'unit': 'deg', 'size': 97, 'sizes': []},
                        'background_overlay_color_stop': pct(0), 'background_overlay_color_b_stop': pct(100), 'background_overlay_opacity': px(1)},
               extra={'padding_mobile': box(44, 22, 44, 22), 'background_color': '#0d3320', '_element_id': 'rk-hero'})

def hero_form():
    fields = [
        {'custom_id': 'service', 'field_label': 'Paslauga', 'field_type': 'radio', 'field_options': 'Avarinis atrakinimas\nRaktų gamyba\nSpynų remontas', 'field_value': 'Avarinis atrakinimas', 'required': 'true', 'width': '100', 'inline_list': 'elementor-subgroup-inline', '_id': 'svc01'},
        {'custom_id': 'phone', 'field_label': 'Telefonas', 'field_type': 'tel', 'placeholder': '+370 6XX XXXXX', 'required': 'true', 'width': '100', '_id': 'phn01'},
        {'custom_id': 'brand', 'field_label': 'Automobilio markė', 'field_type': 'select', 'field_options': 'Automobilio markė\nAudi\nBMW\nMercedes-Benz\nVolkswagen\nToyota\nŠkoda\nFord\nHyundai\nOpel\nLexus\nVolvo\nRenault\nPeugeot\nKia\nNissan\nHonda\nMazda\nKita', 'width': '100', '_id': 'brn01'},
        {'custom_id': 'location', 'field_label': 'Jūsų buvimo vieta', 'field_type': 'text', 'placeholder': 'Jūsų buvimo vieta', 'width': '100', '_id': 'loc01'},
        {'custom_id': 'website', 'field_type': 'honeypot', 'width': '100', '_id': 'hp001'},
    ]
    css = ('selector .elementor-field-group{margin-bottom:10px;padding:0}'
           'selector .elementor-field-type-radio .elementor-field-subgroup{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:4px}'
           'selector .elementor-field-type-radio .elementor-field-option{margin:0;padding:0;position:relative;width:auto}'
           'selector .elementor-field-type-radio input{position:absolute;opacity:0;width:0;height:0}'
           'selector .elementor-field-type-radio label{display:flex;flex-direction:column;align-items:center;gap:5px;border:1px solid #E2E8F0;border-radius:11px;padding:10px 6px;text-align:center;font-size:11.5px;font-weight:600;color:#43514a;line-height:28.9px;font-family:Inter,sans-serif;cursor:pointer;margin:0;width:100%;letter-spacing:0}'
           'selector .elementor-field-type-radio label:before{content:"";width:18px;height:18px;background:center/contain no-repeat}'
           'selector .elementor-field-type-radio .elementor-field-option:nth-child(1) label:before{background-image:url(' + UP + 'rk-ico-tab-car.svg)}'
           'selector .elementor-field-type-radio .elementor-field-option:nth-child(2) label:before{background-image:url(' + UP + 'rk-ico-tab-key.svg)}'
           'selector .elementor-field-type-radio .elementor-field-option:nth-child(3) label:before{background-image:url(' + UP + 'rk-ico-tab-lock.svg)}'
           'selector .elementor-field-type-radio input:checked+label{background:#F0FDF4;border-color:#22C55E;color:#166534}'
           'selector .elementor-field-group input.elementor-field,selector .elementor-field-group select.elementor-field-textual{padding:12px 13px 12px 39px;background:#fff center left 13px/15px no-repeat;line-height:1;min-height:46px;box-shadow:none}selector .elementor-select-wrapper{padding:0;background:none;min-height:0}selector .elementor-field-type-honeypot{display:none}selector .e-form__buttons{margin:4px 0 0}'
           'selector .elementor-field-group-phone input.elementor-field{background-image:url(' + UP + 'rk-ico-fld-phone.svg)}'
           'selector .elementor-field-group .elementor-select-wrapper select.elementor-field-textual{background-image:url(' + UP + 'rk-ico-fld-car.svg);color:#8a968f;appearance:none;-webkit-appearance:none;padding:12px 30px 12px 39px!important;min-height:42px;height:42px!important;line-height:1!important}'
           'selector .elementor-field-group-brand .elementor-select-wrapper:after{content:"\\25BC";position:absolute;right:13px;top:50%;transform:translateY(-50%);font-size:11px;color:#43514a}'
           'selector .elementor-field-group-brand .elementor-select-wrapper{position:relative}selector .select-caret-down-wrapper{display:none}'
           'selector .elementor-field-group-location input.elementor-field{background-image:url(' + UP + 'rk-ico-fld-pin.svg)}'
           'selector .elementor-field::placeholder{color:#8a968f;opacity:1}'
           'selector .elementor-field-type-submit{margin-top:4px}selector .elementor-button{line-height:1;width:100%;justify-content:center}'
           'selector .elementor-message{font-size:13px;border-radius:10px;padding:10px 12px;margin-top:10px}')
    return {'form_name': 'Greitas pasiūlymas', 'form_fields': fields, 'button_text': 'Gauti greitą pasiūlymą', 'button_size': 'sm', 'button_width': '100', 'show_labels': '', 'mark_required': '', 'input_size': 'sm',
            'submit_actions': ['save-to-database'], 'custom_messages': 'yes', 'form_id': 'hero-form', 'success_message': 'Ačiū! Užklausa išsiųsta – susisieksime netrukus.', 'error_message': 'Nepavyko išsiųsti. Paskambinkite mums.', 'required_field_message': 'Įveskite telefono numerį.', 'invalid_message': 'Patikrinkite įvestus duomenis.',
            
            'column_gap': px(0), 'row_gap': px(0), 'field_text_color': TX, 'field_background_color': '#fff', 'field_border_color': LINE, 'field_border_width': box(1), 'field_border_radius': box(11), **typo('field_', INT, 14, 400, 1.0),
            'button_background_color': G, 'button_hover_background_color': GD, 'button_text_color': '#fff', 'button_hover_color': '#fff', 'button_border_radius': box(12), 'button_text_padding': box(14, 24, 14, 24), **typo('button_', INT, 15, 600, 1.0),
            'custom_css': css, '__globals__': FORM_GLOBALS}

# ---------- LOGOTIPAI ----------
def logos():
    items = [('logo-audi', 44), ('logo-bmw', 26), ('logo-mb', 26), ('logo-vw', 26), ('logo-toyota', 30), ('logo-subaru', 26), ('logo-ford', 44), ('logo-hyundai', 30), ('logo-opel', 26), ('logo-volvo', 26)]
    ws = [icon(k, s, extra={'_element_width': 'auto', 'primary_color': '#a9b3ad'}) for k, s in items]
    ws.append(heading('···', 'div', '#a9b3ad', INT, 22, 400, 1.0, extra={'_element_width': 'auto', 'hide_mobile': 'hidden-mobile'}))
    return con(ws, tag='div', width='boxed', boxed=1136, dirn='row', jc='space-between', ai='center', wrap=True, g=16, pad=(0, 22, 0, 22), mh=72, bg='#fafbfa',
               extra={'border_border': 'solid', 'border_width': box(0, 0, 1, 0), 'border_color': LINE})

# ---------- PASLAUGOS ----------
def sec_head(title, more=None, more_url=None):
    kids = [heading(title, 'h2', G, MAN, 38, 800, 1.2, {'unit': 'em', 'size': 0.01, 'sizes': []}, 'uppercase', align='center', extra={'typography_font_size_mobile': px(28)})]
    if more: kids.append(button(more, more_url, 'transparent', G, ico='fas fa-arrow-right', ico_align='right', ico_gap=8, size=12, extra={'text_padding': box(0), 'border_radius': box(0), 'border_width': box(0), **typo('', INT, 12, 700, 28.9, px(1.44), 'uppercase'), 'icon_size': px(11)}))
    return con(kids, ai='center', g=8, extra={'_margin': box(0, 0, 28, 0)})

def service_card(k, title, text, url, ico=None, hot=False):
    fade = '#eefbf3' if hot else '#ffffff'
    fade0 = 'rgba(238,251,243,0)' if hot else 'rgba(255,255,255,0)'
    ph = con([], extra={'background_overlay_background': 'gradient', 'background_overlay_color': fade, 'background_overlay_color_b': fade0, 'background_overlay_gradient_type': 'linear', 'background_overlay_gradient_angle': {'unit': 'deg', 'size': 90, 'sizes': []}, 'background_overlay_color_stop': pct(20), 'background_overlay_color_b_stop': pct(72), 'background_overlay_opacity': px(1), 'position': 'absolute', '_offset_orientation_h': 'end', '_offset_x_end': px(0), '_offset_y': px(0), 'width': pct(56), 'min_height': pct(100), 'background_background': 'classic', 'background_image': img(k), 'background_position': 'center right', 'background_size': 'cover', 'background_repeat': 'no-repeat', 'flex_gap': gap(0), 'padding': box(0), 'margin': box(0), 'content_width': 'full'})
    kids = []
    if hot:
        kids.append(heading('⚡ SKUBI PAGALBA 24/7', 'div', G, INT, 10, 800, 28.9, px(0.5), extra={'_element_width': 'auto', '_background_background': 'classic', '_background_color': '#dcf5e7', '_padding': box(5, 9, 5, 9), '_border_radius': box(20), '_margin': box(0, 0, 12, 0)}))
    else:
        kids.append(con([icon(ico, 20)], w=40, mh=40, jc='center', ai='center', bg=G, radius=10, extra={'_flex_size': 'none', '_margin': box(0, 0, 12, 0), 'flex_align_self': 'flex-start'}))
    kids += [
        heading(title, 'h3', TX, MAN, 17, 800, 1.25, extra={'_margin': box(0, 0, 7, 0)}),
        heading(text, 'p', MUT, INT, 12.5, 400, 1.5, extra={'_margin': box(0, 0, 12, 0), '_flex_size': 'grow'}),
        con([heading('Plačiau', 'div', TX, INT, 13, 700, 28.9, extra={'_element_width': 'auto'}),
             heading('→', 'div', G, INT, 12, 700, 1.0, align='center', extra={'_element_width': 'initial', '_element_custom_width': px(22), '_background_background': 'classic', '_background_color': GL, '_border_radius': box(50, unit='%'), '_padding': box(5, 0, 5, 0)})],
            dirn='row', ai='center', g=8),
    ]
    bd = con(kids, pad=(20, 4, 18, 20), mh=250, extra={'width': pct(66), '_z_index': 1, 'width_mobile': pct(70)})
    extra = {'overflow': 'hidden', 'position': 'relative'}
    if hot:
        return con([ph, bd], link=url, mh=250, radius=16, border=(1, '#bfe9cf'), sh=shadow(0, 2, 6, 0, 'rgba(0,0,0,0.03)'), extra={**extra, 'background_background': 'gradient', 'background_color': '#effbf4', 'background_color_b': '#e4f6eb', 'background_gradient_type': 'linear', 'background_gradient_angle': {'unit': 'deg', 'size': 150, 'sizes': []}, 'background_color_stop': pct(0), 'background_color_b_stop': pct(100)})
    return con([ph, bd], link=url, mh=250, bg='#fff', radius=16, border=(1, LINE), sh=shadow(0, 2, 6, 0, 'rgba(0,0,0,0.03)'), extra=extra)

def services():
    cards = [
        service_card('c1', 'Avarinis automobilių atrakinimas', 'Pametėte raktus, užsitrenkė durys ar palikote raktus automobilyje? Atrakiname automobilį vietoje, greitai ir saugiai.', '/paslaugos/avarinis-atrakinimas/', hot=True),
        service_card('c2', 'Automobilių raktų gamyba', 'Naujų ir atsarginių automobilio raktų gamyba visais pagrindiniais automobilių modeliais.', '/paslaugos/raktu-gamyba-remontas/', 'card-key'),
        service_card('c3', 'Automobilių raktų programavimas', 'Automobilio raktų, imobilaizerių ir nuotolinio valdymo pultelių programavimas.', '/paslaugos/raktu-programavimas/', 'card-chip'),
        service_card('c4', 'Automobilių spynų remontas', 'Durų, užvedimo ir kitų automobilio spynų diagnostika bei remontas.', '/paslaugos/spynu-remontas/', 'card-lock'),
        service_card('c5', 'Buitinių raktų gamyba', 'Namų, butų, garažų, rūsių ir kitų buitinių raktų gamyba.', '/paslaugos/buitiniu-raktu-gamyba/', 'card-home'),
        service_card('c6', 'Galanterijos taisymas', 'Spynelių, užraktų, lagaminų ir kitų galanterijos mechanizmų remontas.', '/paslaugos/galanterijos-taisymas/', 'card-bag'),
    ]
    return con([sec_head('Paslaugos', 'Visos paslaugos', '/paslaugos/'), grid(cards, 3, 20, cols_t=2, cols_m=1)], tag='section', width='boxed', boxed=1136, pad=(64, 22, 64, 22), extra={'_element_id': 'paslaugos'})

# ---------- KODĖL VERTA ----------
def why():
    items = [('why-1', 'Profesionalus darbas', 'Dirbame tvarkingai ir atsakingai – be pažeidimų.'), ('why-2', 'Greita pagalba', 'Į skambutį reaguojame iš karto, kai reikia.'), ('why-3', 'Patirtis su skirtingais automobiliais', 'Dirbame su daugelio markių raktais ir spynų sistemomis.'), ('why-4', 'Moderni įranga', 'Naudojame profesionalią diagnostikos ir programavimo įrangą.'), ('why-5', 'Aiški darbų kaina', 'Kainą pasakome prieš pradedant darbą – be netikėtų priedų.')]
    cards = []
    for k, t, p in items:
        ic = con([icon(k, 18)], w=34, mh=34, jc='center', ai='center', border=(1.5, ACC), radius=9, extra={'_flex_size': 'none'})
        txt = con([heading(t, 'h4', TX, MAN, 12.5, 800, 1.2, extra={'_margin': box(0, 0, 3, 0)}), heading(p, 'p', MUT, INT, 11, 400, 1.4)], g=0)
        cards.append(con([ic, txt], dirn='row', ai='flex-start', g=11, pad=(14, 14, 14, 14), bg='#fff', border=(1, LINE), radius=12))
    lbl = heading('Kodėl verta rinktis mus?', 'div', '#7d8a83', INT, 11, 700, 28.9, px(1.54), 'uppercase', align='center', extra={'_margin': box(0, 0, 14, 0)})
    return con([lbl, grid(cards, 5, 14, cols_t=2, cols_m=2)], tag='section', width='boxed', boxed=1136, pad=(26, 22, 30, 22), bg='#f7fef9',
               extra={'border_border': 'solid', 'border_width': box(1, 0, 1, 0), 'border_color': LINE, '_element_id': 'kodel'})

# ---------- DARBŲ PAVYZDŽIAI ----------
def ex_card(k, brand, title, sub, url):
    ov = {'background_overlay_background': 'gradient', 'background_overlay_color': 'rgba(6,32,18,0.88)', 'background_overlay_color_b': 'rgba(6,32,18,0)', 'background_overlay_gradient_type': 'linear', 'background_overlay_gradient_angle': {'unit': 'deg', 'size': 0, 'sizes': []}, 'background_overlay_color_stop': pct(0), 'background_overlay_color_b_stop': pct(100), 'background_overlay_opacity': px(1)}
    tag = heading('<img src="' + UP + 'rk-ico-tag-car.svg" width="13" height="13" alt="" style="vertical-align:-2px;margin-right:6px">' + brand, 'div', '#d6e7dc', INT, 12, 600, 28.9, extra={'_element_width': 'auto', '_background_background': 'classic', '_background_color': 'rgba(255,255,255,0.14)', '_border_border': 'solid', '_border_width': box(1), '_border_color': 'rgba(255,255,255,0.3)', '_border_radius': box(20), '_padding': box(5, 11, 5, 11)})
    cap = con([con([tag], dirn='row', extra={'margin': box(0, 0, 8, 0)}), heading(title, 'h4', '#fff', MAN, 18, 800, 1.2), heading(sub, 'div', '#d6e7dc', INT, 13, 400, 28.9)], g=0, pad=(0, 18, 16, 18), extra={'_z_index': 1})
    return con([cap], link=url, mh=290, jc='flex-end', radius=16, bgimg=k, bgpos='center center', bgsize='cover', overlay=ov, extra={'overflow': 'hidden'})

def examples():
    intro = con([
        con([heading('Mūsų darbų<br>pavyzdžiai', 'h2', G, MAN, 32, 900, 1.05, {'unit': 'em', 'size': 0.01, 'sizes': []}, 'uppercase')], pad=(22, 24, 22, 24), bg='#fff', radius=14, extra={'_margin': box(0, 0, 16, 0)}),
        heading('Kasdien padedame klientams greitai ir profesionaliai spręsti su automobilio raktais susijusias problemas. Štai keli mūsų darbų pavyzdžiai.', 'p', MUT, INT, 14.5, 400, 28.9, extra={'_padding': box(0, 6, 0, 6), '_margin': box(0, 0, 22, 0)}),
        button('Daugiau pavyzdžių', '/galerija/', G, '#fff', ico='fas fa-arrow-right', ico_align='right', ico_gap=9, extra={'_margin': box(0, 0, 0, 6), 'icon_size': px(13)}),
    ], g=0)
    g = grid([intro, ex_card('w1', 'Opel', 'Naujų raktų gamyba', 'Pilnas raktų programavimas', '/paslaugos/raktu-gamyba-remontas/'), ex_card('w2', 'Volkswagen', 'Automobilio atrakinimas', 'Greita pagalba vietoje', '/paslaugos/avarinis-atrakinimas/')], '.78fr 1fr 1fr', 20, cols_t=1, cols_m=1)
    ov = {'background_overlay_background': 'gradient', 'background_overlay_color': '#dff3e7', 'background_overlay_color_b': 'rgba(223,243,231,0)', 'background_overlay_gradient_type': 'radial', 'background_overlay_gradient_position': 'bottom left', 'background_overlay_color_stop': pct(0), 'background_overlay_color_b_stop': pct(45), 'background_overlay_opacity': px(1)}
    return con([g], tag='section', width='boxed', boxed=1136, pad=(56, 22, 60, 22), bg='#f3f8f5', overlay=ov, extra={'_element_id': 'pavyzdziai'})

# ---------- ATSILIEPIMAI ----------
def review(text, name, when, av):
    top = con([heading('★★★★★', 'div', '#f5b301', INT, 17, 400, 28.9, px(2), extra={'_element_width': 'auto'}), icon('google', 22, extra={'_element_width': 'auto'})], dirn='row', jc='space-between', ai='center', extra={'_margin': box(0, 0, 12, 0)})
    if av in IMG:
        avw = image(av, extra={'width': px(38), 'height': px(38), 'object-fit': 'cover', 'image_border_radius': box(50, unit='%'), '_element_width': 'auto', '_flex_size': 'none'})
    else:
        avw = con([heading(av, 'div', G, MAN, 12.5, 800, 1.0)], w=38, mh=38, jc='center', ai='center', bg=GL, radius=50, extra={'_flex_size': 'none', 'border_radius': box(50, unit='%')})
    who = con([avw, con([heading(name, 'b', MUT, INT, 14, 700, 28.9), heading(when, 'div', MUT, INT, 12.5, 400, 28.9)], g=0)], dirn='row', ai='center', g=11)
    return con([top, heading(text, 'p', '#3a463f', INT, 14, 400, 1.55, extra={'_margin': box(0, 0, 18, 0)}), who], pad=(22, 22, 22, 22), bg='#fff', border=(1, LINE), radius=16, sh=shadow(0, 8, 24, -18, 'rgba(0,0,0,0.15)'))

def reviews():
    cards = grid([
        review('„Užsitrenkė automobilio durys, likau be raktų. Meistras atvyko labai greitai ir profesionaliai atrakino automobilį. Tikrai rekomenduoju!“', 'Tomas K.', 'Prieš 2 savaites', 'T'),
        review('„Pagamino naują automobilio raktą tą pačią dieną. Viskas veikia puikiai, malonus bendravimas ir profesionalus darbas.“', 'Andrius B.', 'Prieš 1 mėnesį', 'av_andrius'),
        review('„Profesionaliai sutvarkė užvedimo spyną, paaiškino kas buvo ne taip. Greitas ir kokybiškas aptarnavimas. Tikrai sugrįšiu, jei reikės.“', 'Rasa M.', 'Prieš 3 savaites', 'av_rasa'),
    ], 3, 20, cols_t=1, cols_m=1, pad=(0, 40, 0, 40), extra={'padding_mobile': box(0)})
    def arrow(ch, side):
        e = {'position': 'absolute', '_offset_orientation_v': 'start', '_offset_y': {'unit': '%', 'size': 50, 'sizes': []}, '_z_index': 2, 'hide_mobile': 'hidden-mobile', 'hide_tablet': 'hidden-tablet'}
        if side == 'l': e.update({'_offset_orientation_h': 'start', '_offset_x': px(-6)})
        else: e.update({'_offset_orientation_h': 'end', '_offset_x_end': px(-6)})
        return con([heading(ch, 'div', '#43514a', INT, 16, 400, 1.0, align='center')], w=36, mh=36, jc='center', ai='center', bg='#fff', border=(1, LINE), radius=50, sh=shadow(0, 4, 12, 0, 'rgba(0,0,0,0.08)'), extra={**e, 'border_radius': box(50, unit='%'), 'margin': box(-18, 0, 0, 0)})
    cards = con([cards, arrow('‹', 'l'), arrow('›', 'r')], extra={'position': 'relative'})
    ov = {'background_overlay_background': 'gradient', 'background_overlay_color': '#e3f4ea', 'background_overlay_color_b': 'rgba(227,244,234,0)', 'background_overlay_gradient_type': 'radial', 'background_overlay_gradient_position': 'bottom right', 'background_overlay_color_stop': pct(0), 'background_overlay_color_b_stop': pct(45), 'background_overlay_opacity': px(1)}
    return con([heading('Atsiliepimai', 'h2', G, MAN, 30, 900, 1.2, {'unit': 'em', 'size': 0.02, 'sizes': []}, 'uppercase', align='center', extra={'_margin': box(0, 0, 30, 0)}), cards],
               tag='section', width='boxed', boxed=1136, pad=(44, 22, 60, 22), bg='#fbfcfb', overlay=ov, extra={'_element_id': 'atsiliepimai'})

# ---------- KONTAKTAI ----------
def contacts():
    rows = [('crow-phone', '+370 600 12345', 'tel:+37060012345'), ('crow-mail', 'info@raktumeistras.lt', 'mailto:info@raktumeistras.lt'), ('crow-pin', 'Savanorių pr. 187, Kaunas', ''), ('crow-clock', 'I–V: 8:00–20:00 &nbsp;|&nbsp; VI: 9:00–16:00', ''), ('crow-alert', 'Avarinis atrakinimas: visą parą', '')]
    lst = wid('icon-list', {'icon_list': [{'_id': 'cr%d' % i, 'text': t, 'selected_icon': svg(k), 'link': {'url': u, 'is_external': '', 'nofollow': ''}} for i, (k, t, u) in enumerate(rows)],
                           'view': 'traditional', 'space_between': px(16), 'icon_size': px(18), 'icon_color': G, 'text_color': '#2a3530', 'text_color_hover': G, 'text_indent': px(12), 'icon_align': 'left', 'icon_self_vertical_align': 'center', **typo('icon_', INT, 14.5, 400, 28.9), 'icon_typography_letter_spacing': px(0), '__globals__': {'icon_typography_typography': '', 'text_color': '', 'icon_color': '', 'text_color_hover': ''}, '_margin': box(0, 0, 16, 0)})
    left = con([
        heading('Kontaktai', 'div', G, INT, 12, 700, 28.9, px(1.68), 'uppercase'),
        heading('Susisiekite su mumis', 'h2', TX, MAN, 30, 800, 1.2, extra={'_margin': box(6, 0, 10, 0)}),
        heading('Skambinkite dėl skubių atvejų arba parašykite užklausą – atsakysime kaip įmanoma greičiau.', 'p', MUT, INT, 14.5, 400, 28.9, extra={'_margin': box(0, 0, 20, 0)}),
        lst,
        wid('google_maps', {'address': 'Savanorių pr. 187, Kaunas', 'zoom': px(15), 'height': px(260), 'prevent_scroll': 'yes', '_border_border': 'solid', '_border_width': box(1), '_border_color': LINE, '_border_radius': box(12), '_margin': box(14, 0, 0, 0)}),
    ], g=0)
    form = wid('form', contact_form())
    right = con([heading('Siųsti užklausą', 'h3', TX, MAN, 19, 800, 1.2, align='center', extra={'_margin': box(0, 0, 18, 0)}), form],
                pad=(26, 26, 26, 26), bg='#fff', border=(1, LINE), radius=18, sh=shadow(0, 14, 40, -28, 'rgba(0,0,0,0.2)'), extra={'flex_align_self': 'flex-start', 'grid_align_self': 'start'})
    g = grid([left, right], 2, 40, cols_t=1, cols_m=1, extra={'grid_align_items': 'start'})
    return con([g], tag='section', width='boxed', boxed=1136, pad=(60, 22, 64, 22), bg='#fff', extra={'_element_id': 'kontaktai'})

def contact_form():
    fields = [
        {'custom_id': 'name', 'field_label': 'Vardas', 'field_type': 'text', 'placeholder': 'Jūsų vardas', 'required': 'true', 'width': '50', '_id': 'nam01'},
        {'custom_id': 'phone', 'field_label': 'Telefono numeris', 'field_type': 'tel', 'placeholder': '+370 ...', 'required': 'true', 'width': '50', '_id': 'phn02'},
        {'custom_id': 'email', 'field_label': 'El. paštas', 'field_type': 'email', 'placeholder': 'vardas@pastas.lt', 'width': '100', '_id': 'eml01'},
        {'custom_id': 'service', 'field_label': 'Pasirinkite paslaugą', 'field_type': 'select', 'field_options': 'Automobilio avarinis atrakinimas\nAutomobilinių raktų gamyba ir remontas\nAutomobilinių raktų programavimas\nAutomobilių spynų remontas\nBuityje naudojamų raktų gamyba\nGalanterijos taisymas\nKita', 'width': '100', '_id': 'svc02'},
        {'custom_id': 'message', 'field_label': 'Žinutė', 'field_type': 'textarea', 'placeholder': 'Trumpai aprašykite situaciją', 'rows': 3, 'width': '100', '_id': 'msg01'},
        {'custom_id': 'website', 'field_type': 'honeypot', 'width': '100', '_id': 'hp002'},
    ]
    css = ('selector .elementor-field-group{margin-bottom:14px}selector .elementor-field-group.elementor-col-50{width:calc(50% - 6px)}selector .elementor-form-fields-wrapper{gap:0 12px}'
           'selector .elementor-field-label{padding-left:4px;margin-bottom:6px;line-height:1}'
           'selector .elementor-field-group input.elementor-field,selector .elementor-field-group select.elementor-field-textual,selector .elementor-field-group textarea.elementor-field{padding:11px 13px!important;line-height:28.9px!important;box-shadow:none;height:auto}selector .elementor-select-wrapper{padding:0;background:none}selector .elementor-field-type-honeypot{display:none}selector .e-form__buttons{margin:0}'
           'selector .select-caret-down-wrapper{display:none}selector .elementor-field-group-service .elementor-select-wrapper{position:relative}selector .elementor-field-group .elementor-select-wrapper select.elementor-field-textual{appearance:none;-webkit-appearance:none;padding:11px 30px 11px 13px!important;height:52.9px!important;line-height:28.9px!important}'
           'selector .elementor-field-group-service .elementor-select-wrapper:after{content:"\\25BC";position:absolute;right:13px;top:50%;transform:translateY(-50%);font-size:11px;color:#9aa59f}'
           'selector .elementor-field::placeholder{color:#9aa59f;opacity:1}selector textarea.elementor-field{height:84px!important;min-height:84px;resize:vertical}'
           'selector .elementor-field-type-submit{margin:0}selector .elementor-button{line-height:1;width:100%;justify-content:center}'
           'selector .elementor-message{font-size:13px;border-radius:10px;padding:10px 12px;margin-top:10px}')
    return {'form_name': 'Užklausa', 'form_fields': fields, 'button_text': 'Siųsti užklausą', 'button_size': 'sm', 'button_width': '100', 'show_labels': 'true', 'mark_required': '', 'input_size': 'sm',
            'submit_actions': ['save-to-database'], 'custom_messages': 'yes', 'form_id': 'contact-form', 'success_message': 'Ačiū! Užklausa išsiųsta – susisieksime netrukus.', 'error_message': 'Nepavyko išsiųsti. Paskambinkite mums.', 'required_field_message': 'Užpildykite šį lauką.', 'invalid_message': 'Patikrinkite įvestus duomenis.',
            'column_gap': px(12), 'row_gap': px(0), 'label_color': '#3a463f', **typo('label_', INT, 12.5, 700, 1.0),
            'field_text_color': TX, 'field_background_color': '#fff', 'field_border_color': LINE, 'field_border_width': box(1), 'field_border_radius': box(10), **typo('field_', INT, 16, 400, 28.9),
            'button_background_color': G, 'button_hover_background_color': GD, 'button_text_color': '#fff', 'button_hover_color': '#fff', 'button_border_radius': box(12), 'button_text_padding': box(14, 24, 14, 24), **typo('button_', INT, 15, 600, 1.0),
            'custom_css': css, '__globals__': FORM_GLOBALS}

def build():
    return [hero(), logos(), services(), why(), examples(), reviews(), contacts()]

if __name__ == '__main__':
    data = build()
    json.dump(data, open('el_home.json', 'w', encoding='utf-8'), ensure_ascii=False)
    print('elementai:', len(json.dumps(data)), 'B')
