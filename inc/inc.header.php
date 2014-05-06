<?php

    include_once __DIR__ . '/../settings.php';                        
    if(!isset($_SESSION)){
        session_start();
    }
/*   $g_oUserManager  =   new UserManager();

 /*if (!$g_oUserManager->checkLogin()){
       header("Location: login.php");
       die();
   }*/
?>
<html>
<head>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.0/jquery.min.js"></script>
    <link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/themes/cupertino/jquery-ui.css" />
    <script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.4/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="inc/styling.css"/>

</head>
<body>
<div id="header_div" class="content-head ui-widget-header">
    <h1>Guild Hub</h1>
</div>
<div>
<!--    <p>Logged in as: <?// echo $_SESSION['user_name']?> <a href="login.php?logout=true">Logout</a></p> -->
</div>