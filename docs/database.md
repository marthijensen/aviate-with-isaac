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
