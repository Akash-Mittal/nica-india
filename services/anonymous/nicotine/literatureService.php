<?php

// Set default content type to HTML
header('Content-Type: text/html; charset=UTF-8');

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the form input values
    $language = isset($_POST['lan']) ? $_POST['lan'] : 'en'; // Default to English
    $format = isset($_POST['format']) ? $_POST['format'] : 'json'; // Default to JSON

    // Define the file paths based on the language
    $filePath = $language === 'hi' ? 'promises_hindi.txt' : 'promises_english.txt';

    // Check if the file exists
    if (file_exists($filePath)) {
        // Read the contents of the file
        $promisesContent = file_get_contents($filePath);

        // Ensure the content is properly handled as UTF-8
        $promisesContent = mb_convert_encoding($promisesContent, 'UTF-8', 'auto');

        // Handle the different formats
        switch ($format) {
            case 'html':
                // Return HTML formatted content
                echo "
                    <html>
                    <head>
                        <title>Promises</title>
                    </head>
                    <body>
                        <h1>Promises</h1>
                        <textarea id='promisesContent' rows='10' cols='50'>" . htmlspecialchars($promisesContent) . "</textarea><br><br>
                        <button onclick='copyToClipboard()'>Copy to Clipboard</button>

                        <script>
                            function copyToClipboard() {
                                var content = document.getElementById('promisesContent');
                                content.select();
                                document.execCommand('copy');
                                alert('Content copied to clipboard!');
                            }
                        </script>
                    </body>
                    </html>
                ";
                break;

            case 'rich-text':
                // Return Rich Text (simplified, could be more advanced)
                echo "<html><body><pre>" . htmlspecialchars($promisesContent) . "</pre></body></html>";
                break;

            case 'whats-app-friendly':
                // Return WhatsApp friendly text (just basic line breaks)
                echo "<html><body><pre>" . preg_replace("/\r\n|\r|\n/", "\n", $promisesContent) . "</pre></body></html>";
                break;

            case 'json':
            default:
                // Return content as JSON
                echo json_encode(["promises" => $promisesContent], JSON_UNESCAPED_UNICODE);
                break;
        }
    } else {
        // Return an error if the file doesn't exist
        echo "<html><body><h1>Error: Promises file not found!</h1></body></html>";
    }
} else {
    // Show the form if the page is accessed without POST data
    ?>
    <html>
    <head>
        <title>Promises Form</title>
    </head>
    <body>
    <h1>Select Your Language and Format</h1>
    <form method="POST" action="">
        <label for="lan">Select Language:</label>
        <select name="lan" id="lan">
            <option value="en">English</option>
            <option value="hi">Hindi</option>
        </select><br><br>

        <label for="format">Select Format:</label>
        <select name="format" id="format">
            <option value="json">JSON</option>
            <option value="html">HTML</option>
            <option value="rich-text">Rich Text</option>
            <option value="whats-app-friendly">WhatsApp Friendly</option>
        </select><br><br>

        <input type="submit" value="Get Promises">
    </form>
    </body>
    </html>
    <?php
}
?>