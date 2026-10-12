<?php
declare(strict_types=1);

final class Course
{
    public function __construct(private PDO $db) {}

    public function browse(array $filters = [], ?array $student = null, ?int $termId = null): array
    {
        $sql = "SELECT c.*,d.name AS department_name,
                (SELECT COUNT(*) FROM registrations r WHERE r.course_id=c.id AND r.term_id=? AND r.status IN ('pending','approved')) AS enrolled,
                cs.schedule
                FROM courses c LEFT JOIN departments d ON d.id=c.department_id
                LEFT JOIN (SELECT course_id,GROUP_CONCAT(CONCAT(day_of_week,':',TIME_FORMAT(starts_at,'%H:%i'),'-',TIME_FORMAT(ends_at,'%H:%i')) ORDER BY day_of_week,starts_at SEPARATOR ', ') AS schedule
                           FROM course_schedules GROUP BY course_id) cs ON cs.course_id=c.id
                WHERE c.active=1";
        $params = [$termId ?? 0];
        if (!empty($filters['q'])) {
            $sql .= ' AND (c.code LIKE ? OR c.title LIKE ? OR c.faculty_name LIKE ?)';
            $needle = '%' . $filters['q'] . '%';
            array_push($params, $needle, $needle, $needle);
        }
        if (!empty($filters['department'])) {
            $sql .= ' AND c.department_id=?';
            $params[] = (int)$filters['department'];
        }
        $sorts = ['title' => 'c.title', 'credits' => 'c.credits', 'seats' => '(c.capacity-enrolled)'];
        $sort = $sorts[$filters['sort'] ?? 'title'] ?? 'c.title';
        $direction = ($filters['direction'] ?? '') === 'desc' ? 'DESC' : 'ASC';
        $sql .= " ORDER BY {$sort} {$direction}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $courses = $stmt->fetchAll();
        foreach ($courses as &$course) {
            $course['eligible'] = $student === null
                || (($course['department_id'] === null || (int)$course['department_id'] === (int)$student['department_id'])
                    && (int)$course['semester'] === (int)$student['semester']);
        }
        unset($course);
        return $courses;
    }

    public function get(int $id, ?int $termId = null): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT c.*,d.name AS department_name,
             (SELECT COUNT(*) FROM registrations r WHERE r.course_id=c.id AND r.term_id=? AND r.status IN ('pending','approved')) AS enrolled
             FROM courses c LEFT JOIN departments d ON d.id=c.department_id WHERE c.id=?"
        );
        $stmt->execute([$termId ?? 0, $id]);
        $course = $stmt->fetch();
        if (!$course) return null;
        $stmt = $this->db->prepare('SELECT day_of_week,starts_at,ends_at,room FROM course_schedules WHERE course_id=? ORDER BY day_of_week,starts_at');
        $stmt->execute([$id]);
        $course['schedules'] = $stmt->fetchAll();
        $stmt = $this->db->prepare('SELECT p.id,p.code,p.title FROM course_prerequisites cp JOIN courses p ON p.id=cp.prerequisite_course_id WHERE cp.course_id=? ORDER BY p.code');
        $stmt->execute([$id]);
        $course['prerequisites'] = $stmt->fetchAll();
        return $course;
    }
}
