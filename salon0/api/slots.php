<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');
if (!user()) { http_response_code(401); echo json_encode(['slots' => [], 'error' => 'login']); exit; }
echo json_encode(['slots' => slot_list((int)($_GET['stylist'] ?? 0), (int)($_GET['service'] ?? 0), $_GET['date'] ?? '', (int)($_GET['exclude'] ?? 0))]);
