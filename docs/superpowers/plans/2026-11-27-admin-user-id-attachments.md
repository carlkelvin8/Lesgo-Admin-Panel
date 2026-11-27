# Admin User ID Attachments Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Show registration IDs and selfie on admin User Details and remove the redundant Business Type input from Partner Edit.

**Architecture:** Use existing `document_verifications` records and `MediaUrlService` URL handling in the standalone admin panel. Keep the existing `profileImageUrl()` behavior, falling back to selfie metadata only when no profile image exists; remove only the Business Type form control.

**Tech Stack:** Laravel, Eloquent, Blade, Tailwind CSS.

**Spec:** `Lesgo-Admin-Panel/docs/superpowers/specs/2026-11-27-admin-user-id-attachments-design.md`

## Global Constraints

- Do not change registration, document upload, verification, or database schemas.
- Do not delete legacy `business_type` data or change API validation.
- Implement against standalone `carlkelvin8/Lesgo-Admin-Panel` `main`; do not replace its history with the monorepo copy.
- Do not deploy or push.

## Review Focus

- Users without any verification records must still render User Details with the current placeholder/profile behavior.
- ID document URLs may be absolute URLs or storage paths; resolve both through `MediaUrlService`.
- ID files may be image or PDF; render a preview for images and a working open/download link for every file.
- A missing or malformed selfie metadata value must not break the page or override a real profile image.
- Removing the Business Type form input must leave the stored value unchanged when a partner is edited.

---

### Task 1: Show registration selfie and uploaded IDs on User Details

**Files:**
- Modify: `app/Http/Controllers/Admin/UserController.php`
- Modify: `resources/views/admin/users/show.blade.php`

**Interfaces:**
- Consumes: Existing `User::documentVerifications()` relation, `User::profileImageUrl()`, and `MediaUrlService::publicUrl()`.
- Produces: The User Details page displays the profile selfie and each `valid_id` file with a preview/link and empty state when no IDs exist.

- [x] Load `documentVerifications` with the user in `UserController::show`, newest first, without changing existing driver/order loading.
- [x] In the User Details view, use the existing profile image URL first; if absent, resolve `metadata.selfie_url` from the latest `valid_id` verification. Keep the current initial placeholder when both are absent.
- [x] Add an “Identity Documents” card on User Details. For each `valid_id` verification, resolve every `document_urls` entry through `MediaUrlService::publicUrl`; show image previews for non-PDF image files and a link to open every original file in a new tab with safe `rel` attributes.
- [x] Preserve verification status and submitted date in the card, and show a clear empty state when the user has no uploaded ID record.
- [x] Review the changed controller/view and inspect the diff for URL fallback, PDF handling, and the empty state. Do not run tests unless requested.

### Task 2: Remove the redundant Business Type edit control

**Files:**
- Modify: `resources/views/admin/partners/edit.blade.php`

**Interfaces:**
- Consumes: Existing `category` form field and the Partner Edit form submission.
- Produces: Partner Edit shows Category without a Business Type input; no backend or stored data changes.

- [x] Remove the Business Type label and input from the form, keeping Legal Name and Category available and correctly spaced.
- [x] Review the form diff to confirm no `business_type` input is submitted and no backend/schema changes were introduced. Do not run tests unless requested.
