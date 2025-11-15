<?php
header('Content-Type: application/json; charset=utf-8');
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$answers = is_array($data) && isset($data['answers']) && is_array($data['answers']) ? $data['answers'] : [];
$dims = ['EI'=>0,'SN'=>0,'TF'=>0,'JP'=>0];
for ($i=0; $i<count($answers); $i++) {
    $score = intval($answers[$i]);
    $dim = ['EI','SN','TF','JP'][$i%4];
    $dims[$dim] += $score - 4;
}
$type = '';
$type .= ($dims['EI'] >= 0) ? 'E' : 'I';
$type .= ($dims['SN'] >= 0) ? 'S' : 'N';
$type .= ($dims['TF'] >= 0) ? 'T' : 'F';
$type .= ($dims['JP'] >= 0) ? 'J' : 'P';
echo json_encode(['type'=>$type], JSON_UNESCAPED_UNICODE);