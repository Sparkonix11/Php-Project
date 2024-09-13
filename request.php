<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['customer_number'])) {
    header("Location: login.php");
    exit();
}

$customer_number = $_SESSION['customer_number'];

// Include database configuration
include 'db_config.php';

// Initialize variables
$item_description = $weight = $pickup_address = $pickup_suburb = "";
$preferred_pickup_date = $receiver_name = $delivery_address = $delivery_suburb = $delivery_state = "";
$errors = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize inputs
    $item_description = trim($_POST['item_description']);
    $weight = intval($_POST['weight']);
    $pickup_address = trim($_POST['pickup_address']);
    $pickup_suburb = trim($_POST['pickup_suburb']);
    $preferred_pickup_date = trim($_POST['preferred_pickup_date']);
    $receiver_name = trim($_POST['receiver_name']);
    $delivery_address = trim($_POST['delivery_address']);
    $delivery_suburb = trim($_POST['delivery_suburb']);
    $delivery_state = trim($_POST['delivery_state']);

    // Input validation
    if (empty($item_description) || empty($weight) || empty($pickup_address) || empty($pickup_suburb) ||
        empty($preferred_pickup_date) || empty($receiver_name) || empty($delivery_address) ||
        empty($delivery_suburb) || empty($delivery_state)) {
        $errors = "All fields are required.";
    } elseif ($weight < 2 || $weight > 20) {
        $errors = "Weight must be between 2 and 20 kg.";
    } else {
        // Validate preferred pickup date and time
        $current_time = new DateTime();
        $pickup_time = DateTime::createFromFormat('Y-m-d\TH:i', $preferred_pickup_date);
        if (!$pickup_time) {
            $errors = "Invalid pickup date and time format.";
        } else {
            $interval = $current_time->diff($pickup_time);
            $hours_diff = ($interval->days * 24) + $interval->h + ($interval->i / 60);
            if ($pickup_time <= $current_time->modify('+24 hours')) {
                $errors = "Preferred pickup date and time must be at least 24 hours from now.";
            } elseif ($pickup_time->format('H:i') < '08:00' || $pickup_time->format('H:i') > '20:00') {
                $errors = "Preferred pickup time must be between 08:00 and 20:00.";
            }
        }
    }

    if (empty($errors)) {
        // Calculate cost
        $cost = 20 + max(0, ($weight - 2) * 3);

        // Insert request into database
        $stmt = $conn->prepare("INSERT INTO requests (customer_number, item_description, weight, pickup_address, pickup_suburb, preferred_pickup_date, receiver_name, delivery_address, delivery_suburb, delivery_state) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isisssssss", $customer_number, $item_description, $weight, $pickup_address, $pickup_suburb, $preferred_pickup_date, $receiver_name, $delivery_address, $delivery_suburb, $delivery_state);

        if ($stmt->execute()) {
            $request_number = $stmt->insert_id;
            $request_date = date("Y-m-d H:i:s"); // Current timestamp

            // Fetch customer details for email
            $stmt_customer = $conn->prepare("SELECT name, email FROM customers WHERE customer_number = ?");
            $stmt_customer->bind_param("i", $customer_number);
            $stmt_customer->execute();
            $stmt_customer->bind_result($customer_name, $customer_email);
            $stmt_customer->fetch();
            $stmt_customer->close();

            // Prepare confirmation message
            $success_message = "Thank you! Your request number is <strong>$request_number</strong>. The cost is <strong>$$cost</strong>. We will pick-up the item at <strong>" . date("H:i", strtotime($preferred_pickup_date)) . "</strong> on <strong>" . date("Y-m-d", strtotime($preferred_pickup_date)) . "</strong>.";

            // Send confirmation email
            $to = $customer_email;
            $subject = "Shipping Request with ShipOnline";
            $message = "Dear $customer_name,\n\nThank you for using ShipOnline! Your request number is $request_number. The cost is $$cost. We will pick-up the item at " . date("H:i", strtotime($preferred_pickup_date)) . " on " . date("Y-m-d", strtotime($preferred_pickup_date)) . ".\n\nBest regards,\nShipOnline Team";
            $headers = "From: no-reply@shiponline.com\r\n";
            // Add envelope sender for bounce messages
            $additional_parameters = "-r 1234567@student.swin.edu.au";
            mail($to, $subject, $message, $headers, $additional_parameters);

            // Clear form data after successful submission
            $item_description = $weight = $pickup_address = $pickup_suburb = "";
            $preferred_pickup_date = $receiver_name = $delivery_address = $delivery_suburb = $delivery_state = "";
        } else {
            $errors = "Error submitting request: " . $stmt->error;
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
    <title>Shipping Request</title>
    <style>
        .error { color: red; }
        .success { color: green; }
        h2{text-align: center;}
        form { width: 50%; margin: auto;  padding:40px; display: flex; flex-direction: column;  gap: 20px;}
        fieldset{display: flex; flex-direction: column;  gap: 10px;}
        select{width: 50%;}
        label { display: block; margin-top: 10px; display: flex;  justify-content: space-between; width: 35%; margin: auto;}
        input[type=submit]{ width: 200px; margin: auto;}
    </style>
</head>
<body>
    <h2>Submit a Shipping Request</h2>
    <?php
    if (!empty($errors)) {
        echo "<p class='error'>$errors</p>";
    }
    if (!empty($success_message)) {
        echo "<p class='success'>$success_message</p>";
    }
    ?>
    <form method="POST" action="request.php">
        <fieldset>
            <legend>Item Information</legend>
            <label>Item Description:
                <input type="text" name="item_description" value="<?php echo htmlspecialchars($item_description); ?>" required>
            </label>
            <label>Weight (kg):
                <select name="weight" required>
                    <?php for ($i = 2; $i <= 20; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php if ($weight == $i) echo "selected"; ?>><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </label>
        </fieldset>

        <fieldset>
            <legend>Pick-up Information</legend>
            <label>Pick-up Address:
                <input type="text" name="pickup_address" value="<?php echo htmlspecialchars($pickup_address); ?>" required>
            </label>
            <label>Pick-up Suburb:
                <input type="text" name="pickup_suburb" value="<?php echo htmlspecialchars($pickup_suburb); ?>" required>
            </label>
            <label>Preferred Pick-up Date and Time:
                <input type="datetime-local" name="preferred_pickup_date" value="<?php echo htmlspecialchars($preferred_pickup_date); ?>" required>
            </label>
        </fieldset>

        <fieldset>
            <legend>Delivery Information</legend>
            <label>Receiver Name:
                <input type="text" name="receiver_name" value="<?php echo htmlspecialchars($receiver_name); ?>" required>
            </label>
            <label>Delivery Address:
                <input type="text" name="delivery_address" value="<?php echo htmlspecialchars($delivery_address); ?>" required>
            </label>
            <label>Delivery Suburb:
                <input type="text" name="delivery_suburb" value="<?php echo htmlspecialchars($delivery_suburb); ?>" required>
            </label>
            <label>Delivery State:
                <select name="delivery_state" required>
                    <option value="">Select State</option>
                    <option value="NSW" <?php if ($delivery_state == "NSW") echo "selected"; ?>>NSW</option>
                    <option value="VIC" <?php if ($delivery_state == "VIC") echo "selected"; ?>>VIC</option>
                    <option value="QLD" <?php if ($delivery_state == "QLD") echo "selected"; ?>>QLD</option>
                    <option value="WA" <?php if ($delivery_state == "WA") echo "selected"; ?>>WA</option>
                    <option value="SA" <?php if ($delivery_state == "SA") echo "selected"; ?>>SA</option>
                    <option value="TAS" <?php if ($delivery_state == "TAS") echo "selected"; ?>>TAS</option>
                    <option value="ACT" <?php if ($delivery_state == "ACT") echo "selected"; ?>>ACT</option>
                    <option value="NT" <?php if ($delivery_state == "NT") echo "selected"; ?>>NT</option>
                </select>
            </label>
        </fieldset>

        <br>
        <p>Pricing Information: $20 for 0-2 kg and $3 for each additional kg.</p>
        <input type="submit" value="Submit Request">
    </form>
</body>
</html>
