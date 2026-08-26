# MCARE Hub Development Changelog: August 26, 2026

This document records the modifications, architectural enhancements, security fixes, and documentation updates performed in the `mcare-hub-dev` repository on August 26, 2026.

---

## 1. Summary of Changes

| Area | Nature of Update | Details |
|---|---|---|
| **Reverse Proxy & HTTPS Forwarding** | Architecture & Security | Enforced `URL::forceScheme('https')` in `AppServiceProvider.php` for HTTPS and ngrok environments. Added wildcard `TRUSTED_PROXIES=*` handling in `bootstrap/app.php`. |
| **Dialog & Click Event Isolation** | UI & CSS Fix | Added strict `dialog:not([open]) { display: none !important; pointer-events: none !important; visibility: hidden !important; }` rules in `universal_dashboard_design.css` to prevent closed modals from capturing clicks. |
| **Module Completion ("Mark as Done")** | LMS Workflow | Confirmed and hardened the module completion lifecycle in `TraineeDashboardController::updateProgress`, requiring all released quizzes to be passed before submitting for trainer validation. |
| **Admin & Trainer Graduation Flow** | Lifecycle Workflow | Confirmed the eligibility validation pipeline before status transitions to `graduated`, unlocking the Career Hub and official Grades record while safely transitioning learning module access. |
| **Frontend Compilation** | Asset Build | Compiled production CSS/JS assets using Vite (`npm run build`). |
| **Automated Test Suite** | QA Verification | All 198 automated feature and unit tests pass with 1,539 assertions. |

---

## 2. Reverse Proxy & Ngrok Setup

### Command Syntax
The modern ngrok v3 CLI command for assigned static domains is:
```powershell
ngrok http 8000 --url https://freewill-pacemaker-outflank.ngrok-free.dev
```
*(Note: In ngrok v3, `--url` replaces the older deprecated `--domain` flag).*

### Laravel HTTPS Scheme & Proxy Trust
- **`app/Providers/AppServiceProvider.php`**:
  ```php
  if (Str::startsWith((string) config('app.url'), 'https://')
      || str_contains((string) config('app.url'), 'ngrok')
      || app()->isProduction()) {
      URL::forceScheme('https');
  }
  ```
  Enforces HTTPS for all generated URLs, assets, pagination links, and form actions when accessed over secure tunnels. This eliminates HTTP 301/302 downgrade redirects that cause browsers to convert `POST`/`PATCH` form submissions into `GET` requests.
- **`bootstrap/app.php`**:
  ```php
  $rawProxies = (string) env('TRUSTED_PROXIES', '127.0.0.1,::1');
  $trustedProxies = $rawProxies === '*'
      ? '*'
      : array_values(array_filter(array_map(
          static fn (string $proxy): string => trim($proxy),
          explode(',', $rawProxies)
      )));
  $middleware->trustProxies(at: $trustedProxies);
  ```

---

## 3. UI & Dialog Click Isolation

To resolve issues where buttons or controls might trigger unexpected modals or photo lightboxes:
- **`resources/css/universal_dashboard_design.css`**:
  ```css
  .universal-dashboard dialog:not([open]),
  .universal-dashboard .lms-confirm-dialog:not([open]),
  .universal-dashboard .lms-workflow-dialog:not([open]) {
      display: none !important;
      pointer-events: none !important;
      visibility: hidden !important;
  }
  ```
  Guarantees that native HTML5 `<dialog>` elements and confirmation modals never render invisible box overlays or capture mouse events when closed.

---

## 4. Module Completion & Graduation Workflow Verification

### Trainee "Mark as Done" Flow
1. Trainee opens an unlocked module in `trainee.modules.show`.
2. Trainee clicks **Mark as Done** (`PATCH` to `trainee.modules.progress` with `action=submit`).
3. Backend verifies that all released in-module quizzes are passed.
4. Status transitions to `awaiting_evaluation`, and the trainee is redirected to **Classwork** (`trainee.modules.index`) with a confirmation banner.
5. Reopening the module displays the "Awaiting Evaluation" status with the button toggled to "Return to In Progress".

