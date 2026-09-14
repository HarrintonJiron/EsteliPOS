#!/usr/bin/env python3
import argparse
import csv
import json
import re
from collections import Counter
from pathlib import Path

import pdfplumber

ROW = re.compile(
    r"^(\S+)\s+(.+?)\s+([\d,]+\.\d{2})\s+(-?[\d,]+\.\d{2})\s+"
    r"(-?[\d,]+\.\d{2})\s+(-?[\d,]+\.\d{2})\s+(-?[\d,]+\.\d{2})\s+"
    r"(-?[\d,]+\.\d{2})\s+(-?[\d,]+\.\d{2})\s+(-?[\d,]+\.\d{2})$"
)


def number(value: str) -> float:
    return float(value.replace(",", ""))


def main() -> None:
    parser = argparse.ArgumentParser(description="Extrae el inventario Crystal Reports a CSV verificable.")
    parser.add_argument("pdf")
    parser.add_argument("csv")
    parser.add_argument("--report", required=True)
    args = parser.parse_args()

    source = Path(args.pdf)
    output = Path(args.csv)
    report_path = Path(args.report)
    output.parent.mkdir(parents=True, exist_ok=True)
    report_path.parent.mkdir(parents=True, exist_ok=True)

    rows = []
    category = None
    page_count = 0
    with pdfplumber.open(source) as pdf:
        page_count = len(pdf.pages)
        for page_number, page in enumerate(pdf.pages, 1):
            for raw in (page.extract_text(x_tolerance=2, y_tolerance=3) or "").splitlines():
                line = raw.strip()
                if line.startswith("Línea:"):
                    category = line.split(":", 1)[1].strip()
                    continue
                match = ROW.match(line)
                if not match:
                    continue
                values = [number(value) for value in match.groups()[2:]]
                source_total = values[-1]
                rows.append({
                    "source_page": page_number,
                    "category": category,
                    "code": match.group(1).strip(),
                    "name": re.sub(r"\s+", " ", match.group(2)).strip(),
                    "sale_price": values[0],
                    "source_initial_stock": values[1],
                    "source_total_stock": source_total,
                    "import_stock": max(0, source_total),
                    "requires_review": "negative_stock" if source_total < 0 else "",
                })

    codes = Counter(row["code"] for row in rows)
    categories = Counter(row["category"] for row in rows)
    report = {
        "source": source.name,
        "pages": page_count,
        "rows": len(rows),
        "unique_codes": len(codes),
        "duplicate_codes": sorted(code for code, count in codes.items() if count > 1),
        "categories": dict(sorted(categories.items())),
        "positive_stock": sum(row["source_total_stock"] > 0 for row in rows),
        "zero_stock": sum(row["source_total_stock"] == 0 for row in rows),
        "negative_stock": [row for row in rows if row["source_total_stock"] < 0],
        "fractional_stock": sum(row["source_total_stock"] % 1 != 0 for row in rows),
        "purchase_cost_available": False,
    }
    if report["duplicate_codes"] or len(rows) == 0:
        raise RuntimeError("La extracción no es segura: no hay filas o existen códigos duplicados.")

    with output.open("w", newline="", encoding="utf-8-sig") as stream:
        writer = csv.DictWriter(stream, fieldnames=rows[0].keys())
        writer.writeheader()
        writer.writerows(rows)
    report_path.write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")
    print(json.dumps(report, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
