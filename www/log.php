<?php
// 1) Allow any site (MangaPark) to talk to us
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// 2) Read the JSON payload
$data = json_decode(file_get_contents('php://input'), true);

// 3) Build a log line
$time    = date('c');
$user    = $data['userId']  ?? 'anon';
$manga   = $data['manga']   ?? '';
$chapter = $data['chapter'] ?? '';
$line    = implode("\t", [$time, $user, $manga, $chapter]) . "\n";

// 4) Append it to reads.log in the same folder
file_put_contents(__DIR__.'/reads.log', $line, FILE_APPEND|LOCK_EX);

// 5) Respond OK
echo json_encode(['status'=>'ok']);
