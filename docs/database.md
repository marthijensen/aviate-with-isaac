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

## Student Progress Checklist (PR #13 / #16)

The shared checklist displays all rows in `public.course_topics` for every student, regardless of their assigned course. Completion is stored independently by `student_id` and `course_topic_id` in `public.student_progress`. Checking a task records a completion timestamp; unchecking sets `is_completed` to false and clears that timestamp. The upsert requires a unique constraint or suitable unique index on the student/topic pair.

### Restricted database roles

During local integration testing on November 24, 2026, the team configured the following permissions and role-specific RLS policies on the shared Supabase database. The SQL below records the setup used during troubleshooting; it is not an automatically executed migration. Review existing policies and grants before applying to another environment. Policy creation is not idempotent.

```sql
GRANT SELECT ON public.course_topics, public.student_progress TO readonly_user;

CREATE POLICY "readonly_user_read_course_topics"
ON public.course_topics FOR SELECT TO readonly_user USING (true);

CREATE POLICY "readonly_user_read_student_progress"
ON public.student_progress FOR SELECT TO readonly_user USING (true);

GRANT SELECT, INSERT ON public.student_progress TO aviate_writer;
GRANT UPDATE (is_completed, completed_at)
ON public.student_progress TO aviate_writer;
GRANT SELECT ON public.course_topics TO aviate_writer;
GRANT SELECT (id) ON public.students TO aviate_writer;
GRANT USAGE ON SEQUENCE public.student_progress_progress_id_seq TO aviate_writer;

CREATE POLICY "aviate_writer_read_topics"
ON public.course_topics FOR SELECT TO aviate_writer USING (true);

CREATE POLICY "aviate_writer_read_progress"
ON public.student_progress FOR SELECT TO aviate_writer USING (true);

CREATE POLICY "aviate_writer_insert_progress"
ON public.student_progress FOR INSERT TO aviate_writer WITH CHECK (true);

CREATE POLICY "aviate_writer_update_progress"
ON public.student_progress FOR UPDATE TO aviate_writer
USING (true) WITH CHECK (true);

CREATE POLICY "aviate_writer_read_student_ids"
ON public.students FOR SELECT TO aviate_writer USING (true);
```

These policies are for the dedicated server-side roles, not anonymous or authenticated public API clients. They permit access across students for the current internal development model. RLS remains enabled on the checklist tables; the writer only requires the student ID for existence validation. Keep credentials server-side and never commit them. Writes remain gated by `AVIATE_LOCAL_WRITES=1`, loopback address, and CSRF validation. Before any public deployment, implement appropriate per-user authentication and authorization and review role permissions.

### Verification

Manual testing confirmed that the universal checklist displays, checking and unchecking save to Supabase, completion timestamps are set/cleared appropriately, and progress persists across refreshes and PHP restarts. The saved rows were independently inspected in Supabase SQL Editor. Offline regression tests are provided in `tests/StudentProgressTest.php`; run those and the existing test suite before deployment. The student-profile aggregate counters remain placeholders outside this PR.
