# NevererWeb — Agent Context File

> **This file should always be read at the start of any Copilot/AI agent chat on this project.**

---

## Project Overview

**NevererWeb** is a PHP web application for creating, editing, sharing and solving cryptic and standard crossword puzzles. It is the web edition of "The Neverer" crossword editor.

- **GitHub repository & issues:** https://github.com/almcnicoll/NevererWeb
- **GitHub Issues list:** https://github.com/almcnicoll/NevererWeb/issues
- **GitHub Project board:** https://github.com/users/almcnicoll/projects/1
- **Language:** PHP (backend), JavaScript/jQuery (frontend), MySQL (database)
- **Frontend framework:** Bootstrap 5.3
- **Local dev environment:** Laragon (`C:\laragon\www\NevererWeb\`)

---

## Key Features

- 2nd-order (180°) and 4th-order (90°) rotational grid symmetry
- Interactive crossword grid editor
- Word suggestions from built-in English dictionary (SOWPODS + names/idioms)
- Anagram builder
- Print/PDF export via mPDF
- Interactive solve mode (shareable link)
- Local accounts + Google OAuth2 login
- User-managed custom word dictionaries ("Tomes") with saved clues ("TomeClues")

---

## Architecture & Directory Structure

```
/
├── index.php              # Front controller / router
├── autoload.php           # PSR-style autoloader (maps namespace\Class → class/Namespace/Class.php)
├── ajax/                  # AJAX endpoint handlers (called as /ajax/[name]/*)
│   ├── crossword.php
│   ├── placed_clue.php    # Create/update/find/delete placed clues; TomeClue saving
│   ├── tome_clue.php
│   ├── tome_entry.php
│   └── tome.php
├── class/                 # PHP classes (namespaced)
│   ├── Basic/
│   │   ├── BaseClass.php  # Root base class; expose() returns get_object_vars()
│   │   ├── db.php         # PDO connection singleton
│   │   ├── Model.php      # ORM base: find/findFirst/getById/save/delete
│   │   └── Typed_List.php # Generic typed collection; expose() via BaseClass
│   ├── Crosswords/
│   │   ├── Clue.php           # The clue text/answer (no grid context)
│   │   ├── Crossword.php      # Crossword entity; setClueNumbers(), getExistingSymmetryClues()
│   │   ├── PlacedClue.php     # Clue placed in a grid (x, y, orientation); getRotatedClue()
│   │   └── PlacedClue_List.php
│   ├── Dictionaries/
│   │   ├── Tome.php           # A user's word list / dictionary
│   │   ├── TomeEntry.php      # A word entry in a Tome
│   │   └── TomeClue.php       # A saved clue in a Tome (word + question + explanation)
│   ├── Security/
│   │   ├── User.php
│   │   ├── Config.php
│   │   └── PageInfo.php
│   ├── UI/                    # Bootstrap UI component builders (forms, modals, etc.)
│   └── Logging/LoggedError.php
├── pages/                 # Page view files (included by index.php)
│   ├── crossword_index.php
│   ├── crossword_create.php
│   ├── crossword_edit.php      # Main editing UI; "This may also affect" warning div here
│   ├── crossword_solve.php     # Solve/share page (no symmetry warning UI)
│   ├── crossword_export.php
│   ├── dictionary_*.php
│   └── ...
├── js/
│   ├── app.js
│   ├── crossword_edit.js   # Main editor JS; populateEditForm(), createClue(), updateClue()
│   ├── crossword_solve.js  # Solver JS; populateEditForm() (different version, no symmetry warning)
│   └── ...
├── sql/
│   ├── create-tables.sql
│   └── db-updates.sql      # Versioned migration script (currently at version 39)
└── inc/                    # Shared PHP includes (header, footer, login checks)
```

---

## Routing

URLs are parsed by `index.php` using `.htaccess` rewriting into `?params=...`:
- `/crossword/edit/42` → loads `pages/crossword_edit.php` with `$params = ['42']`
- `/ajax/placed_clue/*/find/42` → loads `ajax/placed_clue.php` with action `find` and `$params = ['42']`
- The `*` in AJAX routes is a wildcard; action is the first element of `$params` after shift

---

## ORM / Data Layer

- `Basic\Model` is the ORM base. Key methods:
  - `save($onDuplicateKeyUpdate = false)` — INSERT or UPDATE based on whether `id` is set and exists in DB. Pass `true` to append `ON DUPLICATE KEY UPDATE`.
  - `find($criteria, $orderBy)` — returns array of objects
  - `findFirst($criteria)` — returns single object or null
  - `getById($id)` — convenience wrapper
  - `delete()` — deletes by id
- `place_number` on `PlacedClue` is recalculated via `setClueNumbers()` SQL (ROW_NUMBER over y,x) after every `PlacedClue::save()`.
- `expose()` on any object returns `get_object_vars()` — used to serialise for JSON AJAX responses.
- `Typed_List::expose()` returns `get_object_vars($this)` which includes `_list` — the JS side reads `arr["additional"]["_list"]` to access symmetry clues.

---

## Symmetry System

- Crosswords have `rotational_symmetry_order`: 1 (none), 2 (180°), 4 (90°/4-way)
- `PlacedClue::getRotatedClue(int $degrees)` — returns a new (unsaved) PlacedClue at the rotated position
  - 180°: `x = cols-x-1`, `y = rows-y-1`, then subtract `(length-1)` from x (ACROSS) or y (DOWN) to get start
  - 90° and 270° swap orientation and recalculate using `lastRow()`/`lastCol()`
- `Crossword::getExistingSymmetryClues(PlacedClue $placedClue)` — finds actual DB clues that match the symmetry rotations of the given clue
- `PlacedClue::getSymmetryClues()` — convenience wrapper calling `getExistingSymmetryClues`
- "This may also affect: X across/down" warning is shown in `crossword_edit.php` / `crossword_edit.js` when editing a clue that has symmetry partners

---

## Database

- MySQL; accessed via PDO in `Basic\db`
- Key tables: `crosswords`, `placedclues`, `clues`, `tomes`, `tome_entries`, `tome_clues`, `users`, `subscriptions`, `faqs`
- `tome_clues` has unique index `by_entry` on `(tome_id, cryptic, question)` (originally `tomeentry_id`, renamed in v36)
- Migration script: `sql/db-updates.sql` — versioned with `/* VERSION N */` comments; currently at v39

---

## GitHub Issues (resolved as of Aug 2026)

Every issue tracked below is now closed on GitHub (with a comment referencing the fixing commit). Kept here as a historical record since the descriptions explain *why* parts of the code look the way they do. Check https://github.com/almcnicoll/NevererWeb/issues for anything filed since.

### Bugs
| # | Title | Status |
|---|-------|--------|
| ~~#27~~ | ~~Saving clue to database causes save failure when entry already exists~~ | **FIXED** — `TomeClue::save()` now called with `true` (`ON DUPLICATE KEY UPDATE`) in both `create` and `update` cases in `ajax/placed_clue.php`. This only actually worked once `Basic\Model::save(true)` itself was fixed to exclude `id`/`created` from the `ON DUPLICATE KEY UPDATE` clause — see Notes for Agents below |
| ~~#20~~ | ~~"This may also affect" incorrect~~ | **CLOSED — not a bug.** Reporter misunderstood: the warning refers to the symmetry-rotated clue, not intersecting clues, so two across clues at opposite corners of the grid legitimately affecting each other is correct behaviour |

*No currently-open bugs.*

### Enhancements
| # | Title | Resolution |
|---|-------|-----------|
| ~~#33~~ | ~~Set default dictionary on create~~ | **FIXED** — `pages/dictionary_create.php`: after saving a new Tome, if `$user->default_dictionary` is null, set it to the new tome's id and save the user |
| ~~#32~~ | ~~Prompt when there's no default dictionary~~ | **FIXED** — `pages/crossword_edit.php`: inline prompt with a link to `/dictionary/create`, attached directly after the disabled "Save clue to dictionary" checkbox via `BootstrapFormField::setAfterHtml()` (an earlier version used `BootstrapForm::addHtml()`, which always renders at the top of the form regardless of call site, so the message ended up disconnected from the checkbox it explains) |
| ~~#31~~ | ~~Ability to filter words in anagram list~~ | **FIXED** — new "Exclude words" box in `UI\AnagramFinderViewComponent`; `js/dict_worker.js`'s `getAnagrams()` drops excluded words from the candidate pool *before* searching (not filtered from results afterward), so an excluded word can never appear in any solution |
| ~~#30~~ | ~~New anagram request should abort previous ones~~ | **FIXED** — `js/dict_worker.js`: `search()`/`getAnagrams()` use a per-request id (`latestAnagramRequestId`) instead of a shared abort boolean, so a new request can't accidentally un-abort an older in-flight one, and `search()` now yields periodically (real `setTimeout`, tunable via `anagramSearchTuning`) so a request already mid-recursion can actually be interrupted, not just one that hasn't started yet |
| ~~#19~~ | ~~Word definitions~~ | **FIXED** — `js/dict_master.js`: `dictionary.getDefinition()` using the Free Dictionary API (`dictionaryapi.dev`) with a local cache, and `dictionary.initDefinitionPopovers()` which attaches Bootstrap popovers via event delegation on `td.suggested-word-list-item` cells — showing definitions on mouseover. Must use the vanilla `bootstrap.Popover` API (`new bootstrap.Popover(el, ...)`, `bootstrap.Popover.getInstance(el)`) — see Notes for Agents below |
| ~~#14~~ | ~~Mobile interface~~ | **FIXED (scoped)** — fixed the specific bug from the issue's screenshot: Bootstrap's `col-1` grid class applied to the "+ New"/"+ Import" buttons in `crossword_index.php`/`dictionary_index.php`, outside of any `.row`, forced them to ~8% width regardless of viewport and squeezed the button text into unreadable vertical letter-stacks on phone screens. Replaced with a `d-grid gap-2 d-md-flex` button group (stacks full-width on mobile, inline from `md` up). This addressed the diagnosed bug only, not a full mobile redesign — the crossword grid itself was intentionally left untouched |
| ~~#8~~ | ~~Inline JS replacement library~~ | **FIXED** — new `UI\DataTransfer` class emits `<script type="application/json" class="data-transfer">` blocks (read by the pre-existing `transferData()` in `js/app.js`) instead of inline `<script>` blocks with PHP values interpolated directly into executable JS. Replaced every such spot, including the one flagged by `index.php`'s own `TODO #8` comment |
| ~~#7~~ | ~~Toasts for error/confirmation messages~~ | **FIXED** — replaced remaining `alert()` calls with the existing `makeToast()` toast system; `createClue()`/`editClue()`/`editSettings()` in `crossword_edit.js`/`crossword_solve.js` now have a fail handler (`displayAjaxError`) where a failed save previously gave no feedback at all and the modal closed as if it had succeeded |

*No currently-open enhancements.*

---

## Coding Conventions

- PHP namespaces match directory structure under `class/` (e.g. `Crosswords\PlacedClue`)
- No framework — custom MVC-lite pattern
- AJAX responses: `die(json_encode(...))` — always die after output
- Errors in AJAX: `throw_error($msg)` — logs and dies with `{"errors": ...}`
- JS uses jQuery + Bootstrap 5 modals; no build step
- Comments are sparse — only add where genuinely clarifying
- Do not add new libraries unless absolutely necessary

---

## Notes for Agents

- Always check `sql/db-updates.sql` before modifying DB schema — append new versioned `/* VERSION N */` blocks, never edit old ones
- When saving `TomeClue`, always use `$tc->save(true)` to handle duplicates gracefully
- `place_number` is auto-managed by `setClueNumbers()` — do not set it manually
- The `expose()` method is used for all AJAX JSON output — be careful adding properties to model classes as they will be exposed
- The solve page (`crossword_solve.php`) intentionally has no symmetry warning UI — do not add it there
- Session stores serialized `User` object in `$_SESSION['USER']`
