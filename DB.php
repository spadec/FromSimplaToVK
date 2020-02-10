<?php
include('../api/Simpla.php');
class simplaDB extends Simpla{

    /**
     * Проверяет созданы ли столбцы в ВК_ID в БД
     */
    public function checkDB(){
        $query = "show columns FROM `__products` WHERE `field` = 'vk_id'";
        $this->db->query($query);
        return $columns = $this->db->results();
    }
    /**
     * Вставляет столбцы с ВК_ID в БД
     */
    public function insertFields(){
        $exists_cat_id = $this->db->query("ALTER TABLE `__categories` ADD `vk_cat_id` INT(11) NULL");
        $exists_vk_id = $this->db->query("ALTER TABLE `__products` ADD `vk_id` INT(11) NULL");
        if($exists_vk_id==true)
        {
            echo "Столбец vk_id добавлен в таблицу `__products` <br />";
        }
        if($exists_cat_id==true)
        {
            echo "Столбец vk_cat_id добавлен в таблицу `__categories`";
        }
    }
    /**
     * возвращает массив категорий
     */
    public function getCategories(){
        $query = "SELECT `id`, `name`, `visible`, `vk_cat_id` FROM __categories WHERE `visible`=1";
        $this->db->query($query);
        return $categories = $this->db->results();
    }
    /**
     * Возвращает описание категории
     */
    public function getCategory($catID){
        $query = "SELECT * FROM __categories
        WHERE `id` = ?";
        $this->db->query($query, $catID);
        return $category = $this->db->result();
    }
    /**
     * Возвращает только родительские категории
     */
    public function getParentCategories(){
        $query = "SELECT * FROM __categories WHERE parent_id=0";
        $this->db->query($query);
        return $categories = $this->db->results();
    }
    /**
     * возвращает "дочерние категории"
     */
    public function getChildCategories($catId){
        $query = "SELECT * FROM __categories WHERE parent_id = ?";
        $this->db->query($query, $catId);
        return $categories = $this->db->results();
    }
    /**
     * Проверяет является ли категория потомком, если да возвращает id родителя
     */
    public function isCatChild($catId){
        $query = "SELECT * FROM __categories WHERE parent_id > 0 AND id=?";
        $this->db->query($query, $catId);
         $result = $this->db->result();
         if($result){
             return $return->parent_id;
         }
        return false;
    }
    /**
     * Возвращает массив продуктов категории
     */
    public function getItemsFromCategory($catId){
        $query = "SELECT `__products_categories`.`product_id`,
		`__products_categories`.`category_id` AS catID,
        `__products`.`id`,
        `__products`.`name`,
        `__products`.`vk_id`,
        `__products`.`body`,
        `__variants`.`price`
            FROM `__products_categories` 
            LEFT JOIN `__products` 
            ON `__products_categories`.`product_id` = `__products`.`id` 
            LEFT JOIN `__variants` 
            ON `__products_categories`.`product_id`= `__variants`.`product_id`
            WHERE `__products_categories`.`category_id` = ?
            AND `__products`.`visible` = 1
            AND `__variants`.`stock` > 0";
        $this->db->query($query, $catId);
        return $items = $this->db->results();
    }
    /**
     * Возвращает товар по ВК ID
     */
    public function getItemsOnVK($vkId){
        $query = "SELECT 
        `__products`.`name`,
        `__variants`.`price`,
        `__variants`.`stock`
        FROM
        `__products`
        LEFT JOIN
        `__variants` ON `__products`.`id` = `__variants`.`product_id`
        WHERE `__products`.`vk_id` = ?";
        $this->db->query($query, $vkId);
        return $items = $this->db->result();
    }
    /**
     * Возвращает список доп. полей для товара
     */
    public function getFeatures($itemId){
        $query = "SELECT
        `__options`.*,
        `__features`.name
        FROM
        `__options`
        LEFT JOIN
        `__features` ON `__options`.`feature_id` = `__features`.`id`
        WHERE
        `__options`.product_id = ?";
        $this->db->query($query, $itemId);
        return $features = $this->db->results();
    }
    /**
     * Обновляет запись в БД (назначает определенному товару в БД сайта, соотв. ID из ВК)
     */
    public function updateItem($VKitemId, $siteItemId){
        $query = "UPDATE `__products` SET vk_id = ? WHERE id=?";
        return  $this->db->query($query, $VKitemId, $siteItemId);
    }
    /**
     * Обновляет запись в БД (назначает определенной категории в БД сайта, соотв. ID из ВК)
     */
    public function updateCat($VKcatId, $siteCatId){
        $query = "UPDATE `__categories` SET vk_cat_id =? WHERE id= ?";
        return  $this->db->query($query, $VKcatId, $siteCatId);
    }
    /**
     * Возвращает массив с изображениями товаров
     */
    public function getImages($itemId){
        $query = "SELECT * FROM `__images` WHERE `product_id` = ?";
        $this->db->query($query, $itemId);
        return $images = $this->db->results();
    }
    /**
     * Возвращает имя главного изображения
     * @param array
     * @return string  
     */
    public function getMainPhoto($images, $default = null){
        if($images){
            $str = null;
            for($i=0; $i<count($images); $i++){
                if($images[$i]->position == 0){
                  return $str = $images[$i]->filename;
                }
            }
            return $images[0]->filename;
        }
        return $default;
    }
    /**
     * Возвращает массив с именами дополнительных фото 
     * @param array
     * @return array  
     */
    public function getAdditionalPhoto($images){
        $arr = array();
        $count = count($images);
        for($i=0; $i<count($images); $i++){
            if($images[$i]->position>0 && $count>1){
                if($i<=3){//ВК поддерживает только до 4-х доп. изображений
                    array_push($arr, $images[$i]->filename);
                }
            }
        }
        if(count($arr) >= 4){
            $newarr = array_shift($arr);
            return $newarr;
        }
        elseif(count($arr) > 0){
            return $arr;
        }
        else {
            return false;
        }
    }
    /**
     * Возвращает путь до директории с оригиналами изображений
     */
    public function getImageDir($root=0){
        if(!$root){
            $root = $_SERVER['DOCUMENT_ROOT'];
        }
        $original = $this->config->original_images_dir;
        return $root."/".$original;
    }

