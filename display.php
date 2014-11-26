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
$deleted = false;
$updated = 0;
$photo_group = -1;
$conn=connect();
$image_group_name = "public";
$group_name = "";

if (!empty($_POST) && isset($_POST['submitEdit'])) {
    //Need to save new image values, and should probably check again that current user is owner

    $place = $_POST['place'];
    $date = $_POST['date'];
    $description = $_POST['description'];
    $subject = $_POST['subject'];
    $group_id = $_POST['group_id'];
    
    $photo_id = $_POST['photo_id'];
    
    $sql = 'SELECT owner_name, permitted FROM images WHERE photo_id = \'' . $photo_id . '\'';
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    $row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS);
    if ($row) {
        $owner = $row['OWNER_NAME'];
        $permitted = $row['PERMITTED'];
        if ($owner != $user && $user != 'admin') {
            redirect('search.php');
        }
    }
    else {
        redirect('search.php');
    }
        
    oci_free_statement($stid); 

    $sql = 'UPDATE images SET subject=\'' . $subject . '\', permitted=\'' . $group_id . '\', place=\'' . $place . '\',timing=TO_DATE(\''.$date.'\', \'yyyy/mm/dd\'), description=\'' . $description . '\' WHERE photo_id=\'' . $photo_id . '\'';
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
    $deleted_views = false;
    $sql = 'SELECT owner_name, permitted FROM images WHERE photo_id = \'' . $photo_id . '\'';
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    $row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS);
    if ($row) {
        $owner = $row['OWNER_NAME'];
        $permitted = $row['PERMITTED'];
        if ($owner != $user && $user != 'admin') {
            redirect('search.php');
        }
    }
    else {
        redirect('search.php');
    }
    
    oci_free_statement($stid);

    $sql = 'DELETE FROM popular_images WHERE photo_id = \'' . $photo_id . '\'';
    $stid = oci_parse($conn, $sql);
    $row = oci_execute($stid, OCI_NO_AUTO_COMMIT);  
    if($row) {
        $deleted_views = true;      
    }  
    else {
        $err = oci_error($stid); 
        echo htmlentities($err['message']);
        oci_rollback($conn);
    }
    oci_free_statement($stid);
    
    
    if ($deleted_views) {
        $sql = 'DELETE FROM images WHERE photo_id = \'' . $photo_id . '\'';
        $stid = oci_parse($conn, $sql);
        $row = oci_execute($stid, OCI_NO_AUTO_COMMIT);  
        if($row) {
            // This might not be needed??
            if (oci_commit($conn)) {
                $deleted = 1;
            }
            else {
                $err = oci_error($stid); 
                echo htmlentities($err['message']);
                oci_rollback($conn);
            }        
        }  
        else { 
            $err = oci_error($stid); 
            echo htmlentities($err['message']);
            oci_rollback($conn);
        }
        oci_free_statement($stid);

        $sql = 'BEGIN sync_index; END;';
        $stid = oci_parse($conn, $sql);
        oci_execute($stid);
        oci_free_statement($stid);
    }
    
    oci_close($conn);
}
else if (!empty($_GET) && isset($_GET['photo_id'])) {

    $photo_id = $_GET['photo_id'];

    $sql = 'SELECT photo, subject, place, TO_CHAR(timing, \'yyyy-mm-dd\') "DATE", description, owner_name, permitted FROM images WHERE photo_id = \'' . $photo_id . '\'';
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    $row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS);
    if ($row) {
        $data = $row['PHOTO']->load();
        $subject = $row['SUBJECT'];
        $place = $row['PLACE'];
        $date = $row['DATE'];
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
        $image_group_name = $row['GROUP_NAME'];
    }
        
    oci_free_statement($stid);  
}

if ($permitted == -1) {
    redirect('search.php', $conn);
}
else if ($owner != $user && $user != 'admin' && $permitted != 1) {
    
    $sql = 'SELECT g.group_id, g.group_name, g.user_name FROM groups g left outer join group_lists l on g.group_id=l.group_id WHERE g.group_id = l.group_id and (g.user_name=\'' . $user . '\' or l.friend_id=\'' . $user . '\')';

    //$sql = 'select group_id from group_lists where friend_id=\'' . $user . '\'';

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


if ($user == $owner || $user == 'admin') {
    if ($user == 'admin') {
        $groups = '';
        $sql = 'SELECT g.group_id, g.group_name, g.user_name FROM groups g';
    }
    else {
        $groups = '<option value="2">private</option><option value="1">public</option>';
        $sql = 'SELECT g.group_id, g.group_name, g.user_name FROM groups g left outer join group_lists l on g.group_id=l.group_id WHERE g.user_name=\'' . $user . '\' or l.friend_id=\'' . $user . '\'';
    }

    $stid = oci_parse($conn, $sql);
    oci_execute($stid);

    while($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
        $group_id = $row['GROUP_ID'];
        $group_name = $row['GROUP_NAME'];
        $group_owner = $row['USER_NAME'];

        $groups .= '<option value="'.$group_id.'">'.$group_name.' - ' . $group_owner .'</option>';
    }
    oci_free_statement($stid);
    
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
    //If the image exists, and user has permission to view it
    if ($data) {
        
        $conn=connect();
        
        $sql = 'SELECT user_name FROM popular_images WHERE user_name = \'' . $user . '\' and photo_id = \'' . $photo_id . '\'';
        $stid = oci_parse($conn, $sql);
        oci_execute($stid);
        $row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS);
        if (!$row) {

            oci_free_statement($stid);

            $sql = 'INSERT INTO popular_images VALUES (\''.$user.'\',\''.$photo_id.'\')'; 

            $stid = oci_parse($conn, $sql);
            $res=oci_execute($stid);

            if (!$res) {
                $err = oci_error($stid); 
                $message .= htmlentities($err['message']);
                $message .= "<br/>Could not insert view";
            }
            else{ 
                $message = 'Added view';
            }

        }
        else {
            $message = 'View already exists';
        }
        oci_free_statement($stid);
        oci_close($conn);
        
        
        
        echo $imageTag;
        echo 'Message: ' . $message;
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
        <!-- CHECK TO SEE IF THIS CAN BE CHANGED TO INPUT TYPE = DATE IN CHROME maxlength="12" -->
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
    
        <?php
        echo '<tr><td><b><i>Owner: </i></b></td><td><b><i>' . $owner . '</i></b></td></tr>';
        echo '<tr><td><b><i>Group: </i></b></td><td><b><i>' . $image_group_name . '</i></b></td><td></tr>';
        if ($user == $owner || $user == 'admin') {
            echo '<tr><td><b><i>Update group:</i></b></td><td>';
            echo '<select name="group_id">';
            echo $groups;
            echo '</select></td></tr>';
        }
    ?>
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