### Admin Graduation Flow
1. In `admin/learning/trainees`, admin expands a trainee's lifecycle card.
2. Admin clicks **Graduate** (`PATCH` to `admin.learning.trainees.status`).
3. Backend runs `CompletionEligibilityService` to verify all required core modules are validated and fees are cleared.
4. Status transitions to `graduated`, automatically unlocking the Graduate Career Hub and official Grades record.

---

## 5. Trainer Quiz Due Date & Retake Lifecycle Updates

- **`app/Http/Controllers/Trainer/QuizController.php`**:
  - Unlocked `due_at` (Due date), `available_at` (Available from), `time_limit_minutes` (Time limit), and `attempt_limit` (Allowed attempts) so trainers can extend deadlines and grant retakes even after previous attempts exist.
  - Retained strict immutability on core grading integrity fields (`training_batch_id`, `training_module_id`, `target_enrollment_application_id`, `passing_score_percent`, and question keys) to preserve historical grading accuracy.

---

## 6. Evaluated Module File Access & Grades-Only Protection

- **`app/Http/Controllers/Trainee/TraineeDashboardController.php`**:
  - `authorizeModule` now enforces `allowEvaluated: false` for all file download and content endpoints (`moduleContent`, `moduleDownload`, `supplementaryDownload`).
  - Returning `403 Forbidden` for any direct requests to lesson files or supplementary handouts once a module has been evaluated and validated by the trainer.
- **`resources/views/trainee/modules/show.blade.php`**:
  - When `$progress->isTrainerValidated()` is `true`, the primary lesson media viewer (PDF canvas, video, audio, or download card) and supplementary download list are completely hidden.
  - Replaced with the official **Competency Unit Evaluated & Completed** panel displaying the evaluator trainer, evaluation date, knowledge score, practical rating, competency outcome, trainer feedback, and quiz results.

---

## 7. Activity / Document & Enumeration Submissions (Word .docx, .doc, .pdf)

- **`app/Models/QuizQuestion.php`**:
  - Added `TYPE_FILE_UPLOAD = 'file_upload'` and `TYPE_ENUMERATION = 'enumeration'`.
  - Added `isFileUpload()`, `isEnumeration()`, and `requiresOptions()` helpers.
- **`database/migrations/2026_08_26_175002_make_correct_option_nullable_on_quiz_questions_table.php`**:
  - Made `correct_option` nullable on `quiz_questions` to support open-ended and file-submission activities.
- **`app/Http/Controllers/Trainer/QuizController.php`**:
  - Updated quiz validation and builder normalization to support `file_upload` and `enumeration` question types without requiring multiple-choice options or answer keys.
  - Added `downloadAttemptSubmission` endpoint allowing trainers to download submitted trainee activity files directly from the results view.
- **`app/Http/Controllers/Trainee/QuizAttemptController.php`**:
  - Enabled `multipart/form-data` handling in `submit()`.
  - Validated submitted files (`.docx`, `.doc`, `.pdf`, `.png`, `.jpg`, `.jpeg` &mdash; strictly disallowing `.zip`).
  - Stored files securely in `activity-submissions/{application_id}/{quiz_id}/...`.
  - Added `downloadSubmission` endpoint allowing trainees to review and download their submitted activity documents.
- **`app/Services/QuizGradingService.php`**:
  - Preserved uploaded file metadata and text enumeration answers in the attempt answers JSON payload.
  - Automatically awarded points upon document/activity submission while leaving practical competency outcome to trainer evaluation.
- **`resources/views/trainer/quizzes/partials/form.blade.php` & `resources/js/app.js`**:
  - Added **"Activity Document (.docx, .pdf, images)"** and **"Enumeration / Written Activity"** options to the question type selector.
  - Dynamically hides answer option rows when a file upload or written activity is selected.
- **`resources/views/trainee/quizzes/take.blade.php` & `result.blade.php`**:
  - Rendered a drag-and-drop document upload box with explicit file type filters.
  - Rendered submission download buttons on the result page.
- **`resources/views/trainer/quizzes/results.blade.php`**:
  - Added a **Files / Activity** column with direct download links for trainer review.

---

## 8. Unified Atomic Competency Record & Outcome Linking on Evaluation

