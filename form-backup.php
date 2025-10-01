<?php
/**
 * Backup Form Handler for Actief Brandbeveiliging B.V.
 * This handler saves form submissions to files as a backup method
 * when email delivery fails due to hosting provider restrictions
 * Updated: 2025-09-27 - Fixed checkbox handling and improved error handling
 */

// Enable error logging and debugging
ini_set('log_errors', 1);
ini_set('error_log', '/var/www/actiefbrandbeveiliging.nl/form-error.log');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Configuration
$config = [
    'backup_dir' => '/var/www/actiefbrandbeveiliging.nl/form-submissions/',
    'to_email' => 'info@actiefbrandbeveiliging.nl',
    'from_email' => 'website@actiefbrandbeveiliging.nl',
    'from_name' => 'Actief Brandbeveiliging Website',
    'subject' => 'Nieuwe Offerte Aanvraag - Actief Brandbeveiliging',
    'success_redirect' => '/pages/offerte.php?success=1',
    'error_redirect' => '/pages/offerte.php?error=1'
];

// Create backup directory if it doesn't exist
if (!is_dir($config['backup_dir'])) {
    if (!mkdir($config['backup_dir'], 0755, true)) {
        error_log("Failed to create backup directory: " . $config['backup_dir']);
        http_response_code(500);
        header('Location: ' . $config['error_redirect'] . '&reason=directory_error');
        exit('Failed to create backup directory');
    }
}

