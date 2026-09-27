#!/usr/bin/env python3
"""Generates theme/theme.json and theme/styles/*.json from one token table.

Every variation shares the same semantic slugs (base, surface, contrast, primary,
accent, ...), so patterns, templates and CSS work unchanged under any look.
Run: python3 tools/build-styles.py
"""
import json, os, copy

ROOT = os.path.join(os.path.dirname(__file__), "..", "theme")

SLUGS = [
    ("base", "Base"), ("base-2", "Base alt"), ("surface", "Surface"),
    ("contrast", "Contrast"), ("contrast-2", "Contrast muted"),
    ("primary", "Primary"), ("primary-2", "Primary alt"), ("primary-3", "Primary light"),
    ("accent", "Accent"), ("accent-2", "Accent light"), ("accent-3", "Accent text"),
    ("on-primary", "On primary"), ("on-accent", "On accent"), ("line", "Line"),
    ("sale", "Sale"), ("success", "Success"),
]

def font(slug, name, family, fallback, files):
    faces = []
    for style, fname, weight in files:
        faces.append({"fontFamily": family, "fontStyle": style, "fontWeight": weight,
                      "fontDisplay": "swap", "src": [f"file:./assets/fonts/{fname}"]})
    return {"slug": slug, "name": name, "fontFamily": f'"{family}", {fallback}', "fontFace": faces}

SERIF = 'Georgia, "Times New Roman", "Noto Serif Devanagari", serif'
SANS = 'system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans Devanagari", "Helvetica Neue", Arial, sans-serif'

FONTS = {
    "cormorant": lambda slug, name: font(slug, name, "Cormorant Garamond", SERIF, [
        ("normal", "cormorant-garamond/cormorant-garamond-latin-wght-normal.woff2", "300 700"),
        ("italic", "cormorant-garamond/cormorant-garamond-latin-wght-italic.woff2", "300 700")]),
    "jost": lambda slug, name: font(slug, name, "Jost", SANS, [("normal", "jost/jost-latin-wght-normal.woff2", "100 900")]),
    "bodoni": lambda slug, name: font(slug, name, "Bodoni Moda", SERIF, [
        ("normal", "bodoni-moda/bodoni-moda-latin-wght-normal.woff2", "400 900"),
        ("italic", "bodoni-moda/bodoni-moda-latin-wght-italic.woff2", "400 900")]),
    "manrope": lambda slug, name: font(slug, name, "Manrope", SANS, [("normal", "manrope/manrope-latin-wght-normal.woff2", "200 800")]),
    "space": lambda slug, name: font(slug, name, "Space Grotesk", SANS, [("normal", "space-grotesk/space-grotesk-latin-wght-normal.woff2", "300 700")]),
    "inter": lambda slug, name: font(slug, name, "Inter", SANS, [("normal", "inter/inter-latin-wght-normal.woff2", "100 900")]),
    "fraunces": lambda slug, name: font(slug, name, "Fraunces", SERIF, [
        ("normal", "fraunces/fraunces-latin-wght-normal.woff2", "100 900"),
        ("italic", "fraunces/fraunces-latin-wght-italic.woff2", "100 900")]),
    "dmsans": lambda slug, name: font(slug, name, "DM Sans", SANS, [("normal", "dm-sans/dm-sans-latin-wght-normal.woff2", "100 1000")]),
    "outfit": lambda slug, name: font(slug, name, "Outfit", SANS, [("normal", "outfit/outfit-latin-wght-normal.woff2", "100 900")]),
    "nunito": lambda slug, name: font(slug, name, "Nunito Sans", SANS, [("normal", "nunito-sans/nunito-sans-latin-wght-normal.woff2", "200 1000")]),
}

def fonts(h, b, hname, bname):
    return [FONTS[h]("heading", f"Heading ({hname})"), FONTS[b]("body", f"Body ({bname})"),
            {"slug": "system", "name": "System", "fontFamily": SANS}]

