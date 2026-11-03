# Database Design

Aviate with Isaac uses Supabase PostgreSQL.

This document tracks the application's planned tables, relationships, and database decisions.

## Database Principles

- Use primary keys for all tables.
- Use foreign keys to connect related records.
- Use Row Level Security where appropriate.
- Protect student, financial, document, and account information.
- Major database changes should be coordinated with the team.

## Planned Core Tables

### profiles
Application user and instructor information.

### students
Student contact, training, goal, and status information.

### lessons
Planned and completed flight or ground lessons.

### appointments
Scheduled lessons and calendar events.

### training_records
Student training progress and completed requirements.

### invoices
Invoices issued to students.

### invoice_items
Individual instructional charges included on invoices.

### payments
Payments associated with invoices.

### documents
Student and instructional files.

### templates
Reusable lesson plans, documents, checklists, invoices, and other materials.

## Planned Relationships

Instructor
- Students
  - Lessons
  - Appointments
  - Training Records
  - Documents
  - Invoices
    - Invoice Items
    - Payments
- Templates

## Row Level Security

Application tables should use Supabase Row Level Security so users only access information they are authorized to view or modify.

## Sprint 1

The initial database schema will be finalized as part of Sprint 1.

The team should prioritize the tables required by the first Sprint 1 stories before creating the complete application schema.

## Upcoming Lessons (Story 19)

The dashboard's **Upcoming Lessons** card reads existing tables. It doesn't add any.

### appointments

| Column | Used for |
| --- | --- |
| id | Row key |
| student_id | Looks up the student's name in `students` |
| apointment_date | Lesson date (local date, no time zone) |
| start_time | Lesson time (local time, no time zone) |
| apointment_type | Lesson label shown under the student's name |
| status | `cancelled`, `canceled` and `completed` are hidden |

The `apointment_*` spelling matches the column names in Supabase.

### students

| Column | Used for |
| --- | --- |
| id | Matched to `appointments.student_id` |
| first_name, last_name | Student name |

### Rules

- Shows appointments from today on whose start time hasn't passed yet, in `APP_TIMEZONE`.
- Hides cancelled and completed appointments. Appointments with no status are shown.
- Appointments with no `start_time` stay listed for their whole day, after the timed ones, and show "Time TBD".
- Sorted by date, then time. Shows up to 10.
