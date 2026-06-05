<?php
// includes/db.php - Database connection

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'specsync');

// ─── AI API Key ───────────────────────────────────────────────────────────────
// Gemini API Key — dapatkan GRATIS di https://aistudio.google.com/app/apikey
// Paste key kamu di sini:
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: 'AQ.Ab8RN6JlgMcnUsnCh-Dk6u2I2t6qhXKs21hDEbgj_Iugb3rfbw');
// ─────────────────────────────────────────────────────────────────────────────

function getDB() {
    static $conn = null;
    if ($conn === null) {
        $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (!$conn) {
            die(json_encode(['error' => 'Database connection failed: ' . mysqli_connect_error()]));
        }
        mysqli_set_charset($conn, 'utf8mb4');
    }
    return $conn;
}

function formatPrice($price) {
    return 'Rp ' . number_format($price, 0, ',', '.');
}

function sanitize($input) {
    $conn = getDB();
    return mysqli_real_escape_string($conn, htmlspecialchars(strip_tags(trim($input))));
}

function generateShareToken() {
    return bin2hex(random_bytes(16));
}

// Auto-calculate scores based on specs
function calculateScores($device) {
    // Camera score (0-10)
    $cam = 0;
    if ($device['main_camera'] >= 200) $cam = 10;
    elseif ($device['main_camera'] >= 100) $cam = 9.5;
    elseif ($device['main_camera'] >= 50) $cam = 8.5;
    elseif ($device['main_camera'] >= 48) $cam = 8.0;
    elseif ($device['main_camera'] >= 32) $cam = 7.0;
    else $cam = 6.0;

    // Performance score
    $perf = 7.0;
    $flagship_chips = ['Snapdragon 8 Elite', 'Apple A18 Pro', 'Snapdragon 8 Gen 3', 'Google Tensor G4'];
    foreach ($flagship_chips as $chip) {
        if (stripos($device['chipset'], 'Elite') !== false || stripos($device['chipset'], 'A18') !== false) {
            $perf = 9.8; break;
        } elseif (stripos($device['chipset'], 'Gen 3') !== false || stripos($device['chipset'], 'Tensor G4') !== false) {
            $perf = 9.0; break;
        }
    }
    if ($device['ram'] >= 16) $perf = min(10, $perf + 0.2);

    // Battery score
    $bat = 0;
    if ($device['battery'] >= 6000) $bat = 10;
    elseif ($device['battery'] >= 5500) $bat = 9.5;
    elseif ($device['battery'] >= 5000) $bat = 8.5;
    elseif ($device['battery'] >= 4700) $bat = 8.0;
    else $bat = 7.0;
    if ($device['charging_speed'] >= 100) $bat = min(10, $bat + 0.5);

    // Design score
    $des = 7.5;
    if ($device['weight'] <= 200) $des += 0.5;
    if ($device['thickness'] <= 8.5) $des += 0.5;
    if ($device['has_wireless_charging']) $des += 0.3;
    if ($device['nfc']) $des += 0.2;

    return [
        'score_camera' => round(min(10, $cam), 1),
        'score_performance' => round(min(10, $perf), 1),
        'score_battery' => round(min(10, $bat), 1),
        'score_design' => round(min(10, $des), 1),
    ];
}
?>
