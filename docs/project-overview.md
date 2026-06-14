# ServiceDesk Pro Project Overview

## Project overview

ServiceDesk Pro is a lightweight PHP and MySQL help desk application. It lets customers submit support tickets from a public form and gives authenticated staff an admin area for reviewing tickets, managing customer records, adding replies or internal notes, changing ticket status, and reviewing activity history.

## Business purpose

The application supports small support teams that need a simple shared queue for customer requests. Its main business value is centralizing support conversations, customer contact details, ticket priority, ticket status, and operational history so staff can respond consistently and track work over time.

## Target users

- Customers who need to submit support requests.
- Support staff who review, update, and respond to tickets.
- Administrators who monitor workload, customer records, and recent system activity.
- Developers who maintain or extend the application.

## System architecture

The application is a traditional server-rendered PHP app backed by a MySQL database.

- Public interface: `public/index.php` handles customer ticket submission.
- Admin interface: files in `admin/` require authentication and render dashboard, ticket, customer, and detail views.
- Shared includes: files in `includes/` provide configuration, database access, authentication, and common helpers.
- Database layer: PDO is used directly with prepared statements.
- Presentation layer: PHP templates output HTML and share styling from `assets/css/style.css`.

## Folder structure

- `admin/`: authenticated staff pages, including dashboard, tickets list, ticket detail, customers list, customer detail, login, and logout.
- `assets/css/`: shared CSS for public and admin UI.
- `assets/js/`: reserved for client-side behavior.
- `database/`: schema, seed data, and migration SQL.
- `docs/`: project documentation for maintainers and interview preparation, including `project-overview.md` and `interview-cheatsheet.md`.
- `includes/`: configuration, PDO database connection, authentication helpers, and reusable functions.
- `public/`: public-facing ticket submission page.

## Database structure

- `users`: authenticated staff accounts with name, email, password hash, role, and creation timestamp.
- `customers`: customer contact records with name, email, optional phone, and creation timestamp.
- `tickets`: support tickets with customer snapshot fields, subject, message, priority, status, creation timestamp, and update timestamp.
- `ticket_messages`: ticket conversation entries and internal notes linked to tickets and optionally to staff users.
- `activity_logs`: audit trail entries for customer creation, ticket creation, status changes, and message additions.

Important relationships:

- `tickets.customer_id` references `customers.id` and is set to null if the customer is deleted.
- `ticket_messages.ticket_id` references `tickets.id` and cascades on ticket deletion.
- `ticket_messages.user_id` references `users.id` and is set to null if the user is deleted.
- `activity_logs` can link to tickets, customers, and users while preserving logs when related records are removed.

## Technologies used

- PHP for server-side rendering and request handling.
- MySQL or MariaDB for relational data storage.
- PDO for database access with prepared statements.
- HTML and CSS for the user interface.
- XAMPP-friendly local development structure.

## Security features

- Admin pages are protected with session-based authentication.
- Passwords are verified using PHP password hashing APIs.
- Database reads and writes use PDO prepared statements.
- Output is escaped with `htmlspecialchars` before rendering.
- Public ticket submission uses a CSRF token.
- Public ticket submission includes a honeypot field.
- Public ticket submission applies simple session-based rate limiting.
- PDO emulated prepares are disabled.

## Current features

- Public customer ticket submission.
- Automatic customer creation when a new email address submits a ticket.
- Admin login and logout.
- Dashboard with ticket counts, latest tickets, and latest activity.
- Ticket list with status filtering, search, and pagination.
- Ticket detail page with ticket metadata, original message, message history, activity history, status updates, and reply/internal note creation.
- Customer list with total ticket counts and last ticket date.
- Customer detail page with customer profile information and all related tickets.
- Activity logging for customer creation, ticket creation, status changes, and message additions.
- German interview preparation guide for explaining the project clearly in job interviews.

## Future roadmap

- Customer-facing ticket lookup and reply flow.
- Email notifications for new tickets and staff replies.
- Role-specific permissions for staff and administrators.
- File attachments on tickets and messages.
- Customer editing and merge tools.
- More advanced dashboard reporting.
- Full-text ticket search.
- Automated tests for core workflows.
- Deployment and backup documentation.

## Technical decisions and reasons

- Server-rendered PHP keeps the application easy to host on XAMPP or standard shared hosting.
- PDO prepared statements reduce SQL injection risk while keeping database access explicit and readable.
- Customer snapshot fields on tickets preserve submitted contact details even if the customer record changes later.
- Activity logs are stored separately from ticket messages because they represent operational events, not conversation content.
- Admin pages define a local `e()` helper to make safe output escaping obvious at the template level.
- The UI uses a small shared stylesheet instead of a heavy frontend framework to keep the project simple and maintainable.

## Important implementation details

- Admin pages must call `require_login()` before rendering protected content.
- New database queries should use `db()->prepare()` and parameter binding or `execute()` parameters.
- Any user-controlled or database-rendered value should be escaped before output.
- Ticket status values are limited to `open`, `in_progress`, `waiting`, and `closed`.
- Ticket priority values are limited to `low`, `medium`, `high`, and `urgent`.
- New customer and ticket workflows should write activity log entries when they create meaningful audit events.
- This document should be updated after each completed feature so it remains a reliable project briefing.
- `docs/interview-cheatsheet.md` provides simple German talking points for interview preparation.
