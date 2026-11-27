# Admin User ID Attachments Design

## Intent

Make identity documents easier for admins to inspect from User Details, use the registration selfie as the profile image, and remove the redundant Business Type input from Partner Edit.

## Goals

- Show uploaded government ID files on the user's admin details page, with previews and links to open the original files.
- Use the existing registration selfie as the user's profile picture. If a profile picture is absent, fall back to the selfie URL stored in document verification metadata when available.
- Remove Business Type from the Partner Edit form while retaining existing stored values and compatibility with older records.

## Non-goals and constraints

- Do not change registration, document upload, verification, or database schemas; the API already stores registration selfies as `profile_picture` and ID uploads as `document_verifications`.
- Do not delete legacy `business_type` data or change API validation.
- The admin panel has a standalone repository (`carlkelvin8/Lesgo-Admin-Panel`) whose `main` has diverged from the monorepo copy. Implement against the standalone `main` version without replacing its history or copying the monorepo directory over it.

## Approaches

- **Embed previews and links on User Details (selected):** admins can inspect IDs without leaving the user page and open original images/PDFs as needed.
- Link only to the existing document verification page: this avoids duplicate display logic, but requires extra navigation and does not make documents visible in User Details.

The selected approach also keeps the selfie display tied to the existing profile image URL, with verification metadata as a fallback. Removing only the form field avoids data loss while making Category the single editable business classification.

## Design

Load the user's document verification records on the User Details request. Render the `valid_id` documents as labeled image previews or PDF links, preserving secure/public URL resolution through the admin panel's media URL service. Keep the profile card's current image behavior, adding the stored selfie metadata fallback only when the profile image URL is missing. Remove the Business Type control from Partner Edit; leave the model, database, and controller support intact.

## Verification

Review the resulting diff and relevant Blade/controller code. Do not run tests or deploy as part of this task unless separately requested.
