<?php 
require_once("config.php");
require_once("auth.php");

$auth = new vkauth();
if(isset($_GET["code"])){
    $code = $_GET["code"];
    $access_token = $auth->getToken($config,$code);
    echo $access_token;
}
else {
    $auth -> getCode($config);
}
    