// Generates all artwork used by the theme patterns and the demo importer.
// Every image is an original illustration (GPL-compatible), rendered to WebP/JPEG
// with Chromium so no stock photography licences are involved.
//
// Usage (needs `playwright` and `sharp` available to node):
//   node tools/generate-art.mjs
// Replace the output with real product photography whenever you like.
import { mkdirSync, writeFileSync } from "node:fs";
import { dirname, join } from "node:path";
import { fileURLToPath } from "node:url";
import { createRequire } from "node:module";

const require = createRequire(import.meta.url);
const ROOT = join(dirname(fileURLToPath(import.meta.url)), "..");
const THEME_IMG = join(ROOT, "theme/assets/images");
const DEMO_IMG = join(ROOT, "plugin/assets/demo");
mkdirSync(THEME_IMG, { recursive: true });
mkdirSync(DEMO_IMG, { recursive: true });

/* ---------- Jewellery (ported from the reference store) ---------- */
const METALS = {
  gold: ["#7a5a1e", "#f6e3a1", "#b8892f", "#fff3c4", "#8a6a2c"],
  rose: ["#7a3f38", "#f5c6b8", "#b8746a", "#ffe1d6", "#8c4f45"],
  white: ["#7c828a", "#f4f6f8", "#aeb5be", "#ffffff", "#8e949c"],
};
const BACKDROPS = {
  gold: ["#1d4a40", "#0a1f1a"],
  rose: ["#4a2233", "#1a0b12"],
  white: ["#1f3150", "#0a1120"],
};
const GEMS = {
  diamond: ["#ffffff", "#dff3ff", "#9ccfe8"],
  emerald: ["#b9ffd9", "#1fae6b", "#0b5a36"],
  ruby: ["#ffd0d8", "#e0204a", "#7a0a22"],
  sapphire: ["#d4e4ff", "#2a5bd7", "#0e2466"],
  "pink-sapphire": ["#ffe3ef", "#f06aa0", "#9c2a5c"],
  pearl: ["#ffffff", "#f7efe6", "#d9c9b6"],
  onyx: ["#6b6b6b", "#1b1b1b", "#000000"],
  none: null,
};

const defs = (metal, gem, id) => {
  const m = METALS[metal];
  const b = BACKDROPS[metal];
  const g = GEMS[gem] ?? GEMS.diamond;
  return `
  <defs>
    <radialGradient id="bg${id}" cx="50%" cy="38%" r="75%">
      <stop offset="0%" stop-color="${b[0]}"/>
      <stop offset="100%" stop-color="${b[1]}"/>
    </radialGradient>
    <radialGradient id="spot${id}" cx="50%" cy="30%" r="45%">
      <stop offset="0%" stop-color="#fff" stop-opacity=".22"/>
      <stop offset="100%" stop-color="#fff" stop-opacity="0"/>
    </radialGradient>
    <linearGradient id="metal${id}" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="${m[0]}"/>
      <stop offset="25%" stop-color="${m[1]}"/>
      <stop offset="50%" stop-color="${m[2]}"/>
      <stop offset="72%" stop-color="${m[3]}"/>
      <stop offset="100%" stop-color="${m[4]}"/>
    </linearGradient>
    <linearGradient id="metalV${id}" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0%" stop-color="${m[3]}"/>
      <stop offset="45%" stop-color="${m[2]}"/>
      <stop offset="100%" stop-color="${m[0]}"/>
    </linearGradient>
    <radialGradient id="gem${id}" cx="38%" cy="32%" r="75%">
      <stop offset="0%" stop-color="${g[0]}"/>
      <stop offset="45%" stop-color="${g[1]}"/>
      <stop offset="100%" stop-color="${g[2]}"/>
    </radialGradient>
    <radialGradient id="shadow${id}" cx="50%" cy="50%" r="50%">
      <stop offset="0%" stop-color="#000" stop-opacity=".55"/>
      <stop offset="100%" stop-color="#000" stop-opacity="0"/>
    </radialGradient>
    <filter id="glow${id}" x="-50%" y="-50%" width="200%" height="200%">
      <feGaussianBlur stdDeviation="3" result="b"/>
      <feMerge><feMergeNode in="b"/><feMergeNode in="SourceGraphic"/></feMerge>
    </filter>
  </defs>`;
};

const sparkle = (x, y, s, o = 0.9) =>
  `<path d="M${x} ${y - s} Q${x} ${y} ${x + s} ${y} Q${x} ${y} ${x} ${y + s} Q${x} ${y} ${x - s} ${y} Q${x} ${y} ${x} ${y - s}Z" fill="#fff" opacity="${o}"/>`;

// A faceted brilliant-cut stone seen from the front.
function stone(id, gem, cx, cy, r) {
  if (gem === "pearl") {
    return `<circle cx="${cx}" cy="${cy}" r="${r}" fill="url(#gem${id})"/>
      <ellipse cx="${cx - r * 0.3}" cy="${cy - r * 0.35}" rx="${r * 0.3}" ry="${r * 0.2}" fill="#fff" opacity=".85"/>`;
  }
  if (gem === "onyx") {
    return `<ellipse cx="${cx}" cy="${cy}" rx="${r * 1.1}" ry="${r * 0.85}" fill="url(#gem${id})"/>
      <ellipse cx="${cx - r * 0.35}" cy="${cy - r * 0.35}" rx="${r * 0.4}" ry="${r * 0.15}" fill="#fff" opacity=".35"/>`;
  }
  const crownY = cy - r * 0.35;
  const girdleY = cy;
  const tip = cy + r * 1.05;
  const t = r * 0.55;
  return `<g filter="url(#glow${id})">
    <polygon points="${cx - r},${girdleY} ${cx - t},${crownY - r * 0.3} ${cx + t},${crownY - r * 0.3} ${cx + r},${girdleY} ${cx},${tip}" fill="url(#gem${id})"/>
    <polygon points="${cx - t},${crownY - r * 0.3} ${cx + t},${crownY - r * 0.3} ${cx + t * 0.7},${girdleY} ${cx - t * 0.7},${girdleY}" fill="#fff" opacity=".28"/>
    <polygon points="${cx - r},${girdleY} ${cx - t},${crownY - r * 0.3} ${cx - t * 0.7},${girdleY}" fill="#fff" opacity=".45"/>
    <polygon points="${cx + r},${girdleY} ${cx + t},${crownY - r * 0.3} ${cx + t * 0.7},${girdleY}" fill="#000" opacity=".12"/>
    <polygon points="${cx - r},${girdleY} ${cx - t * 0.7},${girdleY} ${cx},${tip}" fill="#000" opacity=".18"/>
    <polygon points="${cx + t * 0.7},${girdleY} ${cx + r},${girdleY} ${cx},${tip}" fill="#fff" opacity=".22"/>
    <polygon points="${cx - t * 0.7},${girdleY} ${cx + t * 0.7},${girdleY} ${cx},${tip}" fill="#fff" opacity=".1"/>
  </g>`;
}

