#!/usr/bin/env python3
"""
Generates the backend API path diagrams for this repository.

Outputs (docs/diagrams/):
  api-endpoints.svg / .png      -> the exposed REST paths, method by method
  request-lifecycle.svg / .png  -> how one request travels through the layers

Everything is drawn from plain SVG primitives so the output is deterministic,
diffable and renders without any diagram toolchain.
"""

import html
import subprocess

SANS = "Adwaita Sans, FreeSans, sans-serif"
MONO = "Adwaita Mono, FreeMono, monospace"

INK = "#0f172a"
BODY = "#334155"
MUTED = "#64748b"
FAINT = "#94a3b8"
LINE = "#cbd5e1"
PANEL = "#f8fafc"
BORDER = "#e2e8f0"
WHITE = "#ffffff"

CAT = "#b45309"
CAT_BG = "#fffbeb"
CAT_BORDER = "#fde68a"

DOG = "#1d4ed8"
DOG_BG = "#eff6ff"
DOG_BORDER = "#bfdbfe"

RETURN = "#7c3aed"

METHOD_COLORS = {
    "GET": ("#0e7490", "#ecfeff", "#a5f3fc"),
    "POST": ("#15803d", "#f0fdf4", "#bbf7d0"),
    "PUT": ("#b45309", "#fffbeb", "#fde68a"),
    "DELETE": ("#b91c1c", "#fef2f2", "#fecaca"),
}

WARNINGS = []


# --------------------------------------------------------------------------- #
# primitives
# --------------------------------------------------------------------------- #
def esc(value):
    return html.escape(str(value), quote=True)


def est_width(value, size, mono=False):
    return len(value) * size * (0.60 if mono else 0.53)


def check_width(value, size, limit, mono, where):
    width = est_width(value, size, mono)
    if width > limit:
        WARNINGS.append(f"overflow in {where}: {width:.0f}px > {limit:.0f}px :: {value!r}")


def text(x, y, value, size=14, fill=BODY, family=SANS, weight="normal",
         anchor="start", opacity=None, spacing=None, rotate=None):
    extra = ""
    if opacity is not None:
        extra += f' opacity="{opacity}"'
    if spacing is not None:
        extra += f' letter-spacing="{spacing}"'
    if rotate is not None:
        extra += f' transform="rotate({rotate} {x:.1f} {y:.1f})"'
    return (
        f'<text x="{x:.1f}" y="{y:.1f}" font-family="{family}" font-size="{size}" '
        f'font-weight="{weight}" fill="{fill}" text-anchor="{anchor}"{extra}>'
        f"{esc(value)}</text>"
    )


def rect(x, y, w, h, fill=WHITE, stroke=None, rx=10, sw=1.2, dash=None):
    stroke_attr = f' stroke="{stroke}" stroke-width="{sw}"' if stroke else ""
    dash_attr = f' stroke-dasharray="{dash}"' if dash else ""
    return (
        f'<rect x="{x:.1f}" y="{y:.1f}" width="{w:.1f}" height="{h:.1f}" '
        f'rx="{rx}" fill="{fill}"{stroke_attr}{dash_attr}/>'
    )


def line(x1, y1, x2, y2, stroke=LINE, sw=1.4, dash=None):
    dash_attr = f' stroke-dasharray="{dash}"' if dash else ""
    return (
        f'<line x1="{x1:.1f}" y1="{y1:.1f}" x2="{x2:.1f}" y2="{y2:.1f}" '
        f'stroke="{stroke}" stroke-width="{sw}"{dash_attr}/>'
    )


def path(d, stroke=LINE, sw=1.4, fill="none", dash=None, marker=None):
    dash_attr = f' stroke-dasharray="{dash}"' if dash else ""
    marker_attr = f' marker-end="url(#{marker})"' if marker else ""
    return (
        f'<path d="{d}" fill="{fill}" stroke="{stroke}" stroke-width="{sw}" '
        f'stroke-linejoin="round" stroke-linecap="round"{dash_attr}{marker_attr}/>'
    )


def marker(mid, color):
    return (
        f'<marker id="{mid}" viewBox="0 0 10 10" refX="8.5" refY="5" '
        f'markerWidth="7" markerHeight="7" orient="auto-start-reverse">'
        f'<path d="M 0 0 L 10 5 L 0 10 z" fill="{color}"/></marker>'
    )


