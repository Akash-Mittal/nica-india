<?php
date_default_timezone_set('Asia/Kolkata');

$allowed = [21, 51, 101, 501, 1001];
$amount = isset($_GET['amount']) ? intval($_GET['amount']) : 51;
if (!in_array($amount, $allowed, true)) $amount = 51;

$upi_id = "anupamtyagi079@okicici";
$name = "Anupam T(NICA-India Online Treasurer)";
$datetime = date('d M Y, h:i A');
$message = "7th tradition contribution for $name made on $datetime";

$upi_link = "upi://pay?pa=" . urlencode($upi_id)
        . "&pn=" . urlencode($name)
        . "&am=" . urlencode($amount)
        . "&tn=" . urlencode($message)
        . "&cu=INR";

$qr_data = $upi_link;
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=" . urlencode($qr_data);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NICA 7th Tradition Contribution</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="container">
    <div class="card">
        <h2>🙏 7th Tradition Offering for Nica 🙏</h2>

        <p class="subtext">Preparing your contribution…</p>
        <div class="amount">₹<?php echo htmlspecialchars((string)$amount, ENT_QUOTES); ?></div>

        <div class="hr"></div>

        <p><strong>Paying to:</strong> <?php echo htmlspecialchars($name, ENT_QUOTES); ?></p>
        <p><strong>UPI ID:</strong> <span id="upiId"><?php echo htmlspecialchars($upi_id, ENT_QUOTES); ?></span></p>
        <p><strong>Note:</strong> <span id="note"><?php echo htmlspecialchars($message, ENT_QUOTES); ?></span></p>

        <div class="hr"></div>

        <div id="androidBlock" style="display:none;">
            <p class="subtext">Redirecting to UPI app…</p>
            <a class="btn" href="<?php echo htmlspecialchars($upi_link, ENT_QUOTES); ?>">Tap if not redirected</a>
        </div>

        <div id="iosBlock" style="display:none;">
            <p class="subtext">On iPhone, UPI apps may not open directly from browser/WhatsApp.</p>

            <div style="text-align:center; margin: 12px 0;">
                <img src="<?php echo htmlspecialchars($qr_url, ENT_QUOTES); ?>" alt="UPI QR" style="max-width:260px; width:100%; height:auto;">
                <p class="meta">Scan this QR in any UPI app to pay ₹<?php echo htmlspecialchars((string)$amount, ENT_QUOTES); ?>.</p>
            </div>

            <div style="display:flex; gap:10px; flex-wrap:wrap; justify-content:center; margin-top:10px;">
                <button class="btn" type="button" id="copyUpi">Copy UPI ID</button>
                <button class="btn" type="button" id="copyNote">Copy Note</button>
                <button class="btn" type="button" id="copyAmount">Copy Amount</button>
            </div>

            <p class="meta" style="text-align:center; margin-top:12px;">
                Or open your UPI app → Pay to UPI ID → paste UPI ID + note + amount.
            </p>
        </div>

    </div>
</div>

<script>
    (function(){
        var upiLink = <?php echo json_encode($upi_link); ?>;
        var amount = <?php echo json_encode((string)$amount); ?>;
        var upiId = <?php echo json_encode($upi_id); ?>;
        var note = <?php echo json_encode($message); ?>;

        var ua = navigator.userAgent || "";
        var isIOS = /iPhone|iPad|iPod/i.test(ua);
        var isAndroid = /Android/i.test(ua);

        var androidBlock = document.getElementById("androidBlock");
        var iosBlock = document.getElementById("iosBlock");

        function copyText(t){
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(t);
                return;
            }
            var ta = document.createElement("textarea");
            ta.value = t;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand("copy");
            document.body.removeChild(ta);
        }

        if (isIOS) {
            iosBlock.style.display = "block";
            document.getElementById("copyUpi").onclick = function(){ copyText(upiId); };
            document.getElementById("copyNote").onclick = function(){ copyText(note); };
            document.getElementById("copyAmount").onclick = function(){ copyText(amount); };
            return;
        }

        androidBlock.style.display = "block";

        setTimeout(function(){
            window.location.href = upiLink;
        }, 600);
    })();
</script>

</body>
</html>
