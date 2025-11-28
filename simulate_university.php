<?php
// simulate_university.php - FIXED VERSION
require_once 'db_connect.php';
$pdo = getDatabaseConnection();

echo "<h1>🚀 Starting University Simulation...</h1>";

try {
    // --- STEP 1: CLEANING OLD DATA (Run this OUTSIDE the transaction) ---
    // TRUNCATE causes an implicit commit in MySQL, so we must do it first.
    echo "<h3>1. Cleaning old data...</h3>";
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE attendance_records");
    $pdo->exec("TRUNCATE TABLE attendance_sessions");
    $pdo->exec("TRUNCATE TABLE students");
    $pdo->exec("TRUNCATE TABLE justifications");
    $pdo->exec("DELETE FROM users WHERE role != 'admin'");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "<span style='color:green'>✔ Database wiped (Admin kept).</span><br>";

    // --- START TRANSACTION HERE ---
    $pdo->beginTransaction();

    // --- STEP 2: CREATE PROFESSORS ---
    echo "<h3>2. Hiring Professors...</h3>";
    $profs = ['prof_web', 'prof_java', 'prof_ai'];
    $prof_ids = [];

    foreach ($profs as $p) {
        $hash = password_hash('123456', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'prof')");
        $stmt->execute([$p, $hash]);
        $prof_ids[] = $pdo->lastInsertId();
        echo "Created Professor: <strong>$p</strong> (Pass: 123456)<br>";
    }

    // --- STEP 3: DEFINE GROUPS & SCHEDULES ---
    $schedules = [
        '1' => [
            'modules' => ['Algorithmique', 'Architecture des Ordi', 'Systemes d\'Exploitation', 'Maths'],
            'students' => [
                ['mat' => '2025001', 'name' => 'Sarah Connor'],
                ['mat' => '2025002', 'name' => 'John Smith']
            ]
        ],
        '2' => [
            'modules' => ['Interface Homme Machine', 'Prog Avancée Web (PAW)', 'Génie Logiciel', 'Admin Sys Info (ASI)'],
            'students' => [
                ['mat' => '2025010', 'name' => 'Amine Tounsi'],
                ['mat' => '2025011', 'name' => 'Lina Benali'],
                ['mat' => '2025012', 'name' => 'Karim Ziani']
            ]
        ],
        '3' => [
            'modules' => ['Machine Learning', 'Deep Learning', 'Big Data Analytics', 'Computer Vision'],
            'students' => [
                ['mat' => '2025020', 'name' => 'Yacine Brahimi'],
                ['mat' => '2025021', 'name' => 'Nora Djaballah']
            ]
        ]
    ];

    echo "<h3>3. Enrolling Students & Starting Classes...</h3>";

    $startDate = new DateTime('2025-10-01');

    foreach ($schedules as $grp_id => $data) {
        echo "<hr><strong>Processing Group $grp_id...</strong><br>";

        // A. Create Students & Logins
        $student_db_ids = [];
        foreach ($data['students'] as $stu) {
            $stmt = $pdo->prepare("INSERT INTO students (matricule, fullname, group_id) VALUES (?, ?, ?)");
            $stmt->execute([$stu['mat'], $stu['name'], $grp_id]);
            $sid = $pdo->lastInsertId();
            $student_db_ids[] = $sid;

            $pass = password_hash($stu['mat'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, related_id) VALUES (?, ?, 'student', ?)");
            $stmt->execute([$stu['mat'], $pass, $sid]);
            
            echo "Enrolled: {$stu['name']} (Login: {$stu['mat']})<br>";
        }

        // B. Create Sessions
        foreach ($data['modules'] as $key => $course) {
            $assigned_prof = $prof_ids[array_rand($prof_ids)];
            
            for ($i = 0; $i < 4; $i++) {
                $date = clone $startDate;
                $date->modify("+" . ($key + ($i*7)) . " days");
                
                $stmt = $pdo->prepare("INSERT INTO attendance_sessions (course_id, group_id, opened_by, date, status) VALUES (?, ?, ?, ?, 'closed')");
                $stmt->execute([$course, $grp_id, $assigned_prof, $date->format('Y-m-d H:i:00')]);
                $sess_id = $pdo->lastInsertId();

                // C. Take Attendance
                foreach ($student_db_ids as $sid) {
                    $status = (rand(1, 10) > 2) ? 'present' : 'absent';
                    $part = ($status == 'present' && rand(1, 10) > 5) ? 1 : 0;

                    $stmt = $pdo->prepare("INSERT INTO attendance_records (session_id, student_id, status, participated) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$sess_id, $sid, $status, $part]);
                }
            }
        }
        echo "<em>Created 4 weeks of classes for Group $grp_id.</em><br>";
    }

    $pdo->commit();
    echo "<h1>✅ SIMULATION COMPLETE!</h1>";
    echo "<p>The system is now live with 3 different groups.</p>";
    echo "<a href='login.php' style='font-size:20px; font-weight:bold;'>Go to Login Page</a>";

} catch (Exception $e) {
    // Only rollback if a transaction is actually active
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    die("Error: " . $e->getMessage());
}
?>