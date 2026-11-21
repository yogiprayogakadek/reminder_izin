# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Stack

Laravel 13, Livewire 4, Tailwind CSS, Alpine.js, MySQL 8.4. Native PHP without Docker. Source code only; deployment and external access are handled separately by the owner.

## Users

Admin, Secretary, and HRD in one company. Admin configures users, categories, contacts, reminder rules, templates, and the WAHA connection. Secretary and HRD enter and maintain company permits. HRD receives and acts on expiry reminders.

## Product Purpose

SIPERIZ is the single source of truth for company permit expiry. It records permits, tracks renewal history, stores optional documents, and sends WhatsApp reminders to the right contacts before a permit expires. Success means no active permit is forgotten and every reminder has a visible delivery record.

## Positioning

Company permits only, not employee documents. Categories are editable, but every permit uses the same standard fields. Notification contacts live in their own area and do not need a login. WhatsApp delivery runs through WAHA on the local network.

## Operating Context

- Single company, one operational team.
- Permits have a category, issuer, expiry date, optional number, issue date, PIC, location, notes, and optional PDF/JPG/PNG file.
- Admin, Secretary, and HRD may create and edit permits.
- Default reminders are H-30, H-14, H-7, H-3, H-1, and H-0 at 08.00 Asia/Makassar, every day. Rules are configurable.
- Reminders pause while a permit is marked as being renewed.
- WAHA runs at a private LAN address. Its base URL and credentials are configured by Admin and must not be hardcoded.
- The application code is built on one machine and deployed later on another. No Apache, NPM, SSL, DNS, or firewall configuration belongs in this build.

## Capabilities and Constraints

MVP capabilities:

- Authentication and role-based access.
- Dashboard with permits needing action and WAHA health.
- Dynamic permit categories with standard permit fields.
- Permit CRUD, optional file, archive, soft delete, and renewal history.
- Separate notification area: contacts, reminder rules, message templates, delivery log.
- Automatic reminders through scheduler and database queue.
- Manual reminder with preview and confirmation.
- WAHA settings, connection test, session status, test message, retry, and delivery log.
- In-app notifications for contacts linked to user accounts.
- Search, filters, pagination, export, and audit log.
- Responsive desktop and mobile UI with light and dark themes.

Constraints and non-goals:

- No custom fields per category in the MVP.
- No employee documents, payroll, attendance, or HRIS features.
- No multi-company tenancy.
- No WhatsApp file attachments or two-way chatbot.
- No Excel import in the MVP.
- No Docker.
- No deployment artifacts.

## Brand Commitments

- Product name: SIPERIZ.
- The owner explicitly requested a UI that closely follows the visual language of the supplied reference: a pale sky mesh background, white bento-style cards with generous rounded corners, a floating pill-shaped icon sidebar, sky-blue primary actions, compact labels, and text-bearing status pills.
- The product must carry its own name, wordmark, icon set, navigation, and information architecture. It must not copy the reference site's logo or identity.
- No real logo asset exists. Use a text wordmark and an honest placeholder mark until an official asset is supplied.

## Evidence on Hand

- No real permit records, contacts, or delivery history exist yet.
- Installation starts empty with one admin account. No demo statistics or fictional testimonials may be shown.
- The visual reference screenshots exist in `/root/.opencode/plan/` and are working references, not shipping assets.

## Product Principles

1. Expiry first: every screen makes the nearest required action obvious.
2. Trust through traceability: status, renewal history, delivery log, and audit are visible.
3. Standard fields, consistent language, no unnecessary configuration.
4. A reminder is a promise: idempotent, logged, retryable, and never silently lost.
5. Calm operational UI: dense where work happens, quiet everywhere else.

## Accessibility & Inclusion

WCAG AA contrast, visible keyboard focus, keyboard-operable controls, 44px minimum touch targets, responsive layout without horizontal overflow, and light/dark themes that both work.

## Design Dials

Dial: ENERGY 2 / RHYTHM 2 / MOTION 1.

- ENERGY 2: sky-blue primary actions and the pale mesh background give the product a recognizable voice without competing with permit data.
- RHYTHM 2: dashboard and list pages stay consistent, while per-page tab bars and the permit detail split provide deliberate breaks.
- MOTION 1: hover, focus, and short state transitions only, because operators scan expiry dates rather than watch animation.