def badge(x, y, w, h, label, fg, bg, stroke, size=11.5):
    return "".join([
        rect(x, y, w, h, fill=bg, stroke=stroke, rx=6, sw=1.1),
        text(x + w / 2, y + h / 2 + size * 0.36, label, size=size, fill=fg,
             family=MONO, weight="bold", anchor="middle"),
    ])


def svg_document(width, height, body, markers=""):
    return (
        f'<svg xmlns="http://www.w3.org/2000/svg" width="{width}" height="{height}" '
        f'viewBox="0 0 {width} {height}" role="img">\n'
        f"<defs>{markers}</defs>\n"
        f'<rect x="0" y="0" width="{width}" height="{height}" fill="{WHITE}"/>\n'
        f"{body}\n</svg>\n"
    )


# --------------------------------------------------------------------------- #
# diagram 1 - endpoint map
# --------------------------------------------------------------------------- #
def build_endpoint_map():
    W, H = 1240, 866
    p = []

    markers = "".join([
        marker("arrow-ink", MUTED),
        marker("arrow-cat", CAT),
        marker("arrow-dog", DOG),
    ])

    # ---- heading -----------------------------------------------------------
    p.append(text(48, 56, "Animal Facts API \u2014 backend paths", size=29,
                  fill=INK, weight="bold"))
    p.append(text(48, 84,
                  "Laravel 9 \u00b7 PHP 8.1 \u00b7 REST over JSON \u00b7 base URL "
                  "http://localhost:8042",
                  size=14, fill=MUTED))

    # ---- request chain -----------------------------------------------------
    chain_y, chain_h = 112, 78

    p.append(rect(48, chain_y, 250, chain_h, fill=PANEL, stroke=BORDER))
    p.append(text(64, chain_y + 30, "HTTP client", size=14.5, fill=INK, weight="bold"))
    p.append(text(64, chain_y + 51, "curl \u00b7 Postman \u00b7 browser", size=11.5, fill=MUTED))
    p.append(text(64, chain_y + 68, "Accept: application/json", size=11, fill=FAINT, family=MONO))

    p.append(path(f"M 306 {chain_y + chain_h / 2:.0f} L 352 {chain_y + chain_h / 2:.0f}",
                  stroke=MUTED, marker="arrow-ink"))

    p.append(rect(358, chain_y, 466, chain_h, fill=PANEL, stroke=BORDER))
    p.append(text(374, chain_y + 30, "api middleware group", size=14.5, fill=INK, weight="bold"))
    p.append(text(374, chain_y + 51,
                  "ForceJsonResponse \u2192 throttle:api (60/min) \u2192 SubstituteBindings",
                  size=11.5, fill=MUTED))
    p.append(text(374, chain_y + 68, "app/Http/Kernel.php", size=11, fill=FAINT, family=MONO))

    p.append(path(f"M 832 {chain_y + chain_h / 2:.0f} L 878 {chain_y + chain_h / 2:.0f}",
                  stroke=MUTED, marker="arrow-ink"))

    p.append(rect(884, chain_y, 308, chain_h, fill=PANEL, stroke=BORDER))
    p.append(text(900, chain_y + 30, "Router  \u00b7  prefix /api/v1", size=14.5,
                  fill=INK, weight="bold"))
    p.append(text(900, chain_y + 51, "routes/api.php \u2192 routes/v1/*.php", size=11.5,
                  fill=MUTED))
    p.append(text(900, chain_y + 68, "2 resources \u00b7 10 REST routes", size=11, fill=FAINT,
                  family=MONO))

    # ---- root endpoint -----------------------------------------------------
    root_y, root_h = 218, 56
    p.append(path(f"M 1038 {chain_y + chain_h} L 1038 {root_y - 12} L 620 {root_y - 12} "
                  f"L 620 {root_y}", stroke=MUTED, marker="arrow-ink"))
    p.append(rect(48, root_y, 1144, root_h, fill="#f1f5f9", stroke=BORDER))
    p.append(badge(66, root_y + 14, 52, 28, "GET", *METHOD_COLORS["GET"]))
    p.append(text(132, root_y + 27, "/api/v1", size=14, fill=INK, family=MONO, weight="bold"))
    p.append(text(132, root_y + 45,
                  "closure \u2192 { name, version, resources }   \u2014   service index, no persistence",
                  size=11.5, fill=MUTED))

    # ---- resource cards ----------------------------------------------------
    card_y, card_h, card_w = 312, 396, 552
    cards = [
        ("cat-facts", "Cat Facts", CAT, CAT_BG, CAT_BORDER, 48, "arrow-cat"),
        ("dog-facts", "Dog Facts", DOG, DOG_BG, DOG_BORDER, 640, "arrow-dog"),
    ]

    for slug, title, color, bg, border, x, arrow in cards:
        p.append(path(f"M {x + card_w / 2:.0f} {root_y + root_h} "
                      f"L {x + card_w / 2:.0f} {card_y}",
                      stroke=color, marker=arrow))

        p.append(rect(x, card_y, card_w, card_h, fill=WHITE, stroke=border, rx=14, sw=1.4))
        p.append(rect(x, card_y, card_w, 6, fill=color, rx=3, sw=0))
        p.append(text(x + 20, card_y + 42, title, size=17.5, fill=INK, weight="bold"))
        p.append(text(x + 20, card_y + 62, f"/api/v1/{slug}", size=12.5,
                      fill=color, family=MONO, weight="bold"))

        rows = [
            ("GET", f"/api/v1/{slug}", "IndexController", "200 \u00b7 422"),
            ("POST", f"/api/v1/{slug}", "StoreController", "200 \u00b7 422 \u00b7 500"),
            ("GET", f"/api/v1/{slug}/{{id}}", "ShowController", "200 \u00b7 404"),
            ("PUT", f"/api/v1/{slug}/{{id}}", "UpdateController",
             "200 \u00b7 404 \u00b7 422 \u00b7 500"),
            ("DELETE", f"/api/v1/{slug}/{{id}}", "DestroyController", "204 \u00b7 404 \u00b7 500"),
        ]

        row_h = 62
        first = card_y + 82
        for i, (method, route, controller, status) in enumerate(rows):
            ry = first + i * row_h
            if i:
                p.append(line(x + 14, ry, x + card_w - 14, ry, stroke="#f1f5f9", sw=1.2))

            fg, mbg, mborder = METHOD_COLORS[method]
            p.append(badge(x + 20, ry + 16, 68, 28, method, fg, mbg, mborder))

            check_width(route, 14, 300, True, f"{slug} route row")
            p.append(text(x + 102, ry + 30, route, size=14, fill=INK, family=MONO))
            p.append(text(x + 102, ry + 48, controller, size=11.5, fill=MUTED))

            check_width(status, 11.5, 190, False, f"{slug} status row")
            p.append(text(x + card_w - 20, ry + 30, status, size=11.5,
                          fill=BODY, anchor="end"))

    # ---- legend ------------------------------------------------------------
    legend_y = 752
    p.append(line(48, legend_y - 26, 1192, legend_y - 26, stroke="#f1f5f9", sw=1.2))

    lx = 48
    for method, label in [
        ("GET", "read"),
        ("POST", "create"),
        ("PUT", "update"),
        ("DELETE", "remove"),
    ]:
        fg, mbg, mborder = METHOD_COLORS[method]
        p.append(badge(lx, legend_y, 68, 26, method, fg, mbg, mborder, size=11))
        p.append(text(lx + 78, legend_y + 17.5, label, size=12, fill=MUTED))
        lx += 158

    p.append(text(48, legend_y + 52,
                  "Both index endpoints accept ?page and ?pageSize (default 25, from "
                  "config/pagination.php).",
                  size=12, fill=MUTED))
    p.append(text(48, legend_y + 72,
                  "Every route sits behind the api group, so all responses \u2014 including "
                  "404, 422 and 500 \u2014 are application/json.",
                  size=12, fill=MUTED))

    return svg_document(W, H, "\n".join(p), markers)


