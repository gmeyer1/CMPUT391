<?php
    include_once ("helper.php"); 
    $message = "";
    $php_self = $_SERVER['PHP_SELF'];
    session_start();
    if (!$_SESSION['username']) {
        redirect('login.php');
        $message = "redirected to login";
    }
    else {
        $message = "logged in";        
    }
    
    if(!empty($_POST) && isset($_POST['submitSearch'])) {
        // The user submitted information
	$keywords = $_POST['keywords'];
	$after = $_POST['after'];
        $before = $_POST['before'];
        
        $message = "keywords: " . $keywords . ", after: " . $after . ", before: " . $before;
    }
    
?>

<html>
<head>
<title>Search</title>
</head>

<body>
<form name="LogoutForm" action="home.php" method="post" >

<input type="submit" name="home" value="Home">

</form>
<h1><center>Search</center></h1>

    <?php
        
        echo 'Hello ' . $_SESSION['username'] . ', message: ' . $message;// . ' session id: ' . session_id() . ", message: " . $message;

    ?>
    
</p>


<form name="SearchForm" action="<?php echo $php_self?>" method="post" >

<table>
<tr valign=top align=left>
<td><b><i>Keywords:</i></b></td>
<td><input type="text" name="keywords" value="keywords..." autofocus ><br></td>
</tr>
<tr valign=top align=left>
<td><b><i>After Date:</i></b></td>
<td><input type="date" name="after"><br></td>
</tr>
<tr valign=top align=left>
<td><b><i>Before Date:</i></b></td>
<td><input type="before" name="before"><br></td>
</tr>
</table>
<input type="submit" name="submitSearch" value="Search">
</form>

</body>
</html>