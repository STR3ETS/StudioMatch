"""
Leest een juridisch PDF-document uit en geeft de inhoud terug als JSON.

Hoort bij `php artisan legal:import`. Dat commando werkt bij voorkeur met de Word-versie,
want die bevat ook de hyperlinks. Komt er alleen een PDF binnen, dan gebruikt het dit
script. Vereist PyMuPDF (pip install pymupdf).

Gebruik: python legal_pdf.py <document.pdf> <uitvoer.json>

De uitvoer is bewust dom: een lijst blokken met een type en tekst. Het omzetten naar HTML
gebeurt in PHP, zodat beide importroutes dezelfde opmaak opleveren.
"""

import json
import re
import sys
from collections import Counter

import pymupdf

BULLETS = ("", "•", "", "●", "▪")


def main() -> int:
    if len(sys.argv) < 3:
        print("gebruik: legal_pdf.py <document.pdf> <uitvoer.json>", file=sys.stderr)
        return 2

    document = pymupdf.open(sys.argv[1])

    lines = collect_lines(document)
    body_size = most_common_size(lines)
    repeated = repeating_lines(lines, len(document))

    blocks = []
    updated = None
    pending_list = []
    expect_item = False

    # Losse status van de opsomming die nu wordt opgebouwd.
    state = {"callout": False, "block": None}

    for line in lines:
        if line["nkey"] in repeated:
            continue

        text = line["text"].strip()

        if not text:
            continue

        # De documenttitel staat al als kop boven de pagina.
        if line["size"] >= body_size * 2:
            continue

        if updated is None:
            match = re.match(
                r"^(?:laatst gewijzigd|versie)\s*:?\s*(\d{1,2}\s+\w+\s+\d{4})(.*)$",
                text,
                re.IGNORECASE,
            )
            if match:
                updated = match.group(1).strip()
                rest = match.group(2).strip(" |-·")
                if len(rest) > 10:
                    blocks.append({"type": "p", "runs": [{"text": rest, "bold": False}]})
                continue

        if line["table"] is not None:
            flush(blocks, pending_list, state)
            if not any(b.get("id") == line["table"]["id"] for b in blocks):
                blocks.append(line["table"])
            continue

        if is_bullet(line):
            items = split_bullets(line)
            state["callout"] = state["callout"] or line["callout"]

            if items:
                pending_list.extend(items)
                state["block"] = line["block"]
                expect_item = False
            else:
                # Alleen het teken; de tekst van dit punt staat op de volgende regel.
                expect_item = True

            continue

        if expect_item:
            pending_list.append(list(line["runs"]))
            state["block"] = line["block"]
            expect_item = False
            continue

        # Een punt dat over twee regels loopt hoort bij het vorige punt.
        if pending_list and state["block"] == line["block"]:
            pending_list[-1].append({"text": " ", "bold": False})
            pending_list[-1].extend(line["runs"])
            continue

        flush(blocks, pending_list, state)

        # Koppen zijn duidelijk groter dan de lopende tekst.
        if line["size"] >= body_size * 1.3:
            blocks.append({"type": "h2", "runs": line["runs"]})
        elif line["size"] >= body_size * 1.12:
            blocks.append({"type": "h3", "runs": line["runs"]})
        else:
            blocks.append(
                {
                    "type": "line",
                    "runs": line["runs"],
                    "block": line["block"],
                    "callout": line["callout"],
                }
            )

    flush(blocks, pending_list, state)

    result = {"updated": updated, "blocks": merge_lines(blocks)}

    with open(sys.argv[2], "w", encoding="utf-8") as handle:
        json.dump(result, handle, ensure_ascii=False, indent=1)

    return 0


def collect_lines(document) -> list:
    """Alle tekstregels, met lettergrootte, vetgedrukt en bij welke tabel ze horen."""
    lines = []

    for page_number, page in enumerate(document):
        tables = []
        try:
            for index, table in enumerate(page.find_tables().tables):
                rows = [[(cell or "").strip() for cell in row] for row in table.extract()]
                rows = [row for row in rows if any(row)]
                if rows:
                    tables.append(
                        {
                            "bbox": table.bbox,
                            "data": {
                                "type": "table",
                                "id": f"{page_number}-{index}",
                                "head": rows[0],
                                "rows": rows[1:],
                            },
                        }
                    )
        except Exception:
            tables = []

        shaded = shaded_areas(page)

        for block_number, block in enumerate(page.get_text("dict")["blocks"]):
            for line in block.get("lines", []):
                runs = [
                    {
                        "text": span["text"],
                        "bold": "bold" in span["font"].lower() or bool(span["flags"] & 2 ** 4),
                        "font": span["font"],
                        "size": round(span["size"], 1),
                    }
                    for span in line["spans"]
                ]

                text = "".join(run["text"] for run in runs)

                if not text.strip():
                    continue

                sizes = Counter()
                for run in runs:
                    sizes[run["size"]] += max(1, len(run["text"].strip()))

                lines.append(
                    {
                        "page": page_number,
                        "block": f"{page_number}-{block_number}",
                        "text": text,
                        "runs": runs,
                        "size": sizes.most_common(1)[0][0],
                        "y": round(line["bbox"][1], 1),
                        "height": page.rect.height,
                        "key": re.sub(r"\s+", " ", text).strip().lower(),
                        "nkey": re.sub(r"[^0-9a-z]+", " ", text.lower()).strip(),
                        "table": table_for(line["bbox"], tables),
                        "callout": inside_any(line["bbox"], shaded),
                    }
                )

    return lines


