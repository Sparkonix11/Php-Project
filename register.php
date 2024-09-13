<?php
// Include database configuration
include 'db_config.php';

// Initialize variables
$name = $password = $re_password = $email = $phone_number = "";
$errors = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize inputs
    $name = trim($_POST['name']);
    $password = trim($_POST['password']);
    $re_password = trim($_POST['re_password']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);

    // Input validation
    if (empty($name) || empty($password) || empty($re_password) || empty($email) || empty($phone_number)) {
        $errors = "All fields are required.";
    } elseif ($password !== $re_password) {
        $errors = "Passwords do not match.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors = "Invalid email format.";
    } else {
        // Check if email is unique
        $stmt = $conn->prepare("SELECT customer_number FROM customers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors = "Email is already registered.";
        } else {
            // Hash the password using crypt
            $salt = substr(str_replace('+', '.', base64_encode(openssl_random_pseudo_bytes(16))), 0, 22);
            $hashed_password = crypt($password, '$2y$10$' . $salt . '$');

            // Insert customer data
            $stmt = $conn->prepare("INSERT INTO customers (name, password, email, phone_number) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $hashed_password, $email, $phone_number);

            if ($stmt->execute()) {
                $customer_id = $stmt->insert_id;
                echo "<p>Dear <strong>$name</strong>, you are successfully registered. Your customer number is <strong>$customer_id</strong>.</p>";
                // Optionally, provide a link to login
                echo '<p><a href="login.php">Click here to login</a></p>';
                // Clear form data after successful registration
                $name = $password = $re_password = $email = $phone_number = "";
            } else {
                $errors = "Error: " . $stmt->error;
            }
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
    <title>ShipOnline System Registration Page</title>
    <style>
        .error { color: red; }
        h2{text-align: center;}
        form { width: 50%; margin: auto; display: flex; flex-direction: column; padding:40px; border: 2px solid black; gap: 20px;}
        label { display: block; margin-top: 10px; display: flex;  justify-content: space-between; width: 35%; margin: auto;}
        input[type=submit]{ width: 200px; margin: auto;}
    </style>
</head>
<body>
    <h2>ShipOnline System Registration Page</h2>
    <?php if (!empty($errors)) { echo "<p class='error'>$errors</p>"; } ?>
    <form method="POST" action="register.php">
        <label>Name:
            <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
        </label>
        <label>Password:
            <input type="password" name="password" required>
        </label>
        <label>Confirm Password:
            <input type="password" name="re_password" required>
        </label>
        <label>Email Address:
            <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
        </label>
        <label>Phone Number:
            <input type="text" name="phone_number" value="<?php echo htmlspecialchars($phone_number); ?>" required>
        </label>
        <br>
        <input type="submit" value="Register">
    </form>
    <a href="shiponline.php">Home</a>
</body>
</html>