    /**
     * Возвращает true если возвращается не пустой результат
     */
    public function isCatVkID($vkID){
        if($vkID){
            $query = "SELECT `id` FROM `__categories`
            WHERE
            `vk_cat_id` = ?";
            $this->db->query($query, $vkID);
            $cat = $this->db->result();
            if($cat){ return $cat;  }
        }
        else {
            return false;
        }
    }
    /**
     * Возвращает true если возвращается не пустой результат
     */
    public function isItemVkID($vkID){
        $query = "SELECT `id` FROM `__products`
        WHERE
        `vk_id` = ?";
        $this->db->query($query, $vkID);
        $item = $this->db->result();
        if($item){ return $item; } else { return false; }
    }
    public function setVKNullAll(){
        $query = "UPDATE `__products` SET vk_id=NULL";
        return $this->db->query($query);
    }
    public function setVKNull($vkId){
        $query = "UPDATE `__products` SET vk_id=NULL WHERE vk_id=?";
        return $this->db->query($query, $vkId);
    }
    /**
     * ВСе товары из БД которые есть в ВК
     */
    public function getAllProduct(){
        $query = "SELECT `__products`.`id`, `__products`.`vk_id`, `__products`.`name` 
        FROM `__products` 
        LEFT JOIN __variants 
        ON `__products`.`id`=`__variants`.`product_id` 
        WHERE `__products`.`visible` = 1 
        AND `__variants`.`stock`>0
        AND `__products`.`vk_id` IS NOT NULL";
        $this->db->query($query);
        $result = $this->db->results();
        return $result;
    }
}