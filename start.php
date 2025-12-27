<?php
header("Content-Type: application/json");
require "db.php";

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input["token"])) {
    echo json_encode(["error"=>"token required"]);
    exit;
}

// validate token
$res = $mysqli->query("SELECT * FROM users WHERE token='".$input['token']."'");
$user = $res->fetch_assoc();
if (!$user) {
    echo json_encode(["error"=>"invalid token"]);
    exit;
}

// create deck
$suits = ["S","H","D","C"];
$deck = [];
foreach ($suits as $suit) {
    for ($i=1; $i<=13; $i++) {
        $deck[] = ["id"=>$suit.$i,"value"=>$i];
    }
}
shuffle($deck);

// deal hands
$hands = [
    1 => array_splice($deck,0,4),
    2 => []
];

// initial table empty
$table = [];

$state = [
    "deck" => $deck,
    "hands" => $hands,
    "table" => $table,
    "players" => [$user['name']],
    "turn" => 1
];

$status = "waiting";
$current_player = 1;

$stmt = $mysqli->prepare("INSERT INTO games (state,current_player,status) VALUES (?,?,?)");
$json_state = json_encode($state);
$stmt->bind_param("sis", $json_state, $current_player, $status);
$stmt->execute();

echo json_encode([
    "game_id" => $stmt->insert_id,
    "status" => "game created",
    "state" => $state,
    "player_no" => 1
]);
