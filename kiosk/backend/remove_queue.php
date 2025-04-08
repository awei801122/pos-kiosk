<?php
header("Content-Type: application/json");

$number = $_POST["number"] ?? "";

if ($number === "") {
  echo json_encode(["success" => false, "message" => "缺少號碼"]);
  exit;
}

// 處理 queue.json：移除號碼
$queueFile = "../queue.json";
$queue = file_exists($queueFile) ? json_decode(file_get_contents($queueFile), true) : [];
$queue = array_filter($queue, fn($n) => $n !== $number);
file_put_contents($queueFile, json_encode(array_values($queue), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// 處理 done.json：加入號碼
$doneFile = "../done.json";
$done = file_exists($doneFile) ? json_decode(file_get_contents($doneFile), true) : [];
if (!in_array($number, $done)) {
  $done[] = $number;
}
file_put_contents($doneFile, json_encode($done, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo json_encode(["success" => true]);
