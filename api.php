<?php

$stateFile = 'display_state.json';
$usersFile = 'active_users.json';


if (!file_exists($stateFile)) {
    $initialState = [
        "segments" => ["a"=>"#2a2a2a", "b"=>"#2a2a2a", "c"=>"#2a2a2a", "d"=>"#2a2a2a", "e"=>"#2a2a2a", "f"=>"#2a2a2a", "g"=>"#2a2a2a"],
        "last_update" => time()
    ];
    file_put_contents($stateFile, json_encode($initialState));
}


$users = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : [];
$currentIp = $_SERVER['REMOTE_ADDR'];
$now = time();


foreach ($users as $ip => $time) {
    if ($now - $time > 10) unset($users[$ip]);
}


if (count($users) < 3 || isset($users[$currentIp])) {
    $users[$currentIp] = $now;
}
file_put_contents($usersFile, json_encode($users));


$input = json_decode(file_get_contents('php://input'), true);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST' && isset($input['type'])) {
    $state = json_decode(file_get_contents($stateFile), true);


    if (count($users) >= 3 && !isset($users[$currentIp])) {
        echo json_encode(["error" => "max_capacity"]);
        exit;
    }

    if ($input['type'] === 'update') {
        // Guardar color en formato HEX o RGB
        $seg = $input['seg'];
        $hex = sprintf("#%02x%02x%02x", $input['r'], $input['g'], $input['b']);
        $state['segments'][$seg] = $hex;
    } 
    elseif ($input['type'] === 'reset') {
        foreach ($state['segments'] as $key => $val) {
            $state['segments'][$key] = "#2a2a2a";
        }
    }

    $state['last_update'] = $now;
    file_put_contents($stateFile, json_encode($state));
    echo json_encode(["status" => "ok", "users" => count($users)]);
    exit;
}


$state = json_decode(file_get_contents($stateFile), true);
$state['user_count'] = count($users);
header('Content-Type: application/json');
echo json_encode($state);