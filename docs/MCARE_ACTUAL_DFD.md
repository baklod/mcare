# MCARE Actual Data Flow Diagram

This document is based on the implemented Laravel application in the `65%-prototype` branch. It excludes Docker, hosting, XAMPP, queue infrastructure, and other deployment details because they are not logical MCARE data-flow entities.

## Implementation Boundary Confirmed

The current system has these implemented user-facing actors and workflows:

- Applicant: enrollment form, TESDA registration data, private document uploads, payment selection, PayMongo checkout, and pay-on-site receipt.
- Admin: enrollment review, document verification, account management, batch and AM/PM scheduling, payment scheduling, LMS administration, reports, audit logs, competency records, COTC/TOR generation, and Career Hub administration.
- Trainer: batch trainee roster, announcements, learning-module uploads, quizzes, competency records, achievement/progress charts, exports, and training previews.
- Trainee / Graduate: one authenticated account backed by `EnrollmentApplication`. Active trainees use the LMS, quizzes, payments, and training calendar. A graduate keeps the same account and records, while `learning_status = graduated` unlocks Career Hub and changes the available dashboard features.
- PayMongo: server-created checkout sessions and signed webhook payment updates.
- Email service: account email verification, administrator email two-factor codes, and database-backed notifications where configured.

The implementation has no separate active alumni authentication flow in the logical model. The legacy `/alumni` URLs are compatibility aliases into the shared trainee-based graduate Career Hub.

## Role-Centered View

The following five labels are the user roles/statuses that should be shown in the formal DFD. The numbered labels make the actors easy to identify across Level 0, Level 1, and any recreated diagram.

### Role legend

| Label | User role or status | What the user can do in the implemented system | Main data sent to MCARE | Main data received from MCARE |
| --- | --- | --- | --- | --- |
| **[1] Applicant** | A person who has not yet been approved for training | Complete a Caregiving NC II enrollment application, upload required documents, select on-site or online payment, continue to the payment page, and view the receipt or payment status | Personal/TESDA registration data, privacy consent, document files, password, payment choice | Validation feedback, application/payment continuation, pay-on-site receipt, PayMongo checkout result, status or document feedback |
| **[2] Administrator** | Staff member with administrative access and email two-factor verification | Review applications and documents, approve or reject applicants, manage accounts, batches, schedules, payments, modules, reports, competency records, official documents, job postings, notifications, and audit logs | Review decisions, schedules, account changes, module settings, grading/document actions, job postings, report/export requests | Queues, dashboards, progress summaries, payment/application records, generated COTC/TOR files, exports, logs, and notifications |
| **[3] Trainee** | Approved learner with an active training status | Sign in, view the training stream, schedule, modules and protected files, mark modules complete, take quizzes, view results, maintain documents, and view payment/COTC information | Login/session request, module completion, quiz attempts, document requests, payment/receipt requests | Assigned training content, announcements, schedule, quiz results, progress, feedback, and released documents |
| **[4] Trainer** | Training staff assigned to trainees or batches | View assigned trainees and schedules, publish modules and resources, post announcements, create quizzes, enter competency/achievement/progress records, review results, and export trainee or competency workbooks | Learning files and metadata, announcements, quiz questions/keys, scores, competency outcomes, export requests | Roster, batch schedule, submissions, trainee progress, results, charts, and generated workbooks |
| **[5] Alumni** | A graduate status on the same trainee account; not a separate login | Open Career Hub, mark **Available for Duty**, read privacy-minimal job opportunities, view career-related schedule/notifications, and retain profile, certificates, completed training, and history | Availability status, career feed requests, profile/session requests | Job postings, career schedules, notifications, certificates, completed training, and training history; active-training-only features are hidden |

### Important role/status rule

`[5] Alumni` is included as a user-facing DFD actor because it has a distinct post-graduation flow. In the implementation, the person keeps the same `users` record, password, account, dashboard layout, enrollment history, and documents. MCARE determines graduate access from the approved `EnrollmentApplication.learning_status = graduated`; it does not create a second alumni account, password, or authentication system. The legacy `alumni` role value is supported only for compatibility with older records.

