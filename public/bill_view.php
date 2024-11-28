<?php
require "./config.php";
require "../vendor/autoload.php";

try {
    // Check if reservation_id is provided
    if (!isset($_GET["reservation_id"])) {
        throw new Exception("Reservation ID is missing.");
    }

    $reservation_id = $_GET["reservation_id"];

    // Fetch reservation details
    $sql = "SELECT 
                room.check_in_date, 
                room.check_out_date, 
                room.room_name, 
                room.room_type, 
                room.room_price, 
                room.room_id, 
                p.payment_id, 
                p.amount 
            FROM 
                reservations 
            LEFT JOIN 
                rooms AS room ON room.room_id = reservations.room_id 
            LEFT JOIN 
                payment AS p ON p.reservation_id = reservations.reservation_id 
            WHERE 
                reservations.reservation_id = :reservation_id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([":reservation_id" => $reservation_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if data was found
    if (!$row) {
        throw new Exception("No reservation found for ID: $reservation_id");
    }

    // Extract data
    $payment_id = $row["payment_id"] ?? "N/A";
    $total_cost = $row["amount"] ?? 0;
    $room_id = $row["room_id"] ?? "N/A";
    $room_price = $row["room_price"] ?? 0;
    $room_type = $row["room_type"] ?? "N/A";
    $room_name = $row["room_name"] ?? "N/A";
    $date = date("d-m-Y", time());

    $check_in = strtotime($row["check_in_date"] ?? "now");
    $check_out = strtotime($row["check_out_date"] ?? "now");
    $no_days = abs(($check_out - $check_in) / 86400); // Calculate days between check-in and check-out

    // Prepare CSS
    $cssPath = './vendor/bill_style.css';
    if (!file_exists($cssPath)) {
        throw new Exception("CSS file not found at $cssPath");
    }
    $css = file_get_contents($cssPath);

    // Generate HTML
    $html = "
    <body>
        <div id='page-wrap'>
            <p id='header'>INVOICE</p>
            <div style='clear:both'></div>
            <div id='customer'>
                <p id='customer-title'>AAA Hotel, Resorts & Spa International</p>
                <table id='meta'>
                    <tr><td class='meta-head'>Invoice #</td><td>$payment_id</td></tr>
                    <tr><td class='meta-head'>Date</td><td>$date</td></tr>
                    <tr><td class='meta-head'>Amount Due</td><td>&#8377;$total_cost</td></tr>
                </table>
            </div>
            <table id='items'>
                <tr><th>Item</th><th>Description</th><th>Unit Cost</th><th>Days</th><th>Price</th></tr>
                <tr class='item-row'>
                    <td>$room_name</td>
                    <td>$room_type</td>
                    <td>&#8377;$room_price</td>
                    <td>$no_days days</td>
                    <td>&#8377;$total_cost</td>
                </tr>
                <tr><td colspan='4' class='total-line'>Total</td><td>&#8377;$total_cost</td></tr>
            </table>
            <div id='terms'>
                <h5>Terms</h5>
                <p>NET 30 Days. Finance Charge of 1.5% will be made on unpaid balances after 30 days.</p>
            </div>
        </div>
    </body>";

    // Generate PDF
    $mpdf = new \Mpdf\Mpdf();
    $mpdf->writeHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->writeHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
    $mpdf->Output("hotel_reservation_$reservation_id.pdf", 'D');
    exit;

} catch (Exception $e) {
    // Handle errors and display them for debugging
    echo "<div style='color: red; font-weight: bold;'>Error: " . $e->getMessage() . "</div>";
    die();
}
?>
