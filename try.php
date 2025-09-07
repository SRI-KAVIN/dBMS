<?php
// ==================== DB CONNECTION ====================
$host = 'localhost';
$user = 'root';   // default XAMPP username
$pass = '';       // default XAMPP password is empty
$dbname = 'blood_db';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ==================== TABLE CREATION (first run only) ====================
$conn->query("CREATE TABLE IF NOT EXISTS donors (
    donorid INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    region VARCHAR(100),
    phonenumber VARCHAR(20),
    bloodgroup VARCHAR(5),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// ==================== VARIABLES ====================
$action = $_GET['action'] ?? '';
$msg = '';
$errors = [];

// ==================== ADD DONOR ====================
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $region = trim($_POST['region'] ?? '');
    $phonenumber = trim($_POST['phonenumber'] ?? '');
    $bloodgroup = strtoupper(trim($_POST['bloodgroup'] ?? ''));

    if ($name === '') $errors[] = "Name is required.";
    if ($phonenumber === '' || !preg_match('/^[0-9]{7,15}$/', $phonenumber)) $errors[] = "Enter a valid phone number.";
    if ($bloodgroup === '' || !preg_match('/^(A|B|AB|O)[+-]$/i', $bloodgroup)) $errors[] = "Blood group invalid.";

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO donors (name, region, phonenumber, bloodgroup) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $region, $phonenumber, $bloodgroup);
        if ($stmt->execute()) {
            $msg = "Donor added successfully.";
        } else {
            $errors[] = "Error: " . $stmt->error;
        }
    }
}

// ==================== UPDATE DONOR ====================
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $donorid = intval($_POST['donorid']);
    $name = trim($_POST['name'] ?? '');
    $region = trim($_POST['region'] ?? '');
    $phonenumber = trim($_POST['phonenumber'] ?? '');
    $bloodgroup = strtoupper(trim($_POST['bloodgroup'] ?? ''));

    if ($name === '') $errors[] = "Name is required.";
    if ($phonenumber === '' || !preg_match('/^[0-9]{7,15}$/', $phonenumber)) $errors[] = "Enter a valid phone number.";
    if ($bloodgroup === '' || !preg_match('/^(A|B|AB|O)[+-]$/i', $bloodgroup)) $errors[] = "Blood group invalid.";

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE donors SET name=?, region=?, phonenumber=?, bloodgroup=? WHERE donorid=?");
        $stmt->bind_param("ssssi", $name, $region, $phonenumber, $bloodgroup, $donorid);
        if ($stmt->execute()) {
            $msg = "Donor updated successfully.";
        } else {
            $errors[] = "Update failed: " . $stmt->error;
        }
    }
}

// ==================== FETCH SINGLE DONOR FOR EDIT ====================
$editDonor = null;
if ($action === 'edit') {
    $donorid = intval($_GET['donorid'] ?? 0);
    if ($donorid > 0) {
        $stmt = $conn->prepare("SELECT * FROM donors WHERE donorid=?");
        $stmt->bind_param("i", $donorid);
        $stmt->execute();
        $res = $stmt->get_result();
        $editDonor = $res->fetch_assoc();
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Blood Donor Information System</title>
</head>
<body>
<h2>Blood Donor Information System</h2>

<?php if ($msg): ?>
  <p style="color:green"><?php echo htmlspecialchars($msg); ?></p>
<?php endif; ?>

<?php if (!empty($errors)): ?>
  <div style="color:red"><ul><?php foreach ($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul></div>
<?php endif; ?>

<!-- ==================== ADD/EDIT FORM ==================== -->
<?php if ($action === 'edit' && $editDonor): ?>
  <h3>Edit Donor #<?php echo $editDonor['donorid']; ?></h3>
  <form method="post" action="?action=update">
    <input type="hidden" name="donorid" value="<?php echo $editDonor['donorid']; ?>">
    <label>Name:<br><input type="text" name="name" value="<?php echo htmlspecialchars($editDonor['name']); ?>" required></label><br><br>
    <label>Region:<br><input type="text" name="region" value="<?php echo htmlspecialchars($editDonor['region']); ?>"></label><br><br>
    <label>Phone Number:<br><input type="text" name="phonenumber" value="<?php echo htmlspecialchars($editDonor['phonenumber']); ?>" required></label><br><br>
    <label>Blood Group:<br><input type="text" name="bloodgroup" value="<?php echo htmlspecialchars($editDonor['bloodgroup']); ?>" required></label><br><br>
    <button type="submit">Update Donor</button>
  </form>
  <p><a href="blood_donor.php">Back to list</a></p>

<?php else: ?>
  <h3>Add Donor</h3>
  <form method="post" action="?action=add">
    <label>Name:<br><input type="text" name="name" required></label><br><br>
    <label>Region:<br><input type="text" name="region"></label><br><br>
    <label>Phone Number:<br><input type="text" name="phonenumber" required></label><br><br>
    <label>Blood Group:<br><input type="text" name="bloodgroup" placeholder="e.g. A+ or O-" required></label><br><br>
    <button type="submit">Add Donor</button>
  </form>
<?php endif; ?>

<hr>

<!-- ==================== DISPLAY DONORS ==================== -->
<h3>Donor List</h3>
<table border="1" cellpadding="6" cellspacing="0">
<tr>
  <th>ID</th><th>Name</th><th>Region</th><th>Phone</th><th>Blood Group</th><th>Created At</th><th>Actions</th>
</tr>
<?php
$res = $conn->query("SELECT * FROM donors ORDER BY donorid DESC");
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        echo "<tr>";
        echo "<td>".htmlspecialchars($row['donorid'])."</td>";
        echo "<td>".htmlspecialchars($row['name'])."</td>";
        echo "<td>".htmlspecialchars($row['region'])."</td>";
        echo "<td>".htmlspecialchars($row['phonenumber'])."</td>";
        echo "<td>".htmlspecialchars($row['bloodgroup'])."</td>";
        echo "<td>".htmlspecialchars($row['created_at'])."</td>";
        echo "<td><a href='?action=edit&donorid=".$row['donorid']."'>Edit</a></td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='7'>No donors found.</td></tr>";
}
?>
</table>
</body>
</html>
