# Landing and admin visual QA

## Source visual truth

- Landing/header issue: `C:\Users\NGBFVDCSXZ\AppData\Local\Temp\codex-clipboard-ee0a023e-7c05-4e97-b89e-313faa9e58ef.png`
- Admin dashboard issue: `C:\Users\NGBFVDCSXZ\AppData\Local\Temp\codex-clipboard-3c4f3f47-0641-402d-b8d7-2cd46bf44329.png`
- Supplied dashboard/landing mark: `C:\Users\NGBFVDCSXZ\AppData\Local\Temp\codex-clipboard-24fb4ca1-dae6-4e3f-ac70-3a7060c0cfe7.png`

## Implementation evidence

- Local implementation: `http://127.0.0.1:8002/` and `http://127.0.0.1:8002/admin`
- Landing desktop capture: `C:\Users\NGBFVDCSXZ\Documents\Capstone Project\mcare-ui-edit\landing-postfix-1920.png`
- Admin desktop capture: `C:\Users\NGBFVDCSXZ\Documents\Capstone Project\mcare-ui-edit\admin-final-1920.png`
- Landing before/after comparison: `C:\Users\NGBFVDCSXZ\Documents\Capstone Project\mcare-ui-edit\landing-before-after.png`
- Admin before/after comparison: `C:\Users\NGBFVDCSXZ\Documents\Capstone Project\mcare-ui-edit\admin-before-after.png`
- Desktop viewports: 1920 x 1035 landing, 1920 x 1080 admin.
- Responsive viewports: 1440 x 1024 and 390 x 844.
- States: authenticated admin for landing/account menu and admin dashboard; mobile navigation opened and closed.

## Full-view comparison

- Landing: passed. The two-tier announcement/header and three competing account actions were replaced by one solid navigation row and one compact role-aware account menu. The primary landing content and the user's mobile work remain intact.
- Admin: passed. The dark/glassy shell, oversized radii, gradients, heavy shadows, and repeated black weights were replaced by the trainer dashboard's white rail, warm neutral canvas, restrained borders, standard bold typography, and compact purple accents.
- Logo use: passed. The supplied mark is used on landing and authenticated admin/trainer/trainee shells. Official logo references remain on login, enrollment/registration, payment, and receipt surfaces. The admin login conditionally keeps the official logo.

## Focused-region comparison

- Header: the desktop brand, navigation, account identity, dropdown affordance, and mobile menu were inspected at full resolution. No overlap or viewport overflow was detected.
- Admin shell: sidebar hierarchy, header search/account controls, overview metrics, schedule panel, action table, and payment queue were inspected at full resolution. The content order and working routes were preserved.
- Mobile: landing and admin at 390 x 844 have no horizontal page overflow. The landing menu opens and exposes the authenticated account state; admin primary navigation remains available as four compact tabs.

## Required fidelity surfaces

- Fonts and typography: passed. Admin now uses the same Inter/Nunito stack and practical `font-semibold`/`font-bold` hierarchy as the trainer shell; excessive `font-black` is normalized inside the dashboard.
- Spacing and layout rhythm: passed. Header height is reduced, admin rail width aligns with the trainer shell, radii are normalized to 8-12px, and major panels use simple borders without stacked elevation.
- Colors and tokens: passed. Opaque white and `#faf9f7` replace translucent/glassy surfaces; purple remains an accent for active states and primary actions.
- Image quality and asset fidelity: passed. The supplied 1254 x 1254 raster mark is used directly without redrawing or substituting it. Existing official logo assets remain on authentication/registration surfaces.
- Copy and content: passed. No admin metrics, queues, schedules, table columns, role identity, or landing content were removed.
- Icons and accessibility: passed. New shell controls use the installed Font Awesome family, semantic links/buttons/details, visible labels, alt text, and practical mobile tap targets.

## Comparison history

1. Initial comparison found a P2 landing hero timing gap: the carousel could be fully blank immediately after navigation.
2. Fixed the keyframe start state so the first real training image is visible immediately and transitions overlap instead of leaving an empty frame.
3. Post-fix evidence: `landing-postfix-1920.png` shows the training image at initial desktop capture; no horizontal overflow and no console errors were detected.

## Validation

- `php artisan view:cache`: passed.
- `php artisan test`: 22 tests passed, 81 assertions.
- `npm.cmd run build`: passed.
- Browser console: no errors or warnings on the admin dashboard.
- Primary interaction: mobile landing navigation opened and exposed the active account state.

## Remaining polish

- P3: The supplied mark has a white raster background. It blends cleanly on the new white surfaces, but a future official transparent export would improve flexibility on non-white backgrounds.

final result: passed

---

# MCARE Login Redesign QA

## Scope

- Source reference: `design-qa/reference-login-desktop.png`
- Final desktop capture: `design-qa/implementation-login-desktop.png`
- Final mobile capture: `design-qa/implementation-login-mobile.png`
- Same-view comparison: `design-qa/comparison-reference-vs-implementation.png`
- Page under test: `http://127.0.0.1:8000/login`

## Visual comparison

The final desktop implementation was captured at the reference viewport of 1448 × 1086 and placed beside the supplied source image in one comparison canvas. The outer shell, column split, logo scale, welcome copy, dashboard-preview crop, wave placement, form-card width, vertical alignment, radii, border treatment, and purple brand palette match the source design intent.

The final form card measured 530 × 733 at x=818, y=178. This closely matches the source card at approximately 530 × 730 at x=819, y=177. The dashboard preview uses the supplied second image as a real raster asset. The MCARE mark is a lossless crop of the existing project logo, and the wave background is a dedicated high-resolution raster asset.

Deliberate product-preserving differences:

- The applicant-enrollment and historical-alumni recovery links remain available beneath Google sign-in.
- The reference's inactive Forgot password label was not added because the application has no password-reset route.
- Non-functional decorative dots and rings were omitted; no fake placeholder art was introduced.

## Responsive verification

| Viewport | Showcase image | Form card | Horizontal overflow | Result |
| --- | --- | --- | --- | --- |
| 1448 × 1086 | Visible | 530 × 733 at x=818, y=178 | None | Passed |
| 768 × 1024 | Hidden | 464 px wide and centered | None | Passed |
| 390 × 844 | Hidden | 351 px wide at x=12, centered | None | Passed |

At tablet and mobile widths the entire left showcase, including the second/dashboard image, is removed. The sign-in card is centered over the bottom-anchored wave background, with the compact MCARE mark retained inside the card.

## Behavior and accessibility

- Password visibility control changed the input type from `password` to `text` and back to `password`, while updating its accessible label.
- Email and password inputs retain explicit labels, autocomplete values, required state, and visible focus treatment.
- Google sign-in resolves to `/auth/google`.
- Email/password sign-in, MFA verification, notices, error rendering, current-account continuation/switching, enrollment, and alumni-claim paths remain rendered through their existing routes.
- Mobile primary controls are at least 52 px high and retain clear focus states.
- Browser console check returned no warnings or errors.

## Verification

- `npm run build`: passed.
- `php artisan test --compact`: 211 tests passed, 1654 assertions.
- Targeted authentication suite: 8 tests passed, 85 assertions.

## Iteration history

1. Captured the existing narrow login at the source viewport.
2. Built the two-column desktop composition and mobile single-card mode with real assets.
3. Corrected logo whitespace by cropping the existing mark, then refined logo scale, dashboard size, wave placement, and panel split.
4. Matched the form-card desktop position and dimensions, rechecked desktop/tablet/mobile overflow, and reran behavior and regression tests.

final result: passed
