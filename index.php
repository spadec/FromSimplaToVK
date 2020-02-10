<!DOCTYPE html>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
</head>
<body>
<script src="http://code.jquery.com/jquery-latest.js"></script>
<?php 
require_once("auth.php");
require_once("DB.php");
require_once("logger.php");
require_once("sync.php");

$auth = new vkauth();
$db = new simplaDB();
$log = new VKLogger("sync.log");
$columns = $db->checkDB();
if(!$columns){ ?>
    <p id="need">Необходимо внести изменения в БД</p>
    <button id="insertDB">Внести изменения</button>
    <div id="message"></div>
<?php
}
else {
    if(isset($_GET["code"])){
        $code = $_GET["code"];
        $access_token = $auth->getToken($config,$code);
        if($access_token)
        sleep(1);
        $sync = new vksync($access_token, $config, $db, $log);
        //$all = $db->getAllProduct();
        
         ?>
       <pre>
       <?php  print_r($db->isCatChild("17"));//print_r($all); ?>
       </pre>
<?php
       // $sync->syncVK();
    }
    else {
        $auth -> getCode($config);
    }
}
    ?>
<script>
        $("#insertDB").click(function(){
            $.ajax({
            url: "updateDB.php",
            type : "POST",
			    success: function (response) {
                    $("#need").hide();
                    $("#insertDB").hide();
                    $("#message").append(response);
                }
            });
        });
        </script>
</body>
</html>