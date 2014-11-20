<?php

require_once("helper.php");


session_start();
if (!$_SESSION['username']) {
    redirect('login.php');
}

$user = $_SESSION['username'];

    //echo '<p>Does this work or not?</p>';
    /*** assign the image id ***/
    $conn=connect();

    $sql = "SELECT photo FROM images WHERE photo_id = 1";
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    $row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS);
    if (!$row) {
        $image = 'Image not found';
    } else {
        $img = $row['PHOTO']->load();
        $image = '<img src="data:image/jpeg;base64,'.base64_encode( $img ).'"/>';
    }
    
?>


<html>
<head>
<title>Image</title>
</head>

<body>
    
<form name="HomeForm" action="home.php" method="get" >

<input type="submit" value="Home">

</form>
    
<h1><center>Image</center></h1>

<?php 
    if ($img) {
        
        //Check that current user can view image
        //if ($user is in group to view this image) {
        echo $image;
        //}
        
        //Check that current user can edit image
        //if ($user owns image) {
        //  Display a form with current values, allow user to edit and submit??
        //
    }
    else {
        echo 'Image not found';
    }

 ?>

</body>
</html>