#!/usr/bin/env bash
# Build installable zips: dist/aurelia.zip (theme) and dist/aurelia-commerce.zip (plugin).
# Each zip contains a single top-level folder named after the slug, as WordPress expects.
#
# Usage: bash tools/build-zips.sh [--rebuild-js]
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST="$ROOT/dist"
STAGE="$(mktemp -d)"
trap 'rm -rf "$STAGE"' EXIT

if [[ "${1:-}" == "--rebuild-js" ]]; then
	(cd "$ROOT" && npm ci && node tools/build-js.mjs)
fi

version_of() {
	grep -m1 -E "^[[:space:]*]*(Version|Stable tag):" "$1" | sed -E 's/.*:[[:space:]]*//' | tr -d '\r'
}

THEME_VERSION="$(version_of "$ROOT/theme/style.css")"
PLUGIN_VERSION="$(version_of "$ROOT/plugin/aurelia-commerce.php")"

# Files that never ship: sources, editor/OS cruft and development metadata.
EXCLUDES=(
	--exclude=.DS_Store
	--exclude=*.map
	--exclude=./assets/src
	--exclude=node_modules
	--exclude=.git*
	--exclude=phpcs.xml*
)

stage() { # stage <source dir> <slug>
	mkdir -p "$STAGE/$2"
	tar -C "$1" "${EXCLUDES[@]}" -cf - . | tar -C "$STAGE/$2" -xf -
}
stage "$ROOT/theme" aurelia
stage "$ROOT/plugin" aurelia-commerce

# Sanity checks: the files WordPress needs to recognise each package.
for f in aurelia/style.css aurelia/theme.json aurelia/templates/index.html aurelia/screenshot.png \
	aurelia-commerce/aurelia-commerce.php aurelia-commerce/assets/js/viewer/viewer.js \
	aurelia-commerce/assets/js/vendor/qrcode.min.js; do
	[[ -f "$STAGE/$f" ]] || { echo "Missing $f" >&2; exit 1; }
done

mkdir -p "$DIST"
rm -f "$DIST/aurelia.zip" "$DIST/aurelia-commerce.zip"
(cd "$STAGE" && zip -qr -X "$DIST/aurelia.zip" aurelia && zip -qr -X "$DIST/aurelia-commerce.zip" aurelia-commerce)

echo "Built dist/aurelia.zip ($THEME_VERSION, $(du -h "$DIST/aurelia.zip" | cut -f1))"
echo "Built dist/aurelia-commerce.zip ($PLUGIN_VERSION, $(du -h "$DIST/aurelia-commerce.zip" | cut -f1))"
