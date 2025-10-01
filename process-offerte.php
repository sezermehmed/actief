<?php
// Professional Form Handler for Actief Brandbeveiliging B.V.
// Created: 2025-09-25
// Purpose: Handle offerte (quote) form submissions with professional email delivery

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Configuration
$config = [
    'to_email' => 'info@actiefbrandbeveiliging.nl',
    'from_email' => 'website@actiefbrandbeveiliging.nl',
    'from_name' => 'Actief Brandbeveiliging Website',
    'subject' => 'Nieuwe Offerte Aanvraag - Actief Brandbeveiliging',
    'success_redirect' => '/pages/offerte.php?success=1',
    'error_redirect' => '/pages/offerte.php?error=1'
];

// Rate limiting (simple session-based)
session_start();
if (isset($_SESSION['last_submission']) && time() - $_SESSION['last_submission'] < 60) {
    http_response_code(429);
    header('Location: ' . $config['error_redirect'] . '&reason=rate_limit');
    exit('Rate limit exceeded. Please wait before submitting again.');
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Location: ' . $config['error_redirect'] . '&reason=method');
    exit('Method not allowed');
}

// CSRF Protection (basic)
if (!isset($_POST['form_token']) || $_POST['form_token'] !== session_id()) {
    http_response_code(403);
    header('Location: ' . $config['error_redirect'] . '&reason=csrf');
    exit('Invalid form token');
}

// Input validation and sanitization
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validate_phone($phone) {
    // Dutch phone number validation (basic)
    return preg_match('/^[\+]?[0-9\s\-\(\)]{6,20}$/', $phone);
}

// Honeypot spam protection
if (!empty($_POST['website'])) {
    http_response_code(403);
    header('Location: ' . $config['error_redirect'] . '&reason=spam');
    exit('Spam detected');
}

// Required fields
$required_fields = ['company', 'firstname', 'lastname', 'emailaddress', 'phonenumber', 'message'];
$errors = [];

// Validate required fields
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        $errors[] = "Veld '{$field}' is verplicht";
    }
}

// Collect and sanitize form data
$form_data = [];
$allowed_fields = ['company', 'firstname', 'lastname', 'emailaddress', 'phonenumber', 'address', 'message'];

foreach ($allowed_fields as $field) {
    $form_data[$field] = isset($_POST[$field]) ? sanitize_input($_POST[$field]) : '';
}

// Specific validation
if (!validate_email($form_data['emailaddress'])) {
    $errors[] = 'Ongeldig email adres';
}

if (!validate_phone($form_data['phonenumber'])) {
    $errors[] = 'Ongeldig telefoonnummer';
}

// Collect checkboxes (services)
$services = [];
if (isset($_POST['checkbox']) && is_array($_POST['checkbox'])) {
    foreach ($_POST['checkbox'] as $service) {
        $services[] = sanitize_input($service);
    }
}

// If there are errors, redirect back with error message
if (!empty($errors)) {
    $error_message = implode(', ', $errors);
    header('Location: ' . $config['error_redirect'] . '&reason=validation&msg=' . urlencode($error_message));
    exit();
}

// Prepare email content
$email_body = generate_email_body($form_data, $services);

// Send email
if (send_email($config, $email_body, $form_data)) {
    // Update rate limiting
    $_SESSION['last_submission'] = time();

    // Log successful submission (optional)
    error_log("Offerte form submitted successfully from: " . $form_data['emailaddress']);

    // Redirect to success page
    header('Location: ' . $config['success_redirect']);
    exit();
} else {
    // Log error
    error_log("Failed to send offerte email for: " . $form_data['emailaddress']);

    // Redirect to error page
    header('Location: ' . $config['error_redirect'] . '&reason=email_send');
    exit();
}

/**
 * Generate professional email body
 */
