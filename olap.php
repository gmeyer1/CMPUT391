<?php

    include_once ("helper.php"); 
    $message = "";
    session_start();
    if (!$_SESSION['username'] || $_SESSION['username'] != 'admin') {
        redirect('home.php');
        $message = "redirected to home";
    }
    else {
        $message = "Welcome, admin";        
    }


?>

<html>
<head>
<title>OLAP</title>
</head>

<body>
    
<form name="HomeForm" action="home.php" method="get" >

<input type="submit" value="Home">

</form>    

<h1><center>OLAP</center></h1>

<p>
    <?php
        
        echo 'Hello ' . $_SESSION['username'];// . ' session id: ' . session_id() . ", message: " . $message;

?>
    
</p>



</body>
</html>