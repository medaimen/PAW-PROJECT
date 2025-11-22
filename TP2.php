<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Attendance System - TP2</title>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { font-family: Arial, sans-serif; margin:0; background:#f8f9fa; }
nav { background:#007bff; color:#fff; padding:15px 20px; display:flex; justify-content:space-between; align-items:center; }
.nav-links { list-style:none; display:flex; gap:20px; margin:0; padding:0; }
.nav-links li { cursor:pointer; font-weight:bold; }
.nav-links li:hover { text-decoration:underline; color:#dce9ff; }
.logout-btn { background:#dc3545; border:none; padding:8px 15px; border-radius:5px; color:#fff; cursor:pointer; font-weight:bold; }
section { padding:30px; display:none; }
section.active { display:block; }
table { width:100%; border-collapse:collapse; margin-top:20px; background:#fff; }
th,td { border:1px solid #333; padding:8px; text-align:center; }
th { background:#007bff; color:white; }
.good { background-color:#b7f0b1 !important; }
.warning { background-color:#fff6b3 !important; }
.bad { background-color:#ffb3b3 !important; }
.delete-btn { background:#dc3545; color:#fff; border:none; padding:6px 10px; border-radius:4px; cursor:pointer; }
.delete-btn:hover { background:#b02a37; }
form { background:white; padding:20px; border-radius:10px; box-shadow:0 2px 6px rgba(0,0,0,0.1); max-width:400px; }
label { display:block; margin-top:10px; font-weight:bold; }
input[type=text],input[type=email]{ width:100%; padding:8px; margin-top:5px; border:1px solid #ccc; border-radius:5px; }
.error { color:red; font-size:0.9em; }
button { margin-top:15px; padding:10px 20px; background:#007bff; color:white; border:none; border-radius:5px; cursor:pointer; }
button:hover { background:#0056b3; }
#reportContainer { max-width:600px; margin:auto; background:white; border-radius:10px; padding:20px; box-shadow:0 2px 6px rgba(0,0,0,0.1); text-align:center; }
#searchInput { padding: 8px; margin-top: 5px; width: 250px; }
#sortStatus { margin-top: 15px; font-style: italic; color: #555; }
</style>
</head>
<body>

<nav>
  <div><strong>Attendance System</strong></div>
  <ul class="nav-links">
    <li onclick="showSection('home')">Home</li>
    <li onclick="showSection('attendance')">Attendance List</li>
    <li onclick="showSection('addStudent')">Add Student</li>
    <li onclick="showReport()">Reports</li>
  </ul>
  <button class="logout-btn" onclick="logout()">Logout</button>
</nav>

<section id="home" class="active">
  <h2>Welcome to the Student Attendance System</h2>
  <p>This system lets teachers track attendance and participation, evaluate students, and generate visual reports easily.</p>
</section>

<section id="attendance">
  <h2>Attendance Table</h2>

  <label for="searchInput"><b>Search by Name:</b></label>
  <input type="text" id="searchInput" placeholder="Type a name to filter...">

  <table id="attendanceTable">
    <thead>
      <tr>
        <th>Last Name</th><th>First Name</th>
        <th colspan="6">Sessions (S1–S6)</th>
        <th colspan="6">Participation (P1–P6)</th>
        <th>Absences</th><th>Participation</th><th>Message</th><th>Action</th>
      </tr>
    </thead>
    <tbody>
      <tbody>
<?php
$studentsFile = 'students.json';

if (file_exists($studentsFile)) {
    $json = file_get_contents($studentsFile);
    $students = json_decode($json, true);

    if (is_array($students)) {
        foreach ($students as $s) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($s['name']) . "</td>";
            echo "<td> - </td>"; // Optional if no first name
            echo str_repeat("<td><input type='checkbox'></td>", 12);
            echo "<td></td><td></td><td></td>";
            echo "<td><button class='delete-btn'>Delete</button></td>";
            echo "</tr>";
        }
    }
}
?>
</tbody>

    </tbody>
  </table>
  <br>
  <button onclick="evaluateAttendance()">Evaluate Attendance</button>
  <button onclick="showReport()">Show Report</button>
  <button id="highlightBtn">Highlight Excellent Students</button>
  <button id="resetBtn">Reset Colors</button>

  <button id="sortAbsence">Sort by Absences (Ascending)</button>
  <button id="sortParticipation">Sort by Participation (Descending)</button>

  <div id="sortStatus"></div>
</section>

<section id="addStudent">
  <h2>Add New Student</h2>
  <!-- Updated form to submit to PHP -->
     <form action="add_student.php" method="post" id="studentForm">

        <label>Student ID:</label>
        <input type="text" name="student_id" id="student_id">
        <span class="error" id="idError"></span>

        <label>Name:</label>
        <input type="text" name="name" id="name">
        <span class="error" id="nameError"></span>

        <label>Group:</label>
        <input type="text" name="group" id="group">
        <span class="error" id="groupError"></span>

        <button type="submit">Add Student</button>

    </form>
</section>

<section id="reports">
  <h2>Attendance Report</h2>
  <div id="reportContainer">
    <p><strong>Total Students:</strong> <span id="totalStudents">0</span></p>
    <p><strong>Students Present:</strong> <span id="presentStudents">0</span></p>
    <p><strong>Students Participating:</strong> <span id="participatingStudents">0</span></p>
    <canvas id="reportChart"></canvas>
    <br>
    <h3>Overall Course Success</h3>
    <canvas id="successChart" width="200" height="200"></canvas>
    <p><strong>Success Rate:</strong> <span id="successRate">0%</span></p>
  </div>
</section>

<script>
// --- Navigation ---
function showSection(id){ $("section").removeClass("active"); $("#"+id).addClass("active"); }
function logout(){ alert("Logged out successfully!"); showSection("home"); }

// --- Attendance Evaluation ---
function evaluateAttendance(){
  $("#attendanceTable tbody tr").each(function(){ 
    const boxes=$(this).find("input[type='checkbox']");
    const sessions=boxes.slice(0,6);
    const parts=boxes.slice(6,12);
    const abs=sessions.filter(":not(:checked)").length;
    const par=parts.filter(":checked").length;
    $(this).find("td").eq(14).text(abs+" Abs");
    $(this).find("td").eq(15).text(par+" Par");
    let msg="";
    $(this).removeClass("good warning bad");
    if(abs<3){ $(this).addClass("good"); msg="Good attendance – Excellent participation"; }
    else if(abs<=4){ $(this).addClass("warning"); msg="Warning – attendance low – You need to participate more"; }
    else{ $(this).addClass("bad"); msg="Excluded – too many absences – You need to participate more"; }
    $(this).find("td").eq(16).text(msg);
  });
}

// --- Search & Sort Functions ---
$("#searchInput").on("keyup", function() {
  let value = $(this).val().toLowerCase();
  $("#attendanceTable tbody tr").filter(function() {
    let lastName = $(this).find("td:eq(0)").text().toLowerCase();
    let firstName = $(this).find("td:eq(1)").text().toLowerCase();
    $(this).toggle(
      lastName.startsWith(value) || 
      firstName.startsWith(value) ||
      (firstName + " " + lastName).startsWith(value)
    );
  });
});

$("#sortAbsence").click(function() { evaluateAttendance(); sortTable(14,'asc'); $("#sortStatus").text("Currently sorted by absences (ascending)."); });
$("#sortParticipation").click(function() { evaluateAttendance(); sortTable(15,'desc'); $("#sortStatus").text("Currently sorted by participation (descending)."); });

function sortTable(columnIndex, order) {
  let tbody = $("#attendanceTable tbody");
  let rows = tbody.find("tr").toArray(); 
  rows.sort(function(a,b){
    let aVal=parseInt($(a).find("td").eq(columnIndex).text())||0;
    let bVal=parseInt($(b).find("td").eq(columnIndex).text())||0;
    return order==='asc'?aVal-bVal:bVal-aVal;
  });
  tbody.empty().append(rows);
}

// --- Highlight & Reset ---
$("#highlightBtn").click(function(){
  $("#attendanceTable tbody tr").each(function(){ 
    const absText=$(this).find("td").eq(14).text();
    if(absText.includes("Abs") && parseInt(absText)<3){
        $(this).fadeOut(400).fadeIn(400).animate({opacity:1},1200).css("background","#b7f0b1");
    }
  });
});
$("#resetBtn").click(function(){ $("#attendanceTable tr").removeAttr("style").removeClass("good warning bad"); });

// --- Delete Student ---
$(document).on("click",".delete-btn",function(){ if(confirm("Delete this student?")) $(this).closest("tr").remove(); });

// --- Report Chart ---
function showReport() {
  showSection("reports");
  const rows = $("#attendanceTable tbody tr"); 
  const total = rows.length;
  let totalAttendance = 0;
  let totalParticipation = 0;

  rows.each(function() {
    const boxes = $(this).find("input[type='checkbox']");
    const sessions = boxes.slice(0, 6);
    const parts = boxes.slice(6, 12);
    totalAttendance += sessions.length > 0 ? sessions.filter(":checked").length / sessions.length : 0;
    totalParticipation += parts.length > 0 ? parts.filter(":checked").length / parts.length : 0;
  });

  const attendancePercent = total > 0 ? (totalAttendance / total) * 100 : 0;
  const participationPercent = total > 0 ? (totalParticipation / total) * 100 : 0;
  const avgRate = (attendancePercent + participationPercent) / 2;

  $("#totalStudents").text(total);
  $("#presentStudents").text(Math.round(totalAttendance));
  $("#participatingStudents").text(Math.round(totalParticipation));
  $("#successRate").text(avgRate.toFixed(1) + "%");

  const ctx = document.getElementById("reportChart").getContext("2d");
  if(window.attendanceChart) window.attendanceChart.destroy();
  window.attendanceChart=new Chart(ctx,{
    type:"bar",
    data:{labels:["Total","Present","Participated"], datasets:[{label:"Report",data:[total,Math.round(totalAttendance),Math.round(totalParticipation)],backgroundColor:["#007bff","#28a745","#ffc107"]}]},
    options:{responsive:true, scales:{y:{beginAtZero:true}}}
  });

  const ctx2=document.getElementById("successChart").getContext("2d");
  if(window.successChart) window.successChart.destroy();
  window.successChart=new Chart(ctx2,{
    type:"doughnut",
    data:{labels:["Success","Remaining"], datasets:[{data:[avgRate,100-avgRate], backgroundColor:["#28a745","#ff4d4f"], borderColor:"#fff", borderWidth:2}]},
    options:{cutout:"50%", responsive:true, plugins:{legend:{display:false}, tooltip:{enabled:true}}}
  });
}
</script>
</body>
</html>
