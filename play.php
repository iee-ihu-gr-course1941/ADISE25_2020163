<?php
header("Content-Type: application/json");
require "db.php";

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input["game_id"]) || !isset($input["token"]) || !isset($input["card_id"])) {
    echo json_encode(["error"=>"game_id, token and card_id required"]);
    exit;
}

// validate token
$res_user = $mysqli->query("SELECT * FROM users WHERE token='".$input['token']."'");
$user = $res_user->fetch_assoc();
if (!$user) {
    echo json_encode(["error"=>"invalid token"]);
    exit;
}

// get game
$res_game = $mysqli->query("SELECT * FROM games WHERE id=".$input["game_id"]);
$game = $res_game->fetch_assoc();
if (!$game) {
    echo json_encode(["error"=>"game not found"]);
    exit;
}

$state = json_decode($game['state'], true);

// find player_no
$player_no = null;
foreach($state["players"] as $i => $name) {
    if ($name == $user['name']) {
        $player_no = $i+1; // 0-indexed -> 1 or 2
        break;
    }
}

if (!$player_no) {
    echo json_encode(["error"=>"user not in game"]);
    exit;
}

// check turn
if ($game['current_player'] != $player_no) {
    echo json_encode(["error"=>"not your turn"]);
    exit;
}

// check card in hand
$hand = $state["hands"][$player_no];
$card_index = null;
foreach ($hand as $i => $c) {
    if ($c["id"] == $input["card_id"]) {
        $card_index = $i;
        break;
    }
}

if ($card_index === null) {
    echo json_encode(["error"=>"card not in hand"]);
    exit;
}

// remove card
$card = $hand[$card_index];
array_splice($state["hands"][$player_no], $card_index, 1);

// play card on table
$found = false;
foreach ($state["table"] as $i => $t) {
    if ($t["value"] == $card["value"]) {
        array_splice($state["table"], $i, 1);
        $found = true;
        break;
    }
}
if (!$found) {
    $state["table"][] = $card;
}

// draw new hands if empty and deck has cards
if (empty($state["hands"][1]) && empty($state["hands"][2]) && !empty($state["deck"])) {
    $state["hands"][1] = array_splice($state["deck"],0,4);
    $state["hands"][2] = array_splice($state["deck"],0,4);
}

// switch turn
$next = $player_no == 1 ? 2 : 1;
$state["turn"] = $next;


// check deadlock / game over
$hands_empty = empty($state["hands"][1]) && empty($state["hands"][2]);
$deck_empty = empty($state["deck"]);
$table_empty = empty($state["table"]);

if ($hands_empty && $deck_empty) {
    $state["game_over"] = true; 
} else {
    $state["game_over"] = false;
}

// update DB
$mysqli->query("UPDATE games SET state='".json_encode($state)."', current_player=$next WHERE id=".$input["game_id"]);

echo json_encode([
    "ok" => true,
    "state" => $state,
    "next_player" => $next
]);