V = {}
V["luxe"] = dict(
    title="Luxe", palette=["#fbf8f2", "#f3ece0", "#ffffff", "#1f1b16", "#6b6255", "#0b1a17", "#122722", "#1f4a41",
                           "#c9a45c", "#f1dca4", "#7f5e22", "#ffffff", "#1c1406", "#e5ddcf", "#b3261e", "#1a7a47"],
    sheen="linear-gradient(120deg, #a67f36 0%, #f1dca4 35%, #c9a45c 55%, #fff1c9 75%, #a67f36 100%)",
    hero="radial-gradient(120% 90% at 70% 30%, #1f4a41 0%, #0b1a17 55%, #050c0b 100%)",
    soft="radial-gradient(80% 120% at 80% 20%, #24554b, #0b1a17)",
    fonts=fonts("cormorant", "jost", "Cormorant Garamond", "Jost"),
    dark=dict(base="#0d1614", base2="#111e1b", surface="#16241f", contrast="#f3ede2", contrast2="#b9ae9c", line="#2a3a35", accent3="#d9b872"),
    radius=dict(sm="10px", md="18px", lg="24px", button="999px"),
    shadow="rgba(11, 26, 23, 0.35)", heading_weight="500", heading_case="none", button_case="uppercase", heading_scale=1.0)
V["noir"] = dict(V["luxe"], title="Noir",
    palette=["#0d1614", "#111e1b", "#16241f", "#f3ede2", "#b9ae9c", "#050c0b", "#0b1a17", "#1f4a41",
             "#c9a45c", "#f1dca4", "#d9b872", "#ffffff", "#1c1406", "#2a3a35", "#ff8a80", "#6fd49b"],
    dark=dict(base="#0d1614", base2="#111e1b", surface="#16241f", contrast="#f3ede2", contrast2="#b9ae9c", line="#2a3a35", accent3="#d9b872"))
V["fashion"] = dict(
    title="Fashion", palette=["#ffffff", "#f5f1ee", "#ffffff", "#111111", "#5f5f5f", "#111111", "#1d1d1d", "#333333",
                              "#e4b7a8", "#f6e3dc", "#9c4f3a", "#ffffff", "#111111", "#e7e2de", "#c0392b", "#2e7d4f"],
    sheen="linear-gradient(120deg, #e4b7a8 0%, #f6e3dc 50%, #e4b7a8 100%)",
    hero="linear-gradient(135deg, #111111 0%, #2b2b2b 100%)",
    soft="linear-gradient(135deg, #1d1d1d, #111111)",
    fonts=fonts("bodoni", "manrope", "Bodoni Moda", "Manrope"),
    dark=dict(base="#0f0f0f", base2="#171717", surface="#1c1c1c", contrast="#f5f1ee", contrast2="#b3aca8", line="#2e2e2e", accent3="#f0b7a4"),
    radius=dict(sm="2px", md="4px", lg="6px", button="0px"),
    shadow="rgba(0, 0, 0, 0.18)", heading_weight="500", heading_case="none", button_case="uppercase", heading_scale=1.0)
V["tech"] = dict(
    title="Electronics", palette=["#f6f8fb", "#eaf0f7", "#ffffff", "#0f172a", "#475569", "#0b1220", "#111b2e", "#1e3a8a",
                                  "#2563eb", "#93c5fd", "#1d4ed8", "#ffffff", "#ffffff", "#dbe3ee", "#dc2626", "#15803d"],
    sheen="linear-gradient(120deg, #1d4ed8 0%, #3b82f6 50%, #06b6d4 100%)",
    hero="radial-gradient(120% 90% at 70% 30%, #1e3a8a 0%, #0b1220 60%, #050910 100%)",
    soft="radial-gradient(80% 120% at 80% 20%, #1e3a8a, #0b1220)",
    fonts=fonts("space", "inter", "Space Grotesk", "Inter"),
    dark=dict(base="#0a0f1a", base2="#0f1626", surface="#141d30", contrast="#e2e8f0", contrast2="#94a3b8", line="#1f2a40", accent3="#60a5fa"),
    radius=dict(sm="8px", md="12px", lg="16px", button="10px"),
    shadow="rgba(15, 23, 42, 0.25)", heading_weight="600", heading_case="none", button_case="none", heading_scale=0.9)
