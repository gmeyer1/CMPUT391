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
<title>Home</title>
</head>

<body>
<h1><center>Home</center></h1>

    <?php
        
        echo 'Hello ' . $_SESSION['username'];// . ' session id: ' . session_id() . ", message: " . $message;

?>
    
</p>

<form name="LogoutForm" action="login.php" method="post" >

<input type="submit" name="logout" value="Logout">

</form>

</body>
</html>