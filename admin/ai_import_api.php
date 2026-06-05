<?php
// admin/ai_import_api.php — GSMArena Scraper + Gemini AI Importer v3
session_start();
require_once '../includes/db.php';
require_once 'auth.php';
requireAdmin();

header('Content-Type: application/json');

$body  = json_decode(file_get_contents('php://input'), true);
$query = trim($body['query'] ?? '');
if (!$query) { echo json_encode(['error' => 'Query kosong']); exit; }

// Source flags — default true for both (backward-compatible)
$useGSMArena = isset($body['use_gsmarena']) ? (bool)$body['use_gsmarena'] : true;
$useGemini   = isset($body['use_gemini'])   ? (bool)$body['use_gemini']   : true;
if (!$useGSMArena && !$useGemini) {
    echo json_encode(['error' => 'Pilih minimal satu sumber data (GSMArena atau Gemini).']);
    exit;
}

$apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : getenv('GEMINI_API_KEY');
if (!$apiKey && $useGemini) {
    echo json_encode(['error' => 'GEMINI_API_KEY belum dikonfigurasi di includes/db.php']);
    exit;
}

// Gunakan model yang pasti tersedia
$MODEL = 'gemini-1.5-flash';

$debugLog = [];

// ══════════════════════════════════════════════════════════════════════════════
// HELPER: Gemini API call
// ══════════════════════════════════════════════════════════════════════════════
function geminiCall($apiKey, $payload, $model) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    return ['body' => $response, 'code' => $httpCode, 'curl_error' => $curlError];
}

function extractText($result) {
    if ($result['code'] !== 200 || !$result['body']) return '';
    $d = json_decode($result['body'], true);
    $text = '';
    foreach (($d['candidates'][0]['content']['parts'] ?? []) as $part) {
        if (!empty($part['text'])) $text .= $part['text'] . "\n";
    }
    return trim($text);
}

function cleanJSON($raw) {
    $clean = trim($raw);
    $clean = preg_replace('/^```(?:json)?\s*/im', '', $clean);
    $clean = preg_replace('/\s*```$/im', '', $clean);
    return trim($clean);
}

// ══════════════════════════════════════════════════════════════════════════════
// HELPER: HTTP fetch
// ══════════════════════════════════════════════════════════════════════════════
function httpFetch($url, $timeoutSec = 20) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => $timeoutSec,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_ENCODING       => 'gzip, deflate',
        CURLOPT_HTTPHEADER     => [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
            'Accept-Language: en-US,en;q=0.9',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Referer: https://www.google.com/',
        ],
    ]);
    $html     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err      = curl_error($ch);
    curl_close($ch);
    return ['html' => $html ?: '', 'code' => $httpCode, 'error' => $err];
}