V["beauty"] = dict(
    title="Beauty", palette=["#fff8f6", "#fbe9e7", "#ffffff", "#2b1d22", "#6e5a60", "#4a1f33", "#5c2a41", "#7a3a58",
                             "#e8a0b4", "#f8d7e0", "#a23e62", "#ffffff", "#2b1d22", "#f0dcdc", "#b3261e", "#2e7d4f"],
    sheen="linear-gradient(120deg, #e8a0b4 0%, #f8d7e0 45%, #f3b8a6 100%)",
    hero="radial-gradient(120% 90% at 70% 30%, #7a3a58 0%, #4a1f33 60%, #2b1020 100%)",
    soft="radial-gradient(80% 120% at 80% 20%, #7a3a58, #4a1f33)",
    fonts=fonts("fraunces", "dmsans", "Fraunces", "DM Sans"),
    dark=dict(base="#1a1115", base2="#22161c", surface="#2a1c23", contrast="#fbeef0", contrast2="#c9adb6", line="#3b2830", accent3="#f2a7bf"),
    radius=dict(sm="12px", md="22px", lg="28px", button="999px"),
    shadow="rgba(74, 31, 51, 0.22)", heading_weight="400", heading_case="none", button_case="none", heading_scale=1.0)
V["grocery"] = dict(
    title="Grocery", palette=["#fbfaf4", "#eef3e2", "#ffffff", "#1d2a1a", "#56624f", "#1f4d2b", "#2a6238", "#3b7d49",
                              "#f2a93b", "#fde3b0", "#9a5b00", "#ffffff", "#1d2a1a", "#dfe6d2", "#c62828", "#2e7d32"],
    sheen="linear-gradient(120deg, #f2a93b 0%, #fcd581 50%, #f2a93b 100%)",
    hero="radial-gradient(120% 90% at 70% 30%, #3b7d49 0%, #1f4d2b 60%, #12301a 100%)",
    soft="radial-gradient(80% 120% at 80% 20%, #3b7d49, #1f4d2b)",
    fonts=fonts("outfit", "nunito", "Outfit", "Nunito Sans"),
    dark=dict(base="#10170f", base2="#152014", surface="#1b281a", contrast="#eef3e2", contrast2="#a9b7a0", line="#2a3a28", accent3="#f7c46b"),
    radius=dict(sm="10px", md="16px", lg="22px", button="12px"),
    shadow="rgba(31, 77, 43, 0.2)", heading_weight="600", heading_case="none", button_case="none", heading_scale=0.9)
V["minimal"] = dict(
    title="Minimal", palette=["#ffffff", "#f5f5f4", "#ffffff", "#18181b", "#52525b", "#18181b", "#27272a", "#3f3f46",
                              "#18181b", "#e4e4e7", "#3f3f46", "#ffffff", "#ffffff", "#e4e4e7", "#b91c1c", "#15803d"],
    sheen="linear-gradient(120deg, #18181b 0%, #3f3f46 100%)",
    # Silver for dark heroes and dark mode, where the near-black sheen would vanish.
    sheen_dark="linear-gradient(120deg, #a1a1aa 0%, #ffffff 40%, #d4d4d8 60%, #ffffff 80%, #a1a1aa 100%)",
    on_sheen_dark="#18181b",
    hero="linear-gradient(180deg, #18181b 0%, #27272a 100%)",
    soft="linear-gradient(180deg, #27272a, #18181b)",
    fonts=fonts("inter", "inter", "Inter", "Inter"),
    dark=dict(base="#09090b", base2="#111113", surface="#18181b", contrast="#fafafa", contrast2="#a1a1aa", line="#27272a", accent3="#d4d4d8"),
    radius=dict(sm="4px", md="6px", lg="8px", button="6px"),
    shadow="rgba(0, 0, 0, 0.12)", heading_weight="600", heading_case="none", button_case="none", heading_scale=0.85)
# Minimal shares one family, so the body face is not loaded twice.
V["minimal"]["fonts"][1] = {"slug": "body", "name": "Body (Inter)", "fontFamily": FONTS["inter"]("x", "x")["fontFamily"]}


def palette(v):
    return [{"slug": s, "name": n, "color": c} for (s, n), c in zip(SLUGS, v["palette"])]

