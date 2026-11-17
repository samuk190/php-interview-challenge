#!/usr/bin/env python3
"""
Generates the system architecture and services diagram for the Animal Facts API.

Outputs:
  docs/diagrams/system-architecture.svg
  docs/diagrams/system-architecture.png
"""

import html
import subprocess

W, H = 1420, 990

WHITE = "#ffffff"
INK = "#0f172a"
BODY = "#334155"
MUTED = "#64748b"
FAINT = "#94a3b8"
LINE = "#cbd5e1"
BORDER = "#e2e8f0"

BG_PANEL = "#f8fafc"

# Services Colors
APP_BG = "#f0fdf4"
APP_BORDER = "#86efac"
APP_ACCENT = "#16a34a"

DB_BG = "#eff6ff"
DB_BORDER = "#93c5fd"
DB_ACCENT = "#2563eb"

CLIENT_BG = "#faf5ff"
CLIENT_BORDER = "#d8b4fe"
CLIENT_ACCENT = "#9333ea"

SEC_BG = "#fef2f2"
SEC_BORDER = "#fca5a5"
SEC_ACCENT = "#dc2626"

DONE_BG = "#f0f9ff"
DONE_BORDER = "#bae6fd"
DONE_ACCENT = "#0284c7"

SANS = "Adwaita Sans, FreeSans, sans-serif"
MONO = "Adwaita Mono, FreeMono, monospace"


def esc(v):
    return html.escape(str(v), quote=True)


def text(x, y, v, size=13, fill=BODY, family=SANS, weight="normal", anchor="start", opacity=None, spacing=None):
    extra = ""
    if opacity is not None:
        extra += f' opacity="{opacity}"'
    if spacing is not None:
        extra += f' letter-spacing="{spacing}"'
    return f'<text x="{x:.1f}" y="{y:.1f}" font-family="{family}" font-size="{size}" font-weight="{weight}" fill="{fill}" text-anchor="{anchor}"{extra}>{esc(v)}</text>'


def rect(x, y, w, h, fill=WHITE, stroke=None, rx=8, sw=1.2, dash=None):
    s = f' stroke="{stroke}" stroke-width="{sw}"' if stroke else ""
    d = f' stroke-dasharray="{dash}"' if dash else ""
    return f'<rect x="{x:.1f}" y="{y:.1f}" width="{w:.1f}" height="{h:.1f}" rx="{rx}" fill="{fill}"{s}{d}/>'


def line(x1, y1, x2, y2, stroke=LINE, sw=1.4, dash=None):
    d = f' stroke-dasharray="{dash}"' if dash else ""
    return f'<line x1="{x1:.1f}" y1="{y1:.1f}" x2="{x2:.1f}" y2="{y2:.1f}" stroke="{stroke}" stroke-width="{sw}"{d}/>'


def path(d, stroke=LINE, sw=1.4, fill="none", dash=None, marker=None):
    dash_attr = f' stroke-dasharray="{dash}"' if dash else ""
    marker_attr = f' marker-end="url(#{marker})"' if marker else ""
    return f'<path d="{d}" fill="{fill}" stroke="{stroke}" stroke-width="{sw}" stroke-linejoin="round" stroke-linecap="round"{dash_attr}{marker_attr}/>'


def marker(mid, color):
    return f'<marker id="{mid}" viewBox="0 0 10 10" refX="8.5" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M 0 0 L 10 5 L 0 10 z" fill="{color}"/></marker>'


def badge(x, y, w, h, label, fg, bg, stroke, size=11):
    return "".join([
        rect(x, y, w, h, fill=bg, stroke=stroke, rx=5, sw=1),
        text(x + w / 2, y + h / 2 + size * 0.35, label, size=size, fill=fg, family=MONO, weight="bold", anchor="middle"),
    ])


