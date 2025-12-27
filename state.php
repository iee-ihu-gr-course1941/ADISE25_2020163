<?php
header("Content-Type: application/json");
require "db.php";

$id = $_GET["game_id"] ?? null;
if (!$id) {
    echo json_encode(["error"=>"game_id required"]);
    exit;
}

$res = $mysqli->query("SELECT * FROM games WHERE id=$id");
$row = $res->fetch_assoc();
if (!$row) {
    echo json_encode(["error"=>"game not found"]);
    exit;
}

$row['state'] = json_decode($row['state'], true);

echo json_encode($row);
