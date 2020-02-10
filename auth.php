<?php
//require_once("config.php");
class vkauth {
	public function __construct(){

	}
	/**
	 * Получаем code для получения access_token для авторизации по типу Authorization Code Flow
	 */
	public function getCode($config){
		$host = "https://oauth.vk.com/authorize?";
		$params = array(
					"client_id" => $config["api_id"],
					"display" => "page",
					"redirect_uri"=>$config["redirect_uri"],
					//"group_ids"=>$config["group_id"],
					"scope"=>$config["scope"],
					"response_type"=>"code",
					"v" => $config["v"],
					);
		$url = http_build_query($params);
		$url = rawurldecode($url);
		echo "<a href=".$host.$url.">Авторизоватся в ВК и начать синхронизацию</a>";
	}
	/**
	 * Получаем access_token используя Authorization Code Flow 
	 */
	public function getToken($config,$code){
		$host = "https://oauth.vk.com/access_token?";
		$params = array(
					"client_id"=> $config["api_id"],
					"client_secret"=>$config["secret_key"],
					"redirect_uri"=>$config["redirect_uri"],
					"code"=>$code,
					);
		$url = http_build_query($params);
		$url = rawurldecode($url);
		$json = file_get_contents($host.$url);
		$obj = json_decode($json);
		if($obj->access_token){
			return $access_token = $obj->access_token;
		}
		else{
			return false;
		}
	}
}