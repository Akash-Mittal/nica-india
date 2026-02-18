<?php

// Get amount from URL
$amount = isset($_GET['amount']) ? intval($_GET['amount']) : 0;

// fallback amount if empty
if ($amount <= 0) {
    $amount = 51;
}

// UPI details
$upi_id = "9588233309@upi";
$name = "NICA-India-Treasurar";

// Build UPI link
$upi_link = "upi://pay?pa={$upi_id}&pn={$name}&am={$amount}&cu=INR";

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Redirecting to UPI Payment</title>

    <script>
        setTimeout(function(){
            window.location.href = "<?php echo $upi_link; ?>";
        }, 800);
    </script>

    <style>
        body {
            font-family: Arial;
            text-align:center;
            padding-top:80px;
        }
        .amount {
            font-size:32px;
            font-weight:bold;
            color:#000;
        }
    </style>

</head>

<body>

<h2>🙏 7th Tradition Offering for Nica 🙏</h2>

<p>Preparing your blessing donation...</p>

<div class="amount">₹<?php echo $amount; ?></div>

<p>Redirecting to UPI payment...</p>

<a href="<?php echo $upi_link; ?>">Click here if not redirected</a>

</body>
</html>