def build_system_diagram():
    p = []

    markers = "".join([
        marker("arrow-ink", INK),
        marker("arrow-muted", MUTED),
        marker("arrow-app", APP_ACCENT),
        marker("arrow-db", DB_ACCENT),
        marker("arrow-client", CLIENT_ACCENT),
    ])

    # Canvas Background
    p.append(rect(0, 0, W, H, fill=WHITE, rx=0))

    # ---- Heading -----------------------------------------------------------
    p.append(text(48, 52, "Animal Facts API — System Architecture & Services", size=26, fill=INK, weight="bold"))
    p.append(text(48, 76, "End-to-end service overview: Docker stack, request lifecycle, storage, and completed challenge features", size=13.5, fill=MUTED))

    # ---- Column 1: Clients & Ingress (x=48, w=280) -------------------------
    c1_x, c1_y, c1_w, c1_h = 48, 104, 280, 560
    p.append(rect(c1_x, c1_y, c1_w, c1_h, fill=CLIENT_BG, stroke=CLIENT_BORDER, rx=12, sw=1.4))
    p.append(rect(c1_x, c1_y, c1_w, 42, fill="#ede9fe", stroke=CLIENT_BORDER, rx=12, sw=1.4))
    p.append(text(c1_x + 16, c1_y + 26, "Clients & Testing Interfaces", size=14, fill=INK, weight="bold"))

    clients = [
        ("Web Browser / Swagger UI", "http://localhost:8042/api/documentation", "Interactive OpenAPI 3.0 testing dashboard"),
        ("Insomnia REST Client", "insomnia_collection.json (v4)", "Preconfigured environments, variables & CRUD"),
        ("Postman / API Tools", "/docs/api-docs.json", "OpenAPI spec import & contract validation"),
        ("CLI / cURL / Automated Tests", "./vendor/bin/sail test", "52 PHPUnit test cases / 161 assertions"),
    ]

    for idx, (title, sub, desc) in enumerate(clients):
        cy = c1_y + 58 + idx * 88
        p.append(rect(c1_x + 12, cy, c1_w - 24, 76, fill=WHITE, stroke=BORDER, rx=8, sw=1.1))
        p.append(rect(c1_x + 12, cy, 4, 76, fill=CLIENT_ACCENT, rx=2, sw=0))
        p.append(text(c1_x + 24, cy + 22, title, size=12.5, fill=INK, weight="bold"))
        p.append(text(c1_x + 24, cy + 42, sub, size=10.5, fill=CLIENT_ACCENT, family=MONO))
        p.append(text(c1_x + 24, cy + 62, desc, size=10.5, fill=MUTED))

    # Host & Ingress Gateway Box
    gw_y = c1_y + 420
    p.append(rect(c1_x + 12, gw_y, c1_w - 24, 126, fill="#fdf4ff", stroke="#e879f9", rx=8, sw=1.2))
    p.append(text(c1_x + 24, gw_y + 24, "WSL2 & Host Port Ingress", size=12.5, fill=INK, weight="bold"))
    p.append(text(c1_x + 24, gw_y + 46, "WSL IP: 10.241.169.153", size=11, fill=BODY, family=MONO))
    p.append(text(c1_x + 24, gw_y + 66, "Host Port: :8042 (APP_PORT)", size=11, fill=BODY, family=MONO))
    p.append(text(c1_x + 24, gw_y + 86, "Forwarded -> Container :80", size=11, fill=MUTED, family=MONO))
    p.append(badge(c1_x + 24, gw_y + 98, 120, 20, "CORS: Origin *", "#a21caf", "#fae8ff", "#f0abfc", size=10))

    # Arrow from Ingress to App Container
    p.append(path(f"M {c1_x + c1_w} {gw_y + 60} L {368} {gw_y + 60}", stroke=CLIENT_ACCENT, sw=2, marker="arrow-client"))

    # ---- Column 2: Application Container (x=368, w=670) -------------------
    c2_x, c2_y, c2_w, c2_h = 368, 104, 670, 560
    p.append(rect(c2_x, c2_y, c2_w, c2_h, fill=APP_BG, stroke=APP_BORDER, rx=12, sw=1.4))
    p.append(rect(c2_x, c2_y, c2_w, 42, fill="#dcfce7", stroke=APP_BORDER, rx=12, sw=1.4))
    p.append(text(c2_x + 16, c2_y + 26, "Service 1: interviews-laravel.test-1  (Laravel 9 / PHP 8.1)", size=14, fill=INK, weight="bold"))
    p.append(badge(c2_x + c2_w - 180, c2_y + 11, 164, 20, "Docker Sail Container", "#15803d", "#dcfce7", "#86efac", size=10.5))

    # Inside App: Layer 1 - Middleware
    m_y = c2_y + 56
    p.append(rect(c2_x + 14, m_y, c2_w - 28, 64, fill=WHITE, stroke=BORDER, rx=8))
    p.append(text(c2_x + 26, m_y + 20, "HTTP Kernel & Security Middleware", size=12, fill=INK, weight="bold"))
    p.append(badge(c2_x + 26, m_y + 32, 140, 22, "ForceJsonResponse", "#15803d", "#f0fdf4", "#bbf7d0", size=10.5))
    p.append(badge(c2_x + 176, m_y + 32, 110, 22, "HandleCors (*)", "#0369a1", "#f0f9ff", "#bae6fd", size=10.5))
    p.append(badge(c2_x + 296, m_y + 32, 140, 22, "ThrottleRequests (60)", "#b45309", "#fffbeb", "#fde68a", size=10.5))
    p.append(badge(c2_x + 446, m_y + 32, 180, 22, "TrimStrings / Validations", "#475569", "#f8fafc", "#cbd5e1", size=10.5))

    # Inside App: Layer 2 - Routing
    r_y = m_y + 76
    p.append(rect(c2_x + 14, r_y, c2_w - 28, 70, fill=WHITE, stroke=BORDER, rx=8))
    p.append(text(c2_x + 26, r_y + 20, "Routing & Regex Route Constraints (routes/api.php, routes/v1/*.php)", size=12, fill=INK, weight="bold"))
    p.append(text(c2_x + 26, r_y + 40, "GET /api/v1 (Discovery)  •  /api/documentation (Swagger UI)  •  /docs (OpenAPI JSON)", size=10.5, fill=MUTED, family=MONO))
    p.append(text(c2_x + 26, r_y + 58, "Resource Routes: /cat-facts & /dog-facts with ->whereNumber('id') (Hardened against non-numeric IDs)", size=10.5, fill=APP_ACCENT, family=MONO, weight="bold"))

    # Inside App: Layer 3 - Validation (FormRequests)
    v_y = r_y + 82
    p.append(rect(c2_x + 14, v_y, c2_w - 28, 80, fill=WHITE, stroke=BORDER, rx=8))
    p.append(text(c2_x + 26, v_y + 20, "FormRequest Validation Layer (Strict 422 JSON Responses)", size=12, fill=INK, weight="bold"))
    p.append(text(c2_x + 26, v_y + 42, "StoreRequest:   fact required, string, max:255  [FIXED: authorize() returns true]", size=11, fill="#15803d", family=MONO))
    p.append(text(c2_x + 26, v_y + 60, "IndexRequest:   pageSize optional, integer, min:1, max:100 (defaults to 25)", size=11, fill=BODY, family=MONO))

    # Inside App: Layer 4 - Controllers & Repositories
    cr_y = v_y + 92
    p.append(rect(c2_x + 14, cr_y, (c2_w - 38) / 2, 130, fill=WHITE, stroke=BORDER, rx=8))
    p.append(rect(c2_x + 14, cr_y, 4, 130, fill="#b45309", rx=2, sw=0))
    p.append(text(c2_x + 26, cr_y + 22, "🐈 Cat Facts Subsystem", size=12.5, fill=INK, weight="bold"))
    p.append(text(c2_x + 26, cr_y + 42, "• IndexController (paginate 25)", size=10.5, fill=BODY, family=MONO))
    p.append(text(c2_x + 26, cr_y + 58, "• StoreController (Bug fixed)", size=10.5, fill=BODY, family=MONO))
    p.append(text(c2_x + 26, cr_y + 74, "• Show / Update / Destroy", size=10.5, fill=BODY, family=MONO))
    p.append(text(c2_x + 26, cr_y + 92, "• CatFactRepository", size=10.5, fill=APP_ACCENT, family=MONO, weight="bold"))
    p.append(text(c2_x + 26, cr_y + 110, "• Model: CatFact ($fillable)", size=10.5, fill=MUTED, family=MONO))

    p.append(rect(c2_x + 24 + (c2_w - 38) / 2, cr_y, (c2_w - 38) / 2, 130, fill=WHITE, stroke=BORDER, rx=8))
    p.append(rect(c2_x + 24 + (c2_w - 38) / 2, cr_y, 4, 130, fill="#1d4ed8", rx=2, sw=0))
    p.append(text(c2_x + 36 + (c2_w - 38) / 2, cr_y + 22, "🐕 Dog Facts Subsystem (New)", size=12.5, fill=INK, weight="bold"))
    p.append(text(c2_x + 36 + (c2_w - 38) / 2, cr_y + 42, "• IndexController (paginate 25)", size=10.5, fill=BODY, family=MONO))
    p.append(text(c2_x + 36 + (c2_w - 38) / 2, cr_y + 58, "• StoreController (200 JSON)", size=10.5, fill=BODY, family=MONO))
    p.append(text(c2_x + 36 + (c2_w - 38) / 2, cr_y + 74, "• Show / Update / Destroy", size=10.5, fill=BODY, family=MONO))
    p.append(text(c2_x + 36 + (c2_w - 38) / 2, cr_y + 92, "• DogFactRepository", size=10.5, fill=APP_ACCENT, family=MONO, weight="bold"))
    p.append(text(c2_x + 36 + (c2_w - 38) / 2, cr_y + 110, "• Model: DogFact ($fillable)", size=10.5, fill=MUTED, family=MONO))

    # Inside App: Layer 5 - Security & Documentation Modules
    s_y = cr_y + 142
    p.append(rect(c2_x + 14, s_y, c2_w - 28, 92, fill="#f8fafc", stroke=BORDER, rx=8))
    p.append(text(c2_x + 26, s_y + 20, "Cross-Cutting Security & Documentation Modules", size=12, fill=INK, weight="bold"))
    p.append(text(c2_x + 26, s_y + 40, "• Exceptions Handler: Sanitizes 500 responses; logs via report() (Zero SQL/credential leak - CWE-209)", size=10.5, fill="#b91c1c", family=MONO))
    p.append(text(c2_x + 26, s_y + 60, "• darkaonline/l5-swagger: Generates OpenAPI 3.0 specification from PHP docblock annotations", size=10.5, fill="#0369a1", family=MONO))
    p.append(text(c2_x + 26, s_y + 80, "• Deterministic Ordering: All pagination calls enforce ->orderBy('id', 'asc')", size=10.5, fill=BODY, family=MONO))

    # Arrow from App to Database
    p.append(path(f"M {c2_x + c2_w} {cr_y + 65} L {1068} {cr_y + 65}", stroke=DB_ACCENT, sw=2, marker="arrow-db"))

    # ---- Column 3: Database Container (x=1068, w=304) ----------------------
    c3_x, c3_y, c3_w, c3_h = 1068, 104, 304, 560
    p.append(rect(c3_x, c3_y, c3_w, c3_h, fill=DB_BG, stroke=DB_BORDER, rx=12, sw=1.4))
    p.append(rect(c3_x, c3_y, c3_w, 42, fill="#dbeafe", stroke=DB_BORDER, rx=12, sw=1.4))
    p.append(text(c3_x + 16, c3_y + 26, "Service 2: MySQL 8.0", size=14, fill=INK, weight="bold"))
    p.append(badge(c3_x + c3_w - 96, c3_y + 11, 80, 20, "Port 3306", "#1d4ed8", "#dbeafe", "#bfdbfe", size=10))

    # DB Connection Details Card
    db_c_y = c3_y + 56
    p.append(rect(c3_x + 12, db_c_y, c3_w - 24, 76, fill=WHITE, stroke=BORDER, rx=8))
    p.append(text(c3_x + 24, db_c_y + 20, "Container: interviews-mysql-1", size=11.5, fill=INK, weight="bold"))
    p.append(text(c3_x + 24, db_c_y + 38, "Network: interviews_sail (Bridge)", size=10.5, fill=MUTED, family=MONO))
    p.append(text(c3_x + 24, db_c_y + 54, "Host Port: 127.0.0.1:33042 (Bound)", size=10.5, fill=DB_ACCENT, family=MONO))
    p.append(text(c3_x + 24, db_c_y + 68, "Status: Up & Healthy", size=10.5, fill="#15803d", family=MONO))

    # Production Database Card
    db_p_y = db_c_y + 88
    p.append(rect(c3_x + 12, db_p_y, c3_w - 24, 210, fill=WHITE, stroke=BORDER, rx=8))
    p.append(rect(c3_x + 12, db_p_y, 4, 210, fill=DB_ACCENT, rx=2, sw=0))
    p.append(text(c3_x + 24, db_p_y + 24, "Database: jobsity_challenge", size=12.5, fill=INK, weight="bold"))
    
    tables = [
        ("cat_facts", "id (PK INT), fact VARCHAR(255), timestamps"),
        ("dog_facts", "id (PK INT), fact VARCHAR(255), timestamps"),
        ("migrations", "Migration tracking history"),
    ]
    for tidx, (tbl, cols) in enumerate(tables):
        ty = db_p_y + 44 + tidx * 54
        p.append(rect(c3_x + 22, ty, c3_w - 44, 46, fill="#f8fafc", stroke=BORDER, rx=6))
        p.append(text(c3_x + 32, ty + 18, f"Table: {tbl}", size=11, fill=INK, weight="bold", family=MONO))
        p.append(text(c3_x + 32, ty + 34, cols, size=9.5, fill=MUTED, family=MONO))

    # Testing Database Card
    db_t_y = db_p_y + 222
    p.append(rect(c3_x + 12, db_t_y, c3_w - 24, 96, fill="#f0fdf4", stroke="#bbf7d0", rx=8))
    p.append(rect(c3_x + 12, db_t_y, 4, 96, fill="#15803d", rx=2, sw=0))
    p.append(text(c3_x + 24, db_t_y + 22, "Isolated Testing Database", size=12, fill=INK, weight="bold"))
    p.append(text(c3_x + 24, db_t_y + 42, "Database: testing", size=11, fill="#15803d", family=MONO, weight="bold"))
    p.append(text(c3_x + 24, db_t_y + 60, "Configured in phpunit.xml", size=10.5, fill=MUTED, family=MONO))
    p.append(text(c3_x + 24, db_t_y + 78, "Isolates automated tests from prod data", size=10, fill=BODY))

    # ---- Bottom Section: Completed Deliverables (x=48, y=684, w=1324) ------
    bot_y = 684
    p.append(rect(48, bot_y, W - 96, 276, fill=DONE_BG, stroke=DONE_BORDER, rx=12, sw=1.4))
    p.append(rect(48, bot_y, W - 96, 38, fill="#e0f2fe", stroke=DONE_BORDER, rx=12, sw=1.4))
    p.append(text(64, bot_y + 25, "Completed Deliverables & Verification Matrix", size=14, fill=INK, weight="bold"))

    features = [
        ("1. Cat Facts Storing Fix", "Fixed StoreRequest::authorize() returning false (HTTP 403). Validated with feature tests.", "#b45309"),
        ("2. Dog Facts REST Resource", "Full CRUD parity (/dog-facts), single-action controllers, DogFactRepository, and migrations.", "#1d4ed8"),
        ("3. Configurable Pagination", "Default 25 items per page, pageSize param (min:1, max:100), deterministic orderBy('id', 'asc').", "#0e7490"),
        ("4. Swagger / OpenAPI 3.0 UI", "Interactive UI at /api/documentation and JSON spec at /docs. Documented request/response schemas.", "#0284c7"),
        ("5. Security & Quality Hardening", "ForceJsonResponse, whereNumber regex routes, CWE-209 safe exceptions, and localhost port bindings.", "#b91c1c"),
        ("6. 52 Tests & Insomnia Collection", "52 PHPUnit tests (161 assertions), Pint PSR-12 clean, and insomnia_collection.json v4 export.", "#15803d"),
    ]

    for f_idx, (f_title, f_desc, f_accent) in enumerate(features):
        row = f_idx // 3
        col = f_idx % 3
        fx = 64 + col * 436
        fy = bot_y + 52 + row * 104
        fw = 418
        fh = 92
        p.append(rect(fx, fy, fw, fh, fill=WHITE, stroke=BORDER, rx=8, sw=1.1))
        p.append(rect(fx, fy, 4, fh, fill=f_accent, rx=2, sw=0))
        p.append(text(fx + 16, fy + 24, f_title, size=12.5, fill=INK, weight="bold"))
        # Description split in 2 lines if needed
        p.append(text(fx + 16, fy + 46, f_desc[:58], size=10.5, fill=BODY))
        p.append(text(fx + 16, fy + 64, f_desc[58:], size=10.5, fill=MUTED))

    # Document wrapper
    svg_code = f'<svg xmlns="http://www.w3.org/2000/svg" width="{W}" height="{H}" viewBox="0 0 {W} {H}" role="img">\n<defs>{markers}</defs>\n' + "\n".join(p) + "\n</svg>\n"
    return svg_code


def main():
    svg = build_system_diagram()
    svg_path = "/home/samuel/interviews/docs/diagrams/system-architecture.svg"
    png_path = "/home/samuel/interviews/docs/diagrams/system-architecture.png"

    with open(svg_path, "w", encoding="utf-8") as f:
        f.write(svg)
    print(f"Saved {svg_path}")

    subprocess.run(["rsvg-convert", "-w", str(W * 2), "-o", png_path, svg_path], check=True)
    print(f"Saved {png_path} (High-res PNG)")


if __name__ == "__main__":
    main()
