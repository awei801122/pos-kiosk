<?php
header('Content-Type: application/json');

$input = json_decode(file_get_contents("php://input"), true);
$number = $input["number"] ?? "";

if ($number === "") {
    echo json_encode(["status" => "error", "message" => "缺少號碼"]);
    exit;
}

$doneFile = __DIR__ . "/../done.json";
$done = [];

if (file_exists($doneFile)) {
    $done = json_decode(file_get_contents($doneFile), true);
    if (!is_array($done)) {
        $done = [];
    }
}

if (!in_array($number, $done)) {
    $done[] = $number;
}

try {
    if (file_put_contents($doneFile, json_encode($done, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
        throw new Exception("無法寫入檔案");
    }
    echo json_encode(["status" => "ok"]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