// ══════════════════════════════════════════════════════════════════════════════
// STEP 1: Build slug candidates dari query (tanpa Gemini dulu)
// Pattern GSMArena: brand_model_name-NUMERICID
// ══════════════════════════════════════════════════════════════════════════════
function buildSlugCandidates($query) {
    // Normalize query → slug-style
    $q    = strtolower(trim($query));
    $base = preg_replace('/[^a-z0-9]+/', '_', $q);
    $base = trim($base, '_');

    // Known device database (brand_slug => [model_slug => [id, ...]])
    // IDs diperoleh dari GSMArena URL patterns yang sudah diketahui
    $known = [
        // Samsung S-series
        'samsung_galaxy_s25_ultra'    => [12821, 12822],
        'samsung_galaxy_s25+'         => [12819],
        'samsung_galaxy_s25'          => [12818],
        'samsung_galaxy_s24_ultra'    => [12505],
        'samsung_galaxy_s24+'         => [12504],
        'samsung_galaxy_s24'          => [12503],
        'samsung_galaxy_s23_ultra'    => [11835],
        'samsung_galaxy_s23+'         => [11834],
        'samsung_galaxy_s23'          => [11833],
        'samsung_galaxy_s22_ultra'    => [11285],
        'samsung_galaxy_s22+'         => [11284],
        'samsung_galaxy_s22'          => [11283],
        'samsung_galaxy_s21_ultra_5g' => [10637],
        'samsung_galaxy_s21+'         => [10636],
        'samsung_galaxy_s21'          => [10635],
        'samsung_galaxy_s20_ultra'    => [10050],
        // Samsung A-series
        'samsung_galaxy_a55'          => [12355, 12356],
        'samsung_galaxy_a54'          => [11865],
        'samsung_galaxy_a35'          => [12354],
        'samsung_galaxy_a34'          => [11864],
        'samsung_galaxy_a15'          => [12214],
        'samsung_galaxy_a14'          => [11832],
        'samsung_galaxy_a05s'         => [12093],
        'samsung_galaxy_a05'          => [12092],
        // iPhone
        'apple_iphone_16_pro_max'     => [12527],
        'apple_iphone_16_pro'         => [12526],
        'apple_iphone_16_plus'        => [12525],
        'apple_iphone_16'             => [12524],
        'apple_iphone_15_pro_max'     => [12179],
        'apple_iphone_15_pro'         => [12178],
        'apple_iphone_15_plus'        => [12177],
        'apple_iphone_15'             => [12176],
        'apple_iphone_14_pro_max'     => [11734],
        'apple_iphone_14_pro'         => [11733],
        'apple_iphone_14_plus'        => [11732],
        'apple_iphone_14'             => [11731],
        'apple_iphone_13_pro_max'     => [11227],
        'apple_iphone_13_pro'         => [11226],
        'apple_iphone_13'             => [11224],
        'apple_iphone_12_pro_max'     => [10617],
        'apple_iphone_12_pro'         => [10616],
        'apple_iphone_12'             => [10509],
        'apple_iphone_12_mini'        => [10508],
        'apple_iphone_11_pro_max'     => [9832],
        'apple_iphone_11_pro'         => [9831],
        'apple_iphone_11'             => [9830],
        // Xiaomi
        'xiaomi_15_ultra'             => [12775],
        'xiaomi_15_pro'               => [12774],
        'xiaomi_15'                   => [12773],
        'xiaomi_14_ultra'             => [12219],
        'xiaomi_14_pro'               => [12218],
        'xiaomi_14'                   => [12217],
        'xiaomi_13_ultra'             => [11908],
        'xiaomi_13_pro'               => [11907],
        'xiaomi_13'                   => [11906],
        'xiaomi_redmi_note_13_pro+'   => [12219],
        'xiaomi_redmi_note_13_pro'    => [12179],
        'xiaomi_redmi_note_13'        => [12178],
        'xiaomi_redmi_note_12_pro+'   => [11856],
        'xiaomi_redmi_note_12_pro'    => [11857],
        'xiaomi_redmi_note_12'        => [11858],
        // POCO
        'poco_x7_pro'                 => [12771],
        'poco_x7'                     => [12770],
        'poco_x6_pro'                 => [12220],
        'poco_x6'                     => [12221],
        'poco_f6_pro'                 => [12470],
        'poco_f6'                     => [12469],
        'poco_m6_pro'                 => [12467],
        // OnePlus
        'oneplus_13'                  => [13477, 13476],
        'oneplus_12'                  => [12254],
        'oneplus_11'                  => [11891],
        'oneplus_nord_4'              => [12499],
        'oneplus_nord_3'              => [12012],
        'oneplus_nord_ce4'            => [12471],
        'oneplus_nord_ce3'            => [12013],
        // Realme
        'realme_gt_7_pro'             => [12772],
        'realme_gt_6'                 => [12468],
        'realme_12_pro+'              => [12222],
        'realme_12_pro'               => [12223],
        'realme_12+'                  => [12466],
        'realme_12'                   => [12465],
        // Google Pixel
        'google_pixel_9_pro_xl'       => [12531],
        'google_pixel_9_pro'          => [12530],
        'google_pixel_9'              => [12529],
        'google_pixel_8_pro'          => [12255],
        'google_pixel_8'              => [12253],
        'google_pixel_7_pro'          => [11908],
        'google_pixel_7'              => [11905],
        // Oppo
        'oppo_find_x8_pro'            => [12776],
        'oppo_find_x8'                => [12777],
        'oppo_find_x7_ultra'          => [12220],
        'oppo_reno12_pro'             => [12473],
        'oppo_reno12'                 => [12474],
        'oppo_reno11_pro'             => [12224],
        // Vivo
        'vivo_x200_pro'               => [12778],
        'vivo_x200'                   => [12779],
        'vivo_x100_pro'               => [12225],
        'vivo_x100'                   => [12226],
        'vivo_v40_pro'                => [12475],
        'vivo_v40'                    => [12476],
        // ASUS
        'asus_rog_phone_8_pro'        => [12227],
        'asus_rog_phone_8'            => [12228],
        'asus_rog_phone_7_ultimate'   => [11892],
        'asus_zenfone_11_ultra'       => [12229],
        // Motorola
        'motorola_edge_50_pro'        => [12477],
        'motorola_edge_50_ultra'      => [12478],
        'motorola_razr_50_ultra'      => [12479],
    ];

    $candidates = [];

    // Cek exact match di known database
    foreach ($known as $knownSlug => $ids) {
        // Normalize known slug untuk comparison
        $norm = preg_replace('/[^a-z0-9]/', '_', strtolower($knownSlug));
        $norm = trim($norm, '_');
        if ($norm === $base || similar_text($norm, $base) / max(strlen($norm), strlen($base)) > 0.85) {
            foreach ($ids as $id) {
                $candidates[] = $norm . '-' . $id;
            }
        }
    }

    // Tambah variasi slug dari query langsung (tanpa perlu exact match)
    if (empty($candidates)) {
        // Buat beberapa kandidat slug dari query dengan range ID umum
        // Ini hanya berguna jika Gemini aktif untuk menyaring yang benar
        $candidates[] = $base . '-12821';
        $candidates[] = $base . '-12345';
        $candidates[] = $base . '-11000';
    }

    return $candidates;
}

