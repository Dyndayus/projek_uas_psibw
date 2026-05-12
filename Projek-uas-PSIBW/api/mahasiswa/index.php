<?php
require_once '../../config/db.php';
setHeaders();
requireLogin();

$db     = getDB();
$page   = max(1, intval($_GET['page']  ?? 1));
$limit  = intval($_GET['limit'] ?? 10);
$offset = ($page - 1) * $limit;
$search = '%' . ($_GET['search'] ?? '') . '%';

$count = $db->prepare("SELECT COUNT(*) as total FROM mhs WHERE nama LIKE ? OR nim LIKE ?");
$count->bind_param('ss', $search, $search);
$count->execute();
$total = $count->get_result()->fetch_assoc()['total'];

$stmt = $db->prepare("SELECT * FROM mhs WHERE nama LIKE ? OR nim LIKE ? ORDER BY id_mhs DESC LIMIT ? OFFSET ?");
$stmt->bind_param('ssii', $search, $search, $limit, $offset);
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'status' => 'success',
    'data'   => $data,
    'pagination' => [
        'total'        => $total,
        'per_page'     => $limit,
        'current_page' => $page,
        'last_page'    => ceil($total / $limit)
    ]
]);
