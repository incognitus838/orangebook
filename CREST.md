# Orangebook — multi-tenant school SaaS

Fork of **[GegoK12](https://github.com/Gego-K12/gegok12)** (MIT). Upstream copyright: GegoSoft Technologies and GegoK12 Contributors. We keep their license notice; Orangebook is the product name we sell.

GegoK12 already has multi-school isolation (`school_id` on every record), the five roles we need, attendance, parent links, subscriptions/plans, branding (logo + school details), spreadsheet import, and activity logs. We are not rewriting that.

## 1. Tech stack (what this repo actually is)

| Layer | Choice |
|---|---|
| App | Laravel 12, PHP 8.4+, Livewire 3, Laravel UI auth |
| Database | MySQL 8 — **row isolation by `school_id`** (one DB, many schools) |
| Auth | Laravel session auth + optional OTP; email and phone on `users` |
| Roles | `usergroups` table (see below) + Laratrust |
| Files / PDFs | Spatie Media Library, DomPDF, Maatwebsite Excel |
| SMS / email | Twilio, MSG91, Laravel mail (Mailgun/Postmark ready) |
| Mobile | Existing API routes (`routes/api.php`, `routes/teacherapi.php`) |
| Hosting this machine | Homebrew PHP + MySQL + `php artisan serve` (Docker optional; `docker-compose.yml` is in the repo) |

Selling model already in schema: `plans` + `subscriptions` per school. Open-source seed uses one unlimited plan; we will later map Free trial / Basic / Pro onto `plans`.

**Gap vs our spec:** term report cards with CA + exam weighting live behind the **Exam addon** (`/addon/install/exam`). Core has scholastic grade *scales* (`sc_grade`) and attendance, not full mark entry. Next implementation slice after this run: enable/build that exam/results path.

## 2. High-level schema (already in `database/migrations`)

```
usergroups          SiteAdmin | SchoolAdmin | Teacher | Student | Parent | …
schools             id, name, slug, email, phone, status
school_details      logo, board, colors, extra branding (per school)
plans               cycle, amount, member limits
subscriptions       school_id → plan

users               school_id, usergroup_id, name, email, mobile_no, password
userprofiles        firstname, lastname, address, DOB, …
parent_profiles
student_parent_link parent user ↔ student user
student_academics   student ↔ class (standardLink) for a year
teacher_profiles

academic_years      school_id, name, status (current year)
standards           school_id, name (class level), order
sections            school_id, name (A / B / Science)
standard_links      school_id + year + standard + section  →  "JSS2A"
subjects            school_id, name
teacherlinks        teacher assigned to class/subject

attendances         school_id, standardLink_id, user_id, date, session,
                    status, reason_id, remarks, recorded_by
absent_reasons

sc_grade            school_id, academic_year_id, grading_method (cbse | passfail)
non_sc_grade        skills / co-scholastic scales

assignments / homework / lesson_plans / notices / library / fees (addon-gated)
activity_log        Spatie — score/attendance changes once exam addon is on
```

Tenant rule: almost every table has `school_id`. Queries go through `Auth::user()->school_id` (see `SiteHelper`). Super Admin (`usergroup` SiteAdmin) has no school, or impersonates a school admin.

## 3. Key user flow — teacher attendance → parent/student sees it

This path **already exists** in core (exam scores are the addon):

1. School Admin creates year, classes (`standard_links`), subjects, invites teachers, admits students, links parents (`student_parent_link`).
2. Teacher opens **Attendance** (`routes/teacher.php` / `AttendanceController`) for a class (`standardLink`).
3. Bulk mark Present / Absent / Late / Excused (`status` + `absent_reasons`); optional per-student override.
4. Row written to `attendances` with `school_id` + `recorded_by`.
5. Student dashboard (`routes/student.php` → `DashboardController`) and parent API (`GET /attendance/{student_id}`) read only that school's rows for that child.
6. Admin reports: daily/class attendance on `StandardsLinkDetailsController`.

**Results flow (after Exam addon):** Teacher uploads CA / mid-term / exam → totals, grades, remarks → publish term → parent/student download PDF. Installer route: `POST /addon/install/exam`.

## 4. Page list by role (existing routes)

### Super Admin (`usergroup` SiteAdmin)
- Platform dashboard, school onboarding, impersonate school admin
- Subscription / plan (`/payment/subscription`)
- Exam addon installer (`/addon/install/exam`)

### School Admin (`routes/admin.php`)
- Dashboard
- School details / logo / branding
- Academic year, standards (classes), sections (arms), subjects, class links
- Students, teachers, parents — invite / CRUD / Excel import
- Attendance add + export; staff attendance
- Homework, assignments, notices, library, admissions (`/{slug}/admission-form`)
- Activity log

### Teacher (`routes/teacher.php`)
- Dashboard + timetable
- Mark attendance (bulk + per student)
- Homework / assignments + marks
- Leave, events, class student list

### Student (`routes/student.php`)
- Dashboard (tasks, events)
- Homework, assignments
- Holidays, library activity, conversations
- Change password / avatar

### Parent (`usergroup` 7, mobile/API + web)
- Linked children only (`student_parent_link`)
- Attendance, homework, notices (API: `routes/api.php`)

---

## Run locally (this machine)

PHP 8.4, Composer, and MySQL are installed via Homebrew. From `/Users/mac/Projects/crest`:

```bash
export PATH="/opt/homebrew/opt/php@8.4/bin:/opt/homebrew/bin:$PATH"
php artisan serve --host=127.0.0.1 --port=8000
```

Open http://127.0.0.1:8000/login

| Role | Login | Password | Lands on |
|---|---|---|---|
| School Admin | `demoschool@mailinator.com` | `password` | `/admin/dashboard` |
| Teacher (JSS2A form teacher) | `teacher@orangebook.ng` | `password` | `/teacher/class` then Results |
| Student (JSS2A) | `student@orangebook.ng` | `password` | `/student/dashboard` |
| Parent | `parent@orangebook.ng` | `password` | `/parent/dashboard` |
| Platform owner | `platform@orangebook.ng` | `password` | `/platform` |
| Queens College admin (2nd school) | `admin@queens.ng` | `password` | `/admin/dashboard` |
| Parent | e.g. `catharinelangosh6746@mailinator.com` | `password` | mobile API only in upstream |

From school admin, **Open desk** on a teacher, learner, or parent record opens their desk. The banner returns you to admin.

Verified: login page (title **Orangebook**), school admin dashboard (200, Demo School), student dashboard (200, attendance + homework). Login emails are disabled locally (`MAIL_STATUS=off`) so Mailtrap SMTP does not 500.

Default school slug: `demo-school`.

## Intake, Admissions, and Promotions

These three screens do different jobs and should not be mixed:

- **Admissions** (`/admin/admissions`, public `/{slug}/admission-form`) is the application pile. Approving a form marks it Approved. Those names then appear as pre-filled rows on **New intake**.
- **Intake** (`/admin/students/intake`) creates the entry-level cohort for the current year (JSS1, Primary 1, Cohort 1, or a first course). Paste or CSV, then auto-balance across arms. Login slips print after confirm.
- **Promotions** (`/admin/promotions`) moves existing learners up a level at year-end. It does not create new people.

Training firms may put the same learner in more than one course in a year (`student_academics` one row per course). Attendance and results still key off `studentAcademicLatest` (the newest row) unless a class is chosen on the register.

## What we change in this fork (small, on purpose)

- Product name **Orangebook**
- Default timezone `Africa/Lagos` for West African secondary schools
- Class seed renamed to **JSS1–JSS3 / SS1–SS3** (instead of Indian 1–12) once the first local run is green
- Exam/results + Nigerian A1–F9 scale + term report PDF — next slice
