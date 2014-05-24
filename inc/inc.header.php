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
    <title>Guild Hub</title>
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
    <div>
        <ul class="nav">
            <li>
                <a href="#">Home</a>
            </li>

            <li>
                <a href="#">Guild</a>
                <ul>
                    <li><a href="GuildSummary.php?id=1">My Guild</a></li>
                    <li><a href="#">Guild Search</a></li>
                    <li><a href="GuildReport.php?id=1">Guild Audit</a></li>
                </ul>
            </li>

            <li>
                <a href="#">Characters</a>
                <ul>
                    <li><a href="#">My Characters</a></li>
                    <li><a href="#">Character Search</a></li>
                </ul>
            </li>

            <li>
                <a href="#">Logout</a>
            </li>
        </ul>
    </div>
    <br>
<!--    <p>Logged in as: <?// echo $_SESSION['user_name']?> <a href="login.php?logout=true">Logout</a></p> -->
</div>
<script>
    $(document).ready(
        function () {
            $('.nav li').hover(
                function () {
                    $('ul', this).fadeIn();
                },
                function () {
                    $('ul', this).fadeOut();
                }
            );
        }
    );
</script>