### Role permission key

The logical DFD should show capabilities rather than every route. The current permission groups behind those capabilities are:

| User | Implemented permission examples |
| --- | --- |
| **[1] Applicant** | `enrollment.submit`, `payments.view` |
| **[2] Administrator** | `admin.access`, `enrollments.review`, `payments.verify`, `schedules.manage`, `modules.manage`, `official-documents.manage`, `accounts.manage`, `reports.export`, `logs.view`, `alumni.jobs.manage` |
| **[3] Trainee** | `trainee.access`, `modules.view`, `announcements.view`, `quizzes.take`, `progress.update`, `grades.view`, `documents.view`, `cotc.download`, `payments.view` |
| **[4] Trainer** | `trainer.access`, `trainees.view`, `modules.publish`, `quizzes.manage`, `grades.view`, `competencies.assess`, `trainees.export`, `sessions.view` |
| **[5] Alumni** | Graduate-only `alumni.jobs.view`, plus profile/document/certificate/history access inherited from the shared trainee portal |

### Role and flow diagram

This role-centered diagram is the recommended first page of the formal DFD. It shows what each user does, which MCARE process receives the data, and where the result goes next.

```mermaid
flowchart LR
    E1["[1] Applicant"]
    E2["[2] Administrator"]
    E3["[3] Trainee"]
    E4["[4] Trainer"]
    E5["[5] Alumni<br/>graduate status"]

    P1(("P1 Enrollment<br/>and payment"))
    P2(("P2 Review,<br/>accounts and schedules"))
    P3(("P3 LMS content,<br/>progress and quizzes"))
    P4(("P4 Competency,<br/>achievement and grading"))
    P5(("P5 Official documents<br/>and Career Hub"))

    D1[("D1 Users, roles<br/>and permissions")]
    D2[("D2 Enrollment,<br/>documents and payments")]
    D3[("D3 Batches,<br/>schedules and LMS records")]
    D4[("D4 Competency,<br/>grades and official documents")]
    D5[("D5 Career jobs,<br/>availability and notifications")]

    E1 -->|"Application, TESDA data,<br/>documents and payment choice"| P1
    P1 -->|"Validation, receipt,<br/>checkout and status"| E1
    P1 -->|"Applicant record for review"| P2

    E2 -->|"Review decisions, schedules,<br/>account, LMS and document actions"| P2
    P2 -->|"Queues, dashboards,<br/>reports, logs and generated files"| E2
    P2 <--> D1
    P2 <--> D2
    P2 <--> D3
    P2 -->|"Approved trainee and batch assignment"| P3

    E3 -->|"Module activity, quiz attempts,<br/>completion and document requests"| P3
    P3 -->|"Stream, files, schedule,<br/>feedback and results"| E3
    P3 <--> D3
    P3 -->|"Progress and results"| P4

    E4 -->|"Modules, announcements, quizzes,<br/>scores and competency records"| P3
    E4 -->|"Achievement/progress updates<br/>and export requests"| P4
    P3 -->|"Roster, submissions and progress"| E4
    P4 -->|"Charts, results and workbooks"| E4
    P4 <--> D4
    P4 -->|"Eligibility and completion evidence"| P5

    E5 -->|"Available for Duty status<br/>and job feed requests"| P5
    P5 -->|"Career jobs, schedules,<br/>notifications and history"| E5
    P5 <--> D5
    P5 <--> D4
```

### End-to-end role flows

1. **[1] Applicant flow:** public enrollment form -> server validation and private document storage -> applicant record and enrollment application -> payment selection -> pay-on-site receipt or PayMongo checkout/webhook -> waiting status -> administrator review -> approved trainee access.
2. **[2] Administrator flow:** secure login and two-factor verification -> review application/documents/payment -> approve or reject -> assign/manage batch and AM/PM schedule -> manage accounts and LMS setup -> monitor progress, competency and payments -> generate/release official documents -> publish Career Hub jobs and inspect logs/reports.
3. **[3] Trainee flow:** sign in with the approved account -> open role-aware dashboard -> read stream/schedule -> view assigned modules and files -> mark completion and take quizzes -> receive progress/results -> complete competency requirements -> download released COTC once when eligible.
4. **[4] Trainer flow:** sign in -> open assigned batch/trainee roster -> publish learning modules/resources and announcements -> create quizzes -> record achievement/progress and competency outcomes -> review results -> export competency workbook/chart -> provide completion evidence used by administrative document eligibility.
5. **[5] Alumni flow:** administrator marks the approved enrollment as graduated -> the same trainee account unlocks Career Hub -> alumni updates Available for Duty -> reads privacy-minimal job postings and career notifications -> retains profile, certificates, completed training and history while active training controls, quizzes and payments are hidden.

