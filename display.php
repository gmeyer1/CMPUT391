<?php
require_once("helper.php");

session_start();
if (!$_SESSION['username']) {
    // If user hasn't started a session, redirect to login page
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
    // If the user clicked 'Save', to update image information

    $place = $_POST['place'];
    $date = $_POST['date'];
    $description = $_POST['description'];
    $subject = $_POST['subject'];
    $group_id = $_POST['group_id'];
    
    $photo_id = $_POST['photo_id'];
    
    // To find owner of image, and check if current user has permission to edit
    $sql = 'SELECT owner_name, permitted FROM images WHERE photo_id = \'' . $photo_id . '\'';
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    $row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS);
    
    if ($row) {
        // If the image has permission information
        $owner = $row['OWNER_NAME'];
        $permitted = $row['PERMITTED'];
        if ($owner != $user && $user != 'admin') {
            // If the current user isn't owner, and isn't admin, they don't have permission to edit this image
            redirect('search.php');
        }
    }
    else {
        // If the image doesn't have permission information, redirect back to search
        redirect('search.php');
    }
        
    oci_free_statement($stid); 

    // The query to update the image information
    $sql = 'UPDATE images SET subject=\'' . $subject . '\', permitted=\'' . $group_id . '\', place=\'' . $place . '\',timing=TO_DATE(\''.$date.'\', \'yyyy/mm/dd\'), description=\'' . $description . '\' WHERE photo_id=\'' . $photo_id . '\'';
    $stid = oci_parse($conn, $sql);
    $row = oci_execute($stid, OCI_DEFAULT);
    if($row) {
        if (oci_commit($conn)) {
            // Image update was successful
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
    
    // Synchronize the index after updating image information
    $sql = 'BEGIN sync_index; END;';
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    oci_free_statement($stid);
    
    oci_close($conn);
}
else if (!empty($_POST) && isset($_POST['submitDelete'])) {
    // If the user clicked to delete this image
    
    $photo_id = $_POST['photo_id'];
    $deleted_views = false;
    
    // To find owner of image, and check if current user has permission to delete
    $sql = 'SELECT owner_name, permitted FROM images WHERE photo_id = \'' . $photo_id . '\'';
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    $row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS);
    if ($row) {
        // If the image has permission information
        $owner = $row['OWNER_NAME'];
        $permitted = $row['PERMITTED'];
        if ($owner != $user && $user != 'admin') {
            // If the current user isn't owner, and isn't admin, they don't have permission to delete this image
            redirect('search.php');
        }
    }
    else {
        // If the image doesn't have permission information, redirect back to search
        redirect('search.php');
    }
    
    oci_free_statement($stid);

    // To delete the unique views associated with this image
    $sql = 'DELETE FROM popular_images WHERE photo_id = \'' . $photo_id . '\'';
    $stid = oci_parse($conn, $sql);
    
    // This will be a transaction
    $row = oci_execute($stid, OCI_NO_AUTO_COMMIT);  
    if($row) {
        // If the views could be deleted
        $deleted_views = true;      
    }  
    else {
        $err = oci_error($stid); 
        echo htmlentities($err['message']);
        oci_rollback($conn);
    }
    oci_free_statement($stid);
    
    if ($deleted_views) {
        // If the views were deleted, try to delete the image
        $sql = 'DELETE FROM images WHERE photo_id = \'' . $photo_id . '\'';
        $stid = oci_parse($conn, $sql);
        $row = oci_execute($stid, OCI_NO_AUTO_COMMIT);  
        if($row) {
            if (oci_commit($conn)) {
                // If the image was successfully deleted from database
                $deleted = 1;
            }
            else {
                // Problem committing
                $err = oci_error($stid); 
                echo htmlentities($err['message']);
                oci_rollback($conn);
            }        
        }  
        else {
            // Could not find the image to delete
            $err = oci_error($stid); 
            echo htmlentities($err['message']);
            oci_rollback($conn);
        }
        oci_free_statement($stid);

        // Synchronize the index after deleting the image
        $sql = 'BEGIN sync_index; END;';
        $stid = oci_parse($conn, $sql);
        oci_execute($stid);
        oci_free_statement($stid);
    }
    
    oci_close($conn);
}
else if (!empty($_GET) && isset($_GET['photo_id'])) {
    // If the user is just viewing an image, probably after clicking a thumbnail in search.php
    $photo_id = $_GET['photo_id'];

    // To find all the image information for this image
    $sql = 'SELECT photo, subject, place, TO_CHAR(timing, \'yyyy-mm-dd\') "DATE", description, owner_name, permitted FROM images WHERE photo_id = \'' . $photo_id . '\'';
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    $row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS);
    if ($row) {
        // If the image exists
        $data = $row['PHOTO']->load();
        $subject = $row['SUBJECT'];
        $place = $row['PLACE'];
        $date = $row['DATE'];
        $description = $row['DESCRIPTION'];
        $owner = $row['OWNER_NAME'];
        $permitted = $row['PERMITTED'];
        
        // Create the image tag to display the full image
        $imageTag = '<img src="data:image/jpeg;base64,'.base64_encode( $data ).'"/>';
    }
    else {
        // Image could not be found, redirect to search.php
        redirect('search.php');
    }
    
    oci_free_statement($stid);
    
    // Find the group name of the permission of this image
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
    // Image permission could not be found, redirect to search.php
    redirect('search.php');
}
else if ($owner != $user && $user != 'admin' && $permitted != 1) {
    // If the current user is not owner, is not admin, and the image is not public
    
    // To check all groups current user is a member of, to see if they have permission to view image
    $sql = 'SELECT g.group_id, g.group_name, g.user_name FROM groups g left outer join group_lists l on g.group_id=l.group_id WHERE g.group_id = l.group_id and (g.user_name=\'' . $user . '\' or l.friend_id=\'' . $user . '\')';

    $stid = oci_parse($conn, $sql);
    oci_execute($stid, OCI_DEFAULT);

    while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
        // Loop through all groups that current user is member of, put into an array
        $group_id = $row['GROUP_ID'];
        $user_groups[] = $group_id;
    }
    oci_free_statement($stid);
    
    if (!in_array($permitted, $user_groups)) {
        // If the image permission is not any of the groups that current user is a member of, redirect to search.php
        redirect('search.php');
    }
}

