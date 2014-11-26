<?php
include_once ("helper.php"); 

$message = "";
session_start();

if (!$_SESSION['username']) {
    // If user hasn't started a session, redirect to login page
    redirect('login.php');
}
?>
<html>
<head>
<title>Home</title>
</head>

<body>
<h1><center>Home</center></h1>

<p>
    <?php echo 'Hello ' . $_SESSION['username']; ?>
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
// If the current user is admin, will display the button to get to OLAP report
if($_SESSION['username'] == 'admin') {
?>

<form name="OLAPForm" action="olap.php" method="get" >

<input type="submit" value="OLAP Report">

</form>
       
<?php
} // end if statement
?>

<form name="LogoutForm" action="login.php" method="post" >

<input type="submit" name="logout" value="Logout">

</form>

<input type=button onClick="parent.location='https://github.com/gmeyer1/CMPUT391/wiki/User-Documentation'" value='Help'>

</body>
</html>