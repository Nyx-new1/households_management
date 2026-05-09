<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Default language
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en';
}

// Handle language switch
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    if (in_array($lang, ['en', 'sw'])) {
        $_SESSION['lang'] = $lang;
        
        // Redirect to clean URL (remove ?lang=xx)
        // Keep other query parameters if they exist
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $uri = strtok($_SERVER["REQUEST_URI"], '?');
        $queryParams = $_GET;
        unset($queryParams['lang']);
        
        $redirectUrl = $protocol . "://" . $host . $uri;
        if (!empty($queryParams)) {
            $redirectUrl .= '?' . http_build_query($queryParams);
        }
        
        header("Location: " . $redirectUrl);
        exit;
    }
}

// Load language file
$langCode = $_SESSION['lang'];
$langFile = __DIR__ . "/../lang/$langCode.php";

if (file_exists($langFile)) {
    $lang = include($langFile);
} else {
    // Fallback to English if file not found
    $lang = include(__DIR__ . "/../lang/en.php");
}

// Global Exchange Rate Logic
if (!isset($pdo)) {
    require_once __DIR__ . "/../config/db_connect.php";
}

// Fetch global rate
$stmt_rate = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'usd_to_tzs_rate'");
$global_exchange_rate = (float)($stmt_rate->fetchColumn() ?: 2500);

/**
 * Formats a USD amount with its TZS equivalent in brackets.
 * @param float $usd The amount in US Dollars
 * @param float|null $rate Optional specific rate to use (for locked-in prices)
 * @param bool $include_tzs Whether to show the TZS part
 * @return string Formatted price string
 */
function formatPrice($usd, $rate = null, $include_tzs = true) {
    global $global_exchange_rate;
    $active_rate = $rate ?: $global_exchange_rate;
    
    $tzs = (float)$usd * $active_rate;
    
    $output = '$' . number_format($usd, 2);
    if ($include_tzs) {
        $output .= ' (' . number_format($tzs, 0) . ' TZS)';
    }
    return $output;
}
?>
