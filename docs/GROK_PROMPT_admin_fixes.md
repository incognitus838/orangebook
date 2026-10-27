# Prompt for Grok: Orangebook admin fixes (schools, NGOs, training firms)

You are working in a Laravel 12 / PHP 8.4 app at `/Users/mac/Projects/orangebook` (product name **Orangebook**, a fork of GegoK12). Read `ORANGEBOOK.md` first. Run it with `php artisan serve` on http://127.0.0.1:8000. Demo login: school admin `demoschool@mailinator.com` / `password` (others are listed in ORANGEBOOK.md).

## Ground rules

- **Three kinds of organisation.** `schools.kind` is `school`, `ngo` or `training`. Every feature below must work for all three.
  - Use the existing word helpers on `App\Models\School`: `learnerWord()`, `learnerWordOne()`, `staffWord()`, `staffWordOne()`, `classWord()`, `classWordOne()`, `orgWord()` and `usesParents()`. Never hard-code "Student", "Teacher", "Class" or "JSS" in views.
  - Parents exist only when `usesParents()` is true (kind = school). Hide or skip all parent features for NGOs and training firms.
- **Tenant isolation.** Every query must be scoped by `school_id = Auth::user()->school_id`, and every lookup by id must check `school_id`. Add a feature test for each new endpoint that proves school A cannot read or write school B's records.
- **Current academic year.** Scope class-related queries to `SiteHelper::getAcademicYear($schoolId)`.
- **UI.** Use the existing Orangebook UI kit, not the legacy GegoK12 Vue/Bootstrap screens:
  - layout `layouts.admin.layout`
  - classes `ob-head`, `admin-h1`, `ob-form`, `ob-form-title`, `ob-filters`, `ob-table-wrap`, `crest-table`, `crest-btn`, `ob-btn-secondary`, `ob-chip`, `ob-flash`, `partials.message`
  - Styling lives in `public/css/ob-paper.css` and `public/css/ob-saas.css`. Add small CSS to `ob-saas.css` only if needed. Use server-rendered Blade plus plain forms. Avoid new Vue components.
- **Menus.** Edit `resources/views/layouts/admin/menu.blade.php` and `resources/views/layouts/teacher/menu.blade.php`. Rail icons are mapped by `href` suffix in `ob-saas.css` (search for `/* icons (generated) */`). Add a mapping when you add a rail link.
- **Code style.** Use plain Laravel validation (`$request->validate`), redirect back with `successmessage` or `failmessage`, and write no new `try/catch` that swallows errors.
- **Don't break other code.** Do not touch `routes/api.php` or `routes/teacherapi.php` (the mobile apps use them). Keep old URLs working by adding redirects.
- **Tests.** Write feature tests in `tests/Feature`, and run `php artisan test` before you finish.

---

## 1. Parents page: filter and sort by the children's class

**File:** `/admin/parents` → `Admin\ParentController@index`, view `resources/views/admin/parent/index.blade.php`.

Today it only searches by name, email or phone, and it orders by name. The Children column shows names only.

**Build:**

1. **Class filter.** Add a `class_id` select listing the current year's StandardLinks, in the order `standard.order` then `section.name`. Show parents who have at least one child in that class, using `student_parent_link` and `student_academics.standardLink_id` for the current year.
2. **Level filter.** Add `standard_id` (for example "all JSS1 parents, every arm").
3. **Sort options.**
   - `sort=name` (default)
   - `sort=class`, ordered by the lowest class order among the parent's children, then by name
   - `sort=recent`, by `users.created_at` descending
4. **Group view.** With `view=grouped`, render one section per class: a heading with the class name and parent count, then that class's parents. A parent with children in two classes appears under both classes.
5. **Children column.** Show each child as a chip in the form "Uche Abdullahi · JSS2 A", linking to the student page.
6. **Data-quality filters.**
   - "Parents with no linked child"
   - "Students with no parent", which links to the student list filtered the same way. Add that filter to `/admin/students` too.
7. **Export.** Add a "Download CSV" button for the current filter, with columns: parent name, email, phone, child, class.
8. **Pagination.** Paginate at 50 rows and keep the query string. Show the count in the heading.

