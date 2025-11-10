<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Direct database connection
$host = 'localhost';
$dbname = 'record_management';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

// Handle Add User (direct in dashboard)
if (isset($_POST['add_user'])) {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $user_role = $_POST['user_role'];
    
    try {
        // Check if username or email already exists
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $checkStmt->execute([$username, $email]);
        
        if ($checkStmt->rowCount() > 0) {
            $_SESSION['message'] = "Username or email already exists";
            $_SESSION['message_type'] = "error";
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (fullname, email, username, password, user_role) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$fullname, $email, $username, $password, $user_role]);
            $_SESSION['message'] = "User added successfully!";
            $_SESSION['message_type'] = "success";
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = "Error adding user";
        $_SESSION['message_type'] = "error";
    }
    header('Location: dashboard.php');
    exit();
}

// Handle Edit User (direct in dashboard)
if (isset($_POST['edit_user'])) {
    $id = $_POST['id'];
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $user_role = $_POST['user_role'];
    
    try {
        // Check if username or email already exists (excluding current user)
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $checkStmt->execute([$username, $email, $id]);
        
        if ($checkStmt->rowCount() > 0) {
            $_SESSION['message'] = "Username or email already exists";
            $_SESSION['message_type'] = "error";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET fullname = ?, email = ?, username = ?, user_role = ? WHERE id = ?");
            $stmt->execute([$fullname, $email, $username, $user_role, $id]);
            $_SESSION['message'] = "User updated successfully!";
            $_SESSION['message_type'] = "success";
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = "Error updating user";
        $_SESSION['message_type'] = "error";
    }
    header('Location: dashboard.php');
    exit();
}

// Handle Delete User (direct in dashboard)
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    
    // Prevent user from deleting themselves
    if ($id == $_SESSION['user_id']) {
        $_SESSION['message'] = "Cannot delete your own account";
        $_SESSION['message_type'] = "error";
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['message'] = "User deleted successfully!";
            $_SESSION['message_type'] = "success";
        } catch (PDOException $e) {
            $_SESSION['message'] = "Error deleting user";
            $_SESSION['message_type'] = "error";
        }
    }
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Record Management System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>Record Management System</h1>
            <div class="user-info">
                Welcome, <?php echo $_SESSION['username']; ?> 
                (<?php echo $_SESSION['user_role']; ?>)
                <a href="?logout" class="logout-btn">Logout</a>
            </div>
        </header>

        <main class="main-content">
            <div class="section-header">
                <h2>User Management</h2>
                <button onclick="openAddModal()" class="btn btn-primary">Add New User</button>
            </div>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert <?php echo $_SESSION['message_type']; ?>">
                    <?php 
                    echo $_SESSION['message']; 
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th>User Role</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
                        while ($row = $stmt->fetch()):
                        ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><span class="role-badge"><?php echo $row['user_role']; ?></span></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($row['date_created'])); ?></td>
                            <td class="actions">
                                <button onclick="openEditModal(
                                    <?php echo $row['id']; ?>,
                                    '<?php echo addslashes($row['fullname']); ?>',
                                    '<?php echo addslashes($row['email']); ?>',
                                    '<?php echo addslashes($row['username']); ?>',
                                    '<?php echo $row['user_role']; ?>'
                                )" class="btn btn-edit">Edit</button>
                                
                                <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                <button onclick="confirmDelete(<?php echo $row['id']; ?>)" class="btn btn-delete">Delete</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Add User Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddModal()">&times;</span>
            <h3>Add New User</h3>
            <form method="POST">
                <input type="hidden" name="add_user" value="1">
                <div class="form-group">
                    <label>Full Name:</label>
                    <input type="text" name="fullname" required>
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>User Role:</label>
                    <select name="user_role" required>
                        <option value="Admin">Admin</option>
                        <option value="Staff">Staff</option>
                        <option value="User">User</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" onclick="closeAddModal()" class="btn btn-cancel">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h3>Edit User</h3>
            <form method="POST">
                <input type="hidden" name="edit_user" value="1">
                <input type="hidden" name="id" id="editId">
                <div class="form-group">
                    <label>Full Name:</label>
                    <input type="text" name="fullname" id="editFullname" required>
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" id="editEmail" required>
                </div>
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" name="username" id="editUsername" required>
                </div>
                <div class="form-group">
                    <label>User Role:</label>
                    <select name="user_role" id="editUserRole" required>
                        <option value="Admin">Admin</option>
                        <option value="Staff">Staff</option>
                        <option value="User">User</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="button" onclick="closeEditModal()" class="btn btn-cancel">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal Functions
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }

        function openEditModal(id, fullname, email, username, userRole) {
            document.getElementById('editId').value = id;
            document.getElementById('editFullname').value = fullname;
            document.getElementById('editEmail').value = email;
            document.getElementById('editUsername').value = username;
            document.getElementById('editUserRole').value = userRole;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Delete Confirmation
        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this user?')) {
                window.location.href = 'dashboard.php?delete_id=' + id;
            }
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const addModal = document.getElementById('addModal');
            const editModal = document.getElementById('editModal');
            
            if (event.target === addModal) {
                closeAddModal();
            }
            if (event.target === editModal) {
                closeEditModal();
            }
        }

        // Form Validation
        document.addEventListener('DOMContentLoaded', function() {
            const addForm = document.querySelector('form');
            if (addForm && addForm.querySelector('input[name="password"]')) {
                addForm.addEventListener('submit', function(e) {
                    const password = this.querySelector('input[name="password"]');
                    if (password && password.value.length < 6) {
                        e.preventDefault();
                        alert('Password must be at least 6 characters long');
                    }
                });
            }
        });
    </script>
</body>
</html>