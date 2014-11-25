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

<p>
    <?php
        
        echo 'Hello ' . $_SESSION['username'];// . ' session id: ' . session_id() . ", message: " . $message;

?>
    
</p>

<form name="SearchForm" action="search.php" method="get" >

<input type="submit" value="Search">

</form>

<form name="UploadForm" action="upload.php" method="get" >

<input type="submit" value="Upload">

</form>

<form name="UploadForm" action="upload_multiple.php" method="get" >

<input type="submit" value="Upload Folder">

</form>

<form name="GroupsForm" action="groups.php" method="get" >

<input type="submit" value="Groups">

</form>

<?php

if($_SESSION['username'] == 'admin') {
    
?>
<form name="OLAPForm" action="olap.php" method="get" >

<input type="submit" value="OLAP Report">

</form>
       
<?php }  ?>

<form name="LogoutForm" action="login.php" method="post" >

<input type="submit" name="logout" value="Logout">

</form>

</body>
</html>