**Tests:**
- The filter returns only parents of that class.
- Grouped view lists a parent under each child's class.
- Another school's parents never appear.

---

## 2. Classes page: add levels, arms and subjects in place

**File:** `/admin/standardlinks` → `Admin\StandardsLinkController@index` / `storeSimple` / `addSubject`, view `resources/views/admin/school/standardlinks/index.blade.php`.

Today you can only pick an existing Year (`standards`) and Arm (`sections`). Creating a new level or arm means the legacy `/admin/settings/standards` Vue screen. Subjects can only be added one class at a time, and only together with a teacher. There is no school-wide subject list.

**Build three tabs (or sections) on one page: Classes, Levels & arms, Subjects.**

### A. Levels & arms

- List the school's `standards` in `order`. Admins can:
  - add one
  - rename one
  - reorder with up/down buttons that update `order`
  - archive one (`status = 0`, refused if students are in it this year)
- List `sections` (arms) the same way, with add, rename and archive.
- **"Start from a preset" button,** shown only when the school has no standards yet, or as "add missing" otherwise:
  - School, Nigerian secondary: JSS1, JSS2, JSS3, SS1, SS2, SS3
  - School, primary: Creche, Nursery 1, Nursery 2, Primary 1 to Primary 6
  - School, both: primary plus secondary
  - NGO: Cohort 1, Cohort 2, Cohort 3 (editable)
  - Training firm: Beginner, Intermediate, Advanced (the admin can rename these to course names)
- Move the preset lists out of `App\Services\SchoolOnboardingService` (around line 120) into one place, for example `App\Support\LevelPresets`, so onboarding and this screen share them.
- Mark one or more levels as **entry levels** (new boolean `standards.is_entry`, migration needed):
  - preset defaults are JSS1 and SS1 for secondary, Creche or Nursery 1 and Primary 1 for primary, and the first cohort or level for NGO and training
  - section 4 uses this

### B. Classes

- Keep the current table.
- In the add-class form, let the admin pick **several arms at once** for a level, creating one StandardLink per arm (for example JSS1 → A, B, C).
- Make the form teacher **optional** (nullable). The current code requires one, which blocks setup before staff exist. Show "No form teacher" as a warning chip.
- Add a **"Copy classes from last year"** action. It creates the same level/arm StandardLinks for the current academic year, plus their subjects.

### C. Subjects

- **School-wide subject catalogue** for the current year: add, rename and archive subjects, with an optional code and type (core / elective).
  - Today `subjects` are stored per standard and section. Keep that schema: the catalogue can be the distinct names, or add a `subject_catalog` table if that is cleaner. Explain the choice in a short comment.
- **"Apply subjects"** form: choose subjects (multi-select) × levels or classes (multi-select). Create the Subject rows and `teacherlinks` with `teacher_id` **nullable**. A teacher is assigned later.
- **Per class:** keep the current chips, and let the admin change a chip's teacher with an inline select. Show "Unassigned" chips in a warning style.
- **Bulk subject entry:** a textarea taking one subject per line.

**Tests:**
- Creating a level, arm and subject.
- Applying 5 subjects to 3 classes creates 15 links.
- A duplicate subject on a class is refused.
- Everything is tenant-scoped.

---

## 3. Merge Notice board and Circulars into one "Announcements" page

**Today:**
- `/admin/mail` (`Admin\CircularController@create/store`, view `crest.mail.compose`) emails people and says "They will also see it on the notice board".
- `/admin/notices` (`Admin\NoticeBoardController@index/storeNotice`, view `crest.board.notices`) posts to the board with the same audience picker (`App\Services\RecipientService`).
- Teachers have both too (`routes/teacher.php` lines ~16–23 and ~440).
- The two forms duplicate the audience UI, and the rail shows both.

**Build** a single **Announcements** page at `/admin/announcements` (and `/teacher/announcements`):

