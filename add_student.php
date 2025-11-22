<?php
// Step 1: Collect and sanitize data
$student_id = isset($_POST['student_id']) ? trim($_POST['student_id']) : '';
$name       = isset($_POST['name']) ? trim($_POST['name']) : '';
$group      = isset($_POST['group']) ? trim($_POST['group']) : '';

$student_id = htmlspecialchars($student_id);
$name       = htmlspecialchars($name);
$group      = htmlspecialchars($group);

// Step 2: Validate fields
$errors = [];

if ($student_id === "") {
    $errors[] = "Student ID is required.";
} elseif (!ctype_digit($student_id)) {
    $errors[] = "Student ID must contain numbers only.";
}

if ($name === "") {
    $errors[] = "Name is required.";
} elseif (!preg_match("/^[A-Za-z ]+$/", $name)) {
    $errors[] = "Name must contain letters only.";
}

if ($group === "") {
    $errors[] = "Group is required.";
} elseif (!preg_match("/^[A-Za-z0-9]+$/", $group)) {
    $errors[] = "Group must contain letters and numbers only.";
}

// Step 3: Load existing students
$students = [];

if (file_exists("students.json")) {
    $json = file_get_contents("students.json");
    $students = json_decode($json, true);

    if (!is_array($students)) {
        $students = [];
    }
}

// Step 4: Check for duplicate Student ID
foreach ($students as $stu) {
    if ($stu["student_id"] == $student_id) {
        $errors[] = "Student ID already exists.";
        break;
    }
}

// Step 5: If errors, show them
if (!empty($errors)) {
    echo "<h2 style='color:red;'>Errors:</h2>";
    foreach ($errors as $e) {
        echo "<p style='color:red;'>$e</p>";
    }
    echo "<p><a href='TP2.php'>Go back</a></p>";
    exit;
}

// Step 6: Add new student
$students[] = [
    "student_id" => $student_id,
    "name"       => $name,
    "group"      => $group
];

// Step 7: Save to JSON
file_put_contents("students.json", json_encode($students, JSON_PRETTY_PRINT));

// Step 8: Success message
echo "<h2 style='color:green;'>Student added successfully!</h2>";
echo "<p><strong>ID:</strong> $student_id</p>";
echo "<p><strong>Name:</strong> $name</p>";
echo "<p><strong>Group:</strong> $group</p>";
echo "<p><a href='TP2.php'>Add another student</a></p>";
?>
