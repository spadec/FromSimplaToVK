<?php
$count = 801;
$integer = intval($count/100);
$ostatok = ($count/100)-intval($count/100);
$ostatok = $ostatok * 100;
if($ostatok>0){
    $integer++;
}

echo $integer;