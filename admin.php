<?php
// Include database configuration
include 'db_config.php';

// Initialize variables
$date_type = $date = "";
$errors = "";
$results = [];
$aggregated_info = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize inputs
    $date_type = $_POST['date_type'];
    $date = trim($_POST['date']);

    // Input validation
    if (empty($date)) {
        $errors = "Please select a date.";
    } else {
        if ($date_type == "request_date") {
            // Fetch requests by request_date
            $stmt = $conn->prepare("SELECT customer_number, request_number, item_description, weight, pickup_suburb, preferred_pickup_date, delivery_suburb, delivery_state, weight FROM requests WHERE DATE(request_date) = ?");
            $stmt->bind_param("s", $date);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $results[] = $row;
                }

                // Calculate total requests and total revenue
                $total_requests = count($results);
                $total_revenue = 0;
                foreach ($results as $req) {
                    $total_revenue += 20 + max(0, ($req['weight'] - 2) * 3);
                }

                $aggregated_info = "Total Requests: $total_requests | Total Revenue: $$total_revenue";
            } else {
                $errors = "No requests found for the selected request date.";
            }

            $stmt->close();
        } elseif ($date_type == "pickup_date") {
            // Fetch requests by preferred_pickup_date
            $stmt = $conn->prepare("SELECT r.customer_number, c.name, c.phone_number, r.request_number, r.item_description, r.weight, r.pickup_address, r.pickup_suburb, r.preferred_pickup_date, r.delivery_suburb, r.delivery_state 
                                    FROM requests r
                                    JOIN customers c ON r.customer_number = c.customer_number
                                    WHERE DATE(r.preferred_pickup_date) = ?
                                    ORDER BY r.pickup_suburb ASC, r.delivery_state ASC, r.delivery_suburb ASC");
            $stmt->bind_param("s", $date);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $results[] = $row;
                }

                // Calculate total requests and total weight
                $total_requests = count($results);
                $total_weight = 0;
                foreach ($results as $req) {
                    $total_weight += $req['weight'];
                }

                $aggregated_info = "Total Requests: $total_requests | Total Weight: $total_weight kg";
            } else {
                $errors = "No requests found for the selected pickup date.";
            }

            $stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ShipOnline System Admin Page</title>
    <style>
        .error { color: red; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        h2{text-align: center;}
        form { width: 50%; margin: auto; display: flex; flex-direction: column; padding:40px; border: 2px solid black; gap: 20px;}
        label { display: block; margin-top: 10px; display: flex;  justify-content: space-evenly; width: 35%; margin: auto;}
        input[type=submit]{ width: 200px; margin: auto;}
    </style>
</head>
<body>
    <h2>ShipOnline System Admin Page</h2>
    <?php if (!empty($errors)) { echo "<p class='error'>$errors</p>"; } ?>
    <form method="POST" action="admin.php">
    <label>Select Date Item for Retrieve:
        <input type="radio" name="date_type" value="request_date" <?php if ($date_type == "request_date" || empty($date_type)) echo "checked"; ?>> Request Date
        <span>&nbsp; &nbsp; &nbsp;</span>
        <input type="radio" name="date_type" value="pickup_date" <?php if ($date_type == "pickup_date") echo "checked"; ?>> Pickup Date
    </label>
    <label>
        Date:
        <input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>" required>
    </label>
    <br>
    <input type="submit" value="Show">
</form>

    <a href="shiponline.php">Home</a>

    <?php if (!empty($results)) { ?>
        <table>
            <thead>
                <?php if ($date_type == "request_date") { ?>
                    <tr>
                        <th>Customer Number</th>
                        <th>Request Number</th>
                        <th>Item Description</th>
                        <th>Weight (kg)</th>
                        <th>Pick-up Suburb</th>
                        <th>Preferred Pick-up Date</th>
                        <th>Delivery Suburb</th>
                        <th>Delivery State</th>
                    </tr>
                <?php } elseif ($date_type == "pickup_date") { ?>
                    <tr>
                        <th>Customer Number</th>
                        <th>Customer Name</th>
                        <th>Contact Phone</th>
                        <th>Request Number</th>
                        <th>Item Description</th>
                        <th>Weight (kg)</th>
                        <th>Pick-up Address</th>
                        <th>Pick-up Suburb</th>
                        <th>Preferred Pick-up Time</th>
                        <th>Delivery Suburb</th>
                        <th>Delivery State</th>
                    </tr>
                <?php } ?>
            </thead>
            <tbody>
                <?php foreach ($results as $row) { ?>
                    <tr>
                        <?php if ($date_type == "request_date") { ?>
                            <td><?php echo htmlspecialchars($row['customer_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['request_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['item_description']); ?></td>
                            <td><?php echo htmlspecialchars($row['weight']); ?></td>
                            <td><?php echo htmlspecialchars($row['pickup_suburb']); ?></td>
                            <td><?php echo htmlspecialchars($row['preferred_pickup_date']); ?></td>
                            <td><?php echo htmlspecialchars($row['delivery_suburb']); ?></td>
                            <td><?php echo htmlspecialchars($row['delivery_state']); ?></td>
                        <?php } elseif ($date_type == "pickup_date") { ?>
                            <td><?php echo htmlspecialchars($row['customer_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['request_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['item_description']); ?></td>
                            <td><?php echo htmlspecialchars($row['weight']); ?></td>
                            <td><?php echo htmlspecialchars($row['pickup_address']); ?></td>
                            <td><?php echo htmlspecialchars($row['pickup_suburb']); ?></td>
                            <td><?php echo htmlspecialchars(date("H:i", strtotime($row['preferred_pickup_date']))); ?></td>
                            <td><?php echo htmlspecialchars($row['delivery_suburb']); ?></td>
                            <td><?php echo htmlspecialchars($row['delivery_state']); ?></td>
                        <?php } ?>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <p><strong><?php echo $aggregated_info; ?></strong></p>
    <?php } ?>
</body>
</html>
