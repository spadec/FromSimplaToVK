<?php
/**
 * Класс для ведения различных логов 
*/
class Logger 
{
    //статические переменные
    public static $PATH;
    protected static $loggers=array();
    protected $name;
    protected $file;
    protected $fp;

	public function __construct($name, $file=null)
	{
	    $this->name=$name;
	    $this->file=$file;
	 
	    $this->open();
	}
 	/**
 	* инициализирует файловый поток если $file не задана, то будет открыт файл с тем же именем, что и логгер
 	*/
	public function open()
	{
	    if(self::$PATH==null){
	        return ;
	    }
	    $this->fp=fopen($this->file==null ? self::$PATH.'/'.$this->name.'.log' : self::$PATH.'/'.$this->file,'a+');
	}
	/**
	* Возвращает логгер по указанному имени
	*/
	public static function getLogger($name='root',$file=null)
	{
	    if(!isset(self::$loggers[$name])){
	        self::$loggers[$name]=new Logger($name, $file);
	    }
	    return self::$loggers[$name];
	}
	/**
	* Записывает сообщение в лог-файл
	*/
	public function log($message)
	{
	    if(!is_string($message)){
	                // если мы хотим вывести, к примеру, массив
	        $this->logPrint($message);
	        return ;
	    }
	    $log='';
	        // зафиксируем дату и время происходящего
	    $log.='['.date('D M d H:i:s Y',time()).'] ';
	    // если мы отправили в функцию больше одного параметра,
	        // выведем их тоже
	    if(func_num_args()>1){
	        $params=func_get_args();
	 
	        $message=call_user_func_array('sprintf',$params);
	    }
	 
	    $log.=$message;
	    $log.="\n";
	    // запись в файл
	    $this->_write($log);
	}
	/**
	* Если объект или массив сообщения
	*/
	public function logPrint($obj)
	{
        ob_start();
        print_r($obj);
        $ob=ob_get_clean();
        $this->log($ob);
    }
    /**
    * Непосредственно запись в файл
    */
    protected function _write($string)
    {
    	fwrite($this->fp, $string);
	}

	/**
	* Закрываем поток записи 
	*/
	public function __destruct()
	{
    	fclose($this->fp);
	}
}
