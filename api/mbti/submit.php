<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false], JSON_UNESCAPED_UNICODE); exit(); }
$input = file_get_contents('php://input');
$payload = json_decode($input, true);
$answers = (isset($payload['answers']) && is_array($payload['answers'])) ? $payload['answers'] : [];
$file = __DIR__ . '/../../data/mbti_questions.json';
$raw = @file_get_contents($file);
$questions = @json_decode($raw, true);
if (!is_array($questions)) { $questions = []; }
$map = [];
foreach ($questions as $q) { if (isset($q['id']) && isset($q['dimension'])) { $map[(string)$q['id']] = $q['dimension']; } }
$expectedCount = count($questions);
if ($expectedCount > 0) {
  $missing = [];
  foreach ($map as $qid => $dim) {
    if (!array_key_exists($qid, $answers)) { $missing[] = $qid; }
    else {
      $v = (int)$answers[$qid];
      if ($v > 2 || $v < -2) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'答案范围非法'], JSON_UNESCAPED_UNICODE); exit(); }
    }
  }
  if (count($missing) > 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'题目未全部完成','missing'=>$missing], JSON_UNESCAPED_UNICODE); exit(); }
}
$scores = ['EI'=>0,'SN'=>0,'TF'=>0,'JP'=>0];
foreach ($answers as $qid=>$val) {
  $k = (string)$qid;
  if (!isset($map[$k])) { continue; }
  $v = (int)$val;
  if ($v > 2) $v = 2; if ($v < -2) $v = -2;
  $dim = $map[$k];
  if (isset($scores[$dim])) { $scores[$dim] += $v; }
}
function pick($dim,$s){
  if ($dim==='EI') return $s>0?'E':($s<0?'I':'E');
  if ($dim==='SN') return $s>0?'S':($s<0?'N':'S');
  if ($dim==='TF') return $s>0?'T':($s<0?'F':'T');
  if ($dim==='JP') return $s>0?'J':($s<0?'P':'J');
  return '';
}
$type = pick('EI',$scores['EI']).pick('SN',$scores['SN']).pick('TF',$scores['TF']).pick('JP',$scores['JP']);
echo json_encode(['type'=>$type,'score'=>$scores], JSON_UNESCAPED_UNICODE);