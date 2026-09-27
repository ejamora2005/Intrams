---
type: "query"
date: "2026-09-27T01:52:32.938154+00:00"
question: "replace the basketball score sheet with this, but inheret the function autofill name based on the team, and lisence replaced with course."
contributor: "graphify"
outcome: "useful"
source_nodes: ["basketball-score-sheet-pdf.blade.php", "basketball-score-sheet.blade.php", "Course", "Student", "Team"]
---

# Q: replace the basketball score sheet with this, but inheret the function autofill name based on the team, and lisence replaced with course.

## Answer

Expanded from original query via graph vocab: [basketball, score, sheet, team, course, license, pdf, sport, student, member, dompdf]. The basketball scoresheet flow uses SportController::basketballScoreSheetContext to provide team rosters to basketball-score-sheet.blade.php, whose team picker fills the submitted player fields consumed by basketball-score-sheet-pdf.blade.php. Course must be eager-loaded through athleteEntries.student.course and included in each player payload.

## Outcome

- Signal: useful

## Source Nodes

- basketball-score-sheet-pdf.blade.php
- basketball-score-sheet.blade.php
- Course
- Student
- Team