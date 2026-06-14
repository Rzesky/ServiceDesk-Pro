# ServiceDesk Pro Project Overview

## Project overview

ServiceDesk Pro is a lightweight PHP and MySQL help desk application. It lets customers submit support tickets from a public form and gives authenticated staff an admin area for reviewing tickets, managing customer records, assigning ownership, adding replies or internal notes, changing ticket status, and reviewing activity history.

## Business purpose

The application supports small support teams that need a simple shared queue for customer requests. Its main business value is centralizing support conversations, customer contact details, ticket priority, ticket status, and operational history so staff can respond consistently and track work over time.

## Target users

- Customers who need to submit support requests.
- Support staff who own, update, and respond to assigned tickets.
- Team leaders who create staff accounts, delete tickets, and manage ticket workflow.
- Administrators who manage staff hierarchy, workload, customer records, and recent system activity.
- Developers who maintain or extend the application.

## System architecture

The application is a traditional server-rendered PHP app backed by a MySQL database.

- Public interface: `public/index.php` handles customer ticket submission.
- Admin interface: files in `admin/` require authentication and render dashboard, ticket, customer, user management, and detail views.
- Shared includes: files in `includes/` provide configuration, database access, authentication, and common helpers.
- Database layer: PDO is used directly with prepared statements.
- Presentation layer: PHP templates output HTML and share a lightweight, responsive UI system from `assets/css/style.css`.

## Folder structure

- `admin/`: authenticated staff pages, including dashboard, tickets list, ticket opening workflow, ticket detail, customers list, customer detail, user management, login, and logout.
- `assets/css/`: shared CSS for public and admin UI.
- `assets/js/`: reserved for client-side behavior.
- `database/`: schema, seed data, and migration SQL.
- `docs/`: project documentation for maintainers and interview preparation, including tracked project docs and a local-only interview cheatsheet.
- `includes/`: configuration, PDO database connection, authentication helpers, and reusable functions.
- `public/`: public-facing ticket submission page.
- `uploads/`: uploaded ticket attachment files. User-uploaded files stay out of Git, while upload safety rules are tracked.

## Database structure

- `users`: authenticated staff accounts with name, email, password hash, role (`admin`, `leader`, or `staff`), and creation timestamp.
- `customers`: customer contact records with name, email, optional phone, and creation timestamp.
- `tickets`: support tickets with customer snapshot fields, subject, message, priority, status, assigned staff owner, soft delete timestamp, creation timestamp, and update timestamp.
- `ticket_messages`: ticket conversation entries and internal notes linked to tickets and optionally to staff users.
- `ticket_attachments`: uploaded ticket attachment metadata, including original file name, stored file name, MIME type, file size, and upload timestamp.
- `activity_logs`: audit trail entries for customer creation, ticket creation, status changes, and message additions.

Important relationships:

- `tickets.customer_id` references `customers.id` and is set to null if the customer is deleted.
- `tickets.assigned_to` references `users.id` and is set to null if the assigned staff account is deleted.
- `ticket_messages.ticket_id` references `tickets.id` and cascades on ticket deletion.
- `ticket_messages.user_id` references `users.id` and is set to null if the user is deleted.
- `ticket_attachments.ticket_id` references `tickets.id` and cascades on ticket deletion.
- `activity_logs` can link to tickets, customers, and users while preserving logs when related records are removed.

## Technologies used

- PHP for server-side rendering and request handling.
- MySQL or MariaDB for relational data storage.
- PDO for database access with prepared statements.
- HTML and CSS for the user interface, with no frontend framework.
- XAMPP-friendly local development structure.

## Security features

