<?php
require_once("helper.php");

session_start();
if (!$_SESSION['username']) {
    redirect('login.php');
}
$image = 'Image not found';
$user = $_SESSION['username'];
$user_groups = array();
$photo_id = 0;
$php_self = $_SERVER['PHP_SELF'];
$subject = "subject";
$place = "place";
$description = "description";
$owner = "";
$deleted = 0;
$updated = 0;
$photo_group = -1;
$conn=connect();
$group_name = "public";

if (!empty($_POST) && isset($_POST['submitEdit'])) {
    //Need to save new image values, and should probably check again that current user is owner

    $place = $_POST['place'];
    $date = $_POST['date'];
    $description = $_POST['description'];
    $subject = $_POST['subject'];
    $group_name = $_POST['group_name'];
    
    $photo_id = $_POST['photo_id'];
    
    $sql = 'SELECT owner_name, permitted FROM images WHERE photo_id = \'' . $photo_id . '\'';
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    $row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS);
    if ($row) {
        $owner = $row['OWNER_NAME'];
        $permitted = $row['PERMITTED'];
        if ($owner != $user) {
            redirect('search.php');
        }
    }
    else {
        redirect('search.php');
    }
        
    oci_free_statement($stid);
    
    $sql = 'SELECT group_id FROM groups WHERE group_name = \'' . $group_name . '\' and user_name = \'' . $user . '\'';
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    $row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS);
    if ($row) {
        $group_id = $row['GROUP_ID'];
    }
    else {
        $group_id = '2';
    }
        
    oci_free_statement($stid);  

    $sql = 'UPDATE images SET subject=\'' . $subject . '\', permitted=\'' . $group_id . '\', place=\'' . $place . '\', timing=\'' . $date . '\', description=\'' . $description . '\' WHERE photo_id=\'' . $photo_id . '\'';
    $stid = oci_parse($conn, $sql);
    $row = oci_execute($stid, OCI_DEFAULT);  
    if($row) {
        if (oci_commit($conn)) {
            $updated = 1;
        }
        else {
            $err = oci_error($stid); 
            echo htmlentities($err['message']);
        }        
    }  
    else { 
        $err = oci_error($stid); 
        echo htmlentities($err['message']);
    }
    oci_free_statement($stid);
    
    $sql = 'BEGIN sync_index; END;';
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    oci_free_statement($stid);
    
    oci_close($conn);
}
else if (!empty($_POST) && isset($_POST['submitDelete'])) {
    
    $photo_id = $_POST['photo_id'];
    
    $sql = 'SELECT owner_name, permitted FROM images WHERE photo_id = \'' . $photo_id . '\'';
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    $row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS);
    if ($row) {
        $owner = $row['OWNER_NAME'];
        $permitted = $row['PERMITTED'];
        if ($owner != $user) {
            redirect('search.php');
        }
    }
    else {
        redirect('search.php');
    }
    
    oci_free_statement($stid);

    $sql = 'DELETE FROM images WHERE photo_id = \'' . $photo_id . '\'';
    $stid = oci_parse($conn, $sql);
    $row = oci_execute($stid, OCI_DEFAULT);  
    if($row) {
        // This might not be needed??
        if (oci_commit($conn)) {
            $deleted = 1;
        }
        else {
            $err = oci_error($stid); 
            echo htmlentities($err['message']);
        }        
    }  
    else { 
        $err = oci_error($stid); 
        echo htmlentities($err['message']);
    }
    oci_free_statement($stid);
    
    $sql = 'BEGIN sync_index; END;';
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    oci_free_statement($stid);
    
    oci_close($conn);
}
else if (!empty($_GET) && isset($_GET['photo_id'])) {

    $photo_id = $_GET['photo_id'];

    $sql = 'SELECT photo, subject, place, timing, description, owner_name, permitted FROM images WHERE photo_id = \'' . $photo_id . '\'';
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    $row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS);
    if ($row) {
        $data = $row['PHOTO']->load();
        $subject = $row['SUBJECT'];
        $place = $row['PLACE'];
        $date = $row['TIMING'];
        $description = $row['DESCRIPTION'];
        $owner = $row['OWNER_NAME'];
        $permitted = $row['PERMITTED'];
        $imageTag = '<img src="data:image/jpeg;base64,'.base64_encode( $data ).'"/>';
    }
    else {
        redirect('search.php');
    }
    
    oci_free_statement($stid);
    
    $sql = 'SELECT group_name FROM groups WHERE group_id = \'' . $permitted . '\'';
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    $row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS);
    if ($row) {
        $group_name = $row['GROUP_NAME'];
    }
        
    oci_free_statement($stid);  
}

