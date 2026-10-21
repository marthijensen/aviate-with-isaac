-- Sample data for local development and testing Story 19 (View upcoming lessons).
--
-- Requires at least one user in Authentication > Users. The sample students
-- and lessons are assigned to the oldest user. Safe to run more than once:
-- it only adds data if the sample students don't exist yet.
--
-- Lesson times are relative to when this runs, so there is always a mix of
-- past, upcoming, completed and cancelled lessons.

do $$
declare
    v_instructor uuid;
    v_ava uuid;
    v_ben uuid;
    v_cole uuid;
begin
    select id into v_instructor from auth.users order by created_at limit 1;
    if v_instructor is null then
        raise exception 'No users found. Add a user in Authentication > Users, then run this seed again.';
    end if;

    if exists (select 1 from public.students where email = 'ava.lee@example.com') then
        raise notice 'Sample data already exists. Nothing to do.';
        return;
    end if;

    insert into public.students (instructor_id, first_name, last_name, email)
    values (v_instructor, 'Ava', 'Lee', 'ava.lee@example.com')
    returning id into v_ava;

    insert into public.students (instructor_id, first_name, last_name, email)
    values (v_instructor, 'Ben', 'Ortiz', 'ben.ortiz@example.com')
    returning id into v_ben;

    insert into public.students (instructor_id, first_name, last_name, email)
    values (v_instructor, 'Cole', 'Diaz', 'cole.diaz@example.com')
    returning id into v_cole;

    insert into public.lessons (instructor_id, student_id, lesson_type, scheduled_start, scheduled_end, status, notes)
    values
        -- Upcoming: these should appear on the dashboard, soonest first.
        (v_instructor, v_ava,  'flight', date_trunc('hour', now()) + interval '1 day 9 hours',  date_trunc('hour', now()) + interval '1 day 11 hours', 'scheduled', 'Steep turns and slow flight'),
        (v_instructor, v_ben,  'ground', date_trunc('hour', now()) + interval '2 days 14 hours', date_trunc('hour', now()) + interval '2 days 16 hours', 'scheduled', 'Airspace review'),
        (v_instructor, v_cole, 'flight', date_trunc('hour', now()) + interval '5 days 8 hours',  date_trunc('hour', now()) + interval '5 days 10 hours', 'scheduled', 'First solo cross-country prep'),
        -- These should NOT appear on the dashboard.
        (v_instructor, v_ava,  'flight', now() - interval '2 days',  now() - interval '2 days' + interval '2 hours', 'completed', 'Past, completed'),
        (v_instructor, v_ben,  'flight', now() - interval '1 day',   now() - interval '1 day' + interval '2 hours',  'scheduled', 'Past, never marked completed'),
        (v_instructor, v_cole, 'ground', now() + interval '3 days',  now() + interval '3 days' + interval '1 hour',   'cancelled', 'Future, but cancelled');
end $$;
