<?php
require_once __DIR__ . '/core/config.php';
require_once ROOT_PATH . '/libs/core/db_connection.php';
require_once ROOT_PATH . '/libs/core/functions.php';
require_once ROOT_PATH . '/libs/core/lang.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$base_url = getSetting($pdo, 'base_url', BASE_URL);

// Fehler- & Erfolgsmeldungen vorbereiten
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name    = trim($_POST["name"] ?? '');
    $email   = trim($_POST["email"] ?? '');
    $message = trim($_POST["message"] ?? '');

    // Einfache Validierung
    if (empty($name) || empty($email) || empty($message)) {
        $error = t("contact_error_empty");
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = t("contact_error_email");
    } else {
        // E-Mail versenden mit PHPMailer
        $mail = new PHPMailer(true);
        try {
            // Server-Einstellungen
            $mail->isSMTP();
            $mail->Host = 'smtp.example.com'; // Setze den SMTP-Server
            $mail->SMTPAuth = true;
            $mail->Username = 'your_email@example.com'; // Dein E-Mail-Adresse
            $mail->Password = 'your_email_password'; // Dein E-Mail-Passwort
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587; // Port für SMTP

            // Empfänger & Absender
            $mail->setFrom($email, $name); // Vom Absender
            $mail->addAddress('kontakt@orange-dev.de', 'Orange Dev'); // An den Empfänger

            // Inhalt der E-Mail
            $mail->isHTML(true);
            $mail->Subject = 'Kontaktformular Nachricht';
            $mail->Body    = "<strong>Name:</strong> $name<br><strong>E-Mail:</strong> $email<br><strong>Nachricht:</strong> <br>$message";

            $mail->send();
            $success = t("contact_success");
        } catch (Exception $e) {
            $error = "❌ Fehler beim Senden der Nachricht. Mailer Error: " . $mail->ErrorInfo;
        }
    }
}

include ROOT_PATH . '/libs/header.php';
?>

<!-- Hauptcontainer -->
<div class="container mt-5" style="margin-top: 120px !important; max-width: 600px;">
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card bg-dark text-white shadow rounded-4 p-4">
                <h3 class="card-title mb-4 text-center"><?php echo t('contact_heading_form'); ?></h3>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php elseif (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <form method="post" action="<?php echo htmlspecialchars($base_url . 'libs/contact.php'); ?>">
                    <div class="mb-3">
                        <label for="name" class="form-label"><?php echo t('contact_label_name'); ?></label>
                        <input type="text" class="form-control" id="name" name="name" required autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label"><?php echo t('contact_label_email'); ?></label>
                        <input type="email" class="form-control" id="email" name="email" required autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label"><?php echo t('contact_label_message'); ?></label>
                        <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><?php echo t('contact_button_submit'); ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include ROOT_PATH . '/libs/footer.php'; ?>