1. **One compose form:**
   - title
   - message
   - audience: the existing picker (everyone / role groups / people groups / class plus "students|parents|both" / named people)
   - template picker (from `CircularTemplates`)
   - **delivery:** Notice board only · Notice board + email. SMS is off unless the school has an SMS setting; leave a hook.
   - live recipient count (reuse `/mail/count`)
   - **schedule for later** is optional; skip it if it needs a queue worker. Note it as a TODO.
2. **Validation and storage:**
   - Board-only posts keep the existing 240-character limit on the board summary.
   - Emailed posts allow 5000 characters, and the board shows the first 240 with "Read more".
   - Store one record in the notices or board table and link it to the `SendMail` log row, so one post is not two separate things. Add a nullable foreign key if needed.
3. **Feed below the form:**
   - every announcement, newest first
   - each card shows audience summary, channel badges (Board / Email), sent count, author and date
   - actions: edit (board text only), unpin or archive, delete
   - filters: channel, class, author
4. **Redirects:**
   - `/admin/mail`, `/admin/notices`, `/admin/mail/log`, and the teacher equivalents redirect to the new page (the log becomes the feed).
   - Keep POST endpoints working for one release, or update every form.
5. **Rail:** replace "Circulars" and "Notice Board" with one item, **Announcements**, in the admin and teacher menus. The icon mapping is `[href$="/announcements"]` → megaphone.
6. **Read side:** students and parents keep their read views (`/student/notices`, `/parent/circulars`), but these read from the merged data. Rename the labels to "Announcements".

**Tests:**
- Board-only creates no mail.
- Board + email creates both and links them.
- The audience resolves only to the same school.
- Old URLs redirect.

---

## 4. Adding students: single, bulk, CSV import and entry-level intake

**Today:**
- `/admin/student/add` (`Admin\StudentController@create/store`, view `resources/views/admin/member/create.blade.php`) adds one student and has these problems:
  - **email is required**, which is impossible for many JSS1 or primary pupils and NGO learners
  - the password is hard-coded to `password`
  - an empty phone becomes a **fake number** (`'0805'.random_int(...)`)
  - the heading says "Add student" even for NGO and training firms
- The legacy CSV import (`/admin/import`, `ImportMemberController` + `App\Imports\UsersImport`) is GegoK12's.
  - It is not linked in the Orangebook UI and expects the legacy column set.
  - `downloadFormat()` lists `Standard::orderBy('name')` **without a school_id scope**, which leaks other schools' class names.
  - `importUsers()` swallows exceptions and returns nothing.
- `users.name` is **globally unique** (migration `2020_02_18_000000_create_users_table.php`). That is why names get random number suffixes, and student, teacher and parent pages are routed by name (`/admin/student/show/{name}`). Two schools cannot both have an "Uche Abdullahi".

**Build:**

### A. Identity fixes (do these first)
1. Drop the global unique index on `users.name`. Instead add a unique admission number per school (`userprofiles.registration_number`, or a new `users.admission_no` with a unique index on `(school_id, admission_no)`). Auto-generate it when blank using a school prefix and year plus a sequence, for example `IKJ/26/0001`.
2. Make `email` optional for learners. Students without email log in with **admission number + PIN**, or join via the existing `/join/student` school-code flow.
3. Stop inventing phone numbers. Allow null.
4. Stop setting `password` for everyone. Generate a random one-time PIN or password per learner and show it **once** on the result screen, with a printable "login slips" page (one slip per learner: name, class, admission no, PIN, school code).
5. Add id-based routes next to the name-based ones (for example `/admin/students/{id}`). Make new links use ids, and keep the name routes as redirects.

### B. Single add (rework the form)
- Fields:
  - first name, last name, other names
  - gender (needed for the dashboard boys/girls chart)
  - date of birth (optional)
  - class (StandardLink)
  - admission no (optional, auto if blank)
  - email (optional)
  - phone (optional)
  - **parent** block, only if `usesParents()`: parent name, email, phone, relationship. Link an existing parent by email if one exists.
- Use the word helpers in labels and headings.