- Admin pages are protected with session-based authentication.
- Admin-only and leader-only workflows are enforced on the backend with role checks.
- Leaders can create staff accounts only; admins can create staff, leader, and admin accounts.
- Staff status transitions are restricted on the backend and cannot reopen tickets or move tickets back to open.
- Ticket deletion is a POST-only soft delete and is limited to leaders and admins.
- Passwords are verified using PHP password hashing APIs.
- Database reads and writes use PDO prepared statements.
- Output is escaped with `htmlspecialchars` before rendering.
- Public ticket submission uses a CSRF token.
- Public ticket submission includes a honeypot field.
- Public ticket submission applies simple session-based rate limiting.
- Ticket attachments are validated by size, extension, and MIME type before storage.
- Uploaded files are stored with unique generated names.
- The uploads directory includes Apache rules to prevent script execution.
- PDO emulated prepares are disabled.

## Current features

- Public customer ticket submission.
- Automatic customer creation when a new email address submits a ticket.
- Admin login and logout.
- Polished public and admin UI with consistent layout, navigation, cards, tables, forms, buttons, and status or priority badges.
- Dashboard with ticket counts, latest tickets, latest activity, and analytics for recent ticket volume, priority distribution, top customers, and 30-day daily averages.
- Dashboard workload tables for tickets assigned to each staff member and tickets closed by each staff member.
- Ticket list with status filtering, search, and pagination.
- Ticket ownership workflow: opening an open ticket from the ticket list moves it to `in_progress`, assigns it to the current user, and logs `ticket_opened`.
- Ticket detail page with ticket metadata, assigned staff, original message, message history, activity history, status updates, soft delete action for leaders/admins, and reply/internal note creation.
- User management page for admins and leaders, with backend role validation and password hashing.
- Customer list with total ticket counts and last ticket date.
- Customer detail page with customer profile information and all related tickets.
- Activity logging for customer creation, ticket creation, status changes, and message additions.
- Ticket attachments for JPG, PNG, and PDF files during public ticket creation.
- German interview preparation guide for explaining the project clearly in job interviews.

## Future roadmap

- Customer-facing ticket lookup and reply flow.
- Email notifications for new tickets and staff replies.
- Message-level attachments for staff replies and internal notes.
- Attachment management, including deleting old or incorrect uploads.
- Customer editing and merge tools.
- Dashboard charts and trend views when JavaScript charting is introduced.
- Full-text ticket search.
- Automated tests for core workflows.
- Deployment and backup documentation.

## Technical decisions and reasons

- Server-rendered PHP keeps the application easy to host on XAMPP or standard shared hosting.
- PDO prepared statements reduce SQL injection risk while keeping database access explicit and readable.
- Customer snapshot fields on tickets preserve submitted contact details even if the customer record changes later.
- Activity logs are stored separately from ticket messages because they represent operational events, not conversation content.
- Ticket attachments are stored on disk while metadata is stored in the database to keep the database smaller and file delivery simpler.
- Admin pages define a local `e()` helper to make safe output escaping obvious at the template level.
- The UI uses a small shared stylesheet instead of a heavy frontend framework to keep the project simple, fast, and maintainable.
- Shared design tokens in `assets/css/style.css` define the calm business palette, spacing, borders, shadows, typography, and reusable component styling.

## Important implementation details

- Admin pages must call `require_login()` before rendering protected content.
- New database queries should use `db()->prepare()` and parameter binding or `execute()` parameters.
- Any user-controlled or database-rendered value should be escaped before output.
- Ticket status values are limited to `open`, `in_progress`, `waiting`, and `closed`.
- User role values are limited to `admin`, `leader`, and `staff`.
- Staff can change `in_progress` to `waiting` or `closed`, and `waiting` to `in_progress` or `closed`.
- Leaders and admins can change tickets to any valid status.
- Deleted tickets have `deleted_at` set and are excluded from normal ticket, customer, and dashboard views.
- Ticket priority values are limited to `low`, `medium`, `high`, and `urgent`.
- Ticket attachments are limited to JPG, JPEG, PNG, and PDF files with a maximum size of 5MB.
- New customer and ticket workflows should write activity log entries when they create meaningful audit events.
- This document should be updated after each completed feature so it remains a reliable project briefing.
- `docs/interview-cheatsheet.md` provides simple German talking points for interview preparation.