// ══════════════════════════════════════════════════════════════════════════════
// STEP 2: Tanya Gemini untuk generate slug + numeric ID
// Gunakan responseMimeType agar pasti dapat JSON
// ══════════════════════════════════════════════════════════════════════════════
function getSlugFromGemini($apiKey, $model, $query) {
    global $debugLog;

    $prompt = 'You are a GSMArena database expert. Given this smartphone: "' . $query . '"\n\n'
        . "Return ONLY this JSON (no markdown, no explanation):\n"
        . '{"slug":"brand_model_name-NUMERICID","alternatives":["slug2","slug3"]}'
        . "\n\nGSMArena URL pattern: https://www.gsmarena.com/SLUG.php\n"
        . "Examples:\n"
        . "- Samsung Galaxy S25 Ultra → samsung_galaxy_s25_ultra-12821\n"
        . "- Apple iPhone 16 Pro Max → apple_iphone_16_pro_max-12527\n"
        . "- Apple iPhone 12 → apple_iphone_12-10509\n"
        . "- Xiaomi 15 Ultra → xiaomi_15_ultra-12775\n"
        . "- OnePlus 13 → oneplus_13-13477\n"
        . "- POCO X7 Pro → poco_x7_pro-12771\n\n"
        . "Rules: lowercase, underscores, exact numeric ID from GSMArena. "
        . "If unsure of exact ID, give your best guess and 2-3 alternatives with different IDs.";

    $r = geminiCall($apiKey, [
        'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
        'generationConfig' => [
            'temperature'      => 0.1,
            'maxOutputTokens'  => 400,
            'responseMimeType' => 'application/json',
        ],
    ], $model);

    $debugLog['gemini_slug_http'] = $r['code'];
    $debugLog['gemini_slug_err']  = $r['curl_error'];

    if ($r['code'] !== 200) {
        $debugLog['gemini_slug_body'] = substr($r['body'] ?? '', 0, 400);
        return null;
    }

    $txt    = extractText($r);
    $clean  = cleanJSON($txt);
    $parsed = json_decode($clean, true);

    $debugLog['gemini_slug_raw'] = substr($clean, 0, 300);

    if (!is_array($parsed)) {
        // Coba extract JSON dari dalam teks
        if (preg_match('/\{[^{}]+\}/s', $clean, $m)) {
            $parsed = json_decode($m[0], true);
        }
    }

    return is_array($parsed) ? $parsed : null;
}

// ══════════════════════════════════════════════════════════════════════════════
// STEP 3: Scrape halaman device GSMArena
// ══════════════════════════════════════════════════════════════════════════════
function scrapeGSMArena($url) {
    $r = httpFetch($url, 20);
    if ($r['code'] !== 200 || strlen($r['html']) < 3000) {
        return ['ok' => false, 'code' => $r['code'], 'len' => strlen($r['html']), 'err' => $r['error']];
    }
    $html = $r['html'];

    // Validasi halaman: ada judul device
    if (!preg_match('|<h1 class="specs-phone-name-title"[^>]*>(.*?)</h1>|is', $html, $nameM)) {
        // Coba alternatif selector
        if (!preg_match('|<div class="specs-phone-name-title"[^>]*>(.*?)</div>|is', $html, $nameM)) {
            return ['ok' => false, 'code' => $r['code'], 'reason' => 'no_device_title', 'html_preview' => substr(strip_tags($html), 0, 200)];
        }
    }

    $data             = ['ok' => true, 'url' => $url];
    $data['name_full'] = trim(strip_tags($nameM[1]));

    // Gambar utama — coba beberapa selector
    if (preg_match('|<div class="specs-photo-main"[^>]*>.*?<img[^>]+src="([^"]+)"|is', $html, $imgM)) {
        $data['image_url'] = $imgM[1];
    } elseif (preg_match('|<img[^>]+src="(https://fdn2?\.gsmarena\.com/vv/bigpic/[^"]+)"|i', $html, $imgM)) {
        $data['image_url'] = $imgM[1];
    } elseif (preg_match('|<img[^>]+src="(//fdn2?\.gsmarena\.com/vv/bigpic/[^"]+)"|i', $html, $imgM)) {
        $data['image_url'] = 'https:' . $imgM[1];
    }

    // Parse tabel specs: <td class="ttl">...<a>LABEL</a>...</td><td class="nfo">VALUE</td>
    preg_match_all('|<td class="ttl"[^>]*>.*?<a[^>]*>\s*(.*?)\s*</a>.*?</td>\s*<td class="nfo"[^>]*>(.*?)</td>|is', $html, $rows);
    $specs = [];
    foreach ($rows[1] as $i => $label) {
        $label = trim(strip_tags($label));
        $val   = trim(strip_tags(html_entity_decode($rows[2][$i])));
        $val   = preg_replace('/\s+/', ' ', $val);
        if ($label && $val && $val !== 'N/A') {
            $specs[$label] = $val;
        }
    }

    $data['specs'] = $specs;
    $data['specs_count'] = count($specs);
    return $data;
}

