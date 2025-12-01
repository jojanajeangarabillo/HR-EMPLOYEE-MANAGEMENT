<?php
$base = getenv('HR_API_BASE') ?: 'http://26.137.144.53/HR-EMPLOYEE-MANAGEMENT/API';
$url = $base . '/get_job_postings.php';

$data = [
  'search' => $_GET['search'] ?? '',
  'department_id' => isset($_GET['department_id']) ? (int) $_GET['department_id'] : 0,
  'employment_type_id' => isset($_GET['employment_type_id']) ? (int) $_GET['employment_type_id'] : 0,
  'created_from' => $_GET['created_from'] ?? '',
  'created_to' => $_GET['created_to'] ?? ''
];

$payload = json_encode($data);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
$res = curl_exec($ch);
$err = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

header('Content-Type: application/json');
if ($err) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => $err]);
  exit;
}
http_response_code($code);
echo $res;