def gradients(v):
    return [{"slug": "accent-sheen", "name": "Accent sheen", "gradient": v["sheen"]},
            {"slug": "primary-radial", "name": "Primary glow", "gradient": v["hero"]},
            {"slug": "primary-soft", "name": "Primary soft", "gradient": v["soft"]},
            {"slug": "fade-to-contrast", "name": "Image shade", "gradient": "linear-gradient(180deg, rgba(0,0,0,0) 45%, rgba(0,0,0,0.72) 100%)"}]

def shadows(v):
    c = v["shadow"]
    return [{"slug": "soft", "name": "Soft", "shadow": f"0 18px 40px -28px {c}"},
            {"slug": "card", "name": "Card", "shadow": f"0 1px 0 rgba(0,0,0,0.04), 0 18px 40px -28px {c}"},
            {"slug": "deep", "name": "Deep", "shadow": f"0 40px 60px -30px {c}"}]

def custom(v):
    r = v["radius"]
    return {"radius": {"sm": r["sm"], "md": r["md"], "lg": r["lg"], "pill": "999px", "button": r["button"]},
            "dark": {"base": v["dark"]["base"], "base-2": v["dark"]["base2"], "surface": v["dark"]["surface"],
                     "contrast": v["dark"]["contrast"], "contrast-2": v["dark"]["contrast2"], "line": v["dark"]["line"],
                     "accent-3": v["dark"]["accent3"]},
            "heading": {"weight": v["heading_weight"], "transform": v["heading_case"]},
            "button": {"transform": v["button_case"], "letter-spacing": "0.06em" if v["button_case"] == "uppercase" else "0.01em"},
            "sheen-on-dark": v.get("sheen_dark", v["sheen"]),
            "on-sheen-dark": v.get("on_sheen_dark", v["palette"][12]),
            "header": {"height": "72px"},
            "whatsapp": {"background": "#25d366", "text": "#06301a"},
            "transition": {"base": "0.25s ease", "spring": "0.5s cubic-bezier(0.2, 0.7, 0.2, 1)"}}

def fsizes(scale):
    def c(mn, mx):
        return {"min": f"{round(mn*scale,3)}rem", "max": f"{round(mx*scale,3)}rem"}
    return [
        {"slug": "x-small", "name": "Extra small", "size": "0.78rem", "fluid": False},
        {"slug": "small", "name": "Small", "size": "0.9rem", "fluid": False},
        {"slug": "medium", "name": "Medium", "size": "1.05rem", "fluid": {"min": "1rem", "max": "1.05rem"}},
        {"slug": "large", "name": "Large", "size": "1.5rem", "fluid": c(1.25, 1.5)},
        {"slug": "x-large", "name": "Extra large", "size": "2.2rem", "fluid": c(1.7, 2.2)},
        {"slug": "xx-large", "name": "2X large", "size": "3.2rem", "fluid": c(2.1, 3.2)},
        {"slug": "huge", "name": "Huge", "size": "5.2rem", "fluid": c(2.6, 5.2)},
    ]

def settings(v):
    return {
        "color": {"palette": palette(v), "gradients": gradients(v)},
        "shadow": {"presets": shadows(v)},
        "typography": {"fontFamilies": v["fonts"], "fontSizes": fsizes(v["heading_scale"])},
        "custom": custom(v),
    }

def styles(v):
    return {
        "elements": {
            "heading": {"typography": {"fontWeight": v["heading_weight"], "textTransform": v["heading_case"]}},
            "button": {"border": {"radius": v["radius"]["button"]},
                       "typography": {"textTransform": v["button_case"], "letterSpacing": custom(v)["button"]["letter-spacing"]}},
        }
    }

