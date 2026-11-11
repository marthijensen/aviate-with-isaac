-- Student progress for the private pilot checklist (Aviate with Isaac Supabase project).
-- Run in the Supabase SQL Editor (needs dashboard/owner access). Tell the team first.
--
-- Adds ONE new table, student_progress. It uses the existing course_topics, students and
-- course tables and changes none of them.
--   One row per student per checklist item. Checking a box creates the row; unchecking edits
--   it to completed = false.
--
-- BEFORE RUNNING, check the existing data:
--   SELECT course_id, COUNT(*) AS items, COUNT(DISTINCT topic) AS topics
--   FROM public.course_topics GROUP BY course_id;
--   For private pilot (course_id 1) expect 235 items and 23 topics. If there are more, the
--   CSV was probably imported twice and every checklist item would appear twice, so clean up
--   the duplicates first.

BEGIN;

CREATE TABLE public.student_progress (
  student_id BIGINT NOT NULL REFERENCES public.students(id) ON DELETE CASCADE,
  topic_id   BIGINT NOT NULL REFERENCES public.course_topics(topic_id) ON DELETE CASCADE,
  completed  BOOLEAN NOT NULL DEFAULT TRUE,
  updated_at TIMESTAMPTZ NOT NULL DEFAULT now(),
  PRIMARY KEY (student_id, topic_id)
);

CREATE INDEX student_progress_topic_idx ON public.student_progress (topic_id);

-- Access. The app connects with Postgres roles (READONLY_DB_USER / WRITE_DB_USER), not the API
-- keys. Supabase gives new tables to the API roles by default, so remove that from the NEW
-- table only, then grant what the app needs.
REVOKE ALL ON public.student_progress FROM anon, authenticated;

-- Read-only role (readonly_user, per database-access/index.html). Harmless if already granted.
GRANT SELECT ON public.student_progress, public.course_topics TO readonly_user;

-- Writer role: replace app_writer with the role name from WRITE_DB_USER (the part before any
-- dot), then uncomment the next two lines.
-- GRANT SELECT, INSERT, UPDATE ON public.student_progress TO app_writer;
-- GRANT SELECT ON public.course_topics TO app_writer;

COMMIT;

-- If the checklist page shows no topics after this, Row Level Security may be on for
-- course_topics without a policy for readonly_user. Ask the team before changing it.
