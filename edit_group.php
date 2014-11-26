<?php
require_once("helper.php");

session_start();
if (!$_SESSION['username']) {
    // If user hasn't started a session, redirect to login page
    redirect('login.php');
}
else if (!isset($_POST['group_id'])) {
    // If the group to be edited isn't specify, redirect back to groups page
    redirect('groups.php');
}

$user = $_SESSION['username'];
$php_self = $_SERVER['PHP_SELF'];
$group_id = $_POST['group_id'];
$group_name = "";
$group_owner = "";
$users = "";
$message = "";
$deleted = false;
$deleted_users = false;
$updated_images = false;

// Connect to database
$conn=connect();

// To check if the current user is owner of group to be edited
$sql = 'select group_name, user_name from groups where group_id=\'' . $group_id . '\'';
    
$stid = oci_parse($conn, $sql);
oci_execute($stid, OCI_DEFAULT);  
if ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
    $group_name = $row['GROUP_NAME'];
    $group_owner = $row['USER_NAME'];
    if ($group_owner != $user && $user != "admin") {
        // If current user is not owner, and is not admin, redirect back to groups page
        redirect('groups.php');
    }
}
else {
    // If the group was not found, redirect back to groups page
    redirect('groups.php');
}

oci_free_statement($stid);

if (isset($_POST['deleteGroup'])) {
    // If user clicked to delete this group
    
    // Will have to update all images which have this group as their permission, and set to private
    $sql = 'UPDATE images SET permitted=\'2\' WHERE permitted=\'' . $group_id . '\'';
    $stid = oci_parse($conn, $sql);
    
    // Will be a transaction, don't commit unless they all succeed
    $row = oci_execute($stid, OCI_NO_AUTO_COMMIT);  
    if($row) {
        // Updating image permissions was success
        $updated_images = true;
    }
    else {
        $message .= "Could not update group permissions on images<br/>";
    }
    
    oci_free_statement($stid);
    
    if ($updated_images) {
        // Will delete all lists of users associated with this group to be deleted
        $sql = 'DELETE FROM group_lists WHERE group_id = \'' . $group_id . '\'';
        $stid = oci_parse($conn, $sql);
        $row = oci_execute($stid, OCI_NO_AUTO_COMMIT);  
        if($row) {
            // Deleting users from group was success
            $deleted_users = 1;
        }
        else {
            $message .= "Could not remove users from group " . $group_name . "<br/>";
        }

        oci_free_statement($stid);
    }
    
    if ($deleted_users) {
        // If updating images, and deleting users was success
        
        // Now finally delete the group
        $sql = 'DELETE FROM groups WHERE group_id = \'' . $group_id . '\'';
        $stid = oci_parse($conn, $sql);
        $row = oci_execute($stid, OCI_NO_AUTO_COMMIT);  
        if($row) {
            if (oci_commit($conn)) {
                // If it successfully committed the transaction
                $deleted = 1;
            }
            else {
                $message .= "Could not commit to delete group " . $group_name;
            }  
        }
        else {
            $message .= "Could not delete group " . $group_name;
        }

        oci_free_statement($stid);
    }
    
    if (!$deleted) {
        // If transaction failed, rollback
        oci_rollback($conn);
    }
}
else if (isset($_POST['addUser'])) {
    // If the current user clicked to add a user to this group
    $user_name = $_POST['user_name'];
    $notice = $_POST['notice'];
    $date = date('d.M.y');
    
    // To check if the user to be added is already in the specified group
    $sql = 'SELECT friend_id FROM group_lists WHERE friend_id = \'' . $user_name . '\' and group_id = \'' . $group_id . '\'';
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    $row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS);
    if ($row) {
        // No need to add them again, already in group
        $message = $user_name . " is already in group " . $group_name;
    }
    else {
        // To check if the specified user to add actually exists
        $sql = 'SELECT user_name FROM users WHERE user_name = \'' . $user_name . '\'';
        $stid = oci_parse($conn, $sql);
        oci_execute($stid);
        $row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS);
        if ($row) {
            // If it's a valid username
            oci_free_statement($stid);

            // Insert the specified user into group_lists for the specified group
            $sql = 'INSERT INTO group_lists VALUES (\''.$group_id.'\',\''.$user_name.'\',\''.$date.'\',\''.$notice.'\')'; 

            $stid = oci_parse($conn, $sql);
            $res=oci_execute($stid);

            if (!$res) {
                $err = oci_error($stid); 
                $message .= htmlentities($err['message']);
                $message .= "<br/>Could not add " . $user_name . " to group";
            }
            else {
                // Successfully added the user
                $message = 'Added ' . $user_name . ' to group';
            }

        }
        else {
            $message = $user_name . ' does not exist';
        }
    }
    oci_free_statement($stid);
}
else if (isset($_POST['removeUser'])) {
    // If the current user clicked to remove an existing group member
    $user_name = $_POST['user_name'];

    // To delete the user from group_lists for this group
    $sql = 'DELETE FROM group_lists WHERE friend_id = \'' . $user_name . '\' and group_id = \'' . $group_id . '\'';
            
    $stid = oci_parse($conn, $sql);
    $res=oci_execute($stid);

    if (!$res) {
        $err = oci_error($stid); 
        $message .= htmlentities($err['message']);
        $message .= "<br/>Could not remove " . $user_name . " from group";
    }
    else {
        // Successfully removed specified user from the group
        $message = 'Removed ' . $user_name . ' from group';
    }
    oci_free_statement($stid);
    
}