### C. Bulk add (paste a grid)
- `/admin/students/bulk`:
  1. choose class
  2. paste rows (from Excel or Sheets) with columns: first name, last name, gender, admission no, email, phone, parent name, parent email, parent phone
  3. **preview table** with per-row validation: duplicates within the paste, duplicates in the DB, bad emails, unknown class
  4. confirm
  5. result screen with created / skipped counts and a link to print login slips
- Parent columns are ignored when `usesParents()` is false.

### D. CSV / XLSX import
- `/admin/students/import`:
  - "Download template" builds a CSV with the headers above plus a `class` column. Class values are matched against **this school's** StandardLinks by name, for example "JSS1 A".
  - Upload goes through `maatwebsite/excel`, which is already installed.
  - Same preview, confirm and result flow as bulk add.
  - Rows can target different classes. Unknown classes are flagged, not silently skipped.
  - Map common header variants: "Surname", "Last Name", "Class/Arm", "Adm No", "Admission Number".
  - Process up to 2,000 rows in chunks inside a transaction per chunk.
  - Errors are listed per row and offered as a downloadable CSV.
- Retire the legacy `/admin/import` page with a redirect. Remove or fix `UsersImport` and the unscoped `downloadFormat`.

### E. Entry-level intake (JSS1 / Primary 1 / new cohort)
- `/admin/students/intake` is a guided screen for bringing in a new intake for the current academic year:
  1. choose an **entry level** (levels marked `is_entry` in section 2A)
  2. add learners by paste or CSV, with the same parser as C and D, without choosing an arm yet
  3. **arm allocation:** Auto-balance across the level's arms (option: balance by gender), Alphabetical split, or Manual (a dropdown per row)
  4. confirm, creating users, admission numbers, `student_academics` rows and parent links
  5. result screen with login slips and a "Send welcome announcement to these parents" button, which goes to section 3 with the audience prefilled
- Link it from `/admin/students` ("New intake") and from the dashboard during the first 4 weeks of a new academic year.
- Make sure it fits with the existing **Promotions** (`/admin/promotions`) and **Admissions** (`/admin/admissions`, public `/{slug}/admission-form`):
  - an approved admission should appear in the intake list as a pre-filled row
  - promotion moves existing learners up, while intake creates the entry level
  - write a short note in ORANGEBOOK.md explaining the three

### F. Kind differences
- **NGO:** "Learners", "Cohorts", "Facilitators". No parent fields. Entry level is usually Cohort 1. Admission numbers use a prefix like `PRG/26/0001`.
- **Training firm:** "Learners" or "Trainees", with levels named by course. Allow the **same learner in more than one cohort or course at once**: one `student_academics` row per course for the year. Check `SchoolDayService` and `ResultsService` still work with that, or block it and say why.
- **School:** parents enabled, plus WAEC results as today.

---

## 5. Teachers and other staff: bulk and CSV the same way

**Today:**
- `/admin/teacher/add` (`TeacherAddController@create/store`, view `admin/teacher/create.blade.php`) has a **required Class** field, hard-codes `password`, and fakes phone numbers.
- Bulk invite exists (`CrestOpsController@storeTeacherBulk`, `POST /admin/people/staff/bulk`, pasting "name, email" lines), but its page `GET /admin/people` now **redirects to `/admin/teachers`**, which has no bulk form. The feature is unreachable. The same is true of bulk parent linking (`POST /admin/people/parent/bulk`).
- Non-teaching staff live in legacy `StaffController` pages (`/admin/staffs`, `/admin/staff/add`). They are not in the rail and use the old Bootstrap UI.
- The legacy teacher import (`/admin/import/teacher`, `App\Imports\TeachersImport`) is not linked.

**Build:**
1. **`/admin/teachers` (label from `staffWord()`)** gets three entry points:
   - **Add one:** name, email (required for staff, since they log in by email), phone, role (teaching / non-teaching), optional form-teacher class, optional subjects. Send an **invite** through `SchoolJoinService::inviteStaff` instead of setting a password.
   - **Bulk paste:** the same preview and confirm flow as 4C, with columns name, email, phone, role, subjects. This replaces the unreachable people page, and `storeTeacherBulk` is reused or replaced.
   - **CSV import:** the same as 4D with a template download.
