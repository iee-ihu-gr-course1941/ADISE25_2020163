<?php
header("Content-Type: application/json");
require "db.php";

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input["name"])) {
    echo json_encode(["error"=>"name required"]);
    exit;
}

$token = bin2hex(random_bytes(16));

$stmt = $mysqli->prepare("INSERT INTO users (name, token) VALUES (?, ?)");
$stmt->bind_param("ss", $input["name"], $token);
$stmt->execute();

echo json_encode([
    "name" => $input["name"],
    "token" => $token
]);
