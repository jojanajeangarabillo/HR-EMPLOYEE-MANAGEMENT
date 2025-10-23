<?php
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

$config = require 'mailer-config.php';

$result = '';
$debug_output = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient = $_POST['recipient'] ?? '';
    $subject = $_POST['subject'] ?? 'Test Email';
    $message = $_POST['message'] ?? 'This is a test email.';
    $html_format = isset($_POST['html_format']) ? true : false;
    $debug_mode = isset($_POST['debug_mode']) ? true : false;

    try {
        $mail = new PHPMailer(true);

        if ($debug_mode) {
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            ob_start();
        }

        $mail->isSMTP();
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = $config['encryption'];
        $mail->Port = $config['port'];

        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($recipient);

        $mail->isHTML($html_format);
        $mail->Subject = $subject;

        if ($html_format) {
            $mail->Body = $message;
            $mail->AltBody = strip_tags($message);
        } else {
            $mail->Body = $message;
        }

        $mail->send();

        $result = "<div class='success'>Email successfully sent to {$recipient}!</div>";

        if ($debug_mode) {
            $debug_output = ob_get_clean();
        }

    } catch (Exception $e) {
        $result = "<div class='error'>Message could not be sent. Mailer Error: {$mail->ErrorInfo}</div>";

        if ($debug_mode) {
            $debug_output = ob_get_clean();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple PHP Mailer Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            color: #333;
        }

        .container {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="email"],
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        textarea {
            height: 150px;
            resize: vertical;
        }

        .checkbox-group {
            margin: 15px 0;
        }

        button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background-color: #2980b9;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .debug-output {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-top: 20px;
            font-family: monospace;
            white-space: pre-wrap;
            font-size: 14px;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Simple PHP Mailer Test</h1>

        <?php if (!empty($result)): ?>
            <?php echo $result; ?>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="recipient">Recipient Email:</label>
                <input type="email" id="recipient" name="recipient" required>
            </div>

            <div class="form-group">
                <label for="subject">Subject:</label>
                <input type="text" id="subject" name="subject" value="Test Email">
            </div>

            <div class="form-group">
                <label for="message">Message:</label>
                <textarea id="message" name="message">This is a test email from the PLP Alumni Portal system.

If you received this email, your email configuration is working correctly!</textarea>
            </div>

            <button type="submit">Send Test Email</button>
        </form>

        <?php if (!empty($debug_output)): ?>
            <div class="debug-output">
                <h3>Debug Output:</h3>
                <?php echo htmlspecialchars($debug_output); ?>
            </div>
        <?php endif; ?>

        <div style="margin-top: 20px; font-size: 14px; color: #666;">
            <p>This test page uses the SMTP configuration from <code>mailer_config.php</code>:</p>
            <ul>
                <li>SMTP Host: <?php echo htmlspecialchars($config['host']); ?></li>
                <li>From Email: <?php echo htmlspecialchars($config['from_email']); ?></li>
                <li>From Name: <?php echo htmlspecialchars($config['from_name']); ?></li>
            </ul>
        </div>
    </div>
</body>

</html>