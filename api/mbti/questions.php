<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); echo json_encode(['success'=>false], JSON_UNESCAPED_UNICODE); exit(); }
$file = __DIR__ . '/../../data/mbti_questions.json';
if (!file_exists($file)) { echo json_encode([], JSON_UNESCAPED_UNICODE); exit(); }
$raw = @file_get_contents($file);
$data = @json_decode($raw, true);
if (!is_array($data)) { echo json_encode([], JSON_UNESCAPED_UNICODE); exit(); }
echo json_encode($data, JSON_UNESCAPED_UNICODE);