const shapes = {
  ring: (id, gem) => `
    <ellipse cx="400" cy="640" rx="230" ry="36" fill="url(#shadow${id})"/>
    <ellipse cx="400" cy="480" rx="200" ry="92" fill="none" stroke="url(#metalV${id})" stroke-width="40"/>
    <ellipse cx="400" cy="474" rx="200" ry="92" fill="none" stroke="url(#metal${id})" stroke-width="26"/>
    <ellipse cx="400" cy="466" rx="186" ry="82" fill="none" stroke="#fff" stroke-opacity=".35" stroke-width="3"/>
    <path d="M360 400 L372 340 M440 400 L428 340 M386 404 L392 330 M414 404 L408 330" stroke="url(#metal${id})" stroke-width="10" stroke-linecap="round"/>
    ${gem === "none" ? "" : stone(id, gem, 400, 320, 70)}`,
  necklace: (id, gem, metal) => {
    const beads = [];
    for (let i = 0; i <= 26; i++) {
      const a = Math.PI * (0.08 + (0.84 * i) / 26);
      beads.push([400 - Math.cos(a) * 280, 150 + Math.sin(a) * 380]);
    }
    const chain = beads.map(([x, y]) => `<circle cx="${x.toFixed(1)}" cy="${y.toFixed(1)}" r="9" fill="url(#metal${id})"/>`).join("");
    const drops =
      gem === "none"
        ? ""
        : beads
            .filter((_, i) => i % 4 === 2)
            .map(([x, y]) => stone(id, gem, x, y + 22, 18))
            .join("");
    return `
      <ellipse cx="400" cy="700" rx="200" ry="30" fill="url(#shadow${id})"/>
      <polyline points="${beads.map(([x, y]) => `${x.toFixed(1)},${y.toFixed(1)}`).join(" ")}" fill="none" stroke="${METALS[metal][2]}" stroke-width="4" opacity=".8"/>
      ${chain}${drops}
      ${gem === "none" ? `<circle cx="400" cy="535" r="26" fill="url(#metal${id})"/>` : stone(id, gem, 400, 560, 48)}`;
  },
  earrings: (id, gem, metal) =>
    [270, 530]
      .map(
        (x) => `
      <ellipse cx="${x}" cy="690" rx="90" ry="18" fill="url(#shadow${id})"/>
      <path d="M${x} 150 q-40 0 -40 40 q0 30 40 40" fill="none" stroke="url(#metal${id})" stroke-width="10" stroke-linecap="round"/>
      <path d="M${x - 70} 300 Q${x} 220 ${x + 70} 300 L${x + 50} 360 Q${x} 330 ${x - 50} 360Z" fill="url(#metal${id})"/>
      ${[-40, -13, 13, 40].map((d) => `<circle cx="${x + d}" cy="${372 + Math.abs(d) * -0.3}" r="7" fill="url(#metal${id})"/>`).join("")}
      <line x1="${x}" y1="226" x2="${x}" y2="268" stroke="${METALS[metal][2]}" stroke-width="7" stroke-linecap="round"/>
      <line x1="${x}" y1="350" x2="${x}" y2="392" stroke="${METALS[metal][2]}" stroke-width="5" stroke-linecap="round"/>
      ${stone(id, gem === "none" ? "diamond" : gem, x, gem === "pearl" ? 440 : 450, 50)}`
      )
      .join(""),
  bracelet: (id, gem) => {
    const pts = [];
    for (let i = 0; i < 34; i++) {
      const a = (Math.PI * 2 * i) / 34;
      pts.push([400 + Math.cos(a) * 250, 430 + Math.sin(a) * 120, Math.sin(a)]);
    }
    pts.sort((a, b) => a[2] - b[2]);
    return `
      <ellipse cx="400" cy="620" rx="280" ry="40" fill="url(#shadow${id})"/>
      <ellipse cx="400" cy="430" rx="250" ry="120" fill="none" stroke="url(#metalV${id})" stroke-width="18"/>
      ${pts
        .map(([x, y, d]) => {
          const s = 16 + d * 5;
          return gem === "none"
            ? `<ellipse cx="${x.toFixed(1)}" cy="${y.toFixed(1)}" rx="${s + 6}" ry="${s * 0.7}" fill="url(#metal${id})" stroke="#fff" stroke-opacity=".3"/>`
            : `<rect x="${(x - s).toFixed(1)}" y="${(y - s).toFixed(1)}" width="${s * 2}" height="${s * 2}" rx="5" fill="url(#metal${id})"/><circle cx="${x.toFixed(1)}" cy="${y.toFixed(1)}" r="${(s * 0.72).toFixed(1)}" fill="url(#gem${id})"/><circle cx="${(x - s * 0.25).toFixed(1)}" cy="${(y - s * 0.25).toFixed(1)}" r="${(s * 0.2).toFixed(1)}" fill="#fff" opacity=".8"/>`;
        })
        .join("")}`;
  },
  pendant: (id, gem) => `
    <ellipse cx="400" cy="700" rx="150" ry="24" fill="url(#shadow${id})"/>
    <path d="M140 80 Q400 520 400 330 Q400 520 660 80" fill="none" stroke="url(#metal${id})" stroke-width="5" stroke-dasharray="3 9" stroke-linecap="round"/>
    <path d="M140 80 Q270 300 390 330 M660 80 Q530 300 410 330" fill="none" stroke="url(#metal${id})" stroke-width="5" stroke-dasharray="3 9" stroke-linecap="round"/>
    <circle cx="400" cy="345" r="16" fill="none" stroke="url(#metal${id})" stroke-width="7"/>
    <path d="M400 370 C320 450 310 540 400 600 C490 540 480 450 400 370Z" fill="url(#metal${id})"/>
    <path d="M400 392 C336 460 330 532 400 580 C470 532 464 460 400 392Z" fill="url(#gem${id})"/>
    <path d="M400 392 C360 440 350 490 372 520 C368 470 380 430 400 392Z" fill="#fff" opacity=".45"/>
    ${Array.from({ length: 12 }, (_, i) => {
      const a = (Math.PI * 2 * i) / 12;
      return `<circle cx="${(400 + Math.cos(a) * 88).toFixed(1)}" cy="${(492 + Math.sin(a) * 112).toFixed(1)}" r="6" fill="#fff" opacity=".85"/>`;
    }).join("")}`,
  bangle: (id, gem) => `
    <ellipse cx="400" cy="660" rx="280" ry="40" fill="url(#shadow${id})"/>
    ${[0, 70]
      .map(
        (o) => `
      <ellipse cx="400" cy="${380 + o}" rx="240" ry="110" fill="none" stroke="url(#metalV${id})" stroke-width="46"/>
      <ellipse cx="400" cy="${374 + o}" rx="240" ry="110" fill="none" stroke="url(#metal${id})" stroke-width="30"/>
      <ellipse cx="400" cy="${374 + o}" rx="240" ry="110" fill="none" stroke="#fff" stroke-opacity=".35" stroke-width="2" stroke-dasharray="6 14"/>
      ${
        gem === "none"
          ? ""
          : Array.from({ length: 9 }, (_, i) => {
              const a = Math.PI * (0.15 + (0.7 * i) / 8);
              return `<circle cx="${(400 - Math.cos(a) * 240).toFixed(1)}" cy="${(374 + o + Math.sin(a) * 110).toFixed(1)}" r="9" fill="url(#gem${id})"/>`;
            }).join("")
      }`
      )
      .join("")}`,
};


