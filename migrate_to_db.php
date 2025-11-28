<?php
require_once 'db_connect.php';

// 1. Read the JSON file
$json_file = 'students.json';

if (!file_exists($json_file)) {
    die("Error: students.json file not found!");
}

$json_data = file_get_contents($json_file);
$students = json_decode($json_data, true);

if (!$students) {
    die("Error: JSON file is empty or invalid.");
}

echo "<h2>Starting Migration...</h2>";
echo "Found " . count($students) . " students in JSON file.<br><hr>";

$pdo = getDatabaseConnection();
$count_success = 0;
$count_skipped = 0;

// 2. Loop through every student in the JSON
foreach ($students as $stu) {
    
    // Get values from JSON (using your JSON keys)
    $fullname  = $stu['name'];
    $matricule = $stu['student_id'];
    $group_id  = $stu['group'];

    try {
        // 3. Insert into Database
        $sql = "INSERT INTO students (fullname, matricule, group_id) VALUES (:fullname, :matricule, :group_id)";
        $stmt = $pdo->prepare($sql);
        
        $stmt->execute([
            ':fullname' => $fullname,
            ':matricule' => $matricule,
            ':group_id' => $group_id
        ]);

        echo "<span style='color:green'>✔ Imported: $fullname ($matricule)</span><br>";
        $count_success++;

    } catch (PDOException $e) {
        // If error is Duplicate Entry (23000), just skip it
        if ($e->getCode() == 23000) {
            echo "<span style='color:orange'>⚠ Skipped: $fullname ($matricule) - Already exists in DB.</span><br>";
            $count_skipped++;
        } else {
            echo "<span style='color:red'>✘ Error: " . $e->getMessage() . "</span><br>";
        }
    }
}

echo "<hr>";
echo "<h3>Migration Complete!</h3>";
echo "Successfully added: <strong>$count_success</strong><br>";
echo "Skipped (Duplicates): <strong>$count_skipped</strong><br>";
echo "<br><a href='list_students.php'>Go to List Students</a>";
?>