<?php
// api.php - JSON API for AJAX requests
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'includes/db.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // Live search for autocomplete
    case 'search':
        $q = sanitize($_GET['q'] ?? '');
        if (strlen($q) < 2) { echo json_encode([]); exit; }
        $conn = getDB();
        $result = mysqli_query($conn, "SELECT id, name, brand, slug, price, image, category FROM devices WHERE (name LIKE '%$q%' OR brand LIKE '%$q%') AND is_active=1 ORDER BY price DESC LIMIT 8");
        $devices = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $row['price_fmt'] = formatPrice($row['price']);
            $devices[] = $row;
        }
        echo json_encode($devices);
        break;

    // Get device details for comparison
    case 'device':
        $id = intval($_GET['id'] ?? 0);
        $slug = sanitize($_GET['slug'] ?? '');
        $conn = getDB();
        $where = $id ? "id=$id" : "slug='$slug'";
        $result = mysqli_query($conn, "SELECT * FROM devices WHERE $where AND is_active=1");
        $device = mysqli_fetch_assoc($result);
        if ($device) {
            $device['price_fmt'] = formatPrice($device['price']);
            echo json_encode($device);
        } else {
            echo json_encode(['error' => 'Device not found']);
        }
        break;

    // Get all devices (catalog)
    case 'catalog':
        $conn = getDB();
        $persona = sanitize($_GET['persona'] ?? '');
        $sort = sanitize($_GET['sort'] ?? 'price_desc');
        $category = sanitize($_GET['category'] ?? '');
        $min_price = intval($_GET['min_price'] ?? 0);
        $max_price = intval($_GET['max_price'] ?? 99999999);

        $where = "is_active=1 AND price BETWEEN $min_price AND $max_price";
        if ($category) $where .= " AND category='$category'";

        // Persona-based filtering
        $order = '';
        if ($persona === 'gaming') {
            $where .= " AND score_performance >= 8";
            $order = "ORDER BY score_performance DESC, ram DESC";
        } elseif ($persona === 'photo') {
            $where .= " AND score_camera >= 8";
            $order = "ORDER BY score_camera DESC, main_camera DESC";
        } elseif ($persona === 'battery') {
            $where .= " AND battery >= 5000";
            $order = "ORDER BY score_battery DESC, battery DESC";
        } elseif ($persona === 'budget') {
            $where .= " AND price <= 5000000";
            $order = "ORDER BY price ASC";
        } else {
            $sort_map = [
                'price_asc' => 'price ASC',
                'price_desc' => 'price DESC',
                'score_camera' => 'score_camera DESC',
                'score_performance' => 'score_performance DESC',
                'score_battery' => 'score_battery DESC',
                'newest' => 'release_year DESC, id DESC',
            ];
            $order = "ORDER BY " . ($sort_map[$sort] ?? 'price DESC');
        }

        $result = mysqli_query($conn, "SELECT id, name, brand, slug, price, image, category, screen_size, ram, storage, battery, main_camera, score_camera, score_performance, score_battery, score_design, network_5g, release_year FROM devices WHERE $where $order");
        $devices = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $row['price_fmt'] = formatPrice($row['price']);
            $row['overall_score'] = round(($row['score_camera'] + $row['score_performance'] + $row['score_battery'] + $row['score_design']) / 4, 1);
            $devices[] = $row;
        }
        echo json_encode($devices);
        break;

    // Add to orders (cart/pending order from device page)
    case 'add_to_orders':
        if (empty($_SESSION['user_id'])) { echo json_encode(['error' => 'Login required']); exit; }
        $user_id = intval($_SESSION['user_id']);
        $device_id = intval($_POST['device_id'] ?? 0);
        if (!$device_id) { echo json_encode(['error' => 'Device tidak valid']); exit; }
        $conn = getDB();
        $dev = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, price, name FROM devices WHERE id=$device_id AND is_active=1"));
        if (!$dev) { echo json_encode(['error' => 'Device tidak ditemukan']); exit; }
        // Check if already has a pending order for this device
        $existing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM orders WHERE user_id=$user_id AND device_id=$device_id AND status='pending'"));
        if ($existing) {
            echo json_encode(['status' => 'ok', 'order_id' => $existing['id'], 'message' => 'Sudah ada di orders']);
        } else {
            $amount = floatval($dev['price']);
            mysqli_query($conn, "INSERT INTO orders (user_id, device_id, amount, status, created_at) VALUES ($user_id, $device_id, $amount, 'pending', NOW())");
            $order_id = mysqli_insert_id($conn);
            echo json_encode(['status' => 'ok', 'order_id' => $order_id]);
        }
        break;


    // Cancel pending order
    case 'cancel_order':
        if (empty($_SESSION['user_id'])) { echo json_encode(['error' => 'Login required']); exit; }
        $user_id = intval($_SESSION['user_id']);
        $order_id = intval($_POST['order_id'] ?? 0);
        $conn = getDB();
        $order = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM orders WHERE id=$order_id AND user_id=$user_id AND status='pending'"));
        if (!$order) { echo json_encode(['error' => 'Order tidak ditemukan atau sudah diproses']); exit; }
        mysqli_query($conn, "UPDATE orders SET status='cancelled' WHERE id=$order_id AND user_id=$user_id");
        echo json_encode(['status' => 'ok']);
        break;

    case 'wishlist_toggle':
        if (empty($_SESSION['user_id'])) { echo json_encode(['error' => 'Login required']); exit; }
        $user_id = intval($_SESSION['user_id']);
        $device_id = intval($_POST['device_id'] ?? 0);
        $conn = getDB();
        $check = mysqli_query($conn, "SELECT id FROM wishlist WHERE user_id=$user_id AND device_id=$device_id");
        if (mysqli_num_rows($check) > 0) {
            mysqli_query($conn, "DELETE FROM wishlist WHERE user_id=$user_id AND device_id=$device_id");
            echo json_encode(['status' => 'removed']);
        } else {
            mysqli_query($conn, "INSERT INTO wishlist (user_id, device_id) VALUES ($user_id, $device_id)");
            echo json_encode(['status' => 'added']);
        }
        break;

    // Save comparison
    case 'save_comparison':
        if (empty($_SESSION['user_id'])) { echo json_encode(['error' => 'Login required']); exit; }
        $user_id = intval($_SESSION['user_id']);
        $d1 = intval($_POST['device1_id'] ?? 0);
        $d2 = intval($_POST['device2_id'] ?? 0);
        $label = sanitize($_POST['label'] ?? '');
        $token = generateShareToken();
        $conn = getDB();
        mysqli_query($conn, "INSERT INTO saved_comparisons (user_id, device1_id, device2_id, share_token, label) VALUES ($user_id, $d1, $d2, '$token', '$label')");
        echo json_encode(['token' => $token, 'url' => "compare.php?token=$token"]);
        break;

    // Get comparison by share token
    case 'get_comparison':
        $token = sanitize($_GET['token'] ?? '');
        $conn = getDB();
        $result = mysqli_query($conn, "SELECT sc.*, d1.name as d1_name, d2.name as d2_name FROM saved_comparisons sc JOIN devices d1 ON sc.device1_id=d1.id JOIN devices d2 ON sc.device2_id=d2.id WHERE sc.share_token='$token'");
        $comp = mysqli_fetch_assoc($result);
        echo json_encode($comp ?: ['error' => 'Not found']);
        break;

    // Set price alert
    case 'set_price_alert':
        if (empty($_SESSION['user_id'])) { echo json_encode(['error' => 'Login required']); exit; }
        $user_id = intval($_SESSION['user_id']);
        $device_id = intval($_POST['device_id'] ?? 0);
        $target_price = floatval($_POST['target_price'] ?? 0);
        if (!$device_id || $target_price <= 0) { echo json_encode(['error' => 'Data tidak valid']); exit; }
        $conn = getDB();
        // Check device exists
        $dev = mysqli_fetch_assoc(mysqli_query($conn, "SELECT price FROM devices WHERE id=$device_id AND is_active=1"));
        if (!$dev) { echo json_encode(['error' => 'Device tidak ditemukan']); exit; }
        // Upsert: delete old alert for same device+user, then insert new
        mysqli_query($conn, "DELETE FROM price_alerts WHERE user_id=$user_id AND device_id=$device_id");
        $is_triggered = ($dev['price'] <= $target_price) ? 1 : 0;
        mysqli_query($conn, "INSERT INTO price_alerts (user_id, device_id, target_price, is_triggered) VALUES ($user_id, $device_id, $target_price, $is_triggered)");
        $msg = $is_triggered
            ? 'Harga HP sudah di bawah target kamu!'
            : 'Alert aktif! Kami beritahu jika harga turun.';
        echo json_encode(['status' => 'ok', 'triggered' => $is_triggered, 'message' => $msg]);
        break;

    // Delete price alert
    case 'delete_price_alert':
        if (empty($_SESSION['user_id'])) { echo json_encode(['error' => 'Login required']); exit; }
        $user_id = intval($_SESSION['user_id']);
        $device_id = intval($_POST['device_id'] ?? 0);
        $conn = getDB();
        mysqli_query($conn, "DELETE FROM price_alerts WHERE user_id=$user_id AND device_id=$device_id");
        echo json_encode(['status' => 'deleted']);
        break;

    // Check wishlist status for a device
    case 'wishlist_check':
        if (empty($_SESSION['user_id'])) { echo json_encode(['in_wishlist' => false]); exit; }
        $user_id = intval($_SESSION['user_id']);
        $device_id = intval($_GET['device_id'] ?? 0);
        $conn = getDB();
        $r = mysqli_query($conn, "SELECT id FROM wishlist WHERE user_id=$user_id AND device_id=$device_id");
        echo json_encode(['in_wishlist' => mysqli_num_rows($r) > 0]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action']);
}
?>