BASE = {
    "$schema": "https://schemas.wp.org/wp/6.6/theme.json",
    "version": 3,
    "settings": {
        "appearanceTools": True,
        "useRootPaddingAwareAlignments": True,
        "layout": {"contentSize": "780px", "wideSize": "1280px"},
        "color": {"defaultPalette": False, "defaultGradients": False, "defaultDuotone": False, "custom": True,
                  "duotone": [{"slug": "primary-accent", "name": "Primary and accent", "colors": ["#0b1a17", "#f1dca4"]}]},
        "shadow": {"defaultPresets": False},
        "spacing": {
            "units": ["px", "em", "rem", "vh", "vw", "%"],
            "defaultSpacingSizes": False,
            "spacingSizes": [
                {"slug": "10", "name": "1", "size": "0.5rem"},
                {"slug": "20", "name": "2", "size": "clamp(0.75rem, 1vw, 1rem)"},
                {"slug": "30", "name": "3", "size": "clamp(1rem, 1.6vw, 1.5rem)"},
                {"slug": "40", "name": "4", "size": "clamp(1rem, 2.4vw, 2rem)"},
                {"slug": "50", "name": "5", "size": "clamp(1.75rem, 4vw, 3.25rem)"},
                {"slug": "60", "name": "6", "size": "clamp(2.5rem, 6vw, 5rem)"},
                {"slug": "70", "name": "7", "size": "clamp(4rem, 9vw, 7.5rem)"},
            ],
        },
        "typography": {"fluid": True, "defaultFontSizes": False, "writingMode": True, "dropCap": False},
        "blocks": {
            "core/button": {"border": {"radius": True}},
        },
    },
    "styles": {
        "color": {"background": "var:preset|color|base", "text": "var:preset|color|contrast"},
        "typography": {"fontFamily": "var:preset|font-family|body", "fontSize": "var:preset|font-size|medium",
                       "lineHeight": "1.6", "fontWeight": "400"},
        "spacing": {"blockGap": "1.25rem", "padding": {"left": "var:preset|spacing|30", "right": "var:preset|spacing|30"}},
        "elements": {
            "link": {"color": {"text": "currentColor"}, "typography": {"textDecoration": "underline"},
                     ":hover": {"color": {"text": "var:preset|color|accent-3"}},
                     ":focus": {"outline": {"color": "var:preset|color|accent", "offset": "2px", "style": "solid", "width": "2px"}}},
            "heading": {"typography": {"fontFamily": "var:preset|font-family|heading", "lineHeight": "1.12", "letterSpacing": "0.005em"},
                        "color": {"text": "inherit"}},
            "h1": {"typography": {"fontSize": "var:preset|font-size|xx-large"}},
            "h2": {"typography": {"fontSize": "var:preset|font-size|x-large"}},
            "h3": {"typography": {"fontSize": "var:preset|font-size|large"}},
            "h4": {"typography": {"fontSize": "clamp(1.15rem, 1.6vw, 1.3rem)"}},
            "h5": {"typography": {"fontSize": "var:preset|font-size|medium"}},
            "h6": {"typography": {"fontSize": "var:preset|font-size|small", "textTransform": "uppercase", "letterSpacing": "0.14em"}},
            "button": {
                "color": {"background": "var:preset|color|primary", "text": "var:preset|color|on-primary"},
                "border": {"width": "1px", "style": "solid", "color": "transparent"},
                "spacing": {"padding": {"top": "0.85rem", "bottom": "0.85rem", "left": "1.75rem", "right": "1.75rem"}},
                "typography": {"fontFamily": "var:preset|font-family|body", "fontSize": "var:preset|font-size|small",
                               "fontWeight": "500", "lineHeight": "1.3", "textDecoration": "none"},
                ":hover": {"color": {"background": "var:preset|color|primary-3", "text": "var:preset|color|on-primary"}},
                ":focus": {"outline": {"color": "var:preset|color|accent", "offset": "2px", "style": "solid", "width": "2px"}},
            },
            "caption": {"typography": {"fontSize": "var:preset|font-size|x-small"}, "color": {"text": "var:preset|color|contrast-2"}},
            "cite": {"typography": {"fontSize": "var:preset|font-size|small", "fontStyle": "normal"}},
        },
        "blocks": {
            "core/site-title": {"typography": {"fontFamily": "var:preset|font-family|heading", "fontSize": "1.6rem", "fontWeight": "600", "letterSpacing": "0.02em"},
                                "elements": {"link": {"typography": {"textDecoration": "none"}}}},
            "core/navigation": {"typography": {"fontSize": "0.84rem", "textTransform": "uppercase", "letterSpacing": "0.1em", "fontWeight": "500"},
                                "elements": {"link": {"typography": {"textDecoration": "none"}}}},
            "core/post-title": {"elements": {"link": {"typography": {"textDecoration": "none"}}}},
            "core/separator": {"color": {"text": "var:preset|color|line"}, "border": {"width": "1px"}},
            "core/quote": {"typography": {"fontFamily": "var:preset|font-family|heading", "fontSize": "var:preset|font-size|large", "fontStyle": "italic", "lineHeight": "1.4"},
                           "border": {"left": {"color": "var:preset|color|accent", "width": "2px", "style": "solid"}},
                           "spacing": {"padding": {"left": "var:preset|spacing|30"}}},
            "core/pullquote": {"typography": {"fontFamily": "var:preset|font-family|heading", "fontStyle": "italic"}},
            "core/code": {"typography": {"fontFamily": "ui-monospace, SFMono-Regular, Menlo, monospace"}},
            "core/details": {"border": {"bottom": {"color": "var:preset|color|line", "width": "1px", "style": "solid"}},
                             "spacing": {"padding": {"top": "1.1rem", "bottom": "1.1rem"}}},
            "core/search": {"border": {"radius": "var(--wp--custom--radius--pill)"}},
            "core/post-date": {"typography": {"fontSize": "var:preset|font-size|x-small", "textTransform": "uppercase", "letterSpacing": "0.12em"},
                               "color": {"text": "var:preset|color|contrast-2"}},
            "core/post-terms": {"typography": {"fontSize": "var:preset|font-size|x-small"}},
            "core/query-pagination": {"typography": {"fontSize": "var:preset|font-size|small"}},
            "woocommerce/product-price": {"typography": {"fontWeight": "600"}},
            "woocommerce/breadcrumbs": {"typography": {"fontSize": "var:preset|font-size|x-small", "letterSpacing": "0.06em"},
                                        "color": {"text": "var:preset|color|contrast-2"}},
            "woocommerce/product-title": {"typography": {"fontFamily": "var:preset|font-family|heading"}},
        },
    },
    "templateParts": [
        {"name": "announcement", "title": "Announcement bar", "area": "header"},
        {"name": "header", "title": "Header", "area": "header"},
        {"name": "header-minimal", "title": "Header (minimal)", "area": "header"},
        {"name": "footer", "title": "Footer", "area": "footer"},
        {"name": "footer-minimal", "title": "Footer (minimal)", "area": "footer"},
        {"name": "post-meta", "title": "Post meta", "area": "uncategorized"},
    ],
    "customTemplates": [
        {"name": "page-no-title", "title": "Page without title (full width)", "postTypes": ["page"]},
        {"name": "page-wide", "title": "Page wide", "postTypes": ["page"]},
        {"name": "page-landing", "title": "Landing page (no header or footer)", "postTypes": ["page"]},
    ],
}

def deep_merge(a, b):
    for k, val in b.items():
        if isinstance(val, dict) and isinstance(a.get(k), dict):
            deep_merge(a[k], val)
        else:
            a[k] = copy.deepcopy(val)
    return a

theme = copy.deepcopy(BASE)
deep_merge(theme["settings"], settings(V["luxe"]))
deep_merge(theme["styles"], styles(V["luxe"]))


def dump(path, data):
    with open(path, "w") as f:
        json.dump(data, f, indent="\t", ensure_ascii=False)
        f.write("\n")

dump(os.path.join(ROOT, "theme.json"), theme)
os.makedirs(os.path.join(ROOT, "styles"), exist_ok=True)
for key, v in V.items():
    if key == "luxe":
        continue
    var = {"$schema": "https://schemas.wp.org/wp/6.6/theme.json", "version": 3, "title": v["title"],
           "settings": settings(v), "styles": styles(v)}
    if key == "noir":
        var["styles"]["color"] = {"background": "var:preset|color|base", "text": "var:preset|color|contrast"}
    dump(os.path.join(ROOT, "styles", f"{key}.json"), var)
print("ok", list(V))
