<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
  http_response_code(405);
  echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
  exit;
}

$base = getenv('ATTENDANCE_API_BASE') ?: 'http://26.89.31.92/SIA/SIA-Payroll-System-Modules/modules/get_all_attendance.php';
$ct = $_SERVER['CONTENT_TYPE'] ?? '';
$raw = file_get_contents('php://input');
$body = (stripos($ct, 'application/json') !== false) ? json_decode($raw, true) : null;
if (!is_array($body))
  $body = ($_SERVER['REQUEST_METHOD'] === 'POST') ? $_POST : $_GET;
$params = $body ?: [];
$empCode = isset($params['emp_code']) ? trim($params['emp_code']) : '';
$from = isset($params['from']) ? trim($params['from']) : '';
$to = isset($params['to']) ? trim($params['to']) : '';
$query = http_build_query($params);
$url = $base . (strpos($base, '?') === false ? '?' : '&') . $query;
$ttlMs = (int) (getenv('ATTENDANCE_CACHE_TTL_MS') ?: 60000);
$cacheKey = sha1($url);
$cacheDir = sys_get_temp_dir();
$cacheFile = rtrim($cacheDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . "attendance_" . $cacheKey . ".json";
function getCached($file, $ttlMs)
{
  if (!file_exists($file))
    return null;
  $ageMs = (int) ((microtime(true) - (float) filemtime($file)) * 1000);
  if ($ageMs > $ttlMs)
    return null;
  $txt = @file_get_contents($file);
  if ($txt === false)
    return null;
  return $txt;
}
function setCached($file, $text)
{
  @file_put_contents($file, $text);
}

$headers = ['Accept: application/json'];
$connectTimeout = (int) (getenv('ATTENDANCE_CONNECT_TIMEOUT_MS') ?: 5000);
$timeout = (int) (getenv('ATTENDANCE_TIMEOUT_MS') ?: 15000);
$res = getCached($cacheFile, $ttlMs);
$err = '';
$code = 200;
if ($res === null) {
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $connectTimeout);
  curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout);
  $res = curl_exec($ch);
  $err = curl_error($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if (!$err && is_string($res) && $code >= 200 && $code < 300)
    setCached($cacheFile, $res);
}

if ($err) {
  http_response_code($code ?: 500);
  echo json_encode(['status' => 'error', 'message' => $err, 'url' => $url]);
  exit;
}

$decoded = null;
if (is_string($res)) {
  $decoded = json_decode($res, true);
}
$records = [];
if (is_array($decoded)) {
  if (isset($decoded['attendance_records']) && is_array($decoded['attendance_records'])) {
    $records = $decoded['attendance_records'];
  } elseif (isset($decoded['data']) && is_array($decoded['data'])) {
    $records = $decoded['data'];
  } elseif (isset($decoded['records']) && is_array($decoded['records'])) {
    $records = $decoded['records'];
  } elseif (array_is_list($decoded)) {
    $records = $decoded;
  }
}
if (!is_array($records))
  $records = [];
$scope = $records;
if ($empCode !== '') {
  $scope = array_values(array_filter($records, function ($r) use ($empCode) {
    $code = '';
    if (is_array($r)) {
      $code = (string) ($r['emp_code'] ?? $r['empID'] ?? $r['employee_code'] ?? '');
    }
    return trim($code) === trim($empCode);
  }));
}
function num($r, $keys, $def = 0)
{
  foreach ($keys as $k) {
    if (isset($r[$k])) {
      $v = $r[$k];
      if (is_numeric($v))
        return (float) $v;
      // try to parse HH:MM format for late hours
      if (is_string($v) && preg_match('/^(\d+):(\d+)/', $v, $m)) {
        return ((int) $m[1]) + ((int) $m[2] / 60.0);
      }
    }
  }
  return (float) $def;
}
$presentDays = 0;
$absentDays = 0;
$totalWorkDays = 0;
$sumHoursWorked = 0.0;
$sumOvertime = 0.0;
$sumLate = 0.0;
$countHours = 0;