if ($permitted == -1) {
    redirect('search.php', $conn);
}
else if ($owner != $user && $user != 'admin' && $permitted != 1) {
    
    $sql = 'select group_id from group_lists where friend_id=\'' . $user . '\'';

    $stid = oci_parse($conn, $sql);
    oci_execute($stid, OCI_DEFAULT);

    while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
        $group_id = $row['GROUP_ID'];
        $user_groups[] = $group_id;
    }
    oci_free_statement($stid);
    
    if (!in_array($permitted, $user_groups)) {
        redirect('search.php');
    }
}

oci_close($conn);
    
?>


<html>
<head>
<title>Image</title>
</head>

<body>
    
<form name="HomeForm" action="home.php" method="get" >

<input type="submit" value="Home">

</form>
    
<form name="SearchForm" action="search.php" method="get" >

<input type="submit" value="New Search">

</form>
    
<h1><center>Image</center></h1>


<?php
    //If the image exists, show all its information
    //TODO: add a check that the user has permission to view it
    if ($data) {
        echo $imageTag;
?>

<form id='edit' action="<?php echo $php_self?>" method='post'
    accept-charset='UTF-8'>

<input type='hidden' name='photo_id' value='<?php echo $photo_id ?>' />
    
<table>
    <tr valign=top align=left>
    <td>
        <b><i>Subject: </i></b></td>
    <td>
        <input type='text' name='subject' value="<?php echo $subject ?>" id='subject' maxlength="128" <?php if ($user != $owner && $user != 'admin') { echo 'readonly'; } ?>
        /><br>
    </td>
    </tr>
    <tr valign=top align=left>
    <td>
        <b><i>Place: </i></b></td>
    <td>
        <input type='text' name='place' value="<?php echo $place ?>" id='place' maxlength="128" <?php if ($user != $owner && $user != 'admin') { echo 'readonly'; } ?>/><br>
    </td>
    </tr>
    <tr valign=top align=left>
    <td>
        <b><i>Date: </i></b></td>
    <td>
        <input type='date' name='date' value="<?php echo $date ?>" id='date' <?php if ($user != $owner && $user != 'admin') { echo 'readonly'; } ?>/><br>
    </td>
    </tr>
    <tr valign=top align=left>
    <td>
        <b><i>Description: </i></b></td>
    <td>
        <input type='text' name='description' value="<?php echo $description  ?>" id='description' maxlength="2048" <?php if ($user != $owner && $user != 'admin') { echo 'readonly'; } ?>/><br>
    </td>
    </tr>
    <tr valign=top align=left>
    <td>
        <b><i>Group: </i></b></td>
    <td>
        <input type='text' name='group_name' value="<?php echo $group_name  ?>" id='group' maxlength="24" <?php if ($user != $owner && $user != 'admin') { echo 'readonly'; } ?>/><br>
    </td>
    </tr>
    </table>

<?php
// If user is owner, display the Save and Delete buttons
if ($user == $owner || $user == 'admin') {
?>
<input type='submit' name='submitEdit' value='Save' />
<input type='submit' name='submitDelete' value='Delete' />
<?php
}
?>


</form>


<?php
    }
    else if ($deleted) {
        // If user clicked delete button, show this message
        echo 'Image deleted';
    }
    else if ($updated) {
        // If user clicked save button, show this message
        echo 'Image updated';
    }
    else {
        // If image could not be found, show this message
        echo 'Image not found';
    }
 ?>

</body>
</html>