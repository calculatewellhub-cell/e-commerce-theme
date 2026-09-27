#!/usr/bin/env python3
"""Append translatable theme.json / style variation strings to the theme POT.

`wp i18n make-pot` fetches the theme.json i18n schema from develop.svn.wordpress.org.
When that host is unreachable, run make-pot with --skip-theme-json and then this
script, passing the schema from any local WordPress install
(wp-includes/theme-i18n.json). Entries use the schema label as msgctxt, which is
exactly how WordPress looks them up at runtime.

Usage: python3 tools/i18n-theme-json.py <theme-dir> <pot-file> <theme-i18n.json>
"""

import glob
import json
import os
import sys


def walk(schema, data, found):
    if isinstance(schema, str):
        if isinstance(data, str) and data.strip():
            found.append((schema, data))
    elif isinstance(schema, list):
        if isinstance(data, list):
            for item in data:
                walk(schema[0], item, found)
    elif isinstance(schema, dict) and isinstance(data, dict):
        for key, sub in schema.items():
            if key == "*":
                for value in data.values():
                    walk(sub, value, found)
            elif key in data:
                walk(sub, data[key], found)


def esc(text):
    return text.replace("\\", "\\\\").replace('"', '\\"').replace("\n", "\\n")


def main(theme_dir, pot_path, schema_path):
    with open(schema_path, encoding="utf-8") as fh:
        schema = json.load(fh)

    entries = {}
    files = [os.path.join(theme_dir, "theme.json")]
    files += sorted(glob.glob(os.path.join(theme_dir, "styles", "**", "*.json"), recursive=True))
    for path in files:
        with open(path, encoding="utf-8") as fh:
            data = json.load(fh)
        found = []
        walk(schema, data, found)
        # Section/block style variations carry a top-level title too.
        if "title" in data and path != files[0]:
            found.append(("Style variation name", data["title"]))
        rel = os.path.relpath(path, theme_dir)
        for ctx, msgid in found:
            entries.setdefault((ctx, msgid), [])
            if rel not in entries[(ctx, msgid)]:
                entries[(ctx, msgid)].append(rel)

    with open(pot_path, encoding="utf-8") as fh:
        pot = fh.read()

    out = []
    for (ctx, msgid), refs in entries.items():
        head = 'msgctxt "%s"\nmsgid "%s"' % (esc(ctx), esc(msgid))
        if head in pot:
            continue
        out.append("#: %s\n%s\nmsgstr \"\"\n" % (" ".join(refs), head))

    with open(pot_path, "a", encoding="utf-8") as fh:
        fh.write("\n" + "\n".join(out))
    print("Added %d theme.json strings to %s" % (len(out), pot_path))


if __name__ == "__main__":
    if len(sys.argv) != 4:
        sys.exit(__doc__)
    main(*sys.argv[1:])
