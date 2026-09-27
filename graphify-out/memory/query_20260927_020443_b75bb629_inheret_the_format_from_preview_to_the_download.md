---
type: "query"
date: "2026-09-27T02:04:43.899953+00:00"
question: "inheret the format from preview to the download"
contributor: "graphify"
outcome: "useful"
source_nodes: ["basketball-score-sheet.blade.php", "basketball-score-sheet-pdf.blade.php", ".downloadBasketballScoreSheet()"]
---

# Q: inheret the format from preview to the download

## Answer

Expanded from original query via graph vocab: [basketball, score, sheet, blade, dompdf, download, form, layout, pdf, style]. The preview and Dompdf export now include admin.sports.partials.basketball-score-sheet, so both use the same table structure, dimensions, and CSS; the preview renders inputs while the PDF renders their submitted values.

## Outcome

- Signal: useful

## Source Nodes

- basketball-score-sheet.blade.php
- basketball-score-sheet-pdf.blade.php
- .downloadBasketballScoreSheet()