if ($user == $owner || $user == 'admin') {
    // If current user owns image, or is admin, will need to display relevant groups to possibly update image information
    if ($user == 'admin') {
        // Admin can update the image permission to be any group
        $groups = '';
        // To find all groups
        $sql = 'SELECT g.group_id, g.group_name, g.user_name FROM groups g';
    }
    else {
        // Non-admin can only update the image permission to public, private, or any group they own/are member of
        $groups = '<option value="2">private</option><option value="1">public</option>';
        // To find all groups current user owns or is member of
        $sql = 'SELECT g.group_id, g.group_name, g.user_name FROM groups g left outer join group_lists l on g.group_id=l.group_id WHERE g.user_name=\'' . $user . '\' or l.friend_id=\'' . $user . '\'';
    }

    $stid = oci_parse($conn, $sql);
    oci_execute($stid);

    while($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
        // Loop through all groups that will be an option to set image permission to
        $group_id = $row['GROUP_ID'];
        $group_name = $row['GROUP_NAME'];
        $group_owner = $row['USER_NAME'];

        // HTML selector will have an option for every valid group
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

if ($data) {
    //If the image exists, and user has permission to view it
    $conn=connect();

    // To check if user has already viewed this image or not
    $sql = 'SELECT user_name FROM popular_images WHERE user_name = \'' . $user . '\' and photo_id = \'' . $photo_id . '\'';
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    $row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS);
    if (!$row) {
        // If user has not viewed this image before, need to now add a view
        oci_free_statement($stid);

        // Insert a view into the popular_images table to track unique views
        $sql = 'INSERT INTO popular_images VALUES (\''.$user.'\',\''.$photo_id.'\')'; 

        $stid = oci_parse($conn, $sql);
        $res=oci_execute($stid);

        if (!$res) {
            $err = oci_error($stid); 
            $message .= htmlentities($err['message']);
            $message .= "<br/>Could not insert view";
        }

}
oci_free_statement($stid);
oci_close($conn);

// Display the image
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
        // Display the image owner and group permission
        echo '<tr><td><b><i>Owner: </i></b></td><td><b><i>' . $owner . '</i></b></td></tr>';
        echo '<tr><td><b><i>Group: </i></b></td><td><b><i>' . $image_group_name . '</i></b></td><td></tr>';
        if ($user == $owner || $user == 'admin') {
            // If the current user is owner or admin, display the selector to possibly update group permission
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