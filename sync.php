<?php
require_once("config.php");

class vksync {
   protected $access_token;
   protected $db;
   protected $host = "https://api.vk.com/method/";
   protected $config;
   protected $log;
    public function __construct($token, $config, $db, $log){
        $this->access_token = $token;
        $this->db = $db;
        $this->config = $config;
        $this->log = $log;
    }
    /**
     * Удаляет товар из ВК
     */
    public function deleteItem($itemId){
        $method = "market.delete";
        $params = array(
            "owner_id"=>$this->config["owner_id"],
            "item_id"=>$itemId
        );
        return $this->exec($method, $params);
    }
    /**
     * Получает товары из ВК
     */
    public function getItems($offset = 0, $count=100, $ext = 0){
        $method = "market.get";
        $params = array(
                "owner_id"=>$this->config["owner_id"],
                "offset" => $offset,
                "count" => $count,
                "extended"=>$ext
        );
        return $this->exec($method, $params);
    }
    /**
     * Очищает полностью все товары из ВК
     * очищает vkID из БД
     */
    public function clearAll(){
        $items = $this->getItems(0,1);
        $ItemsCount = $items->count;
        $iterations = $this->getIteration($ItemsCount);
        if($ItemsCount<=100){
            $fullItems = $this->getItems();
            for($i=0;$i<count($fullItems->items);$i++){
                $this->deleteItem($fullItems->items[$i]->id);
                //$this->db->setVKNull($fullItems->items[$i]->id);
            }
        }
        if($ItemsCount>100){
            $offset = 0;
            for($i=0;$i<$iterations;$i++){
                $Initems = $this->getItems($offset);
                if (($i % 3) == 0){
                    sleep(1);
                }
                for($j=0;$j<count($Initems->items);$j++){
                        $delete = $this->deleteItem($Initems->items[$j]->id);
                }
                $offset = $offset + 100;
            }
        }
    }
    /**
     * Синхронизация товаров на сайте и в ВК
     */
    public function syncVK(){
        
        $cat = $this->db->getCategories();
        $countCat = count($cat);
        for($i=0;$i<$countCat;$i++){
            if(isset($cat[$i]->id)){
                $items = $this->db->getItemsFromCategory($cat[$i]->id);    
                $ItemsCount = count($items);
                //print_r($items);
                if($ItemsCount>1000){
                    $this->log->putToLog("В подборке ".$cat[$i]->name." больше 1000 товаров, попадет только первые 1000\n");
                }
                if($ItemsCount){//в категории $i есть товары
                    //проверяем категорию $i есть ли она уже во вконтакте
                    $iscatVK = $this->db->isCatVkID($cat[$i]->vk_cat_id);
                    if($iscatVK){ //если она есть обновляем ее
                       $this->insertCat($cat[$i]->name, $isupdate = $cat[$i]->vk_cat_id);
                       $marketAlbumID = $cat[$i]->vk_cat_id;
                       //запишем в лог что обновили
                       $this->log->putCategory($cat[$i]->name, $marketAlbumID, $isupdate=1);
                    }
                    else {//категории $i нету в ВК, создадим ее
                       //является ли эта подборка потомком?
                       //если да, то находим ее родителя и смотрим есть ли он уже в ВК
                        
                       //если нет, просто создаем эту подборку в ВК 
                       $album = $this->insertCat($cat[$i]->name);
                       $marketAlbumID = $album->market_album_id;
                        //запишем в лог что создали
                        $this->log->putCategory($cat[$i]->name, $marketAlbumID);
                    }
                     if($marketAlbumID){ //есть ID подборки в которую будем добавлять товар
/*                         $this->db->updateCat($marketAlbumID, $cat[$i]->id); //обновляем ID в БД 
                        $addItems = 0;//кол-во добавленных товаров категории $i
                        $updateItems = 0;//кол-во обновленных товаров категории $i
                        $errors = 0;
                        for($j= 0; $j<$ItemsCount; $j++){
                            // проверим есть ли этот товар в ВК
                            if(!empty($items[$j]->vk_id)){
                               $this->addItem($items[$j], $isupdate = 1);
                               $updateItems++;
                            }
                            else {//такого товара нет 
                                $item = $this->addItem($items[$j]);
                                if(isset($item->market_item_id)){
                                    //записываем 
                                    $addItems++;
                                    $this->db->updateItem($item->market_item_id,$items[$j]->product_id);
                                    $this->addToAlbum($marketAlbumID, $item->market_item_id);
                                } 
                                if(isset($item->error_code)){
                                    $this->log->putToLog("Ошибка! :".$item->error_msg."\n");
                                    $this->log->putToLog("Товар :".$items[$j]->id."\n");
                                    $errors++;
                                }
                            }
                        }
                        //положим в лог кол-во добавленных/обновленных товаров
                        $this->log->putItem($ItemsCount, $addItems);
                        $this->log->putItem($ItemsCount, $updateItems, $isupdate = 1);
                        $this->log->putToLog("Ошибок: ".$errors."\n"); */
                    } 
                } 
            }
        }
        //проверим и удалим не актульные товары в ВК
        $this->syncFromVk();
        //удалим пустые подборки если таковые имеются
        $this->delEmptyAlbum();
    }
    /**
     * Добавляет категорию из БД в ВК
     */
    public function insertCat($name, $isupdate = null){
        if($isupdate){
            $method = "market.editAlbum";
            $params = array(
                "owner_id"=>$this->config["owner_id"],
                "title" => $name,
                "album_id" => $isupdate,
                "main_album"=>"0"
            );
        }
        else {
            $method = "market.addAlbum";
            $params = array(
                "owner_id"=>$this->config["owner_id"],
                "title" => $name,
                "main_album"=>"0"
            );
        }

        return $this->exec($method, $params);
    }
    /**
     * Формирует полное описание товара включая дополнительные поля
     * @param объект stdClass товара из БД сайта
     * @return text полное описание товара 
     */
    public function getItemDesc($item){
        $options = $this->db->getFeatures($item->product_id);
        $withouttags = strip_tags($item->body);//убираем теги из описаний
        if($withouttags){
            $description = html_entity_decode($withouttags)."%0A";
        }
        else {
            $description = "";
        }
        if($options){
            for($i=0;$i<count($options);$i++){//добавляем в описание доп. опции
                $featured = $options[$i]->name.": ".$options[$i]->value."%0A";
                if(!empty($options[$i]->name)){
                    $description .= $featured;
                }
            }
        }
        $descLenght = strlen($description);
        if($descLenght < 10){
            $description = "Добавте описание товара";
        }
        return $description;
    }
    /**
     * Добавляет товар или обновляет из БД в товары ВК
     * @param объект stdClass товара из БД сайта
     * @param флаг какой метод API вызвать(обновить/добавить)
     *  по умолчанию метод add
     * @return идентификатор товара уже в ВК в случаи добавления
     */
    public function addItem($item, $isupdate = null){
        $images = $this->addImages($item->product_id);
        $main_photo_id = $images["main"];
        $photo_ids = $images["additional"];
        $description = $this->getItemDesc($item);
        $nameLenght = strlen($item->name);
        if($nameLenght >= 100) {
            //$name = mb_strimwidth($item->name, 0, 96, "..>");
            $name = substr($item->name,0,99);
        }
        else {
            $name = $item->name;
        }
        if($item->price == "00.0"){
            $price = 1;
        }
        else {
            $price = $item->price;
        }
        if($isupdate){
            $method = "market.edit";
            $params = array(
                "owner_id"=>$this->config["owner_id"],
                "name" => $name,
                "item_id" => $item->vk_id,
                "description" => $description,
                "category_id"=> $this->config["category_id"],
                "price"=> $price,
                "main_photo_id"=>$main_photo_id,
                "photo_ids"=>$photo_ids
            );
        }
        else {
            $method =  "market.add";
            $params = array(
                "owner_id"=>$this->config["owner_id"],
                "name" => $name,
                "description" => $description,
                "category_id"=> $this->config["category_id"],
                "price"=> $price,
                "main_photo_id"=>$main_photo_id,
                "photo_ids"=>$photo_ids
            );
        }
       return $this->exec($method, $params);
    } 
    /**
     * Добавляет товар в подборку ВК
     */
    public function addToAlbum($albumId, $ItemId){
        $method =  "market.addToAlbum";
        $params = array(
            "owner_id"=>$this->config["owner_id"],
            "item_id"=>$ItemId,
            "album_ids"=>$albumId
        );
        return $this->exec($method, $params);
    }
    /**
     * Загружает изображение на сервер ВК
     * @return string возвращает ID загруженного в ВК изображения 
     */
    public function uploadImage($dir, $imageName, $serverUrl, $ismain){
        $img_src_main = $dir."".$imageName;
        $upimage = $this->makeCurlFile($img_src_main);
        $post_params = array(
            'file' => $upimage,
            'v'=>$this->config["v"]
        );
        $upload = $this->setPostRequest($serverUrl->upload_url, $post_params);
        if(isset($upload->error)){//если в результате загрузки произошла ошибка
            $img_src_main = $this->config["noImage"];
            $upimage = $this->makeCurlFile($img_src_main);
            $post_params = array(
                'file' => $upimage,
                'v'=>$this->config["v"]
            );
            $upload = $this->setPostRequest($serverUrl->upload_url, $post_params);
        }
        $saveMainPhoto = $this->saveMarketPhoto($upload, $ismain);
        return $saveMainPhoto[0]->id; 
    }
    /**
     * Добавляет изображения на сервер в ВК, возвращает массив с айдишками добавленных
     * изображений
     * @return array
     * @param ID товара в БД 
     */
    public function addImages($ItemId=2333){
        $images = $this->db->getImages($ItemId);
        $dir = $this->db->getImageDir();
        $main_photo = $this->db->getMainPhoto($images,$this->config["defaultImg"]);
        $additional_photo = $this->db->getAdditionalPhoto($images);
        $serverUrl = $this->getMarketUploadServer(1);//URL для главного 
        $mainPhotoId = $this->uploadImage($dir, $main_photo, $serverUrl, $ismain=1);
        $additionalPhotoIds = array();
         if($additional_photo){
            $serverUrl = $this->getMarketUploadServer(); // URL для доп. изображений
            for($i=0;$i<count($additional_photo);$i++){
                if(count($additional_photo)>3){
                    sleep(1);
                }
                $uplAdd = $this->uploadImage($dir, $additional_photo[$i], $serverUrl, $ismain=0);
                array_push($additionalPhotoIds, $uplAdd);
            }
            $additionalPhotoIds = implode(",", $additionalPhotoIds);
        }
       return $result = array("main"=>$mainPhotoId,"additional"=>$additionalPhotoIds); 
    }
    /**
     * подготавливает файл в формат multipart/form-data
     */
    public function makeCurlFile($file){
        $mime = mime_content_type($file);
        if($mime){
            $info = pathinfo($file);
            $name = $info['basename'];
            $output = new CURLFile($file, $mime, $name);
            return $output;
        }
        else {
            $file = $this->config["noImage"];
            return $this->makeCurlFile($file);
        }
    }
    /**
     * Возвращает адрес сервера для загрузки фотографий
     * @param bool 1 если загружаем главное фото для товара
     */
    public function getMarketUploadServer($main_photo=0){
        $method = "photos.getMarketUploadServer";
        $params = array(
            "group_id"=>$this->config["group_id"],
            "main_photo"=>$main_photo
        );
        return $this->exec($method, $params);
    }
    /**
     * Возвращает PHP stdClass от сервиса ВК
     * Принимает имя сервиса ВК и его параметры
     * Если ошибка то вернет false
     * @param string, array
     * @return stdClass
     */
    public function exec($method, $params){
		$url = http_build_query($params);
        $url = rawurldecode($url);
        $request = $this->host.$method."?".$url."&access_token=".$this->access_token."&v=".$this->config["v"];
        $json = file_get_contents($request);
        $obj = json_decode($json);
        if(isset($obj->response)){
            return $obj->response;
        }
        elseif(isset($obj->error)){
            return $obj->error;
        }
        else{
            return false;
        }
    }