if (!$deleted) {
    // If this group was not deleted, need a list of current members
    
    // To find all members of this group, not including owner
    $sql = 'select friend_id from group_lists where group_id=\'' . $group_id . '\'';

    $stid = oci_parse($conn, $sql);
    oci_execute($stid, OCI_DEFAULT);

    while ($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
        // Loop through all members of group
        $friend_id = $row['FRIEND_ID'];
        
        // The HTML selector will a selectable option for each member
        $users .= '<option value="'.$friend_id.'">'.$friend_id.'</option>';
    }

    oci_free_statement($stid);
}

oci_close($conn);

?>
<html>
<head>
<title>Group</title>
</head>

<body>
    
<form name="HomeForm" action="home.php" method="get" >

<input type="submit" value="Home">

</form>
    
<form name="GroupsForm" action="groups.php" method="get" >

<input type="submit" value="Groups">

</form>
    
    
<?php echo $message . '<br/>'?>
    
<h1><center>
        
<?php
// If the current user had clicked to delete the group from the group pages,
// then we only display this message and a button to go back to groups
if ($deleted) {
    echo 'Deleted group ' . $group_name;
}
else {
    // Otherwise the group is not deleted, and is editable
?>
  
Edit Group

<?php
// Otherwise the current user is trying to edit this group
// Show group owner so the admin isn't confused
echo ': ' . $group_name . '<br/>Group owner: ' . $group_owner;
?>

</center></h1>
      
<?php
// If there are members in the group, display them
if ($users) {
?>

<form id='remove' action="<?php echo $php_self?>" method='post'
    accept-charset='UTF-8'>
    
<input type='hidden' name='group_id' value="<?php echo $group_id ?>" />    

<table>
    
People in group:
<select name="user_name">
    
<?php
// A selectable HTML option per group member
echo $users;
?>
</select>
    
<input type='submit' name='removeUser' value='Remove' />

<?php
}
else {
    // If there are no current members of this group
    echo '<p>No group members</p>';
}
?>

</table>

</form>
    
    <p>Add User:</p>    

<form id='add' action="<?php echo $php_self?>" method='post'
    accept-charset='UTF-8'>

<input type='hidden' name='group_id' value="<?php echo $group_id ?>" />

<table>
    <tr valign=top align=left>
    <td>
        <b><i>Username: </i></b></td>
    <td>
        <input type='text' name='user_name' value="" id='user_name' maxlength="24"/><br>
    </td>
    </tr>
    <tr valign=top align=left>
    <td>
        <b><i>Notice: </i></b></td>
    <td>
        <input type='text' name='notice' value="" id='notice' maxlength="1024" /><br>
    </td>
    </tr>
    
    <tr valign=top align=left>
    <td><input type="submit" name="addUser" value="Add"></td>
    </tr>


</form>
    
<?php

} // Closing brace for if this group wasn't deleted

?>
    
</body>
</html>