// Log form submission attempt
error_log("Form submission attempt from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . " at " . date('Y-m-d H:i:s'));

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

// Input validation and sanitization
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validate_phone($phone) {
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

// Collect checkboxes (services) - Debug checkbox data
error_log("Checkbox POST data: " . print_r($_POST['checkbox'] ?? 'Not set', true));

$services = [];
if (isset($_POST['checkbox']) && is_array($_POST['checkbox'])) {
    foreach ($_POST['checkbox'] as $service) {
        $services[] = sanitize_input($service);
    }
    error_log("Processed services: " . implode(', ', $services));
} else {
    error_log("No checkbox services selected or not properly formatted");
}

// If there are errors, redirect back with error message
if (!empty($errors)) {
    $error_message = implode(', ', $errors);
    error_log("Form validation errors: " . $error_message);
    header('Location: ' . $config['error_redirect'] . '&reason=validation&msg=' . urlencode($error_message));
    exit();
}

// Debug all collected form data
error_log("Form data collected successfully. Company: " . $form_data['company'] . ", Email: " . $form_data['emailaddress'] . ", Services count: " . count($services));

// Generate submission ID
$submission_id = date('Y-m-d_H-i-s') . '_' . uniqid();

// Save to file as primary method (MUST work for form to succeed)
error_log("Attempting to save submission to file with ID: " . $submission_id);
$success = save_submission_to_file($config, $form_data, $services, $submission_id);

if ($success) {
    error_log("File backup successful for submission: " . $submission_id);

    // Update rate limiting
    $_SESSION['last_submission'] = time();

    // Try to send email as secondary method (failure is OK)
    $email_sent = false;
    try {
        $email_body = generate_email_body($form_data, $services);
        $email_sent = send_email($config, $email_body, $form_data);
        if ($email_sent) {
            error_log("Email sent successfully for submission: " . $submission_id);
        } else {
            error_log("Email sending failed for submission: " . $submission_id . " (but form submission still successful)");
        }
    } catch (Exception $e) {
        error_log("Email sending exception for submission: " . $submission_id . " - " . $e->getMessage() . " (but form submission still successful)");
    }

    // Log successful submission
    error_log("Form submission completed successfully: " . $submission_id . " (Email sent: " . ($email_sent ? 'Yes' : 'No') . ")");

    // Redirect to success page
    header('Location: ' . $config['success_redirect'] . '&id=' . $submission_id);
    exit();
} else {
    // File backup failed - this is a real error
    error_log("CRITICAL: Failed to save form submission to file for submission: " . $submission_id);
    error_log("Form data was: " . print_r($form_data, true));
    error_log("Services were: " . print_r($services, true));

    // Redirect to error page
    header('Location: ' . $config['error_redirect'] . '&reason=save_error');
    exit();
}

/**
 * Save form submission to file
 */
function save_submission_to_file($config, $data, $services, $submission_id) {
    $services_list = !empty($services) ? implode(', ', $services) : 'Geen specifieke diensten geselecteerd';

    $submission_data = [
        'id' => $submission_id,
        'timestamp' => date('Y-m-d H:i:s'),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'form_data' => $data,
        'services' => $services,
        'services_text' => $services_list
    ];

    // Check if directory is writable
    if (!is_writable($config['backup_dir'])) {
        error_log("Backup directory is not writable: " . $config['backup_dir']);
        return false;
    }

    // Save as JSON
    $json_filename = $config['backup_dir'] . $submission_id . '.json';
    $json_success = file_put_contents($json_filename, json_encode($submission_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    if ($json_success === false) {
        error_log("Failed to write JSON file: " . $json_filename);
    } else {
        error_log("Successfully wrote JSON file: " . $json_filename . " (" . $json_success . " bytes)");
    }

    // Save as human-readable text
    $txt_content = "=== OFFERTE AANVRAAG ===\n";
    $txt_content .= "ID: {$submission_id}\n";
    $txt_content .= "Datum/Tijd: " . date('Y-m-d H:i:s') . "\n";
    $txt_content .= "IP Adres: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n\n";

    $txt_content .= "CONTACTGEGEVENS:\n";
    $txt_content .= "Bedrijfsnaam: {$data['company']}\n";
    $txt_content .= "Naam: {$data['firstname']} {$data['lastname']}\n";
    $txt_content .= "Email: {$data['emailaddress']}\n";
    $txt_content .= "Telefoon: {$data['phonenumber']}\n";
    $txt_content .= "Adres: " . ($data['address'] ?: 'Niet opgegeven') . "\n\n";

    $txt_content .= "GEWENSTE DIENSTEN:\n{$services_list}\n\n";

    $txt_content .= "BERICHT:\n{$data['message']}\n";
    $txt_content .= "\n=== EINDE AANVRAAG ===\n";

    $txt_filename = $config['backup_dir'] . $submission_id . '.txt';
    $txt_success = file_put_contents($txt_filename, $txt_content);

    if ($txt_success === false) {
        error_log("Failed to write TXT file: " . $txt_filename);
    } else {
        error_log("Successfully wrote TXT file: " . $txt_filename . " (" . $txt_success . " bytes)");
    }

    return $json_success !== false && $txt_success !== false;
}

/**
 * Generate simple email body to avoid content policy issues
 */
function generate_email_body($data, $services) {
    $services_list = !empty($services) ? implode(', ', $services) : 'Geen specifieke diensten geselecteerd';

    // Simple text format to avoid spam filters
    $body = "Nieuwe aanvraag via website

CONTACTGEGEVENS:
Bedrijf: " . htmlspecialchars($data['company']) . "
Naam: " . htmlspecialchars($data['firstname']) . " " . htmlspecialchars($data['lastname']) . "
Email: " . htmlspecialchars($data['emailaddress']) . "
Telefoon: " . htmlspecialchars($data['phonenumber']) . "
Adres: " . htmlspecialchars($data['address'] ?: 'Niet opgegeven') . "

GEWENSTE DIENSTEN:
" . htmlspecialchars($services_list) . "

BERICHT:
" . htmlspecialchars($data['message']) . "

---
Verzonden via actiefbrandbeveiliging.nl op " . date('d-m-Y H:i:s') . "
IP: " . htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'Unknown');

    return $body;
}

/**
 * Send email using PHP mail function
 */
function send_email($config, $body, $data) {
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/plain; charset=UTF-8',
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
?>