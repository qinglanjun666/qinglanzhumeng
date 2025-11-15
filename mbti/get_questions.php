<?php
header('Content-Type: application/json; charset=utf-8');
$questions = [];
for ($i = 1; $i <= 120; $i++) {
    $questions[] = ["id"=>$i, "text"=>"第 {$i} 题：请根据实际感受进行选择", "dimension"=>["EI","SN","TF","JP"][($i-1)%4]];
}
echo json_encode($questions, JSON_UNESCAPED_UNICODE);