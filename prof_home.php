<?php
// prof_home.php - The Complete Professor Dashboard
session_start();
require_once 'db_connect.php';

// 1. Security Check
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'prof') {
    header("Location: login.php"); 
    exit;
}

$pdo = getDatabaseConnection();
$prof_id = $_SESSION['user_id'];
$msg = "";
$msg_type = ""; // 'good' or 'bad'

// --- HANDLE FORM 1: CREATE SESSION ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_session'])) {
    $course = $_POST['course_id'];
    $level = $_POST['level'];
    $group = $_POST['group_id'];
    
    try {
        $sql = "INSERT INTO attendance_sessions (course_id, level, group_id, opened_by) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$course, $level, $group, $prof_id]);
        $msg = "Session created successfully!";
        $msg_type = "good";
    } catch (PDOException $e) {
        $msg = "Error: " . $e->getMessage();
        $msg_type = "bad";
    }
}

// --- HANDLE FORM 2: ADD STUDENT (New!) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_student_action'])) {
    $matricule = $_POST['matricule'];
    $fullname = $_POST['fullname'];
    $level = $_POST['level'];
    $group = $_POST['group_id'];

    try {
        $pdo->beginTransaction();
        // 1. Insert Student
        $stmt = $pdo->prepare("INSERT INTO students (matricule, fullname, level, group_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$matricule, $fullname, $level, $group]);
        $sid = $pdo->lastInsertId();

        // 2. Create Login
        $hash = password_hash($matricule, PASSWORD_DEFAULT);
        $stmt2 = $pdo->prepare("INSERT INTO users (username, password, role, related_id) VALUES (?, ?, 'student', ?)");
        $stmt2->execute([$matricule, $hash, $sid]);

        $pdo->commit();
        $msg = "Student '$fullname' added successfully!";
        $msg_type = "good";
    } catch (Exception $e) {
        $pdo->rollBack();
        $msg = "Error adding student: " . $e->getMessage();
        $msg_type = "bad";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Professor Dashboard</title>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
/* CSS Styles */
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin:0; background:#f4f7f6; }
nav { background:#007bff; color:#fff; padding:15px 20px; display:flex; justify-content:space-between; align-items:center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
.nav-links { list-style:none; display:flex; gap:20px; margin:0; padding:0; }
.nav-links li { cursor:pointer; font-weight:bold; color: white; opacity: 0.8; transition: 0.3s; }
.nav-links li:hover, .nav-links li.active-link { text-decoration:underline; color:#fff; opacity: 1; }
.logout-btn { background:#dc3545; border:none; padding:8px 15px; border-radius:5px; color:#fff; cursor:pointer; font-weight:bold; text-decoration: none; }

/* Message Box */
.alert { padding: 15px; margin: 20px auto; border-radius: 5px; max-width: 800px; text-align: center; font-weight: bold; }
.alert.good { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.alert.bad { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

section { padding:30px; display:none; max-width: 1200px; margin: auto; }
section.active { display:block; animation: fadeIn 0.4s; }
@keyframes fadeIn { from { opacity:0; transform: translateY(10px); } to { opacity:1; transform: translateY(0); } }

/* Cards */
.card { background:white; padding:25px; border-radius:8px; box-shadow:0 4px 15px rgba(0,0,0,0.05); margin-bottom:20px; }
h2 { margin-top:0; border-bottom:2px solid #f0f0f0; padding-bottom:10px; color:#333; }

/* Guide Steps */
.step { display: flex; gap: 15px; margin-bottom: 20px; align-items: flex-start; }
.step-num { background: #007bff; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-weight: bold; flex-shrink: 0; }
.step-content h4 { margin: 0 0 5px 0; color: #007bff; }
.step-content p { margin: 0; color: #666; }

/* Buttons & Tables */
table { width:100%; border-collapse:collapse; margin-top:15px; }
th, td { padding:12px 15px; text-align:left; border-bottom: 1px solid #eee; }
th { background:#f8f9fa; color:#555; font-weight: 600; }
.btn { padding:6px 12px; border-radius:4px; text-decoration:none; font-size:14px; display:inline-block; margin-right:5px; font-weight:bold; color:white; border:none; cursor:pointer;}
.btn-take { background:#28a745; } .btn-close { background:#dc3545; } .btn-edit { background:#ffc107; color:#333; }
.btn-create { background:#007bff; padding: 12px; font-size: 16px; width: 100%; margin-top: 10px; }

/* Form Inputs */
input, select { padding: 10px; border: 1px solid #ddd; border-radius: 4px; width: 100%; box-sizing: border-box; margin-bottom: 10px; }
label { font-weight: bold; color: #555; font-size: 0.9em; }
</style>
</head>
<body>

<nav>
  <div style="font-size: 1.2em;">🎓 <strong>Professor Portal</strong></div>
  <ul class="nav-links">
    <li onclick="showSection('guide')" id="nav-guide">Home / Guide</li>
    <li onclick="showSection('sessions')" id="nav-sessions">Sessions</li>
    <li onclick="showSection('add_student')" id="nav-add_student">+ Add Student</li>
    <li><a href="manage_justifications.php" style="color:white; text-decoration:none; opacity:0.8;">Justifications</a></li>
    <li><a href="TP2.php" style="color:white; text-decoration:none; opacity:0.8;">Statistics</a></li>
  </ul>
  <a href="logout.php" class="logout-btn">Logout</a>
</nav>

<?php if($msg) echo "<div class='alert $msg_type'>$msg</div>"; ?>

<section id="guide" class="active">
  <h2>👋 Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
  <div class="card">
      <h3>How to use this system:</h3>
      
      <div class="step">
          <div class="step-num">1</div>
          <div class="step-content">
              <h4>Create a Session</h4>
              <p>Go to the <strong>Sessions</strong> tab. Enter the Course Name (e.g., WEB-2), select the Level (L1, L2...), and the Group ID. Click "Start Session".</p>
          </div>
      </div>

      <div class="step">
          <div class="step-num">2</div>
          <div class="step-content">
              <h4>Take Attendance</h4>
              <p>Find your session in the list and click <strong>📝 Attendance</strong>. Mark students as Present or Absent. Click "Update" to save.</p>
          </div>
      </div>

      <div class="step">
          <div class="step-num">3</div>
          <div class="step-content">
              <h4>Manage Students</h4>
              <p>If a student is missing from your list, go to the <strong>+ Add Student</strong> tab to create their account instantly.</p>
          </div>
      </div>

      <div class="step">
          <div class="step-num">4</div>
          <div class="step-content">
              <h4>View Reports</h4>
              <p>Click <strong>Statistics</strong> in the menu to see color-coded performance tables (Green/Yellow/Red).</p>
          </div>
      </div>
  </div>
</section>

<section id="sessions">
  <h2>📅 Session Manager</h2>
  <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
      
      <div class="card" style="border-top: 4px solid #007bff;">
          <h3>Start New Session</h3>
          <form method="POST">
              <label>Course Name:</label>
              <input type="text" name="course_id" placeholder="e.g. JAVA-101" required>
              
              <label>Level:</label>
              <select name="level">
                  <option>L1</option><option>L2</option><option selected>L3</option><option>M1</option><option>M2</option>
              </select>
              
              <label>Group ID:</label>
              <input type="number" name="group_id" placeholder="e.g. 20" required>
              
              <button type="submit" name="create_session" class="btn btn-create">Start Class</button>
          </form>
      </div>

      <div class="card">
          <h3>Recent Sessions</h3>
          <table>
              <thead><tr><th>Course</th><th>Group</th><th>Status</th><th>Action</th></tr></thead>
              <tbody>
              <?php
              $stmt = $pdo->prepare("SELECT * FROM attendance_sessions WHERE opened_by = ? ORDER BY id DESC LIMIT 8");
              $stmt->execute([$prof_id]);
              while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                  $color = ($row['status'] == 'open') ? '#28a745' : '#6c757d';
                  echo "<tr>";
                  echo "<td><strong>{$row['course_id']}</strong><br><small>{$row['date']}</small></td>";
                  echo "<td>{$row['level']} - G{$row['group_id']}</td>";
                  echo "<td style='color:$color; font-weight:bold;'>".strtoupper($row['status'])."</td>";
                  echo "<td>";
                  if($row['status'] == 'open') {
                      echo "<a href='take_attendance.php?session_id={$row['id']}' class='btn btn-take'>📝</a>";
                      echo "<a href='close_session.php?id={$row['id']}' class='btn btn-close'>❌</a>";
                  } else {
                      echo "<a href='take_attendance.php?session_id={$row['id']}' class='btn btn-edit'>👁️</a>";
                  }
                  echo "<a href='delete_session.php?id={$row['id']}' class='btn btn-close' onclick='return confirm(\"Delete?\")'>🗑️</a>";
                  echo "</td></tr>";
              }
              ?>
              </tbody>
          </table>
      </div>
  </div>
</section>

<section id="add_student">
  <div class="card" style="max-width: 500px; margin: auto; border-top: 4px solid #28a745;">
      <h2>➕ Add New Student</h2>
      <p>Add a student to the database. Login will be created automatically.</p>
      
      <form method="POST">
          <input type="hidden" name="add_student_action" value="1">
          
          <label>Matricule (ID):</label>
          <input type="text" name="matricule" required placeholder="e.g. 2024099">
          
          <label>Full Name:</label>
          <input type="text" name="fullname" required placeholder="e.g. Sarah Connor">
          
          <label>Level:</label>
          <select name="level">
              <option>L1</option><option>L2</option><option selected>L3</option><option>M1</option><option>M2</option>
          </select>
          
          <label>Group ID:</label>
          <input type="number" name="group_id" required placeholder="e.g. 20">
          
          <button type="submit" class="btn btn-create" style="background: #28a745;">Add Student</button>
      </form>
  </div>
</section>

<script>
function showSection(id){ 
    // Hide all
    document.querySelectorAll("section").forEach(el => el.classList.remove("active"));
    document.querySelectorAll(".nav-links li").forEach(el => el.classList.remove("active-link"));
    
    // Show Target
    document.getElementById(id).classList.add("active");
    document.getElementById("nav-"+id).classList.add("active-link");
}

// Default active tab check
document.getElementById("nav-guide").classList.add("active-link");
</script>

</body>
</html>