2. **Non-teaching staff.** Either fold them into this page with a Role filter (Teaching / Non-teaching / All, using the existing usergroup ids for staff), or add a "Staff" rail item built with the new UI kit. Retire the legacy `/admin/staffs` screens with redirects.
3. **Parent bulk linking.** Move it to `/admin/parents` ("Import parents" → paste or CSV with columns parent name, email, phone, child admission no or child name, relationship). Hide this for NGOs and training firms.
4. **Teacher subjects.** Show the teacher's classes and subjects on their row, as chips from `teacherlinks`.

**Tests:**
- Bulk invite creates invites, not passwords.
- Duplicates are skipped.
- A CSV with a bad row reports that row and imports the rest.
- Tenant scope holds.

---

## 6. Broken or orphaned admin routes found in the audit (fix or remove)

The audit ran every parameter-less `GET` in `routes/admin.php` as a school admin on 27 Oct 2026. Fix these:

| Route | Problem | Action |
|---|---|---|
| `GET /admin/absentees/teacher` | 500: method missing on `AttendanceController` | implement it or remove the route |
| `GET /admin/student/export` | 500: `array_values()` on null when no POST body | redirect GET to the list, or make export work with the current filters |
| `GET /admin/teacher/export` | 500: same as above | same as above |
| `GET /admin/staff/export` | 500: same as above | same as above |
| `GET /admin/students/blockedstudents` | 500: `count()` on null | fix it, or replace it with a "Status: blocked" filter on `/admin/students` |
| `GET /admin/people` | redirects to `/admin/teachers`, so the bulk staff/parent forms are unreachable | see section 5 |
| `GET /admin/invite-teacher`, `/admin/link-parent` | redirect chain to `/admin/teachers` | point them at the new add/invite and parent-import screens |
| `GET /admin/import`, `/admin/import/teacher`, `/admin/downloadformat` | legacy UI; template leaks other schools' class names (unscoped `Standard::orderBy`) | replace with 4D and 5 |
| `GET /admin/billing` | silently redirects to the dashboard | decide: show the plan, or remove it from routes |
| `GET /admin/settings/generalsettings` | redirects to `/admin/marks` | fine if intended; otherwise rename the link |
| `GET /admin/settings/standards` | legacy Vue screen for levels | replace with section 2A and redirect |
| `GET /admin/staffs*`, `/admin/staff/add` | legacy UI, not in the rail | see 5.2 |

Also run the same audit for `routes/teacher.php`, `routes/student.php` and `routes/parent.php`:
1. Write `tests/Feature/RouteSmokeTest.php`. It logs in as each demo role and requests every `GET` route without parameters, plus a seeded id for `{id}` routes.
2. It asserts the status is not 500.
3. It treats routes blocked by `App\Http\Middleware\BlockUnusedForkRoutes` (404 by design) as passing.

---

## 7. Acceptance checklist

- [x] As a school admin I can:
  - create JSS1 A/B/C in one go
  - add 9 subjects to all three arms in one go
  - run an intake of 120 JSS1 students from a CSV, auto-balanced across arms
  - print their login slips
  - see their parents grouped by class on `/admin/parents`
- [x] As an NGO admin I see "Learners", "Cohorts" and "Facilitators", no parent fields anywhere, and can import learners into Cohort 1 by CSV.
- [x] As a training firm admin I can rename levels to courses and put one learner in two courses.
- [x] One "Announcements" item in the admin and teacher rails. Old Circulars and Notices URLs redirect.
- [x] No route in the RouteSmokeTest returns 500.
- [x] No new code sets the password `password` or invents phone numbers.
- [x] `php artisan test` passes, and each new endpoint has a cross-tenant test.
- [x] ORANGEBOOK.md is updated with the new screens and the Intake / Admissions / Promotions note.

Work in small commits, one section at a time, in this order:
1. 4A (identity)
2. 2
3. 4B–4F
4. 5
5. 1
6. 3
7. 6

After each section, list the files you changed and how to check them by hand in the browser.