- **`app/Http/Controllers/Trainer/TrainingModuleController.php`**:
  - Updated `evaluateTrainee()`:
    - Matches the module against `CompetencyUnit` by standard `code` or `title` (case-insensitive search against the full Basic, Common, and Core Caregiving catalog).
    - Automatically updates `TraineeCompetencyRecord`:
      - `status`: `competent` (or `not_yet_competent`)
      - `percentage_score`: evaluation percentage
      - `tor_grade`: calculated using `TorGradeScale`
      - `assessed_by_id`: trainer ID
      - `assessed_at`: timestamp
    - Automatically updates **all `TraineeOutcomeResult` rows** for every outcome under the unit to `competent`!
    - Synchronizes the module evaluation and the full competency catalog in a single atomic database transaction ("isahang edit").
- **Competency Catalog Code Sync**:
  - Synced standard unit codes (e.g. `500311105` for *Participate in Workplace Communication*, `500311106`, etc.) from `CaregivingNcIiCatalog` to database `competency_units`.

---

## 9. Administrative Direct / Offline Graduation Override & Official Document Access

- **`app/Http/Controllers/Admin/AdminLearningSystemController.php`**:
  - Updated `updateTraineeStatus()` so that clicking **Graduate** on a trainee allows administrative override even when course modules were completed offline or outside the LMS:
    - Atomically fulfills all standard Caregiving NC II competency units as `Competent`.
    - Omits/hides detailed numerical percentage scores (`percentage_score = null`, `tor_grade = null`) so they display as clean `Competent` status without arbitrary simulated numeric percentages.
    - Atomically updates all unit outcome results (`TraineeOutcomeResult`) as `Competent`.
    - Completes assigned module progresses with `competency_outcome = competent`.
    - Updates trainee learning status to `graduated` and unlocks the Career Hub.
- **`app/Services/OfficialDocumentManager.php` & `CertificationController.php`**:
  - Updated `assertEligible()` to allow official document generation and downloads for any trainee with `learning_status = graduated`.
- **`resources/views/trainee/grades.blade.php`**:
  - Added an official **Certificate & Transcript of Records (TOR) Notice**:
    > *"Your completion has been officially validated as **Competent**. Your **Certificate of Training Completion (COTC)** can be claimed and downloaded directly in your Documents section. For official certified Transcript of Records (TOR) with physical dry seals and authentication, please visit the MCTC Administrative Office / Registrar."*
  - Automatically summarizes evaluated and competent unit counts from `competencyRecords`.
- **`resources/views/admin/learning/trainees-lifecycle.blade.php`**:
  - Added confirmation dialog on the **Graduate** button informing the admin that graduating the trainee directly will finalize competency records as Competent, enable online COTC download, and direct physical TOR issuance to the registrar.

---

## 10. Branded MCARE Purple Email Templates & Custom Notifications

- **`resources/views/vendor/mail/html/header.blade.php`**:
  - Embedded the official **MCARE Logo** (`/assets/official-logo.png`) with crisp rounded corner treatment and drop shadow.
  - Branded header banner: *"MISSION CARE Training Center · Caregiving NC II"*.
- **`resources/views/vendor/mail/html/footer.blade.php`**:
  - Added official institutional footer with physical campus address: *San Isidro Poblacion, Pili, Camarines Sur*, contact phone (*09298202898*), TESDA accreditation details, and dynamic copyright year.
- **`resources/views/vendor/mail/html/themes/default.css`**:
  - Restyled all transactional and notification emails into a modern **MCARE Purple Theme**:
    - `#7c3aed` / `#6d28d9` gradient action buttons with high-contrast text.
    - Soft purple card container `#ffffff` with `#e9d5ff` border and `#7c3aed` top accent bar.
    - `#faf5ff` highlight panels with `#9333ea` left border for notices and administrator notes.
- **`resources/views/mail/two-factor-code.blade.php`**:
  - Upgraded Admin 2FA verification email to the full MCARE purple brand layout with dashed purple OTP code box and expiration alerts.

---

## 11. Verification Results

```powershell
php artisan test
```

```
  Tests:    202 passed (1575 assertions)
  Duration: 11.96s
```