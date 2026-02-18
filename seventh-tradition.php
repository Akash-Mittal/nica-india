<?php

// Get amount from URL
$amount = isset($_GET['amount']) ? intval($_GET['amount']) : 0;
date_default_timezone_set('Asia/Kolkata');

if ($amount <= 0) {
    $amount = 51;
}

// UPI details
$upi_id = "anupamtyagi079@okicici";
$name = "Anupam T(NICA-India Online Treasurer)";
$datetime = date('d M Y, h:i A');
$message = "7th tradition contribution for $name made on $datetime";
$upi_link = "upi://pay?pa=" . urlencode($upi_id) .
        "&pn=" . urlencode($name) .
        "&am=" . urlencode($amount) .
        "&tn=" . urlencode($message) .
        "&cu=INR";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>(NICA-India-Online 7th Tradition)Redirecting to UPI Payment</title>

    <link rel="stylesheet" href="style.css">

    <script>
        setTimeout(function(){
            window.location.href = "<?php echo $upi_link; ?>";
        }, 800);
    </script>

</head>

<body>

<div class="container">
    <div class="card">

        <h2>🙏 7th Tradition Offering for Nica 🙏</h2>

        <p class="subtext">Preparing your blessing donation...</p>

        <div class="amount">₹<?php echo $amount; ?></div>

        <p class="subtext">Redirecting to UPI payment...</p>

        <a class="btn" href="<?php echo $upi_link; ?>">
            Click here if not redirected
        </a>

    </div>
</div>

</body>

</html>