### DFD symbol legend

| Symbol in the Mermaid diagrams | Meaning in the formal DFD |
| --- | --- |
| Rectangle such as **[1] Applicant** | External user entity that sends data to or receives data from MCARE |
| Circle such as **P3 LMS content** | Logical MCARE process that transforms incoming data into an output |
| Open-ended store such as **D3** | Logical data store/database entity; the name is a grouped view of implemented tables/models |
| Labeled solid arrow | A named data flow; the label describes the payload or business result |
| **PayMongo** or **Email service** box | External service outside MCARE that exchanges data with the system |
| `[5] Alumni` note | A status-based view of the same trainee account, not a second authentication boundary |

For a formal diagramming tool, keep external entities outside the MCARE boundary, processes inside the boundary, and data stores below or beside the processes. Do not draw a direct user-to-database arrow: all role actions must pass through an MCARE process and its authorization rules.

## Level 0: Context Diagram

```mermaid
flowchart LR
    Applicant(["[1] Applicant"])
    Admin(["[2] Administrator"])
    Trainee(["[3] Trainee"])
    Trainer(["[4] Trainer"])
    Alumni(["[5] Alumni<br/>graduate status on same account"])
    PayMongo([PayMongo])
    Email([Email service / SMTP])
    MCARE((MCARE Training Management<br/>and Alumni Career Hub))

    Applicant -->|Personal data, TESDA fields,<br/>documents, payment choice| MCARE
    MCARE -->|Application status, document feedback,<br/>schedule, receipt, checkout link| Applicant

    Admin -->|Reviews, account actions, batches,<br/>modules, grades, documents, jobs| MCARE
    MCARE -->|Queues, dashboards, reports,<br/>logs, records, generated documents| Admin

    Trainer -->|Modules, announcements, quizzes,<br/>competency results, scores| MCARE
    MCARE -->|Assigned trainees, schedules, progress,<br/>submissions, charts, exports| Trainer

    Trainee -->|Login, module activity, quiz attempts,<br/>document requests and payment requests| MCARE
    MCARE -->|Learning content, feedback, results,<br/>schedule and released documents| Trainee

    Alumni -->|Available for Duty status,<br/>career feed and schedule requests| MCARE
    MCARE -->|Job postings, career notices,<br/>certificates and training history| Alumni

    MCARE -->|Checkout session request| PayMongo
    PayMongo -->|Hosted checkout result and signed webhook| MCARE

    MCARE -->|Verification and security messages| Email
    Email -->|Delivery outcome / verification action| MCARE
```

### Context flow explanation

MCARE is the single system boundary. Applicants submit their own enrollment records, while trainees and graduates consume the records that MCARE releases to them. Administrators and trainers maintain operational and educational records. PayMongo is the external payment provider; it is never trusted to update payment state through a browser return alone. The email service delivers verification and security messages, while MCARE remains responsible for validating signed links and persisting the resulting state.

## Level 1: Major MCARE Processes and Data Stores

