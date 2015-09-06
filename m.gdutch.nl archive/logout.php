<?php
include("inc/common.php");

$user = new uFlex();
            
//Logouts user and clears any auto login cookie
$user->logout();

 // printr($user->report());
 // exit;

$host  = $_SERVER['HTTP_HOST'];
$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$extra = LOGINPAGE;
header("Location: http://$host$uri/$extra");
exit;

?>