foreach ($scope as $r) {
  if (!is_array($r))
    continue;
  $status = strtolower(trim((string) ($r['attendance_status'] ?? $r['status'] ?? '')));
  if ($status === 'present')
    $presentDays++;
  if ($status === 'absent')
    $absentDays++;
  if (in_array($status, ['present', 'absent']))
    $totalWorkDays++;

  $hrs = num($r, ['hours_worked', 'total_hours', 'worked_hours']);
  $ot = num($r, ['overtime_hours', 'ot_hours', 'overtime']);
  $late = num($r, ['late_hours', 'late_time_hours', 'late']);
  if ($hrs > 0) {
    $sumHoursWorked += $hrs;
    $countHours++;
  }
  $sumOvertime += max(0.0, $ot);
  $sumLate += max(0.0, $late);
}
$approvedLeaves = 0;
if ($empCode !== '') {
  $leaveUrl = (getenv('HR_API_BASE') ?: (isset($_SERVER['HTTP_HOST']) ? ("http://" . $_SERVER['HTTP_HOST'] . "/HR-EMPLOYEE-MANAGEMENT/API") : ''));
  if ($leaveUrl !== '') {
    $leaveEndpoint = rtrim($leaveUrl, '/') . '/leave_requests.php';
    $payload = json_encode(['empID' => $empCode, 'status' => 'Approved']);
    $ch = curl_init($leaveEndpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $lr = curl_exec($ch);
    $lerr = curl_error($ch);
    curl_close($ch);
    if (!$lerr && is_string($lr)) {
      $ld = json_decode($lr, true);
      if (is_array($ld) && isset($ld['requests']) && is_array($ld['requests'])) {
        $approvedLeaves = count($ld['requests']);
      }
    }
  }
}
$payslipsCount = 0;
if ($empCode !== '') {
  $hostBase = isset($_SERVER['HTTP_HOST']) ? ("http://" . $_SERVER['HTTP_HOST'] . "/HR-EMPLOYEE-MANAGEMENT/API") : '';
  $payrollEndpoint = ($hostBase !== '' ? (rtrim($hostBase, '/') . '/consumer_payroll.php') : '');
  if ($payrollEndpoint !== '') {
    $payload = json_encode(['emp_code' => $empCode]);
    $ch = curl_init($payrollEndpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $pr = curl_exec($ch);
    curl_close($ch);
    $pd = is_string($pr) ? json_decode($pr, true) : null;
    if (is_array($pd)) {
      $list = $pd['data'] ?? $pd['payload'] ?? [];
      if (!is_array($list))
        $list = [];
      if (isset($pd['payload']) && is_string($pd['payload'])) {
        $tmp = json_decode($pd['payload'], true);
        if (is_array($tmp))
          $list = $tmp;
      }
      if (!array_is_list($list) && is_array($list)) {
        $list = $list['data'] ?? $list['records'] ?? [];
      }
      if (is_array($list)) {
        $payslipsCount = count(array_filter($list, function ($r) use ($empCode) {
          return is_array($r) && trim((string) ($r['emp_code'] ?? '')) === trim($empCode);
        }));
      }
    }
  }
}

$attendancePercentage = ($totalWorkDays > 0) ? ($presentDays / $totalWorkDays) : 0.0;
$avgHoursWorked = ($countHours > 0) ? ($sumHoursWorked / $countHours) : 0.0;

http_response_code($code ?: 200);
echo json_encode([
  'attendance_records' => $records,
  'analytics' => [
    'absences_count' => $absentDays,
    'present_days' => $presentDays,
    'payslips_count' => $payslipsCount,
    'dashboard_stats' => [
      'present_days' => $presentDays,
      'absent_days' => $absentDays,
      'approved_leaves' => $approvedLeaves,
      'payslips_issued' => $payslipsCount,
      'attendance_percentage' => round($attendancePercentage, 4),
      'avg_hours_worked' => round($avgHoursWorked, 2),
      'total_overtime' => round($sumOvertime, 2),
      'total_late_hours' => round($sumLate, 2)
    ]
  ]
]);
?>