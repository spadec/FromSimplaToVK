<?php
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('max_execution_time', 0);
ini_set('max_input_vars', 3000);
ini_set('memory_limit', '100M');
set_time_limit(0);
date_default_timezone_set("Asia/Omsk");
$config = array(
	"scope" => "market,photos,offline", //параметры доступа
	"api_id" => "6664159", #id приложения
	"secret_key" => "SQBSIzM3ELhzWlG8O07A", #секретный ключь приложения
	"owner_id" => "-120204230",//id группы ВК со знаком "-"
	"group_id" => "120204230", //id группы ВК
	"category_id" => "905", //категория ВК "Подарочные наборы и сертификаты"
	"redirect_uri" => "http://apple.my/vksync/index.php", //путь к скрипту
	"no_photo" => "design/noimage.jpg",
	"v" => "5.80", // Версия ВК API 
	"api" => "https://api.vk.com/method/", //URL на который отправляется http запрос
	"noImage"=>"C:/OSPanel/domains/apple.my/files/400x400.jpg",//путь от корня сервера до файла заглушки
	"defaultImg"=>"400x400.jpg"
);