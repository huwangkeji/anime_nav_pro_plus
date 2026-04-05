<?php
$data=json_decode(file_get_contents('data.json'),true);
$cat=$_GET['cat'];
$id=$_GET['id'];

$data[$cat][$id]['click']++;

file_put_contents('data.json',json_encode($data,JSON_UNESCAPED_UNICODE));
header("Location:".$data[$cat][$id]['url']);
?>