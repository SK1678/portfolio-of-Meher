<?php
/**
 * GitHub Auto-Deploy Script for Portfolio CMS
 * 
 * Instructions:
 * 1. Upload this file to your server root.
 * 2. In GitHub Repo Settings > Webhooks, add: https://yourdomain.com/deploy
 * 3. Content Type: application/json
 * 4. Secret: (Copy the value from $secret below)
 */

// --- CONFIGURATION ---
// Change this to a secure random string for your Webhook Secret
$secret = 'meher_portfolio_deploy_987654'; 

// Path to your git repository root on the server
$repo_path = __DIR__; 

// Log file for debugging
$log_file = 'deploy.log';
// --- END CONFIG ---

// Get payload and signature
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

if (!$signature) {
    header('HTTP/1.1 403 Forbidden');
    exit('No signature');
}

// Verify HMAC SHA256 Signature
$hash = 'sha256=' . hash_hmac('sha256', $payload, $secret);
if (!hash_equals($hash, $signature)) {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - [ERROR] Invalid Signature\n", FILE_APPEND);
    header('HTTP/1.1 403 Forbidden');
    exit('Invalid signature');
}

// Check for Branch (Optional: only deploy on main)
$data = json_decode($payload, true);
if (isset($data['ref']) && $data['ref'] !== 'refs/heads/main') {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - [INFO] Pushed to {$data['ref']}, skipping deployment.\n", FILE_APPEND);
    exit('Not main branch');
}

// Execute deployment
$cmd = "cd $repo_path && git pull origin main 2>&1";
$output = shell_exec($cmd);

// Log Result
$log_entry = date('Y-m-d H:i:s') . " - [SUCCESS] Deployment triggered.\nOutput:\n$output\n" . str_repeat('-', 40) . "\n";
file_put_contents($log_file, $log_entry, FILE_APPEND);

header('Content-Type: text/plain');
echo "Deployment Success:\n$output";