def shaded_areas(page) -> list:
    """Gevulde vlakken op de pagina; die markeren in deze documenten een kader."""
    areas = []

    try:
        drawings = page.get_drawings()
    except Exception:
        return areas

    for drawing in drawings:
        if drawing.get("fill") is None or drawing.get("rect") is None:
            continue

        rect = drawing["rect"]

        # Alleen echte vlakken, geen haarlijntjes of de hele pagina.
        if rect.width < 100 or rect.height < 12 or rect.height > page.rect.height * 0.5:
            continue

        areas.append(tuple(rect))

    return areas


def inside_any(bbox, areas) -> bool:
    for left, top, right, bottom in areas:
        if bbox[0] >= left - 3 and bbox[2] <= right + 3 and bbox[1] >= top - 3 and bbox[3] <= bottom + 3:
            return True

    return False


def table_for(bbox, tables):
    for table in tables:
        left, top, right, bottom = table["bbox"]
        if bbox[0] >= left - 2 and bbox[2] <= right + 2 and bbox[1] >= top - 2 and bbox[3] <= bottom + 2:
            return table["data"]

    return None


def most_common_size(lines) -> float:
    sizes = Counter()

    for line in lines:
        sizes[line["size"]] += len(line["text"].strip())

    return sizes.most_common(1)[0][0] if sizes else 10.0


def repeating_lines(lines, pages: int) -> set:
    """
    Kop- en voetteksten horen niet in de tekst thuis.

    Meestal staan ze op elke pagina, maar de eerste pagina heeft soms een eigen
    variant. Daarom geldt in de marge ook: korte regel is opmaak, geen inhoud.
    """
    seen = {}

    for line in lines:
        # Een opsommingsteken staat soms bovenaan een pagina en is op elke pagina
        # gelijk; dat is geen paginakop.
        if is_bullet(line):
            continue

        margin = line["height"] * 0.09
        in_margin = line["y"] < margin or line["y"] > line["height"] - margin

        if not in_margin:
            continue

        seen.setdefault(line["nkey"], set()).add(line["page"])

    return {key for key, found in seen.items() if len(found) >= 2}


def is_bullet(line) -> bool:
    if any(line["text"].lstrip().startswith(bullet) for bullet in BULLETS):
        return True

    return any("opensymbol" in run["font"].lower() for run in line["runs"])


def split_bullets(line) -> list:
    """Een opsommingsblok bevat soms het teken en de tekst als losse stukjes."""
    items = []
    current = []

    for run in line["runs"]:
        text = run["text"]

        if "opensymbol" in run["font"].lower() or any(b in text for b in BULLETS):
            cleaned = text
            for bullet in BULLETS:
                cleaned = cleaned.replace(bullet, "")

            if current and "".join(r["text"] for r in current).strip():
                items.append(current)

            current = []

            if cleaned.strip():
                current.append({"text": cleaned, "bold": run["bold"]})

            continue

        current.append({"text": text, "bold": run["bold"]})

    if current and "".join(r["text"] for r in current).strip():
        items.append(current)

    return items


def flush(blocks, pending_list, state) -> None:
    if pending_list:
        blocks.append({"type": "ul", "callout": state["callout"], "items": list(pending_list)})
        pending_list.clear()

    state["callout"] = False
    state["block"] = None


def merge_lines(blocks) -> list:
    """Een alinea staat in de PDF als losse regels; die horen weer aan elkaar."""
    merged = []

    for block in blocks:
        if block["type"] != "line":
            merged.append(strip_fonts(block))
            continue

        if merged and merged[-1].get("block") == block["block"] and merged[-1].get("type") in ("p", "callout"):
            merged[-1]["runs"].append({"text": " ", "bold": False})
            merged[-1]["runs"].extend(clean_runs(block["runs"]))
            continue

        merged.append(
            {
                "type": "callout" if block.get("callout") else "p",
                "block": block["block"],
                "runs": clean_runs(block["runs"]),
            }
        )

    for block in merged:
        block.pop("block", None)

    return merged


def clean_runs(runs) -> list:
    return [{"text": run["text"], "bold": run["bold"]} for run in runs]


def strip_fonts(block) -> dict:
    if "runs" in block:
        block["runs"] = clean_runs(block["runs"])

    if "items" in block:
        block["items"] = [clean_runs(item) for item in block["items"]]

    return block


if __name__ == "__main__":
    sys.exit(main())
