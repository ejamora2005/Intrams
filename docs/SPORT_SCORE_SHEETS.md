# Sport score sheets

Administrators can open **Score sheet** on every sport card, participant list or bracket. Tabulators have the same action on their sport cards. Select a faction and, for singles/doubles, beach volleyball, chess or 3x3, choose the actual players for this match. Editable names also allow an official to complete a blank sheet.

The registry in `config/score_sheets.php` selects a discipline from the sport code, independently of its gender category or tournament mechanic. It covers all configured sports, including separate 3x3 and 5x5 basketball, indoor and beach volleyball, racket singles/doubles and team ties, baseball/softball, chess, Mobile Legends, races, relays, throws, horizontal jumps and high jump. New unknown sports receive a custom result record until their discipline is added to the registry.

The existing basketball 5x5 and indoor volleyball reference layouts remain available. Old 3x3, beach and modified volleyball links open their corresponding new formats. Modified/mixed volleyball use the regular set structure with editable period labels. Russian Softball uses a softball scorebook with editable inning labels, as requested; local pairing/scoring details go in its rules field.

Registered active athletes prefill names, including individual entries whose faction is stored through membership. Track and field have 16 athletes per sheet and page links for additional entries. Relays list the registered runners by faction; the scorer confirms the actual four-person running order. Team tie sheets record individual rubbers; use the corresponding singles/doubles sheet for each rubber. Scorebooks contain nine batting positions and three substitute lines; print a continuation sheet when needed, and a separate sheet for the opposing batting team. Roster overflow is identified next to the faction selector.

Choose a scheduled session to prefill its exact date, time and venue. All result, scoring and official fields remain editable. Download **PDF**, **Word** or **Excel** from the download dialog. Each export preserves the complete A4 page as an image; Word and Excel are printable copies, not formula-driven workbooks. Sheet entries are not stored as match results and never award points or declare winners. Use the existing result declaration workflow for standings.

Format references are linked on the individual sheets:

| Discipline | Reference / structure |
| --- | --- |
| Basketball 5x5 | [FIBA official basketball rules, scoresheet appendix](https://assets.fiba.basketball/image/upload/documents-corporate-fiba-official-rules-2024-v10a.pdf) |
| Basketball 3x3 | [FIBA 3x3 scoring records](https://help.fiba3x3.com/en/support/solutions/articles/35000012976-where-can-i-print-the-play-by-play-report-or-the-digital-scoresheet-): four players, team fouls, running score, regular time and overtime |
| Volleyball | [FIVB refereeing documents](https://www.fivb.com/inside-fivb/refereeing/reports-documents/) |
| Beach volleyball | [FIVB tools and forms](https://www.fivb.com/beach-volleyball/tools-and-forms/): two players, three sets, service order, timeouts and side changes |
| Badminton | [BWF umpire manual](https://www.badminton.org.au/wp-content/uploads/2026/01/bwf_umpires_manual_level_1.pdf): player/service identification and three game score sequences |
| Table tennis | [ITTF officials' documents](https://www.ittf.com/committees/umpires-referees/documents/): individual game points, match result, service, timeouts and penalties |
| Chess | [FIDE arbiters' manual](https://arbiters.fide.com/wp-content/uploads/Publications/Manual/Arbiters_Manual_2025.pdf): numbered white/black move columns, board, round, time control, result and signatures |
| Baseball / softball | [WBSC scorer requirements](https://static.wbsc.org/uploads/federations/279/documents/4bd5da5e-f5a9-9aa5-6be9-5789589528f6.pdf): innings, batting order, substitutions, line score and pitching record |
| Track and field | [World Athletics technical information](https://worldathletics.org/about-iaaf/documents/technical-information): times, places, status, relay declarations, trial series, wind and height progression |
| Mobile Legends | School match record: starters, substitutes, IGN/hero, K/D/A, draft, objectives and series results |

These are editable competition forms based on the disciplines' scoring structures; governing-body references remain the authority for event rules.

No migration or database seeding is needed for this feature. Deploy the application code and rebuild Vite assets.
