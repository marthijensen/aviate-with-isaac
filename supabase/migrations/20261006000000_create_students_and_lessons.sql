-- Story 19 - View upcoming lessons
-- Creates the minimum tables needed to list upcoming lessons on the dashboard.

create table if not exists public.students (
    id uuid primary key default gen_random_uuid(),
    instructor_id uuid not null default auth.uid() references auth.users (id) on delete cascade,
    first_name text not null,
    last_name text not null,
    email text,
    phone text,
    status text not null default 'active' check (status in ('active', 'inactive')),
    created_at timestamptz not null default now()
);

create table if not exists public.lessons (
    id uuid primary key default gen_random_uuid(),
    instructor_id uuid not null default auth.uid() references auth.users (id) on delete cascade,
    student_id uuid not null references public.students (id) on delete cascade,
    lesson_type text not null default 'flight' check (lesson_type in ('flight', 'ground')),
    scheduled_start timestamptz not null,
    scheduled_end timestamptz,
    status text not null default 'scheduled' check (status in ('scheduled', 'completed', 'cancelled')),
    notes text,
    created_at timestamptz not null default now(),
    check (scheduled_end is null or scheduled_end > scheduled_start)
);

create index if not exists lessons_student_id_idx on public.lessons (student_id);
create index if not exists lessons_upcoming_idx on public.lessons (status, scheduled_start);

-- Expose the tables to the Supabase Data API. Access is still limited by RLS
-- below; service_role (the server-side secret key) bypasses RLS.
grant select, insert, update, delete on public.students, public.lessons to authenticated, service_role;

-- Row Level Security: instructors only see and change their own records.
alter table public.students enable row level security;
alter table public.lessons enable row level security;

create policy "Instructors manage their own students"
    on public.students for all to authenticated
    using (instructor_id = auth.uid())
    with check (instructor_id = auth.uid());

create policy "Instructors manage their own lessons"
    on public.lessons for all to authenticated
    using (instructor_id = auth.uid())
    with check (
        instructor_id = auth.uid()
        and exists (
            select 1 from public.students s
            where s.id = student_id and s.instructor_id = auth.uid()
        )
    );
