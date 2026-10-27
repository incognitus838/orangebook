# UI refresh — "Paper" (26 Oct 2026)

White canvas, generous spacing, Inter for UI, Instrument Serif for page titles and marketing headlines, black pill buttons, one warm orange accent used sparingly.

## How it is built
- One new stylesheet, `public/css/ob-paper.css`, loaded **after** `crest.css` / `landing.css` in the `app`, `platform`, `marketing`, `empty` and `admission` layouts. Delete the `<link>` lines (or the file) to roll back the look.
- Backup of all views and the old CSS before any change: `storage/ui-backup-2026-10-26/`.
- Cache-busting: the stylesheet URL carries the file's mtime, so edits show without a hard refresh.

## Routes checked (58 pages, all 200 after the change)
Public 9 · School admin 23 · Teacher 11 · Student 6 · Parent 5 · Platform 4.

## Bugs fixed
| Page | Problem | Fix |
|---|---|---|
| `/teacher/results` → `/teacher/class/{id}/results` | **500** — Blade syntax error (`F9@endif` is not compiled as a directive) | Space before `@endif` in `teacher/results/show.blade.php` |
| `/student/dashboard` | Showed teacher content to students ("Open class", "Who is absent", "Nothing waiting to publish") | Role-aware cards: My class + today's mark, Report card, Homework due; greeting by name |
| `/student/results`, `/parent/child/{id}/results` | "Download report card" button invisible (`bg-gray-900` not in the loaded CSS) | Uses `crest-btn` |
| `/platform/organisations/{id}` | "Suspend / Activate" button invisible (inline transparent bg) | Secondary button style |
| Teacher rail | "My class" highlighted on the Results page | Menu checks segment 4 |
| `/{slug}/admission-form` | Black page, broken logo, low-contrast "Admission Not Opened" | Clean header with school name, readable closed state, mailto school |
| `/password/reset`, reset-token page | Legacy off-brand page ("E-Mail Address", tiny button) | Rebuilt on the same split layout as login |
| Promotions | Confirm checkbox and submit button collided | Checkbox on its own row |
| Every app page | Tab title was just the school name | Title = page heading · school |

## Not changed (flagged)
- `/admin/students/blockedstudents` returns 500 (`count()` on a string, PHP 8). Not linked from any menu.
- `/register` is the upstream self-sign-up form; the product flow is "Apply for a quote" (`/start`). Consider redirecting `/register` → `/start`.
- Marketing hero / band screenshots in `public/` still show the old navy dashboard — retake them from the new UI.
- `/admin/attendance/add` and other legacy Vue screens not in the menus keep their own markup; they inherit the new tokens but were not redesigned.