/* ---------- Universal product illustrations ---------- */
const BG = {
  fashion: ["#f7e4dc", "#d9a898"],
  electronics: ["#27407a", "#0b1220"],
  beauty: ["#fbe3ea", "#d98ea6"],
  grocery: ["#f1f5e4", "#a8c291"],
  home: ["#f6e8d6", "#c99d74"],
};

const lg = (id, stops, x2 = 1, y2 = 1) =>
  `<linearGradient id="${id}" x1="0" y1="0" x2="${x2}" y2="${y2}">${stops
    .map((c, i) => `<stop offset="${Math.round((i / (stops.length - 1)) * 100)}%" stop-color="${c}"/>`)
    .join("")}</linearGradient>`;
const rg = (id, stops, cx = "40%", cy = "35%", r = "75%") =>
  `<radialGradient id="${id}" cx="${cx}" cy="${cy}" r="${r}">${stops
    .map((c, i) => `<stop offset="${Math.round((i / (stops.length - 1)) * 100)}%" stop-color="${c}"/>`)
    .join("")}</radialGradient>`;
const shadow = (cx, cy, rx, ry, o = 0.35) =>
  `<ellipse cx="${cx}" cy="${cy}" rx="${rx}" ry="${ry}" fill="#000" opacity="${o}" filter="url(#soft)"/>`;