function generate_email_body($data, $services) {
    $services_list = !empty($services) ? implode(', ', $services) : 'Geen specifieke diensten geselecteerd';

    $body = "
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
            .header { background: #00b359; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .field { margin-bottom: 15px; }
            .label { font-weight: bold; color: #00b359; }
            .value { margin-left: 10px; }
            .footer { padding: 15px; background: #e9e9e9; font-size: 12px; text-align: center; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>Nieuwe Offerte Aanvraag</h1>
            <p>Actief Brandbeveiliging B.V.</p>
        </div>

        <div class='content'>
            <h2>Contactgegevens</h2>

            <div class='field'>
                <span class='label'>Bedrijfsnaam:</span>
                <span class='value'>" . htmlspecialchars($data['company']) . "</span>
            </div>

            <div class='field'>
                <span class='label'>Naam:</span>
                <span class='value'>" . htmlspecialchars($data['firstname']) . " " . htmlspecialchars($data['lastname']) . "</span>
            </div>

            <div class='field'>
                <span class='label'>Email:</span>
                <span class='value'><a href='mailto:" . htmlspecialchars($data['emailaddress']) . "'>" . htmlspecialchars($data['emailaddress']) . "</a></span>
            </div>

            <div class='field'>
                <span class='label'>Telefoon:</span>
                <span class='value'><a href='tel:" . htmlspecialchars($data['phonenumber']) . "'>" . htmlspecialchars($data['phonenumber']) . "</a></span>
            </div>

            <div class='field'>
                <span class='label'>Adres:</span>
                <span class='value'>" . htmlspecialchars($data['address'] ?: 'Niet opgegeven') . "</span>
            </div>

            <h2>Gewenste Diensten</h2>
            <div class='field'>
                <span class='value'>" . htmlspecialchars($services_list) . "</span>
            </div>

            <h2>Bericht</h2>
            <div class='field' style='background: white; padding: 15px; border-radius: 5px;'>
                " . nl2br(htmlspecialchars($data['message'])) . "
            </div>

            <h2>Formulier Details</h2>
            <div class='field'>
                <span class='label'>Datum/Tijd:</span>
                <span class='value'>" . date('d-m-Y H:i:s') . "</span>
            </div>

            <div class='field'>
                <span class='label'>IP Adres:</span>
                <span class='value'>" . htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "</span>
            </div>
        </div>

        <div class='footer'>
            <p>Deze email werd automatisch verzonden via het offerte formulier op actiefbrandbeveiliging.nl</p>
            <p>Actief Brandbeveiliging B.V. | Tenierslaan 11-13, 5613 DZ Eindhoven | info@actiefbrandbeveiliging.nl</p>
        </div>
    </body>
    </html>";

    return $body;
}

/**
 * Send email using PHP mail function
 */
function send_email($config, $body, $data) {
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . $config['from_name'] . ' <' . $config['from_email'] . '>',
        'Reply-To: ' . $data['emailaddress'],
        'Return-Path: ' . $config['from_email'],
        'X-Mailer: PHP/' . phpversion(),
        'X-Priority: 3',
        'X-MSMail-Priority: Normal',
        'Message-ID: <' . time() . '.' . uniqid() . '@actiefbrandbeveiliging.nl>'
    ];

    return mail(
        $config['to_email'],
        $config['subject'],
        $body,
        implode("\r\n", $headers)
    );
}

/**
 * Alternative: Send confirmation email to customer
 */
function send_confirmation_email($customer_email, $customer_name) {
    $subject = "Bevestiging van uw offerte aanvraag - Actief Brandbeveiliging";

    $body = "
    <html>
    <head><meta charset='UTF-8'></head>
    <body style='font-family: Arial, sans-serif;'>
        <div style='background: #00b359; color: white; padding: 20px; text-align: center;'>
            <h1>Bedankt voor uw aanvraag</h1>
        </div>

        <div style='padding: 20px;'>
            <p>Beste " . htmlspecialchars($customer_name) . ",</p>

            <p>Hartelijk dank voor uw interesse in onze diensten. Wij hebben uw offerte aanvraag in goede orde ontvangen.</p>

            <p>Ons team zal uw aanvraag binnen 24 uur beoordelen en contact met u opnemen om de mogelijkheden te bespreken.</p>

            <p>Heeft u nog vragen? Neem dan gerust contact met ons op via:</p>
            <ul>
                <li>Telefoon: 040 2026678</li>
                <li>Email: info@actiefbrandbeveiliging.nl</li>
            </ul>

            <p>Met vriendelijke groet,<br>
            Het team van Actief Brandbeveiliging B.V.</p>
        </div>

        <div style='background: #f0f0f0; padding: 15px; font-size: 12px; text-align: center;'>
            <p>Actief Brandbeveiliging B.V. | Tenierslaan 11-13, 5613 DZ Eindhoven</p>
        </div>
    </body>
    </html>";

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: Actief Brandbeveiliging B.V. <noreply@actiefbrandbeveiliging.nl>',
        'X-Mailer: PHP/' . phpversion()
    ];

    return mail($customer_email, $subject, $body, implode("\r\n", $headers));
}
?>