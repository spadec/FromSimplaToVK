<?php
/**
 * пишет в лог все действия скрипта
 */
class VKLogger {
protected $path = "logs/";
protected $file;
	/**
	 * Конструктор 
	 */
	public function __construct($file){
		$this->file = $file;
		$startMessage = "**********************************************\n";
		$startMessage .= "Скрипт запущен в: ".date('D M d H:i:s Y',time())."\n";
		file_put_contents($this->path.$this->file, $startMessage,FILE_APPEND | LOCK_EX);

	}
	/**
	 * записывает сообщение в лог
	 */
	public function putToLog($message = null)
	{
		file_put_contents($this->path.$this->file, $message, FILE_APPEND | LOCK_EX);
	}
	/**
	 * Сообщение успешной загрузки категории с сайта в ВК
	 */
	public function putCategory($catName, $albumVk, $isupdate= null){
		if($isupdate){
			$mes = "Категория: ".$catName."\n";
			$mes .= "Успешно обновлена в ВК с ID: ".$albumVk."\n";
		}
		else{
			$mes = "Категория: ".$catName."\n";
			$mes .= "Успешно добавлена в ВК с ID: ".$albumVk."\n";
		}
		$this->putToLog($mes);
	}
	/**
	 * Сообщение успешной загрузки товара с сайта в ВК
	 */
	public function putItem($itemCount = 0, $succesItems = 0, $isupdate = null){
		$mes = "в этой категории товаров: ".$itemCount."\n";
		if($isupdate){
			$mes .= "Успешно обновлено: ".$succesItems."\n";
		}
		else {
			$mes .= "Успешно добалено: ".$succesItems."\n";
		}
		$this->putToLog($mes);
	}
	public function putDelItem($deleteCount){
		$mes = "Удалено товаров: ".$deleteCount."\n";
		$this->putToLog($mes);
	}
}