```mermaid
flowchart LR
    Applicant(["[1] Applicant"])
    Admin(["[2] Administrator"])
    Trainee(["[3] Trainee"])
    Trainer(["[4] Trainer"])
    Alumni(["[5] Alumni<br/>graduate status"])
    PayMongo([PayMongo])
    Email([Email service / SMTP])

    P1((1. Account, authentication<br/>and role access))
    P2((2. Enrollment review<br/>and payment))
    P3((3. Batch and class<br/>scheduling))
    P4((4. LMS content,<br/>viewing and progress))
    P5((5. Quizzes, competency<br/>records and grading))
    P6((6. COTC, TOR and<br/>official documents))
    P7((7. Graduate Career Hub<br/>and notifications))
    P8((8. Reports, exports<br/>and audit logs))

    D1[(D1 Users, roles<br/>and permissions)]
    D2[(D2 Enrollment applications<br/>and uploaded documents)]
    D3[(D3 Training batches<br/>and schedules)]
    D4[(D4 Training modules<br/>and module progress)]
    D5[(D5 Announcements, quizzes<br/>and quiz attempts)]
    D6[(D6 Payment attempts,<br/>PayMongo events and receipts)]
    D7[(D7 Competency units,<br/>outcomes and results)]
    D8[(D8 Official documents,<br/>downloads and batch exports)]
    D9[(D9 Career opportunities,<br/>availability and notifications)]
    D10[(D10 Admin activity logs)]

    Applicant -->|Credentials, enrollment data| P1
    Trainee -->|Credentials, session request| P1
    Alumni -->|Same account session request| P1
    Trainer -->|Credentials, session request| P1
    Admin -->|Credentials and MFA code| P1
    P1 <-->|User identity, role and permissions| D1
    P1 -->|Verification/security mail request| Email
    Email -->|Signed verification action / delivery| P1
    P1 -->|Authenticated role-scoped session| P2
    P1 -->|Authenticated role-scoped session| P3
    P1 -->|Authenticated role-scoped session| P4
    P1 -->|Authenticated role-scoped session| P5
    P1 -->|Authenticated role-scoped session| P6
    P1 -->|Authenticated role-scoped session| P7
    P1 -->|Authenticated role-scoped session| P8

    Applicant -->|Application, files and payment method| P2
    Admin -->|Approve/reject, verify files, payment policy| P2
    P2 <-->|Application and document status| D2
    P2 <-->|Payment state and receipts| D6
    P2 -->|Checkout session request| PayMongo
    PayMongo -->|Signed payment webhook| P2
    P2 -->|Status, feedback and receipt| Applicant
    P2 -->|Approved learner record| P3

    Admin -->|Batch dates, AM/PM days, rooms,<br/>enrollment deadline| P3
    P3 <--> D3
    P3 -->|Schedule and batch assignment| P2
    P3 -->|Class calendar| Trainer
    P3 -->|Training calendar| Trainee
    P3 -->|Career calendar| Alumni

    Trainer -->|Files, metadata and publication| P4
    Trainee -->|View content, mark complete| P4
    P4 <--> D4
    P4 -->|Protected content and progress| Trainee
    P4 -->|Progress summary| Admin
    P4 -->|Assigned materials and completion| Trainer

    Trainer -->|Quiz questions, answer keys,<br/>competency outcomes and scores| P5
    Trainee -->|Quiz attempts and answers| P5
    P5 <--> D5
    P5 <--> D7
    P5 -->|Results, grades and charts| Trainer
    P5 -->|Learning readiness and competency status| Admin
    P5 -->|Quiz results and training history| Trainee

    Admin -->|Generate, release or reissue COTC/TOR| P6
    Trainer -->|Completed competency evidence| P6
    P6 <-->|Eligibility, grades and learner identity| D2
    P6 <-->|Competency and progress data| D4
    P6 <-->|Quiz completion and results| D5
    P6 <-->|Locked competency results| D7
    P6 <-->|Generated files, downloads and exports| D8
    P6 -->|Released COTC and training records| Trainee
    P6 -->|Certificates and training history| Alumni
    P6 -->|Document status and scalable exports| Admin

    Admin -->|Privacy-minimal job posting| P7
    Alumni -->|Available for Duty status and job feed request| P7
    P7 <-->|Career jobs, graduate status and availability| D9
    P7 -->|Published duties and notifications| Alumni
    P7 -->|Graduate roster and availability counts| Admin

    Admin -->|Report, roster, workbook and log request| P8
    Trainer -->|Trainee export and competency workbook request| P8
    P8 <-->|Source records| D2
    P8 <-->|Learning and competency records| D4
    P8 <-->|Scores and outcomes| D7
    P8 <-->|Audit entries| D10
    P8 -->|CSV/XLSX-style workbook, reports and logs| Admin
    P8 -->|Trainee/competency export| Trainer

    P1 --> D10
    P2 --> D10
    P3 --> D10
    P4 --> D10
    P5 --> D10
    P6 --> D10
    P7 --> D10
```

