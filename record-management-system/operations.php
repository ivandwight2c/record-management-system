<?php
// Include config but avoid circular includes
if (!isset($pdo)) {
    require_once 'config.php';
}

// Handle Add User
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

// Handle Edit User
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

// Handle Delete User
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