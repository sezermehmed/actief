<?php
/**
 * Simple Admin Dashboard for Form Submissions
 * View and manage form submissions when email delivery fails
 */

// Simple password protection (change this password!)
$admin_password = 'actief2025!';
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    if (isset($_POST['password']) && $_POST['password'] === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        // Show login form
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Admin Dashboard - Actief Brandbeveiliging</title>
            <style>
                body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 50px; }
                .login-box { max-width: 400px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                h1 { color: #00b359; text-align: center; }
                input[type="password"] { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
                button { width: 100%; padding: 12px; background: #00b359; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
                button:hover { background: #008a44; }
            </style>
        </head>
        <body>
            <div class="login-box">
                <h1>Admin Dashboard</h1>
                <p>Actief Brandbeveiliging B.V.</p>
                <form method="POST">
                    <input type="password" name="password" placeholder="Wachtwoord" required>
                    <button type="submit">Inloggen</button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Logout functionality
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin-submissions.php');
    exit;
}

$submissions_dir = '/var/www/actiefbrandbeveiliging.nl/form-submissions/';

// Get all submission files
$submissions = [];
if (is_dir($submissions_dir)) {
    $files = glob($submissions_dir . '*.json');
    foreach ($files as $file) {
        $data = json_decode(file_get_contents($file), true);
        if ($data) {
            $submissions[] = $data;
        }
    }

    // Sort by timestamp (newest first)
    usort($submissions, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Form Submissions - Actief Brandbeveiliging</title>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .header { background: #00b359; color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; }
        .logout { float: right; color: white; text-decoration: none; background: #008a44; padding: 8px 15px; border-radius: 5px; }
        .submission { background: white; margin-bottom: 20px; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .submission-header { border-bottom: 2px solid #00b359; padding-bottom: 10px; margin-bottom: 15px; }
        .submission-id { color: #666; font-size: 14px; }
        .field { margin-bottom: 10px; }
        .field strong { color: #00b359; }
        .services { background: #f9f9f9; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .message { background: #f0f8f0; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #00b359; }
        .no-submissions { text-align: center; padding: 50px; background: white; border-radius: 10px; }
        .stats { background: white; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Form Submissions Dashboard</h1>
        <p>Actief Brandbeveiliging B.V. - Offerte Aanvragen</p>
        <a href="?logout=1" class="logout">Uitloggen</a>
        <div style="clear: both;"></div>
    </div>

    <div class="stats">
        <strong>Totaal aantal aanvragen:</strong> <?= count($submissions) ?>
        <?php if (count($submissions) > 0): ?>
        | <strong>Laatste aanvraag:</strong> <?= $submissions[0]['timestamp'] ?>
        <?php endif; ?>
    </div>

    <?php if (empty($submissions)): ?>
        <div class="no-submissions">
            <h2>Geen form submissions gevonden</h2>
            <p>Er zijn nog geen offerte aanvragen binnengekomen via het formulier.</p>
        </div>
    <?php else: ?>
        <?php foreach ($submissions as $submission): ?>
            <div class="submission">
                <div class="submission-header">
                    <h3>Offerte Aanvraag - <?= htmlspecialchars($submission['form_data']['company']) ?></h3>
                    <div class="submission-id">
                        ID: <?= htmlspecialchars($submission['id']) ?> |
                        Datum: <?= htmlspecialchars($submission['timestamp']) ?> |
                        IP: <?= htmlspecialchars($submission['ip_address']) ?>
                    </div>
                </div>

                <div class="field">
                    <strong>Bedrijfsnaam:</strong> <?= htmlspecialchars($submission['form_data']['company']) ?>
                </div>

                <div class="field">
                    <strong>Contactpersoon:</strong> <?= htmlspecialchars($submission['form_data']['firstname']) ?> <?= htmlspecialchars($submission['form_data']['lastname']) ?>
                </div>

                <div class="field">
                    <strong>Email:</strong> <a href="mailto:<?= htmlspecialchars($submission['form_data']['emailaddress']) ?>"><?= htmlspecialchars($submission['form_data']['emailaddress']) ?></a>
                </div>

                <div class="field">
                    <strong>Telefoon:</strong> <a href="tel:<?= htmlspecialchars($submission['form_data']['phonenumber']) ?>"><?= htmlspecialchars($submission['form_data']['phonenumber']) ?></a>
                </div>

                <div class="field">
                    <strong>Adres:</strong> <?= htmlspecialchars($submission['form_data']['address'] ?: 'Niet opgegeven') ?>
                </div>

                <div class="services">
                    <strong>Gewenste diensten:</strong><br>
                    <?= htmlspecialchars($submission['services_text']) ?>
                </div>

                <div class="message">
                    <strong>Bericht:</strong><br>
                    <?= nl2br(htmlspecialchars($submission['form_data']['message'])) ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div style="text-align: center; margin-top: 30px; color: #666; font-size: 14px;">
        <p>Actief Brandbeveiliging B.V. - Admin Dashboard</p>
        <p>Voor technische ondersteuning: <a href="mailto:info@actiefbrandbeveiliging.nl">info@actiefbrandbeveiliging.nl</a></p>
    </div>
</body>
</html>