    /**
     * Отправляет пост запрос на URI 
     */
    public function setPostRequest($link, $post_params){
        $ch = curl_init($link);
        curl_setopt( $ch, CURLOPT_POST, 1);
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $post_params);
        $response = curl_exec( $ch );
        curl_close( $ch );
        return $response=json_decode($response);
    }
    /**
     * Сохраняет отправленные фото товара
     */
    public function saveMarketPhoto($response, $ismain = null){
        $method = "photos.saveMarketPhoto";
        if($ismain){
            $params = array(
                "group_id"=>$this->config["group_id"],
                "photo"=>$response->photo,
                "server"=>$response->server,
                "hash"=>$response->hash,
                "crop_data"=>$response->crop_data,
                "crop_hash"=>$response->crop_hash
            );
        }
        else{
            $params = array(
                "group_id"=>$this->config["group_id"],
                "photo"=>$response->photo,
                "server"=>$response->server,
                "hash"=>$response->hash
            );
        }
        return $this->exec($method, $params);
    }
    /**
     * Добавляет товар к подборке ВК
     * @param $ItemId - Id товара из ВК и ID ВК подборки 
     */
    public function toVkAlbum($ItemId, $CatId){
        $method = "";
        $params = array(
            "owner_id"=>$this->config["owner_id"],
            "item_id"=>$ItemId,
            "album_ids"=>$CatId
        );
        return $this->exec($method, $params);//response:1
    }
    /**
     * Возвращает подборки из ВК
     */
    public function getVkAlbums($count = 100){
        $method = "market.getAlbums";
        $params = array(
            "owner_id"=>$this->config["owner_id"],
            "count"=>$count
        );
        return $this->exec($method, $params);
    }
    /**
     * Возвращает подборку ВК по ее ID
     */
    public function getVkAlbumsById($albumId){
        $method = "market.getAlbumById";
        $params = array(
            "owner_id"=>$this->config["owner_id"],
            "album_ids"=>$albumId
        );
        return $this->exec($method, $params);
    }
    /**
     * Удаляет подборку из ВК
     */
    public function delVkAlbum($albumId){
        $method = "market.deleteAlbum";
        $params = array(
            "owner_id"=>$this->config["owner_id"],
            "album_id"=>$albumId,
        );
        return $this->exec($method, $params);
    }
    /**
     * Удаляет подборки из ВК в которых нет товаров
     */
    public function delEmptyAlbum(){
        $albums = $this->getVkAlbums();
        $j=0;
        for($i=0;$i<count($albums->items);$i++){
            if($albums->items[$i]->count==0){
                $this->delVkAlbum($albums->items[$i]->id);
                $j++;
                if (($j % 3) == 0){
                    sleep(1);
                }
            }
        }
        $this->log->putToLog("Удалено пустых альбомов: ".$j);
    }
    /**
     * Возвращает количество итераций при получении больше 100 товаров
     */
    public function getIteration($count){
        $integer = intval($count/100);
        $ostatok = ($count/100)-intval($count/100);
        $ostatok = $ostatok * 100;
        if($ostatok>0){
            $integer++;
        }
        return $integer;
    }
    /**
     * Удаляет те товары которые сняты с публикации или удалены с сайта
     */
    public function syncFromVk(){
        $del = 0;
        $items = $this->getItems(0,1);
        $ItemsCount = $items->count;
        $DB = $this->db->getAllProduct();
        $iterations = $this->getIteration($ItemsCount);
        if($ItemsCount<=100){
            $fullItems = $this->getItems();
            for($i=0;$i<count($fullItems->items);$i++){
                if ($this->in_array_field($fullItems->items[$i]->id,'vk_id',$DB)) {
                }
                else {
                    $delete = deleteItem($fullItems->items[$i]->id);
                    $del++;
                }
            }
        }
        if($ItemsCount>100){
            $offset = 0;
            for($i=0;$i<$iterations;$i++){
                $Initems = $this->getItems($offset);
                if (($i % 3) == 0){
                    sleep(1);
                }
                for($j=0;$j<count($Initems->items);$j++){
                    if ($this->in_array_field($Initems->items[$j]->id,'vk_id',$DB)) {
                        //echo $Initems->items[$j]->id."<br />";
                    }
                    else {
                        $delete = $this->deleteItem($Initems->items[$j]->id);
                        $del++;
                        echo $Initems->items[$j]->id."<br />";
                    }
                }
                $offset = $offset + 100;
            }
        }
        $this->log->putDelItem($del);  
    }
    /**
     * Lea Hayes
     * Determine whether an object field matches needle. 
     */
    public function in_array_field($needle, $needle_field, $haystack, $strict = false) { 
        if ($strict) { 
            foreach ($haystack as $item) 
                if (isset($item->$needle_field) && $item->$needle_field === $needle) 
                    return true; 
        } 
        else { 
            foreach ($haystack as $item) 
                if (isset($item->$needle_field) && $item->$needle_field == $needle) 
                    return true; 
        } 
        return false; 
    } 
}