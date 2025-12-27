<?php
header("Content-Type: application/json");
require "db.php";

$input = json_decode(file_get_contents("php://input"), true);
if (!isset($input["game_id"]) || !isset($input["token"])) {
    echo json_encode(["error"=>"game_id and token required"]);
    exit;
}

// validate token
$res = $mysqli->query("SELECT * FROM users WHERE token='".$input['token']."'");
$user = $res->fetch_assoc();
if (!$user) {
    echo json_encode(["error"=>"invalid token"]);
    exit;
}

$res_game = $mysqli->query("SELECT * FROM games WHERE id=".$input["game_id"]);
$game = $res_game->fetch_assoc();
$state = json_decode($game['state'], true);

// add second player
$state["players"][1] = $user['name'];

// give initial hand if empty
if (empty($state["hands"][2])) {
    $state["hands"][2] = array_splice($state["deck"],0,4);
}

$new_state = json_encode($state);
$mysqli->query("UPDATE games SET state='$new_state', status='playing' WHERE id=".$input["game_id"]);

echo json_encode([
    "status" => "player joined",
    "player_no" => 2,
    "state" => $state
]);
