<?php
// Start session
session_start();

// Include database configuration
include 'db_config.php';

// Initialize variables
$customer_number = $password = "";
$errors = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize inputs
    $customer_number = trim($_POST['customer_number']);
    $password = trim($_POST['password']);

    // Input validation
    if (empty($customer_number) || empty($password)) {
        $errors = "Both Customer Number and Password are required.";
    } else {
        // Fetch customer data
        $stmt = $conn->prepare("SELECT password FROM customers WHERE customer_number = ?");
        $stmt->bind_param("i", $customer_number);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($hashed_password);
            $stmt->fetch();

            // Verify password
            $salt = substr($hashed_password, 7, 22); // Extract salt from hashed password
            if (crypt($password, '$2y$10$' . $salt . '$') === $hashed_password) {
                // Successful login
                $_SESSION['customer_number'] = $customer_number;
                header("Location: request.php");
                exit();
            } else {
                $errors = "Invalid Customer Number or Password.";
            }
        } else {
            $errors = "Invalid Customer Number or Password.";
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Login</title>
    <style>
        .error { color: red; }
        h2{text-align: center;}
        form { width: 50%; margin: auto; display: flex; flex-direction: column; padding:40px; border: 2px solid black; gap: 20px;}
        label { display: block; margin-top: 10px; display: flex;  justify-content: space-between; width: 35%; margin: auto;}
        input[type=submit]{ width: 200px; margin: auto;}
    </style>
</head>
<body>
    <h2>Customer Login</h2>
    <?php if (!empty($errors)) { echo "<p class='error'>$errors</p>"; } ?>
    <form method="POST" action="login.php">
        <label>Customer Number:
            <input type="number" name="customer_number" value="<?php echo htmlspecialchars($customer_number); ?>" required>
        </label>
        <label>Password:
            <input type="password" name="password" required>
        </label>
        <br>
        <input type="submit" value="Login">
    </form>
    <a href="shiponline.php">Home</a>
</body>
</html>
