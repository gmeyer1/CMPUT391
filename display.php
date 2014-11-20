<?php
require_once("helper.php");

session_start();
if (!$_SESSION['username']) {
    redirect('login.php');
}
$image = 'Image not found';
$user = $_SESSION['username'];
$photo_id = 0;
$php_self = $_SERVER['PHP_SELF'];
$subject = "subject";
$place = "place";
$description = "description";
$owner = "";

if (!empty($_POST) && isset($_POST['photo_id'])) {
    //Need to save new image values, and should probably check again that current user is owner
    
}
else if (!empty($_GET) && isset($_GET['photo_id'])) {

    $photo_id = $_GET['photo_id'];
    $conn=connect();

    $sql = 'SELECT photo, subject, place, description, owner_name FROM images WHERE photo_id = \'' . $photo_id . '\'';
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    $row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS);
    if ($row) {
        $data = $row['PHOTO']->load();
        $subject = $row['SUBJECT'];
        $place = $row['PLACE'];
        $description = $row['DESCRIPTION'];
        $owner = $row['OWNER_NAME'];
        $imageTag = '<img src="data:image/jpeg;base64,'.base64_encode( $data ).'"/>';
    }
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
    //If the image exists
    if ($data) {
    //And need to check that current user has permission to view this image
        echo $imageTag;
?>

<form id='edit' action="<?php echo $php_self?>" method='post'
    accept-charset='UTF-8'>

<table>
    <tr valign=top align=left>
    <td>
        <b><i>Subject*: </i></b></td>
    <td>
        <input type='text' name='subject' value="<?php echo $subject ?>" id='subject' maxlength="128" <?php if ($user != $owner) { echo 'readonly'; } ?>
        /><br>
    </td>
    </tr>
    <tr valign=top align=left>
    <td>
        <b><i>Place*: </i></b></td>
    <td>
        <input type='text' name='place' value="<?php echo $place ?>" id='place' maxlength="128" <?php if ($user != $owner) { echo 'readonly'; } ?>/><br>
    </td>
    </tr>
    <tr valign=top align=left>
    <td>
        <b><i>Description*: </i></b></td>
    <td>
        <input type='text' name='description' value="<?php echo $description  ?>" id='description' maxlength="2048" <?php if ($user != $owner) { echo 'readonly'; } ?>/><br>
    </td>
    </tr>
    </table>

<?php
// If user is owner, display the Save button
if ($user == $owner) {
?>
<input type='submit' name='submitEdit' value='Save' />
<?php
}
?>


</form>


<?php
    } //End of if image exists
    else {
        echo 'Image not found';
    }
 ?>

</body>
</html>