# --------------------------------------------------------------------------- #
# diagram 2 - request lifecycle
# --------------------------------------------------------------------------- #
def build_lifecycle():
    """Vertical request pipeline: one box per layer, tagged by concern."""
    W = 1240
    box_x, box_w = 176, 800
    tag_x = box_x + box_w + 22
    box_h, gap = 76, 16

    steps = [
        ("HTTP client", "transport",
         "POST /api/v1/dog-facts",
         'body  {"fact": "Dogs have three eyelids."}',
         PANEL, BORDER, MUTED),
        ("Router", "routing",
         "routes/api.php  \u2192  routes/v1/dog-facts.php",
         "Route::post('/', StoreController::class)->name('api.v1.dog-facts.store')",
         PANEL, BORDER, MUTED),
        ("api middleware group", "middleware",
         "app/Http/Kernel.php",
         "ForceJsonResponse  \u00b7  throttle:api  \u00b7  SubstituteBindings",
         PANEL, BORDER, MUTED),
        ("StoreController  (invokable)", "controller",
         "app/Http/Controllers/API/V1/DogFacts/Rest/StoreController.php",
         "__invoke(StoreRequest $request)",
         DOG_BG, DOG_BORDER, DOG),
        ("StoreRequest  (FormRequest)", "validation",
         "app/Http/Requests/API/V1/DogFacts/Rest/StoreRequest.php",
         "authorize(): true     rules: fact => required|string",
         DOG_BG, DOG_BORDER, DOG),
        ("DogFactRepository", "repository",
         "app/Repositories/DogFactRepository.php",
         "store(array $options): ?DogFact",
         DOG_BG, DOG_BORDER, DOG),
        ("DogFact  (Eloquent model)", "persistence",
         "app/Models/DogFact.php",
         "$dogFact->fact = $options['fact'];   $dogFact->save();",
         DOG_BG, DOG_BORDER, DOG),
        ("MySQL 8.0", "storage",
         "table  dog_facts",
         "id  \u00b7  fact  \u00b7  created_at  \u00b7  updated_at",
         "#f1f5f9", BORDER, MUTED),
    ]

    top = 150
    total = len(steps) * box_h + (len(steps) - 1) * gap
    response_y = top + total + 24
    response_h = 162
    foot_y = response_y + response_h + 46
    H = int(foot_y + 56)

    p = []

    markers = "".join([
        marker("arrow-ink", MUTED),
        marker("arrow-return", RETURN),
    ])

    # ---- heading -----------------------------------------------------------
    p.append(text(48, 56, "One request, layer by layer", size=29, fill=INK, weight="bold"))
    p.append(text(48, 84,
                  "POST /api/v1/dog-facts  \u2014  the same shape as every other route, "
                  "for either resource",
                  size=14, fill=MUTED))

    # ---- lane labels -------------------------------------------------------
    p.append(text(box_x - 18, top - 14, "REQUEST", size=10.5, fill=FAINT,
                  weight="bold", spacing="1.6", anchor="end"))
    p.append(text(box_x - 18, response_y + 18, "RESPONSE", size=10.5, fill=FAINT,
                  weight="bold", spacing="1.6", anchor="end"))

    # ---- step boxes --------------------------------------------------------
    for i, (title, tag, location, note, bg, border, accent) in enumerate(steps):
        y = top + i * (box_h + gap)

        p.append(rect(box_x, y, box_w, box_h, fill=bg, stroke=border, rx=11, sw=1.3))
        p.append(rect(box_x, y, 5, box_h, fill=accent, rx=2.5, sw=0))
        p.append(text(box_x + 26, y + 27, title, size=15, fill=INK, weight="bold"))
        p.append(text(box_x + 26, y + 48, location, size=11.5, fill=MUTED, family=MONO))
        p.append(text(box_x + 26, y + 66, note, size=11.5, fill=FAINT, family=MONO))

        # concern tag in a right-hand gutter, vertically centred on the box
        p.append(text(tag_x, y + box_h / 2 + 4, tag, size=10.5, fill=FAINT,
                      family=MONO, weight="bold", spacing="1.4"))

        check_width(location, 11.5, box_w - 52, True, f"step {i} location")
        check_width(note, 11.5, box_w - 52, True, f"step {i} note")
        check_width(tag, 10.5, W - tag_x - 24, True, f"step {i} tag")

        if i < len(steps) - 1:
            cx = box_x + box_w / 2
            p.append(path(f"M {cx:.0f} {y + box_h} L {cx:.0f} {y + box_h + gap}",
                          stroke=MUTED, sw=1.4, marker="arrow-ink"))

    # ---- response panel ----------------------------------------------------
    p.append(rect(box_x, response_y, box_w, response_h,
                  fill="#faf5ff", stroke="#ddd6fe", rx=11, sw=1.3))
    p.append(rect(box_x, response_y, 5, response_h, fill=RETURN, rx=2.5, sw=0))
    p.append(text(box_x + 26, response_y + 30, "JSON response", size=15,
                  fill=INK, weight="bold"))
    p.append(text(box_x + 26, response_y + 51,
                  "rendered by the invokable controller; failures short-circuit "
                  "at the point they happen",
                  size=11.5, fill=MUTED))

    outcomes = [
        ("200", "created fact, with its new id", "#15803d"),
        ("204", "empty body after a successful delete", "#15803d"),
        ("404", '{"message":"Not Found"}', "#b45309"),
        ("422", '{"message": ..., "errors": {"fact": [...]}}', "#b45309"),
        ("500", '{"message": "..."} from the exception handler', "#b91c1c"),
    ]
    left_x, right_x = box_x + 26, box_x + 416
    for i, (code, desc, color) in enumerate(outcomes):
        cx, cy = (left_x, response_y + 80 + i * 22) if i < 3 else \
                 (right_x, response_y + 80 + (i - 3) * 22)
        p.append(text(cx, cy, code, size=11.5, fill=color, family=MONO, weight="bold"))
        p.append(text(cx + 42, cy, desc, size=11.5, fill=BODY, family=MONO))
        check_width(desc, 11.5, box_w - 460, True, f"outcome {code}")

    # ---- dashed return rail ------------------------------------------------
    rail_x = 100
    client_mid = top + box_h / 2
    resp_mid = response_y + response_h / 2

    p.append(path(
        f"M {box_x} {resp_mid:.0f} L {rail_x} {resp_mid:.0f} L {rail_x} {client_mid:.0f} "
        f"L {box_x - 11} {client_mid:.0f}",
        stroke=RETURN, sw=1.5, dash="5 5", marker="arrow-return"))
    p.append(text(rail_x, (resp_mid + client_mid) / 2,
                  "response", size=10.5, fill=RETURN, weight="bold",
                  anchor="middle", spacing="1.2",
                  rotate=-90))

    # ---- footnotes ---------------------------------------------------------
    p.append(line(48, foot_y - 22, 1192, foot_y - 22, stroke="#f1f5f9", sw=1.2))
    p.append(text(48, foot_y,
                  "Validation runs before the controller body: an invalid payload never "
                  "reaches the repository.",
                  size=12, fill=MUTED))
    p.append(text(48, foot_y + 20,
                  "Cat Facts follow the identical path through "
                  "CatFacts\\Rest\\*Controller and CatFactRepository.",
                  size=12, fill=MUTED))

    return svg_document(W, H, "\n".join(p), markers), W, H


# --------------------------------------------------------------------------- #
# render
# --------------------------------------------------------------------------- #
def write(name, svg, width):
    svg_path = f"/home/samuel/interviews/docs/diagrams/{name}.svg"
    png_path = f"/home/samuel/interviews/docs/diagrams/{name}.png"

    with open(svg_path, "w", encoding="utf-8") as fh:
        fh.write(svg)

    subprocess.run(
        ["rsvg-convert", "-w", str(width * 2), "-o", png_path, svg_path],
        check=True,
    )
    print(f"wrote {svg_path}")
    print(f"wrote {png_path}")


def main():
    write("api-endpoints", build_endpoint_map(), 1240)
    lifecycle_svg, w, h = build_lifecycle()
    write("request-lifecycle", lifecycle_svg, w)
    print(f"lifecycle canvas: {w}x{h}")

    if WARNINGS:
        print("\nWARNINGS:")
        for warning in dict.fromkeys(WARNINGS):
            print("  " + warning)
    else:
        print("no layout warnings")


if __name__ == "__main__":
    main()
