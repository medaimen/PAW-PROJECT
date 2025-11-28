<?php
// TP2.php - Read-Only Statistics Dashboard (No Actions)
session_start();
require_once 'db_connect.php';

// Security Check
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 'prof' && $_SESSION['role'] != 'admin')) {
    header("Location: login.php"); 
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Statistics Dashboard</title>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
/* CSS Styles */
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin:0; background:#f4f7f6; }
nav { background:#007bff; color:#fff; padding:15px 30px; display:flex; justify-content:space-between; align-items:center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
.logout-btn { background:#dc3545; border:none; padding:8px 15px; border-radius:5px; color:#fff; cursor:pointer; font-weight:bold; text-decoration: none; }

section { padding:30px; display:block; max-width: 1300px; margin: auto; }

/* Table Styles */
table { width:100%; border-collapse:collapse; margin-top:20px; background:#fff; border-radius: 8px; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
th, td { padding:12px 15px; text-align:left; border-bottom: 1px solid #eee; }
th { background:#007bff; color:white; text-transform: uppercase; font-size: 0.85em; letter-spacing: 1px; }

/* Status Colors */
.good { background-color:#d4edda; color: #155724; }
.warning { background-color:#fff3cd; color: #856404; }
.bad { background-color:#f8d7da; color: #721c24; }

button { margin-top:10px; padding:10px 15px; background:#007bff; color:white; border:none; border-radius:4px; cursor:pointer; margin-right: 5px; }
button:hover { background:#0056b3; }
#searchInput { padding: 10px; margin-top: 5px; width: 100%; max-width: 300px; border: 1px solid #ddd; border-radius: 4px; }

/* Report Styles */
#reportContainer { background:white; padding:30px; border-radius:8px; box-shadow:0 2px 10px rgba(0,0,0,0.1); text-align:center; display: flex; flex-wrap: wrap; justify-content: center; gap: 20px;}
.stat-box { padding: 20px; background: #f8f9fa; border-radius: 8px; width: 200px; border: 1px solid #ddd;}
.stat-number { font-size: 2em; font-weight: bold; color: #007bff; }
</style>
</head>
<body>

<nav>
  <div><strong>📊 Statistics Dashboard</strong></div>
  <div>
      <?php if($_SESSION['role'] == 'prof'): ?>
          <a href="prof_home.php" style="color:white; margin-right:20px; font-weight:bold; text-decoration:none;">← Back to Sessions</a>
      <?php else: ?>
          <a href="admin_home.php" style="color:white; margin-right:20px; font-weight:bold; text-decoration:none;">← Back to Admin</a>
      <?php endif; ?>
      
      <a href="logout.php" class="logout-btn">Logout</a>
  </div>
</nav>

<section id="attendance">
  <h2>Student Performance Report</h2>

  <div style="display: flex; justify-content: space-between; align-items: end;">
      <div>
        <label for="searchInput"><b>Search Student:</b></label><br>
        <input type="text" id="searchInput" placeholder="Type name or matricule...">
      </div>
      <div>
        <button id="sortAbsence">Sort by Absences</button>
        <button id="sortParticipation">Sort by Participation</button>
      </div>
  </div>

  <table id="attendanceTable">
    <thead>
      <tr>
        <th>Matricule</th>
        <th>Full Name</th>
        <th>Group</th>
        <th>Attendance Summary</th>
        <th>Participation %</th>
        <th>Absences</th>
        <th>Status</th>
        </tr>
    </thead>
    <tbody>
    <?php
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->query("SELECT * FROM students ORDER BY group_id ASC, fullname ASC");

        $total_students_count = 0;
        $global_present_count = 0;
        $global_session_count = 0;

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $sid = $row['id'];
            $matricule = htmlspecialchars($row['matricule']);
            $fullname = htmlspecialchars($row['fullname']);
            $group_id = $row['group_id'];

            // 1. GET STATS
            $sql1 = "SELECT COUNT(*) FROM attendance_sessions WHERE group_id = :gid AND status = 'closed'";
            $sth1 = $pdo->prepare($sql1);
            $sth1->execute([':gid' => $group_id]);
            $total_sessions = $sth1->fetchColumn();

            $sql2 = "SELECT COUNT(*) FROM attendance_records WHERE student_id = :sid AND status = 'present'";
            $sth2 = $pdo->prepare($sql2);
            $sth2->execute([':sid' => $sid]);
            $times_present = $sth2->fetchColumn();

            $sql3 = "SELECT COUNT(*) FROM attendance_records WHERE student_id = :sid AND participated = 1";
            $sth3 = $pdo->prepare($sql3);
            $sth3->execute([':sid' => $sid]);
            $times_active = $sth3->fetchColumn();

            // 2. CALCULATE PERCENTAGES
            $attendance_pct = ($total_sessions > 0) ? ($times_present / $total_sessions) * 100 : 0;
            $participation_pct = ($total_sessions > 0) ? ($times_active / $total_sessions) * 100 : 0;
            
            // Update Global Counters
            $total_students_count++;
            $global_present_count += $times_present;
            $global_session_count += $total_sessions;

            // 3. EVALUATION LOGIC
            $status_class = "good";
            $status_msg = "✅ Excellent";

            if ($attendance_pct < 50) {
                $status_class = "bad";
                $status_msg = "⛔ Excluded (<50% Att)";
            } elseif ($attendance_pct < 75 || $participation_pct < 20) {
                $status_class = "warning";
                $msg_parts = [];
                if ($attendance_pct < 75) $msg_parts[] = "Low Att";
                if ($participation_pct < 20) $msg_parts[] = "Passive";
                $status_msg = "⚠️ Warning (" . implode(", ", $msg_parts) . ")";
            }

            // 4. RENDER ROW (No Actions)
            echo "<tr class='$status_class'>";
            echo "<td>$matricule</td>";
            echo "<td>$fullname</td>";
            echo "<td><span style='background:#eee; padding:2px 6px; border-radius:4px;'>Grp $group_id</span></td>";

            echo "<td>";
            echo "<div><strong>$times_present</strong> / $total_sessions <small>(".round($attendance_pct)."%)</small></div>";
            echo "<div style='background:#ccc; height:6px; width:100px; border-radius:3px; margin-top:2px;'>";
            echo "<div style='background:#28a745; height:100%; width:{$attendance_pct}%; border-radius:3px;'></div>";
            echo "</div>";
            echo "</td>";

            echo "<td>";
            echo "<div><strong>$times_active</strong> / $total_sessions <small>(".round($participation_pct)."%)</small></div>";
            echo "<div style='background:#ccc; height:6px; width:100px; border-radius:3px; margin-top:2px;'>";
            echo "<div style='background:#007bff; height:100%; width:{$participation_pct}%; border-radius:3px;'></div>";
            echo "</div>";
            echo "</td>";

            echo "<td>" . ($total_sessions - $times_present) . "</td>"; 
            echo "<td>$status_msg</td>"; 
            echo "</tr>";
        }
    } catch (Exception $e) { echo "<tr><td colspan='7'>Error: " . $e->getMessage() . "</td></tr>"; }
    ?>
    </tbody>
  </table>

  <input type="hidden" id="rawTotalStudents" value="<?php echo $total_students_count; ?>">

  <br>
  <button onclick="showReport()">View Visual Charts</button>
  <button id="resetBtn">Reset Filters</button>
  <button onclick="window.print()" style="background:#6c757d;">🖨️ Print Report</button>
</section>

<section id="reports" style="display:none;">
  <h2>Global Attendance Analytics</h2>
  <button onclick="showSection('attendance')">← Back to Table</button>
  <br><br>
  
  <div id="reportContainer">
    <div class="stat-box">
        <div>Total Students</div>
        <div class="stat-number" id="rptTotal">0</div>
    </div>
    
    <div style="width: 300px;">
        <canvas id="reportChart"></canvas>
    </div>
  </div>
</section>

<script>
function showSection(id){ 
    // Simple toggle for reports
    if(id === 'home') { /* redirect handled by php */ }
    if(id === 'attendance') { 
        document.getElementById('attendance').style.display = 'block';
        document.getElementById('reports').style.display = 'none';
    }
    if(id === 'reports') { 
        document.getElementById('attendance').style.display = 'none';
        document.getElementById('reports').style.display = 'block';
    }
}

$("#searchInput").on("keyup", function() {
  let value = $(this).val().toLowerCase();
  $("#attendanceTable tbody tr").filter(function() {
    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
  });
});

$("#sortAbsence").click(function() { sortTable(5, 'desc'); });
$("#sortParticipation").click(function() { sortTable(4, 'asc'); }); 

function sortTable(columnIndex, order) {
  let tbody = $("#attendanceTable tbody");
  let rows = tbody.find("tr").toArray(); 
  rows.sort(function(a,b){
    let aText = $(a).find("td").eq(columnIndex).text().replace('%','').trim();
    let bText = $(b).find("td").eq(columnIndex).text().replace('%','').trim();
    let aVal = parseFloat(aText) || 0;
    let bVal = parseFloat(bText) || 0;
    return order==='asc'? aVal-bVal : bVal-aVal;
  });
  tbody.empty().append(rows);
}

$("#resetBtn").click(function(){ $("#searchInput").val(''); $("#attendanceTable tbody tr").show(); });

function showReport() {
  showSection('reports');
  
  const totalStudents = parseInt($("#rawTotalStudents").val()) || 0;
  $("#rptTotal").text(totalStudents);
  
  let goodCount = $(".good").length;
  let warningCount = $(".warning").length;
  let badCount = $(".bad").length;

  const ctx = document.getElementById("reportChart").getContext("2d");
  if(window.attendanceChart) window.attendanceChart.destroy();
  window.attendanceChart = new Chart(ctx, {
    type: "doughnut",
    data: {
        labels: ["Good Standing", "Warning", "Excluded"],
        datasets: [{
            data: [goodCount, warningCount, badCount],
            backgroundColor: ["#28a745", "#ffc107", "#dc3545"]
        }]
    },
    options: { responsive: true }
  });
}
</script>
</body>
</html>