// ══════════════════════════════════════════════════════════════════════════════
// STEP 4: Konversi raw specs → format specsync JSON via Gemini
// ══════════════════════════════════════════════════════════════════════════════
function convertToSpecsync($apiKey, $model, $scraped, $query) {
    $rawJSON  = json_encode($scraped['specs'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $imageUrl = $scraped['image_url'] ?? '';
    $nameFull = $scraped['name_full'] ?? $query;

    $priceHint = 'IDR realistis: Samsung S25 Ultra=23000000, iPhone 16 Pro Max=23999000, '
        . 'Xiaomi 15 Ultra=16000000, POCO X7 Pro=5999000, Samsung A55=6999000.';

    $prompt = "Convert these GSMArena raw specs for \"{$nameFull}\" into this exact JSON format.\n"
        . "Image URL: {$imageUrl}\n\n"
        . "RAW SPECS:\n{$rawJSON}\n\n"
        . "OUTPUT JSON FORMAT:\n"
        . '{"name":"","brand":"","slug":"","category":"flagship|midrange|budget|gaming",'
        . '"price":0,"release_year":0,"image":"",'
        . '"screen_size":0.0,"resolution":"","refresh_rate":0,"display_type":"",'
        . '"chipset":"","gpu":"","cpu_cores":0,"ram":0,"storage":0,'
        . '"main_camera":0,"front_camera":0,"camera_features":"",'
        . '"battery":0,"charging_speed":0,"has_wireless_charging":0,"network_5g":0,"nfc":0,'
        . '"wifi_version":"","bluetooth_version":"","os":"","os_version":"",'
        . '"weight":0,"thickness":0.0,"width":0.0,"height":0.0,"color_options":"",'
        . '"score_camera":0.0,"score_performance":0.0,"score_battery":0.0,"score_design":0.0}'
        . "\n\nRULES:\n"
        . "- price: {$priceHint}\n"
        . "- screen_size: number only (e.g. 6.9 from '6.9 inches')\n"
        . "- ram/storage: first option as integer GB\n"
        . "- main_camera: highest MP number from main camera\n"
        . "- has_wireless_charging/network_5g/nfc: 1=yes, 0=no\n"
        . "- image: use the image URL provided above\n"
        . "- scores: realistic 0.0-10.0\n"
        . "Reply ONLY with valid JSON.";

    $r = geminiCall($apiKey, [
        'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
        'generationConfig' => [
            'temperature'      => 0.05,
            'maxOutputTokens'  => 1500,
            'responseMimeType' => 'application/json',
        ],
    ], $model);

    $txt   = extractText($r);
    $clean = cleanJSON($txt);
    $specs = json_decode($clean, true);
    if (!is_array($specs) && preg_match('/\{[\s\S]+\}/m', $clean, $m)) {
        $specs = json_decode($m[0], true);
    }
    return $specs;
}

// ══════════════════════════════════════════════════════════════════════════════
// STEP 5: Fallback — pure Gemini grounding search
// ══════════════════════════════════════════════════════════════════════════════
function geminiGrounding($apiKey, $model, $query) {
    global $debugLog;

    $priceHint = 'IDR realistis: Samsung S25 Ultra=23000000, iPhone 16 Pro Max=23999000, '
        . 'Xiaomi 15 Ultra=16000000, POCO X7 Pro=5999000.';

    $jsonFormat = '{"name":"","brand":"","slug":"","category":"flagship|midrange|budget|gaming",'
        . '"price":0,"release_year":0,"image":"",'
        . '"screen_size":0.0,"resolution":"","refresh_rate":0,"display_type":"",'
        . '"chipset":"","gpu":"","cpu_cores":0,"ram":0,"storage":0,'
        . '"main_camera":0,"front_camera":0,"camera_features":"",'
        . '"battery":0,"charging_speed":0,"has_wireless_charging":0,"network_5g":0,"nfc":0,'
        . '"wifi_version":"","bluetooth_version":"","os":"","os_version":"",'
        . '"weight":0,"thickness":0.0,"width":0.0,"height":0.0,"color_options":"",'
        . '"score_camera":0.0,"score_performance":0.0,"score_battery":0.0,"score_design":0.0}';

    // Grounding search
    $r1 = geminiCall($apiKey, [
        'contents' => [['role' => 'user', 'parts' => [['text' =>
            "Cari spesifikasi LENGKAP smartphone: \"{$query}\"\n"
            . "Sebutkan: nama, merek, harga IDR, tahun rilis, layar, chipset, GPU, CPU cores, "
            . "RAM, storage, kamera utama MP, kamera depan MP, baterai mAh, charging W, "
            . "wireless charging, 5G, NFC, WiFi, Bluetooth, OS, berat gram, dimensi mm, warna."
        ]]]],
        'tools' => [['google_search' => (object)[]]],
        'generationConfig' => ['temperature' => 0.1, 'maxOutputTokens' => 3000],
    ], $model);

    $rawText = extractText($r1);
    $debugLog['grounding_http']     = $r1['code'];
    $debugLog['grounding_raw_len']  = strlen($rawText);

    $basePrompt = ($rawText
        ? "Data dari Google Search:\n\n{$rawText}\n\nKonversi ke JSON:"
        : "Berikan spesifikasi \"{$query}\" dalam JSON:")
        . "\n\n{$jsonFormat}"
        . "\n\nCAT price: {$priceHint} | has_wireless_charging/network_5g/nfc: 1=ya 0=tidak"
        . "\nBALAS HANYA JSON VALID.";

    $r2 = geminiCall($apiKey, [
        'contents' => [['role' => 'user', 'parts' => [['text' => $basePrompt]]]],
        'generationConfig' => [
            'temperature'      => 0.05,
            'maxOutputTokens'  => 2000,
            'responseMimeType' => 'application/json',
        ],
    ], $model);

    $debugLog['grounding_json_http'] = $r2['code'];
    $txt   = extractText($r2);
    $clean = cleanJSON($txt);
    $specs = json_decode($clean, true);
    if (!$specs && preg_match('/\{[\s\S]+\}/m', $clean, $m)) {
        $specs = json_decode($m[0], true);
    }
    if ($specs) $specs['_source'] = $model . '+gemini_grounding';
    return $specs;
}

// ══════════════════════════════════════════════════════════════════════════════
// MAIN FLOW
// ══════════════════════════════════════════════════════════════════════════════

// ── A. Build slug candidates dari hardcoded DB dulu ──────────────────────────
$hardcodedSlugs = buildSlugCandidates($query);
$debugLog['hardcoded_slugs'] = $hardcodedSlugs;

// ── B. Dapatkan slug dari Gemini (dengan responseMimeType JSON) ───────────────
$geminiSlugData = null;
if ($useGemini) {
    $geminiSlugData = getSlugFromGemini($apiKey, $MODEL, $query);
}
$debugLog['gemini_slug_data'] = $geminiSlugData;

// Gabungkan semua slug candidates
$allSlugs = $hardcodedSlugs;
if ($geminiSlugData) {
    if (!empty($geminiSlugData['slug']))         array_unshift($allSlugs, $geminiSlugData['slug']); // prioritas Gemini
    if (!empty($geminiSlugData['alternatives'])) $allSlugs = array_merge($allSlugs, (array)$geminiSlugData['alternatives']);
}

// Deduplicate
$allSlugs = array_unique(array_filter($allSlugs));
$debugLog['all_slugs'] = $allSlugs;

// ── C. Coba fetch & scrape setiap slug ───────────────────────────────────────
$scrapedData = null;
$usedUrl     = null;

// Helper: cek apakah nama hasil scrape relevan dengan query
function nameMatchesQuery($scrapedName, $query) {
    $normalize = fn($s) => strtolower(preg_replace('/[^a-z0-9]/i', ' ', $s));
    $sn = $normalize($scrapedName);
    $qn = $normalize($query);
    // Ambil kata-kata penting dari query (panjang > 1)
    $qWords = array_filter(explode(' ', $qn), fn($w) => strlen($w) > 1);
    if (empty($qWords)) return true;
    $matchCount = 0;
    foreach ($qWords as $w) {
        if (strpos($sn, $w) !== false) $matchCount++;
    }
    // Minimal 60% kata query harus ada di nama hasil scrape
    return ($matchCount / count($qWords)) >= 0.6;
}

// Helper: parse integer dari string GSMArena (ambil angka pertama)
function parseIntSpec($val) {
    if (!$val) return 0;
    preg_match('/(\d+)/', str_replace(',', '', $val), $m);
    return isset($m[1]) ? (int)$m[1] : 0;
}

// Helper: parse float dari string GSMArena
function parseFloatSpec($val) {
    if (!$val) return 0.0;
    preg_match('/(\d+\.?\d*)/', $val, $m);
    return isset($m[1]) ? (float)$m[1] : 0.0;
}

if ($useGSMArena) {
    foreach ($allSlugs as $slug) {
        // Sanitize slug
        $slug = strtolower(trim(preg_replace('/[^a-z0-9_\-]/', '', $slug)));
        if (!preg_match('/-\d+$/', $slug)) continue; // wajib ada numeric ID

        $url    = "https://www.gsmarena.com/{$slug}.php";
        $result = scrapeGSMArena($url);

        $nameOk = !empty($result['name_full']) && nameMatchesQuery($result['name_full'], $query);

        $debugLog['fetch_log'][] = [
            'url'       => $url,
            'ok'        => $result['ok'] ?? false,
            'code'      => $result['code'] ?? 0,
            'specs'     => $result['specs_count'] ?? 0,
            'name'      => $result['name_full'] ?? '',
            'name_ok'   => $nameOk,
            'reason'    => $result['reason'] ?? ($result['err'] ?? ''),
        ];

        if (!empty($result['ok']) && !empty($result['specs']) && count($result['specs']) > 5 && $nameOk) {
            $scrapedData = $result;
            $usedUrl     = $url;
            break;
        }
    }
}

// ── D. Konversi scraped → specsync JSON ──────────────────────────────────────
$specs = null;
if ($scrapedData) {
    $debugLog['gsmarena_success'] = [
        'url'         => $usedUrl,
        'name'        => $scrapedData['name_full'],
        'specs_count' => $scrapedData['specs_count'],
    ];
    if ($useGemini) {
        $specs = convertToSpecsync($apiKey, $MODEL, $scrapedData, $query);
    } else {
        // ── Parse GSMArena raw specs tanpa Gemini ──────────────────────────
        // GSMArena menggunakan key-key spesifik ini di tabel specsnya
        $raw = $scrapedData['specs'];

        // Nama & brand
        $nameFull = $scrapedData['name_full'] ?? $query;
        $nameParts = explode(' ', $nameFull);
        $brand = $nameParts[0] ?? '';

        // Layar
        $sizeRaw = $raw['Size'] ?? $raw['size'] ?? '';
        $screenSize = parseFloatSpec($sizeRaw); // e.g. "6.7 inches" → 6.7

        $resRaw = $raw['Resolution'] ?? $raw['resolution'] ?? '';
        // Extract resolution like "1080 x 2400 pixels" → "1080x2400"
        preg_match('/(\d{3,4})\s*x\s*(\d{3,4})/', $resRaw, $resM);
        $resolution = isset($resM[1]) ? $resM[1] . 'x' . $resM[2] : $resRaw;

        $refreshRaw = $raw['Type'] ?? ''; // GSMArena taruh Hz di kolom Type panel
        preg_match('/(\d+)Hz/', $refreshRaw, $hzM);
        $refreshRate = isset($hzM[1]) ? (int)$hzM[1] : 60;

        $displayType = '';
        if (preg_match('/(AMOLED|OLED|IPS|LCD|TFT|Super AMOLED|Dynamic AMOLED)/i', $refreshRaw, $dtM))
            $displayType = $dtM[1];

        // Chipset & prosesor
        $chipset  = $raw['Chipset'] ?? '';
        $gpu      = $raw['GPU'] ?? '';
        $cpuRaw   = $raw['CPU'] ?? '';
        preg_match('/(\d+)\s*x\s*|(\d+)-core/i', $cpuRaw, $coreM);
        $cpuCores = isset($coreM[1]) ? (int)$coreM[1] : (isset($coreM[2]) ? (int)$coreM[2] : 8);

        // RAM & storage — GSMArena: "Internal" field, e.g. "128GB 8GB RAM" atau "128GB, 256GB 6GB, 8GB RAM"
        $internalRaw = $raw['Internal'] ?? '';
        // Ambil RAM (angka sebelum "GB RAM")
        preg_match('/(\d+)\s*GB\s*RAM/i', $internalRaw, $ramM);
        $ram = isset($ramM[1]) ? (int)$ramM[1] : parseIntSpec($internalRaw);
        // Ambil storage (angka pertama sebelum "GB" yang bukan RAM)
        preg_match('/^(\d+)\s*GB/i', trim($internalRaw), $storM);
        $storage = isset($storM[1]) ? (int)$storM[1] : 0;

        // Kamera utama — GSMArena: bisa "Main Camera", "Triple", "Quad", "Single", "Dual"
        $camKeys = ['Main Camera', 'Triple', 'Quad', 'Dual', 'Single'];
        $camRaw  = '';
        foreach ($camKeys as $ck) { if (!empty($raw[$ck])) { $camRaw = $raw[$ck]; break; } }
        // Ambil MP terbesar
        preg_match_all('/(\d+)\s*MP/i', $camRaw, $mpAll);
        $mainCamera = !empty($mpAll[1]) ? max(array_map('intval', $mpAll[1])) : 0;
        $cameraFeatures = $camRaw;

        // Kamera depan
        $selfieRaw  = $raw['Selfie camera'] ?? $raw['Front Camera'] ?? $raw['Front'] ?? '';
        preg_match('/(\d+)\s*MP/i', $selfieRaw, $selM);
        $frontCamera = isset($selM[1]) ? (int)$selM[1] : 0;

        // Baterai
        $batRaw  = $raw['Capacity'] ?? $raw['Battery'] ?? '';
        preg_match('/(\d{3,5})\s*mAh/i', $batRaw, $batM);
        $battery = isset($batM[1]) ? (int)$batM[1] : parseIntSpec($batRaw);

        // Charging
        $chargingRaw = $raw['Charging'] ?? '';
        preg_match('/(\d+)\s*W/i', $chargingRaw, $chgM);
        $chargingSpeed = isset($chgM[1]) ? (int)$chgM[1] : 0;
        $hasWireless   = (stripos($chargingRaw, 'wireless') !== false) ? 1 : 0;

        // Konektivitas
        $techRaw  = $raw['Technology'] ?? $raw['Network'] ?? '';
        $has5g    = (stripos($techRaw, '5G') !== false || stripos($raw['2G bands'] ?? '', '5G') !== false) ? 1 : 0;
        // Cek 5G di "Speed" atau semua field
        foreach ($raw as $rk => $rv) {
            if (stripos($rv, '5G') !== false && in_array($rk, ['Technology','Speed','Network'])) { $has5g = 1; break; }
        }

        $commsRaw = $raw['WLAN'] ?? $raw['Wi-Fi'] ?? '';
        preg_match('/(Wi-Fi \d+\.?\d*|802\.11[a-z\/]+)/i', $commsRaw, $wifiM);
        $wifiVersion = $wifiM[1] ?? $commsRaw;

        $btRaw = $raw['Bluetooth'] ?? '';
        preg_match('/(\d+\.\d+)/', $btRaw, $btM);
        $bluetoothVersion = isset($btM[1]) ? $btM[1] : $btRaw;

        $nfcRaw = $raw['NFC'] ?? '';
        $hasNfc = (stripos($nfcRaw, 'Yes') !== false || stripos($nfcRaw, 'available') !== false) ? 1 : 0;

        // OS
        $osRaw     = $raw['OS'] ?? '';
        $os        = preg_match('/Android/i', $osRaw) ? 'Android' : (preg_match('/iOS/i', $osRaw) ? 'iOS' : $osRaw);
        $osVersion = '';
        if (preg_match('/Android\s*([\d.]+)/i', $osRaw, $ovM)) $osVersion = $ovM[1];
        elseif (preg_match('/iOS\s*([\d.]+)/i', $osRaw, $ovM)) $osVersion = $ovM[1];

        // Dimensi fisik — GSMArena: "Dimensions" field, e.g. "163.4 x 74.9 x 8.6 mm, 220 g"
        $dimRaw = $raw['Dimensions'] ?? '';
        preg_match('/([\d.]+)\s*x\s*([\d.]+)\s*x\s*([\d.]+)/i', $dimRaw, $dimM);
        $height    = isset($dimM[1]) ? (float)$dimM[1] : 0.0;
        $width     = isset($dimM[2]) ? (float)$dimM[2] : 0.0;
        $thickness = isset($dimM[3]) ? (float)$dimM[3] : 0.0;

        $weightRaw = $raw['Weight'] ?? '';
        preg_match('/(\d+)\s*g/i', $weightRaw, $wtM);
        $weight = isset($wtM[1]) ? (int)$wtM[1] : 0;

        // Warna
        $colorOptions = $raw['Colors'] ?? $raw['Color'] ?? '';

        // Kategori harga
        $priceMap = ['flagship' => 8000000, 'midrange' => 3000000, 'budget' => 0];
        $category = 'budget';
        if ($ram >= 8 && $battery >= 4000 && $mainCamera >= 50) $category = 'flagship';
        elseif ($ram >= 6 || $mainCamera >= 48) $category = 'midrange';

        // Skor AI sederhana berdasarkan specs
        $scoreCamera      = min(10.0, round($mainCamera / 20 + ($frontCamera > 10 ? 1.5 : 0.5), 1));
        $scorePerformance = min(10.0, round(($cpuCores >= 8 ? 5.0 : 3.0) + ($ram >= 8 ? 2.5 : 1.0) + ($has5g ? 1.5 : 0), 1));
        $scoreBattery     = min(10.0, round($battery / 500, 1));
        $scoreDesign      = min(10.0, round($screenSize + ($thickness < 9 ? 1 : 0), 1));

        $specs = [
            'name'                => $nameFull,
            'brand'               => $brand,
            'slug'                => trim(strtolower(preg_replace('/[^a-z0-9]+/', '-', $nameFull)), '-'),
            'category'            => $category,
            'price'               => 0,
            'release_year'        => (int)($raw['Announced'] ? substr($raw['Announced'], 0, 4) : date('Y')),
            'image'               => $scrapedData['image_url'] ?? '',
            'screen_size'         => $screenSize,
            'resolution'          => $resolution,
            'refresh_rate'        => $refreshRate,
            'display_type'        => $displayType,
            'chipset'             => $chipset,
            'gpu'                 => $gpu,
            'cpu_cores'           => $cpuCores,
            'ram'                 => $ram,
            'storage'             => $storage,
            'main_camera'         => $mainCamera,
            'front_camera'        => $frontCamera,
            'camera_features'     => $cameraFeatures,
            'battery'             => $battery,
            'charging_speed'      => $chargingSpeed,
            'has_wireless_charging'=> $hasWireless,
            'network_5g'          => $has5g,
            'nfc'                 => $hasNfc,
            'wifi_version'        => $wifiVersion,
            'bluetooth_version'   => $bluetoothVersion,
            'os'                  => $os,
            'os_version'          => $osVersion,
            'weight'              => $weight,
            'thickness'           => $thickness,
            'width'               => $width,
            'height'              => $height,
            'color_options'       => $colorOptions,
            'score_camera'        => $scoreCamera,
            'score_performance'   => $scorePerformance,
            'score_battery'       => $scoreBattery,
            'score_design'        => $scoreDesign,
            '_source'             => 'gsmarena_only',
        ];
    }
    if ($specs) {
        if (empty($specs['_source'])) $specs['_source'] = 'gsmarena+' . $MODEL;
        $specs['_gsmarena_url'] = $usedUrl;
    }
}

// ── E. Fallback ke Gemini grounding ──────────────────────────────────────────
if ($useGemini && (!is_array($specs) || empty($specs['name']))) {
    $debugLog['using_gemini_fallback'] = true;
    $specs = geminiGrounding($apiKey, $MODEL, $query);
}

// ── F. Gagal total ───────────────────────────────────────────────────────────
if (!is_array($specs) || empty($specs['name'])) {
    echo json_encode([
        'error'  => "Gagal mengambil spesifikasi \"{$query}\". Coba dengan nama lebih lengkap.",
        '_debug' => $debugLog,
    ]);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════════
// SANITASI FIELD
// ══════════════════════════════════════════════════════════════════════════════
foreach (['price','release_year','refresh_rate','cpu_cores','ram','storage',
          'main_camera','front_camera','battery','charging_speed',
          'has_wireless_charging','network_5g','nfc','weight'] as $f) {
    if (isset($specs[$f])) $specs[$f] = intval($specs[$f]);
}
foreach (['screen_size','thickness','width','height',
          'score_camera','score_performance','score_battery','score_design'] as $f) {
    if (isset($specs[$f])) $specs[$f] = round(floatval($specs[$f]), 1);
}

// Fallback image
if (empty($specs['image'])) {
    $b = strtolower(preg_replace('/[^a-z0-9]/', '', $specs['brand'] ?? ''));
    $s = trim(strtolower(preg_replace('/[^a-z0-9]+/', '-', $specs['name'] ?? '')), '-');
    $specs['image'] = "https://fdn2.gsmarena.com/vv/bigpic/{$b}-{$s}.jpg";
}

// Fallback slug
if (empty($specs['slug'])) {
    $specs['slug'] = trim(strtolower(preg_replace('/[^a-z0-9]+/', '-', $specs['name'] ?? $query)), '-');
}

echo json_encode($specs);