### Level 1 process notes

1. **Account, authentication and role access** reads the existing `users` and Spatie role/permission tables. Admin login also uses an email two-factor step. The trainee and graduate distinction is read from the approved enrollment application's `learning_status`, not a second alumni login.
2. **Enrollment review and payment** uses `EnrollmentApplication`, its private upload paths, `PaymentAttempt`, and `PaymongoWebhookEvent`. PayMongo webhooks are signed and idempotent; a browser return does not mark an online payment as paid.
3. **Batch and class scheduling** uses `TrainingBatch`, including enrollment deadlines, training boundaries, AM/PM day patterns, times, and rooms. Related records are checked before a batch can be deleted.
4. **LMS content, viewing and progress** uses `TrainingModule` and `ModuleProgress`. Audience, publication, availability, batch, and trainee authorization are checked on the server.
5. **Quizzes, competency records and grading** uses `Quiz`, `QuizQuestion`, `QuizAttempt`, `CompetencyUnit`, `CompetencyOutcome`, `TraineeCompetencyRecord`, and `TraineeOutcomeResult`.
6. **COTC, TOR and official documents** uses `OfficialDocument`, `OfficialDocumentDownload`, and `BatchDocumentExport`. The eligibility service combines payment, completion, published module progress, quiz results, and competency outcomes before generation.
7. **Graduate Career Hub and notifications** uses `CareerOpportunity`, `AlumniProfile`, and Laravel `notifications`. Job data is intentionally limited to estimated start date, patient gender, mobility, age, contraptions, and a basic care context.
8. **Reports, exports and audit logs** uses the source records plus `AdminActivityLog`. Exports are role-scoped and document downloads are throttled.

## Level 2A: LMS Module Completion and File Access

This decomposition is justified because the Mark as Done defect crosses the button, route, authorization, progress persistence, and admin summary.

```mermaid
flowchart LR
    Trainer([Trainer])
    Trainee(["[3] Trainee"])
    Admin([Administrator])
    P41((4.1 Authorize module<br/>audience and publication))
    P42((4.2 Serve typed protected<br/>file response))
    P43((4.3 Create or update<br/>module progress))
    P44((4.4 Read shared progress<br/>for dashboards))
    D4[(Training modules<br/>and module progress)]
    D10[(Admin activity logs)]

    Trainer -->|Upload metadata and file| P41
    Trainee -->|Open module or content URL| P41
    P41 <-->|Published module, batch and trainee scope| D4
    P41 -->|Authorized viewer request| P42
    P42 -->|PDF embed, image, video, audio,<br/>or Office open/download fallback| Trainee
    Trainee -->|Mark complete / reopen| P43
    P43 -->|One row per application + module,<br/>status, percent, timestamps| D4
    P43 --> D10
    P44 <-->|Same available-module query<br/>and progress rows| D4
    P44 -->|Completed count and percentage| Admin
```

The completion action is a `PATCH` to `trainee.modules.progress`. It uses `firstOrCreate` on the application/module pair, then updates the existing row. The response redirects explicitly to the module viewer instead of relying on the browser referrer, so a protected file request cannot accidentally become the completion action. The admin roster uses the same `TrainingModule::availableTo()` scope and the same `ModuleProgress` rows.

Supported upload and viewer behavior:

- PDF: inline PDF viewer.
- JPG, JPEG, PNG, WEBP, GIF: responsive image viewer.
- MP4, WEBM, MOV: HTML video player.
- MP3, WAV, M4A, OGG: HTML audio player.
- PPT, PPTX, DOC, DOCX: validated upload with open/download fallback because reliable inline browser rendering is not assumed.

## Level 2B: Official Documents and Scalable TOR Output

