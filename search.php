<?php
    include_once ("helper.php"); 
    $message = "";
    session_start();
    if (!$_SESSION['username']) {
        redirect('login.php');
        $message = "redirected to login";
    }
    else {
        $message = "logged in";        
    }
?>

<html>
<head>
<title>Search</title>
</head>

<body>
<h1><center>Search</center></h1>

    <?php
        
        echo 'Hello ' . $_SESSION['username'] . ', searching';// . ' session id: ' . session_id() . ", message: " . $message;

    ?>
    
</p>

<form name="LogoutForm" action="home.php" method="post" >

<input type="submit" name="home" value="Home">

</form>

</body>
</html>