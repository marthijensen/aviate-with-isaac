<?php

declare(strict_types=1);

/**
 * Reads a course's checklist (public.course_topics) and a student's progress through it.
 *
 * public.course_topics (existing): one row per checklist item (topic_id, topic, subtopic, display_order, course_id).
 * public.student_progress (new): one row per student per item (completed true/false).
 */
final class TrainingProgress
{
    /**
     * Topics for a course in syllabus order, each with its subtopics. Rows of
     * course_topics that share a topic name are grouped under one topic.
     *
     * @return list<array{id: int, topic_name: string, subtopics: list<array{id: int, subtopic_name: string}>}>
     */
    public static function syllabus(PDO $db, int $courseId): array
    {
        $statement = $db->prepare(
            'SELECT topic_id, topic, subtopic
            FROM public.course_topics
            WHERE course_id = :course_id
            ORDER BY display_order ASC, topic_id ASC'
        );

        $statement->execute(['course_id' => $courseId]);

        $topics = [];

        foreach ($statement->fetchAll() as $row) {
            $name = (string) $row['topic'];

            if (!isset($topics[$name])) {
                $topics[$name] = [
                    // The first item's topic_id doubles as a unique key for the topic group.
                    'id' => (int) $row['topic_id'],
                    'topic_name' => $name,
                    'subtopics' => [],
                ];
            }

            $topics[$name]['subtopics'][] = [
                'id' => (int) $row['topic_id'],
                'subtopic_name' => (string) $row['subtopic'],
            ];
        }

        return array_values($topics);
    }

    /**
     * topic_id values (course_topics rows) a student has completed.
     *
     * @return array<int, true>
     */
    public static function completedIds(PDO $db, int $studentId): array
    {
        $statement = $db->prepare(
            'SELECT topic_id
            FROM public.student_progress
            WHERE student_id = :student_id
            AND completed = TRUE'
        );

        $statement->execute(['student_id' => $studentId]);

        return array_fill_keys(
            array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN)),
            true
        );
    }

    /**
     * Totals for the progress cards.
     *
     * @param list<array{id: int, topic_name: string, subtopics: list<array{id: int, subtopic_name: string}>}> $topics
     * @param array<int, true> $done
     * @return array{subtopics_total: int, subtopics_done: int, topics_total: int, topics_done: int, percent: int}
     */
    public static function summary(array $topics, array $done): array
    {
        $subtopicsTotal = 0;
        $subtopicsDone = 0;
        $topicsDone = 0;

        foreach ($topics as $topic) {
            $count = count($topic['subtopics']);
            $finished = 0;

            foreach ($topic['subtopics'] as $subtopic) {
                if (isset($done[$subtopic['id']])) {
                    $finished++;
                }
            }

            $subtopicsTotal += $count;
            $subtopicsDone += $finished;

            if ($count > 0 && $finished === $count) {
                $topicsDone++;
            }
        }

        return [
            'subtopics_total' => $subtopicsTotal,
            'subtopics_done' => $subtopicsDone,
            'topics_total' => count($topics),
            'topics_done' => $topicsDone,
            'percent' => $subtopicsTotal > 0
                ? (int) round($subtopicsDone / $subtopicsTotal * 100)
                : 0,
        ];
    }

    /**
     * Whether the progress checkboxes may save.
     *
     * - AVIATE_DEMO_WRITES=1 allows saving from any computer. It is meant for a showcase on a
     *   temporary server. The app has no login yet, so turn it off (0) when the demo is over.
     * - Otherwise the Add Student / Schedule rule applies: AVIATE_LOCAL_WRITES=1 and the
     *   request comes from this machine.
     */
    public static function writesEnabled(): bool
    {
        if (Env::get('AVIATE_DEMO_WRITES', '0') === '1') {
            return true;
        }

        $remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';

        return Env::get('AVIATE_LOCAL_WRITES', '0') === '1'
            && in_array($remoteAddress, ['127.0.0.1', '::1'], true);
    }
}