const ITEMS = {
  dress: (c = ["#e9dccb", "#c8b39a", "#9c8468"]) => ({
    defs: lg("fab", c, 1, 1),
    body: `${shadow(400, 720, 180, 20, 0.22)}
      <path d="M400 90 q-18 0 -18 18 q0 14 18 22" fill="none" stroke="#6b5847" stroke-width="7" stroke-linecap="round"/>
      <path d="M250 175 L400 130 L550 175" fill="none" stroke="#6b5847" stroke-width="8" stroke-linecap="round" stroke-linejoin="round"/>
      <path d="M330 170 Q400 250 470 170 L520 190 L505 330 Q480 360 470 380 L585 690 Q400 730 215 690 L330 380 Q320 360 295 330 L280 190Z" fill="url(#fab)"/>
      <path d="M330 170 Q400 250 470 170 L460 200 Q400 300 340 200Z" fill="#000" opacity=".08"/>
      <rect x="300" y="360" width="200" height="26" rx="10" fill="#8b6b4f"/>
      <path d="M420 386 q30 60 10 130 M455 386 q40 70 30 150" stroke="#8b6b4f" stroke-width="10" fill="none" stroke-linecap="round"/>
      <path d="M340 400 L290 680 M400 400 L400 700 M460 400 L520 680" stroke="#000" stroke-opacity=".07" stroke-width="6"/>`,
  }),
  sneaker: () => ({
    defs: lg("leather", ["#ffffff", "#f1ede7", "#d9d2c7"], 0, 1) + lg("accent", ["#c8583f", "#9c3a27"]),
    body: `${shadow(400, 600, 290, 26, 0.3)}
      <path d="M120 560 L120 520 Q125 470 170 455 L300 430 Q360 330 430 320 L480 330 Q520 400 600 430 Q690 450 690 520 L690 560Z" fill="url(#leather)" stroke="#cfc6b8" stroke-width="3"/>
      <rect x="105" y="548" width="600" height="46" rx="20" fill="#fbfaf7" stroke="#d6cfc4" stroke-width="3"/>
      <path d="M105 578 H705" stroke="#c9c1b3" stroke-width="3"/>
      <path d="M250 470 Q400 520 600 452" fill="none" stroke="url(#accent)" stroke-width="22" stroke-linecap="round"/>
      ${[0, 1, 2, 3, 4].map((i) => `<path d="M${345 + i * 26} ${370 + i * 12} l40 -8" stroke="#8a8278" stroke-width="7" stroke-linecap="round"/>`).join("")}
      <path d="M430 320 Q455 360 470 332" fill="none" stroke="#bdb4a6" stroke-width="5"/>
      <circle cx="630" cy="500" r="7" fill="#c8583f"/>`,
  }),
  tote: () => ({
    defs: lg("bag", ["#b5835a", "#8a5a36", "#6e4526"]) + lg("bagSide", ["#7a4c2c", "#5a3519"], 0, 1),
    body: `${shadow(400, 690, 240, 24, 0.3)}
      <path d="M300 300 Q300 160 400 160 Q500 160 500 300" fill="none" stroke="#5a3519" stroke-width="20"/>
      <path d="M335 300 Q335 205 400 205 Q465 205 465 300" fill="none" stroke="#7a4c2c" stroke-width="14"/>
      <path d="M200 300 H600 L640 670 H160Z" fill="url(#bag)"/>
      <path d="M200 300 H600 L602 330 H198Z" fill="#000" opacity=".15"/>
      <path d="M214 318 H586 L622 652 H178Z" fill="none" stroke="#e8c9a6" stroke-width="3" stroke-dasharray="10 9" opacity=".7"/>
      <rect x="360" y="420" width="80" height="54" rx="8" fill="#d9b25f" stroke="#a7802f" stroke-width="4"/>
      <circle cx="400" cy="447" r="10" fill="#8a6a2c"/>`,
  }),
  tee: (c = ["#f5f1ea", "#e4ddd1", "#cfc5b4"]) => ({
    defs: lg("cotton", c, 0, 1),
    body: `${shadow(400, 700, 210, 22, 0.22)}
      <path d="M330 150 Q400 200 470 150 L610 205 L670 330 L585 370 L560 320 L560 670 L240 670 L240 320 L215 370 L130 330 L190 205Z" fill="url(#cotton)" stroke="#bfb4a2" stroke-width="3"/>
      <path d="M330 150 Q400 215 470 150" fill="none" stroke="#b6aa96" stroke-width="10"/>
      <path d="M240 320 L240 670 M560 320 L560 670" stroke="#000" stroke-opacity=".06" stroke-width="10"/>
      <circle cx="470" cy="300" r="22" fill="#1f4a41" opacity=".85"/>
      <path d="M461 300 l7 7 l12 -14" stroke="#f1dca4" stroke-width="5" fill="none" stroke-linecap="round"/>`,
  }),
  headphones: () => ({
    defs: lg("shell", ["#3a4660", "#1a2233", "#0d131f"]) + lg("cush", ["#2a2f3a", "#111"], 0, 1) + lg("band", ["#56627c", "#1a2233"], 0, 1),
    body: `${shadow(400, 690, 250, 24, 0.45)}
      <path d="M200 470 Q200 150 400 150 Q600 150 600 470" fill="none" stroke="url(#band)" stroke-width="42" stroke-linecap="round"/>
      <path d="M215 440 Q215 175 400 175 Q585 175 585 440" fill="none" stroke="#7f8aa3" stroke-width="4" opacity=".6"/>
      <rect x="140" y="400" width="140" height="230" rx="62" fill="url(#shell)"/>
      <rect x="520" y="400" width="140" height="230" rx="62" fill="url(#shell)"/>
      <rect x="250" y="420" width="46" height="190" rx="23" fill="url(#cush)"/>
      <rect x="504" y="420" width="46" height="190" rx="23" fill="url(#cush)"/>
      <circle cx="210" cy="515" r="36" fill="none" stroke="#60a5fa" stroke-width="4" opacity=".7"/>
      <circle cx="590" cy="515" r="36" fill="none" stroke="#60a5fa" stroke-width="4" opacity=".7"/>
      <path d="M165 440 Q175 410 205 405" stroke="#fff" stroke-opacity=".35" stroke-width="8" fill="none" stroke-linecap="round"/>`,
  }),
  watch: () => ({
    defs: lg("case", ["#c9ced6", "#6b7280", "#e5e7eb"]) + rg("screen", ["#1e3a8a", "#0b1220"], "50%", "40%") + lg("strap", ["#f97316", "#c2410c"], 0, 1),
    body: `${shadow(400, 700, 150, 20, 0.45)}
      <rect x="320" y="80" width="160" height="200" rx="40" fill="url(#strap)"/>
      <rect x="320" y="520" width="160" height="200" rx="40" fill="url(#strap)"/>
      ${[0, 1, 2, 3, 4].map((i) => `<circle cx="400" cy="${580 + i * 26}" r="7" fill="#7c2d12" opacity=".6"/>`).join("")}
      <rect x="250" y="230" width="300" height="340" rx="80" fill="url(#case)"/>
      <rect x="272" y="252" width="256" height="296" rx="64" fill="url(#screen)"/>
      <rect x="552" y="330" width="18" height="70" rx="8" fill="#9ca3af"/>
      <circle cx="400" cy="400" r="96" fill="none" stroke="#f97316" stroke-width="12" stroke-dasharray="420 200" stroke-linecap="round" transform="rotate(-90 400 400)"/>
      <circle cx="400" cy="400" r="74" fill="none" stroke="#22d3ee" stroke-width="12" stroke-dasharray="300 200" stroke-linecap="round" transform="rotate(-90 400 400)"/>
      <text x="400" y="414" text-anchor="middle" font-family="Arial, sans-serif" font-weight="700" font-size="44" fill="#fff">10:09</text>
      <path d="M290 280 Q300 262 330 258" stroke="#fff" stroke-opacity=".35" stroke-width="8" fill="none" stroke-linecap="round"/>`,
  }),
  speaker: () => ({
    defs: lg("cyl", ["#111827", "#374151", "#111827"], 1, 0),
    body: `${shadow(400, 700, 200, 24, 0.45)}
      <rect x="250" y="180" width="300" height="480" rx="40" fill="url(#cyl)"/>
      <ellipse cx="400" cy="185" rx="150" ry="36" fill="#4b5563"/>
      <ellipse cx="400" cy="185" rx="120" ry="26" fill="#1f2937"/>
      <circle cx="400" cy="185" r="14" fill="#60a5fa" opacity=".9"/>
      ${Array.from({ length: 13 }, (_, r) => Array.from({ length: 9 }, (_, c) => `<circle cx="${278 + c * 31}" cy="${250 + r * 30}" r="4.5" fill="#000" opacity=".45"/>`).join("")).join("")}
      <rect x="250" y="630" width="300" height="12" fill="#2563eb" opacity=".9"/>
      <rect x="265" y="195" width="18" height="440" rx="9" fill="#fff" opacity=".08"/>`,
  }),
  earbuds: () => ({
    defs: lg("pod", ["#ffffff", "#e5e7eb", "#cbd5e1"], 0, 1),
    body: `${shadow(400, 660, 230, 22, 0.4)}
      <rect x="230" y="380" width="340" height="250" rx="110" fill="url(#pod)"/>
      <path d="M230 470 H570" stroke="#94a3b8" stroke-width="4"/>
      <circle cx="400" cy="540" r="8" fill="#22c55e"/>
      <g transform="rotate(-18 320 280)"><ellipse cx="320" cy="270" rx="58" ry="66" fill="url(#pod)"/><rect x="302" y="300" width="34" height="120" rx="17" fill="url(#pod)"/><ellipse cx="305" cy="255" rx="22" ry="18" fill="#334155"/></g>
      <g transform="rotate(18 480 280)"><ellipse cx="480" cy="270" rx="58" ry="66" fill="url(#pod)"/><rect x="464" y="300" width="34" height="120" rx="17" fill="url(#pod)"/><ellipse cx="495" cy="255" rx="22" ry="18" fill="#334155"/></g>`,
  }),
  serum: () => ({
    defs: lg("amber", ["#f3b25f", "#c77a22", "#8a4c10"]) + lg("bulb", ["#3b2a2a", "#111"], 0, 1),
    body: `${shadow(400, 690, 150, 20, 0.28)}
      <path d="M365 120 Q365 90 400 90 Q435 90 435 120 L435 210 H365Z" fill="url(#bulb)"/>
      <rect x="345" y="205" width="110" height="70" rx="10" fill="#e6c068"/>
      <rect x="290" y="270" width="220" height="400" rx="34" fill="url(#amber)" opacity=".95"/>
      <rect x="315" y="380" width="170" height="170" rx="10" fill="#fff8f0"/>
      <text x="400" y="445" text-anchor="middle" font-family="Georgia, serif" font-size="30" fill="#4a1f33">GLOW</text>
      <text x="400" y="485" text-anchor="middle" font-family="Arial, sans-serif" font-size="18" letter-spacing="3" fill="#7a3a58">VITAMIN C</text>
      <text x="400" y="520" text-anchor="middle" font-family="Arial, sans-serif" font-size="14" fill="#7a3a58">15% · 30 ml</text>
      <rect x="305" y="285" width="24" height="360" rx="12" fill="#fff" opacity=".25"/>`,
  }),
  lipstick: (shade = ["#e0204a", "#9a0f2f"]) => ({
    defs: lg("gold", ["#7a5a1e", "#f6e3a1", "#b8892f", "#fff3c4", "#8a6a2c"], 1, 0) + lg("shade", shade),
    body: `${shadow(400, 700, 130, 18, 0.3)}
      <path d="M340 330 L340 200 Q340 140 400 120 L460 160 L460 330Z" fill="url(#shade)"/>
      <path d="M350 320 L350 205 Q352 160 395 138" stroke="#fff" stroke-opacity=".35" stroke-width="10" fill="none"/>
      <rect x="320" y="320" width="160" height="110" rx="8" fill="url(#gold)"/>
      <rect x="300" y="420" width="200" height="260" rx="14" fill="#2b1d22"/>
      <rect x="300" y="420" width="200" height="30" fill="url(#gold)"/>
      <rect x="315" y="460" width="18" height="200" rx="9" fill="#fff" opacity=".12"/>`,
  }),
  cream: () => ({
    defs: lg("jar", ["#fff6f3", "#f3d6de", "#e2b3c1"], 0, 1) + lg("lid", ["#e6c068", "#a67f36"], 1, 0),
    body: `${shadow(400, 640, 230, 26, 0.28)}
      <rect x="200" y="300" width="400" height="80" rx="20" fill="url(#lid)"/>
      <rect x="215" y="370" width="370" height="250" rx="60" fill="url(#jar)"/>
      <text x="400" y="495" text-anchor="middle" font-family="Georgia, serif" font-style="italic" font-size="44" fill="#4a1f33">Rosé</text>
      <text x="400" y="535" text-anchor="middle" font-family="Arial, sans-serif" font-size="16" letter-spacing="4" fill="#7a3a58">HYDRATION CREAM</text>
      <rect x="235" y="390" width="28" height="200" rx="14" fill="#fff" opacity=".45"/>
      <path d="M470 240 q20 -40 50 -30 q-10 40 -50 30z M520 250 q40 -10 50 20 q-40 10 -50 -20z" fill="#e8a0b4"/>`,
  }),
  perfume: () => ({
    defs: lg("glass", ["#fbe3ea", "#f0b7c7", "#c27a92"]) + lg("capg", ["#1c1406", "#4a3a1e"], 1, 0),
    body: `${shadow(400, 690, 190, 22, 0.3)}
      <rect x="350" y="150" width="100" height="110" rx="10" fill="url(#capg)"/>
      <rect x="370" y="250" width="60" height="40" fill="#c9a45c"/>
      <path d="M250 300 H550 L580 360 V630 Q580 670 540 670 H260 Q220 670 220 630 V360Z" fill="url(#glass)" opacity=".92"/>
      <path d="M240 420 H560 V630 Q560 650 540 650 H260 Q240 650 240 630Z" fill="#c27a92" opacity=".45"/>
      <path d="M250 300 L220 360 M550 300 L580 360" stroke="#fff" stroke-width="4" opacity=".6"/>
      <rect x="320" y="460" width="160" height="90" rx="6" fill="#fff" opacity=".85"/>
      <text x="400" y="510" text-anchor="middle" font-family="Georgia, serif" font-size="30" fill="#4a1f33">OUD</text>
      <text x="400" y="535" text-anchor="middle" font-family="Arial, sans-serif" font-size="12" letter-spacing="3" fill="#7a3a58">EAU DE PARFUM</text>
      <rect x="240" y="370" width="22" height="260" rx="11" fill="#fff" opacity=".4"/>`,
  }),
  honey: () => ({
    defs: lg("hon", ["#ffcf5c", "#e8960c", "#a85d00"], 0, 1) + lg("cloth", ["#e8dcc8", "#b9a47f"]),
    body: `${shadow(400, 690, 200, 22, 0.28)}
      <path d="M250 250 Q400 190 550 250 L560 310 H240Z" fill="url(#cloth)"/>
      <path d="M240 305 H560" stroke="#8a6a2c" stroke-width="8"/>
      <rect x="250" y="310" width="300" height="370" rx="50" fill="url(#hon)" opacity=".95"/>
      <rect x="290" y="400" width="220" height="170" rx="12" fill="#fff9ec"/>
      ${[0, 1, 2].map((i) => `<path d="M${345 + i * 40} 440 l17 -10 l17 10 v20 l-17 10 l-17 -10z" fill="none" stroke="#c77a22" stroke-width="3"/>`).join("")}
      <text x="400" y="515" text-anchor="middle" font-family="Georgia, serif" font-size="28" fill="#5a3a0a">Wild Forest</text>
      <text x="400" y="545" text-anchor="middle" font-family="Arial, sans-serif" font-size="15" letter-spacing="4" fill="#8a5a10">RAW HONEY</text>
      <rect x="268" y="330" width="24" height="320" rx="12" fill="#fff" opacity=".3"/>`,
  }),
  tea: () => ({
    defs: lg("tin", ["#2a6238", "#1f4d2b", "#12301a"], 1, 0) + lg("tlid", ["#e6c068", "#a67f36"], 1, 0),
    body: `${shadow(400, 690, 190, 22, 0.3)}
      <rect x="260" y="220" width="280" height="70" rx="12" fill="url(#tlid)"/>
      <rect x="270" y="280" width="260" height="400" rx="16" fill="url(#tin)"/>
      <rect x="295" y="370" width="210" height="200" rx="100" fill="#fbfaf4"/>
      <path d="M400 395 q-60 50 0 120 q60 -70 0 -120z" fill="#3b7d49"/>
      <path d="M400 400 v110" stroke="#fbfaf4" stroke-width="4"/>
      <text x="400" y="555" text-anchor="middle" font-family="Arial, sans-serif" font-size="15" letter-spacing="3" fill="#1f4d2b">GREEN TEA</text>
      <path d="M580 600 q40 -50 90 -20 q-40 50 -90 20z M130 560 q50 -30 90 10 q-50 30 -90 -10z" fill="#3b7d49"/>`,
  }),
  oil: () => ({
    defs: lg("bottle", ["#4d6b1f", "#2e4412", "#1b2a08"], 1, 0) + lg("oilL", ["#e7d36a", "#b8a22a"], 0, 1),
    body: `${shadow(400, 700, 150, 20, 0.3)}
      <rect x="370" y="100" width="60" height="60" rx="8" fill="#a07850"/>
      <path d="M375 160 H425 V240 Q470 280 470 330 V660 Q470 690 440 690 H360 Q330 690 330 660 V330 Q330 280 375 240Z" fill="url(#bottle)"/>
      <rect x="345" y="420" width="110" height="170" rx="6" fill="#f7f1dc"/>
      <text x="400" y="480" text-anchor="middle" font-family="Georgia, serif" font-size="22" fill="#2e4412">Extra</text>
      <text x="400" y="505" text-anchor="middle" font-family="Georgia, serif" font-size="22" fill="#2e4412">Virgin</text>
      <text x="400" y="540" text-anchor="middle" font-family="Arial, sans-serif" font-size="12" letter-spacing="2" fill="#4d6b1f">OLIVE OIL</text>
      <rect x="342" y="340" width="16" height="300" rx="8" fill="#fff" opacity=".2"/>
      <path d="M520 360 Q600 300 660 330" stroke="#4d6b1f" stroke-width="6" fill="none"/>
      ${[[560, 330], [600, 318], [635, 330]].map(([x, y]) => `<ellipse cx="${x}" cy="${y}" rx="22" ry="9" fill="#6b8e23" transform="rotate(-25 ${x} ${y})"/>`).join("")}
      <circle cx="585" cy="355" r="16" fill="#3d4a12"/><circle cx="625" cy="350" r="14" fill="#556b1a"/>`,
  }),
  trailmix: () => ({
    defs: lg("jarg", ["#ffffff", "#e8efe0"], 0, 1),
    body: `${shadow(400, 690, 200, 22, 0.28)}
      <rect x="265" y="210" width="270" height="60" rx="10" fill="#8a5a36"/>
      <rect x="250" y="265" width="300" height="410" rx="60" fill="url(#jarg)" opacity=".55" stroke="#c8d5b8" stroke-width="4"/>
      ${Array.from({ length: 70 }, (_, i) => {
        const x = 280 + ((i * 53) % 240);
        const y = 340 + ((i * 37) % 310);
        const col = ["#c77a22", "#7a4c2c", "#e8dcc8", "#b3261e", "#6b8e23", "#f2a93b"][i % 6];
        return `<ellipse cx="${x}" cy="${y}" rx="${13 + (i % 3) * 3}" ry="${9 + (i % 2) * 3}" fill="${col}" transform="rotate(${(i * 29) % 180} ${x} ${y})"/>`;
      }).join("")}
      <rect x="310" y="420" width="180" height="90" rx="8" fill="#fbfaf4"/>
      <text x="400" y="475" text-anchor="middle" font-family="Arial, sans-serif" font-size="18" letter-spacing="3" fill="#1f4d2b">TRAIL MIX</text>
      <rect x="268" y="290" width="22" height="360" rx="11" fill="#fff" opacity=".45"/>`,
  }),
  lamp: () => ({
    defs: lg("terra", ["#d98a5f", "#b5623a", "#8a4424"]) + lg("shade", ["#fbf3e4", "#e8d6b8"], 0, 1) + rg("glowL", ["rgba(255,220,150,.75)", "rgba(255,220,150,0)"], "50%", "50%", "50%"),
    body: `<circle cx="400" cy="330" r="280" fill="url(#glowL)"/>${shadow(400, 690, 170, 22, 0.3)}
      <path d="M270 150 H530 L600 360 H200Z" fill="url(#shade)"/>
      ${[0, 1, 2, 3, 4, 5, 6, 7].map((i) => `<path d="M${285 + i * 33} 152 L${215 + i * 53} 358" stroke="#d8c3a0" stroke-width="3"/>`).join("")}
      <rect x="392" y="360" width="16" height="50" fill="#6b5847"/>
      <path d="M400 410 C300 420 290 520 330 580 C350 620 330 650 320 680 H480 C470 650 450 620 470 580 C510 520 500 420 400 410Z" fill="url(#terra)"/>
      <path d="M340 470 C330 520 345 570 360 600" stroke="#fff" stroke-opacity=".3" stroke-width="12" fill="none" stroke-linecap="round"/>`,
  }),
  vase: () => ({
    defs: lg("glaze", ["#9fc2c0", "#4f8a87", "#2f5f5c"]),
    body: `${shadow(400, 690, 160, 20, 0.3)}
      <path d="M400 330 Q380 200 300 120 M400 330 Q420 190 520 100 M400 330 Q440 240 590 200" stroke="#6b5847" stroke-width="6" fill="none"/>
      ${[[300, 120], [520, 100], [590, 200], [340, 170], [470, 160]].map(([x, y]) => `<circle cx="${x}" cy="${y}" r="16" fill="#f1dca4"/><circle cx="${x}" cy="${y}" r="7" fill="#c9a45c"/>`).join("")}
      <path d="M360 330 H440 Q440 380 480 420 Q560 490 530 600 Q510 680 400 680 Q290 680 270 600 Q240 490 320 420 Q360 380 360 330Z" fill="url(#glaze)"/>
      <path d="M270 560 Q400 600 530 560" stroke="#f3ece0" stroke-width="10" fill="none" opacity=".7"/>
      <path d="M305 470 Q290 530 305 600" stroke="#fff" stroke-opacity=".35" stroke-width="12" fill="none" stroke-linecap="round"/>`,
  }),
  cushion: () => ({
    defs: lg("weave", ["#e8dcc8", "#d6c3a3"], 0, 1),
    body: `${shadow(400, 680, 250, 24, 0.3)}
      <path d="M170 200 Q400 170 630 200 Q660 400 630 620 Q400 650 170 620 Q140 400 170 200Z" fill="url(#weave)"/>
      ${[0, 1, 2, 3, 4, 5].map((i) => `<path d="M${165 + i * 0} ${250 + i * 70} Q400 ${230 + i * 70} 640 ${250 + i * 70}" stroke="${i % 2 ? "#1f4a41" : "#c9a45c"}" stroke-width="${i % 2 ? 10 : 6}" fill="none" opacity=".85"/>`).join("")}
      ${[[170, 200], [630, 200], [170, 620], [630, 620]].map(([x, y]) => `<path d="M${x} ${y} l${x < 400 ? -30 : 30} ${y < 400 ? -30 : 30}" stroke="#c9a45c" stroke-width="8" stroke-linecap="round"/>`).join("")}`,
  }),
  candle: () => ({
    defs: lg("tumbler", ["#3b3b3b", "#161616"], 1, 0) + rg("flame", ["#fff6c8", "#ffc14d", "rgba(255,120,0,0)"], "50%", "60%", "50%") + rg("halo", ["rgba(255,210,120,.6)", "rgba(255,210,120,0)"], "50%", "50%", "50%"),
    body: `<circle cx="400" cy="250" r="220" fill="url(#halo)"/>${shadow(400, 680, 170, 20, 0.35)}
      <ellipse cx="400" cy="230" rx="30" ry="60" fill="url(#flame)"/>
      <path d="M400 190 q-18 35 0 70 q18 -35 0 -70z" fill="#fff3b0"/>
      <rect x="397" y="265" width="6" height="40" fill="#222"/>
      <rect x="260" y="300" width="280" height="370" rx="26" fill="url(#tumbler)"/>
      <ellipse cx="400" cy="305" rx="140" ry="18" fill="#f3ece0"/>
      <rect x="310" y="430" width="180" height="110" rx="4" fill="#c9a45c"/>
      <text x="400" y="480" text-anchor="middle" font-family="Georgia, serif" font-size="24" fill="#1c1406">Sandalwood</text>
      <text x="400" y="510" text-anchor="middle" font-family="Arial, sans-serif" font-size="12" letter-spacing="3" fill="#1c1406">SOY WAX CANDLE</text>
      <rect x="276" y="320" width="18" height="320" rx="9" fill="#fff" opacity=".12"/>`,
  }),
};

function productSvg(bg, item, size = 800) {
  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 800" width="${size}" height="${size}">
  <defs>
    ${rg("bgx", [bg[0], bg[1]], "50%", "38%", "80%")}
    ${rg("spotx", ["rgba(255,255,255,.35)", "rgba(255,255,255,0)"], "50%", "30%", "45%")}
    <filter id="soft" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="10"/></filter>
    ${item.defs}
  </defs>
  <rect width="800" height="800" fill="url(#bgx)"/>
  <rect width="800" height="800" fill="url(#spotx)"/>
  ${item.body}
  ${sparkle(150, 140, 12, 0.55)}${sparkle(660, 180, 8, 0.45)}
</svg>`;
}

function jewelSvg(type, metal, gem, id = "j", size = 800) {
  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 800" width="${size}" height="${size}">
  ${defs(metal, gem, id)}
  <rect width="800" height="800" fill="url(#bg${id})"/>
  <rect width="800" height="800" fill="url(#spot${id})"/>
  ${shapes[type](id, gem, metal)}
  ${[sparkle(160, 150, 18), sparkle(640, 200, 12, 0.7), sparkle(600, 560, 10, 0.6), sparkle(200, 520, 8, 0.5), sparkle(470, 120, 7, 0.6)].join("")}
</svg>`;
}

/* ---------- Demo catalogue artwork ---------- */
export const PRODUCT_ART = {
  // Jewellery
  "eternal-solitaire-diamond-ring": () => jewelSvg("ring", "white", "diamond"),
  "rose-petal-halo-ring": () => jewelSvg("ring", "rose", "pink-sapphire"),
  "royal-emerald-kundan-necklace": () => jewelSvg("necklace", "gold", "emerald"),
  "pearl-drop-jhumka-earrings": () => jewelSvg("earrings", "gold", "pearl"),
  "sapphire-teardrop-pendant": () => jewelSvg("pendant", "white", "sapphire"),
  "celestial-diamond-tennis-bracelet": () => jewelSvg("bracelet", "white", "diamond"),
  // Fashion
  "linen-wrap-dress": () => productSvg(BG.fashion, ITEMS.dress()),
  "classic-leather-sneakers": () => productSvg(BG.fashion, ITEMS.sneaker()),
  "structured-leather-tote": () => productSvg(BG.fashion, ITEMS.tote()),
  "organic-cotton-tee": () => productSvg(BG.fashion, ITEMS.tee()),
  // Electronics
  "aura-anc-headphones": () => productSvg(BG.electronics, ITEMS.headphones()),
  "pulse-smartwatch": () => productSvg(BG.electronics, ITEMS.watch()),
  "nova-bluetooth-speaker": () => productSvg(BG.electronics, ITEMS.speaker()),
  "echo-wireless-earbuds": () => productSvg(BG.electronics, ITEMS.earbuds()),
  // Beauty
  "vitamin-c-glow-serum": () => productSvg(BG.beauty, ITEMS.serum()),
  "velvet-matte-lipstick": () => productSvg(BG.beauty, ITEMS.lipstick()),
  "rose-hydration-cream": () => productSvg(BG.beauty, ITEMS.cream()),
  "oud-eau-de-parfum": () => productSvg(BG.beauty, ITEMS.perfume()),
  // Grocery
  "wild-forest-honey": () => productSvg(BG.grocery, ITEMS.honey()),
  "himalayan-green-tea": () => productSvg(BG.grocery, ITEMS.tea()),
  "cold-pressed-olive-oil": () => productSvg(BG.grocery, ITEMS.oil()),
  "superfood-trail-mix": () => productSvg(BG.grocery, ITEMS.trailmix()),
  // Home
  "terracotta-table-lamp": () => productSvg(BG.home, ITEMS.lamp()),
  "celadon-ceramic-vase": () => productSvg(BG.home, ITEMS.vase()),
  "handloom-cushion-cover": () => productSvg(BG.home, ITEMS.cushion()),
  "sandalwood-soy-candle": () => productSvg(BG.home, ITEMS.candle()),
};

// Variation swatch images (lipstick shades, tee colours) for the demo.
const VARIANT_ART = {
  "velvet-matte-lipstick-nude": () => productSvg(BG.beauty, ITEMS.lipstick(["#c98b76", "#8f5645"])),
  "velvet-matte-lipstick-berry": () => productSvg(BG.beauty, ITEMS.lipstick(["#8e2453", "#4f0f2c"])),
  "organic-cotton-tee-forest": () => productSvg(BG.fashion, ITEMS.tee(["#2f5d4a", "#1f4a41", "#143329"])),
  "organic-cotton-tee-charcoal": () => productSvg(BG.fashion, ITEMS.tee(["#4b4b4b", "#333", "#1f1f1f"])),
};

/* ---------- Theme pattern artwork ---------- */
function tile(bg, item, w, h) {
  // Portrait/landscape canvas with the product centred.
  const scale = Math.min(w, h) / 800;
  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${w} ${h}" width="${w}" height="${h}">
  <defs>${rg("bgx", [bg[0], bg[1]], "50%", "40%", "85%")}<filter id="soft" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="10"/></filter>${item.defs}</defs>
  <rect width="${w}" height="${h}" fill="url(#bgx)"/>
  <g transform="translate(${(w - 800 * scale) / 2} ${(h - 800 * scale) / 2}) scale(${scale})">${item.body}</g>
</svg>`;
}
function jewelTile(type, metal, gem, w, h) {
  const scale = Math.min(w, h) / 800;
  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${w} ${h}" width="${w}" height="${h}">
  ${defs(metal, gem, "t")}
  <rect width="${w}" height="${h}" fill="url(#bgt)"/>
  <g transform="translate(${(w - 800 * scale) / 2} ${(h - 800 * scale) / 2}) scale(${scale})">${shapes[type]("t", gem, metal)}</g>
  ${sparkle(w * 0.2, h * 0.15, 14)}${sparkle(w * 0.8, h * 0.25, 10, 0.7)}
</svg>`;
}
function duo(bg, a, b, w, h) {
  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${w} ${h}" width="${w}" height="${h}">
  <defs>${rg("bgx", [bg[0], bg[1]], "70%", "40%", "90%")}<filter id="soft" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="10"/></filter>${a.defs}${b.defs}</defs>
  <rect width="${w}" height="${h}" fill="url(#bgx)"/>
  <g transform="translate(${w * 0.42} ${h * 0.08}) scale(${(h * 0.84) / 800})">${a.body}</g>
  <g transform="translate(${w * 0.66} ${h * 0.22}) scale(${(h * 0.7) / 800})">${b.body}</g>
</svg>`;
}

const THEME_ART = {
  "hero-ring": [() => jewelSvg("ring", "gold", "diamond", "j", 1000), 1000, 1000],
  "cat-jewellery": [() => jewelTile("necklace", "gold", "emerald", 600, 800), 600, 800],
  "cat-fashion": [() => tile(BG.fashion, ITEMS.dress(), 600, 800), 600, 800],
  "cat-electronics": [() => tile(BG.electronics, ITEMS.headphones(), 600, 800), 600, 800],
  "cat-beauty": [() => tile(BG.beauty, ITEMS.perfume(), 600, 800), 600, 800],
  "cat-grocery": [() => tile(BG.grocery, ITEMS.honey(), 600, 800), 600, 800],
  "cat-home": [() => tile(BG.home, ITEMS.lamp(), 600, 800), 600, 800],
  "promo-fashion": [() => duo(BG.fashion, ITEMS.dress(), ITEMS.tote(), 1200, 720), 1200, 720],
  "promo-tech": [() => duo(BG.electronics, ITEMS.headphones(), ITEMS.watch(), 1200, 720), 1200, 720],
  "hero-banner": [() => duo(["#24554b", "#0b1a17"], ITEMS.perfume(), ITEMS.watch(), 1600, 900), 1600, 900],
  story: [() => jewelTile("earrings", "gold", "pearl", 800, 1000), 800, 1000],
  "gallery-1": [() => jewelSvg("bangle", "gold", "ruby", "j", 600), 600, 600],
  "gallery-2": [() => productSvg(BG.beauty, ITEMS.serum(), 600), 600, 600],
  "gallery-3": [() => productSvg(BG.fashion, ITEMS.sneaker(), 600), 600, 600],
  "gallery-4": [() => productSvg(BG.home, ITEMS.candle(), 600), 600, 600],
  "gallery-5": [() => productSvg(BG.grocery, ITEMS.tea(), 600), 600, 600],
  "gallery-6": [() => productSvg(BG.electronics, ITEMS.speaker(), 600), 600, 600],
};

/* ---------- Render ---------- */
const { chromium } = require("playwright");
const sharp = require("sharp");
const browser = await chromium.launch(process.env.CHROMIUM_PATH ? { executablePath: process.env.CHROMIUM_PATH } : {});
const page = await browser.newPage();

async function render(svg, w, h) {
  await page.setViewportSize({ width: w, height: h });
  await page.setContent(`<html><body style="margin:0">${svg}</body></html>`);
  return page.screenshot({ type: "png", clip: { x: 0, y: 0, width: w, height: h } });
}

for (const [slug, fn] of Object.entries({ ...PRODUCT_ART, ...VARIANT_ART })) {
  const png = await render(fn(), 800, 800);
  await sharp(png).jpeg({ quality: 82, mozjpeg: true }).toFile(join(DEMO_IMG, `${slug}.jpg`));
}
for (const [slug, [fn, w, h]] of Object.entries(THEME_ART)) {
  const png = await render(fn(), w, h);
  await sharp(png).webp({ quality: 80 }).toFile(join(THEME_IMG, `${slug}.webp`));
}
await browser.close();
console.log("Rendered", Object.keys(PRODUCT_ART).length + Object.keys(VARIANT_ART).length, "demo images and", Object.keys(THEME_ART).length, "theme images");