```mermaid
flowchart LR
    Trainer([Trainer])
    Admin([Administrator])
    Trainee(["[3] Trainee"])
    P61((6.1 Evaluate completion,<br/>payment and competency eligibility))
    P62((6.2 Queue COTC or TOR<br/>generation))
    P63((6.3 Render, hash and lock<br/>official document))
    P64((6.4 Release and authorize<br/>single trainee download))
    P65((6.5 Queue batch TOR<br/>export))
    D2[(Enrollment applications<br/>and payment state)]
    D4[(Module progress<br/>and quiz completion)]
    D5[(Quizzes and<br/>quiz attempts)]
    D7[(Competency records<br/>and outcomes)]
    D8[(Official documents,<br/>download ledger and exports)]
    D10[(Admin activity logs)]

    Trainer -->|Outcome results and grades| D7
    Admin -->|Generate request| P61
    P61 <-->|Approved, paid and completed state| D2
    P61 <-->|Published modules and quizzes| D4
    P61 <-->|Quiz attempts and results| D5
    P61 <-->|Competency results| D7
    P61 -->|Eligible learner| P62
    P62 -->|Queued document| D8
    P62 --> P63
    P63 -->|PDF bytes, SHA-256, locked source grades| D8
    Admin -->|Release request| P64
    P64 <-->|Document status and download count| D8
    P64 -->|One released COTC download| Trainee
    Admin -->|Batch TOR export request| P65
    P65 <-->|Eligible batch documents| D2
    P65 <-->|TOR files and expiry| D8
    P65 -->|Expiring scalable export| Admin
    P62 --> D10
    P63 --> D10
    P64 --> D10
    P65 --> D10
```

Generation is asynchronous in the implementation, and the document renderer uses server-controlled templates. The logical DFD treats rendering as part of MCARE; BrowserShot is an implementation detail of that internal process, not an external actor. COTC trainee downloads are recorded atomically. TOR generation, release, download, reissue, and batch export remain administrator-only.

## Level 2C: PayMongo Payment Confirmation

```mermaid
flowchart LR
    Applicant(["[1] Applicant"])
    PayMongo([PayMongo])
    P21((2.1 Select on-site or<br/>online payment))
    P22((2.2 Create or reuse<br/>idempotent checkout))
    P23((2.3 Verify signed webhook<br/>and update payment state))
    P24((2.4 Issue receipt or<br/>show server status))
    D2[(Enrollment applications)]
    D6[(Payment attempts,<br/>webhook events and receipt data)]
    D10[(Admin activity logs)]

    Applicant -->|Payment method| P21
    P21 -->|On-site selection| P24
    P21 -->|Online selection| P22
    P22 <-->|Attempt and idempotency record| D6
    P22 -->|Checkout session| PayMongo
    PayMongo -->|Hosted checkout and signed webhook| P23
    P23 <-->|Application/payment attempt| D2
    P23 <-->|Immutable provider event and state| D6
    P23 --> P24
    P24 -->|Receipt, expiration, or payment status| Applicant
    P21 --> D10
    P23 --> D10
    P24 --> D10
```

## Formal Diagramming Guide

When recreating these diagrams in a formal tool:

- Use rounded rectangles or circles for processes.
- Use rectangles for external entities: Applicant, Administrator, Trainer, Trainee/Graduate, PayMongo, and Email service.
- Use open-ended or parallel-line data-store symbols for the ten logical stores in Level 1.
- Label every arrow with a noun phrase describing the data, not only an action. Examples: `Enrollment application`, `Signed payment webhook`, `Module progress`, `Competency results`, and `Generated COTC/TOR PDF`.
- Keep the same-account Graduate decision visible near Account Access or Career Hub. Do not draw a separate alumni login database.
- Do not draw Docker, XAMPP, MySQL, browsers, Blade, Vite, or hosting as logical entities. They are implementation/deployment concerns rather than MCARE business data flows.

## Verification Notes

The diagrams were reconciled against the current routes, controllers, models, migrations, services, and tests. The current automated suite passes with 125 tests and 851 assertions. PayMongo and email delivery still require their production environment secrets and provider configuration before a live deployment can be claimed.
