<?php
/*
 * CMPUT 391 Project Group 6
 * Written by Glenn Meyer and Dylan Stankievech
 * November 26, 2014 * 
 * 
 */
require_once("helper.php");

session_start();
if (!$_SESSION['username']) {
    // If user hasn't started a session, redirect to login page
    redirect('login.php');
}

$user = $_SESSION['username'];
$php_self = $_SERVER['PHP_SELF'];
$conn=connect();

if (isset($_POST['addGroup'])) {
    // If user is adding a new group
    
    $group_name = $_POST['group_name'];
    $curr_id = 0;
    
    // To check if new group already exists for current user
    $sql = 'SELECT user_name FROM groups WHERE user_name = \'' . $user . '\' and group_name = \'' . $group_name . '\'';
    $stid = oci_parse($conn, $sql);
    oci_execute($stid);
    $row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS);
    
    
    if ($row) {
        // Group name already exists for current user
        $message = "Group " . $group_name . " already exists";
        $valid = false;
    }
    else if (empty($group_name)) {
        // User clicked add group with empty group name
        $message = "Group name cannot be empty";
        $valid = false;
    }
    else {
        // Valid group name
        
        // Get a unique group id
        $curr_id = hexdec(uniqid());
        
        $date = date('d.M.y');
        
        // To create the new group
        $sql = 'INSERT INTO groups VALUES (\''.$curr_id.'\',\''.$user.'\',\''.$group_name.'\',\''.$date.'\')'; 

        $stid = oci_parse($conn, $sql);
        $res=oci_execute($stid);

        if (!$res) {
            // Group could not be created
            $err = oci_error($stid); 
            $message .= htmlentities($err['message']);
            $message .= "<br/>Could not create group " . $group_name;
        }
        else {
            // Group created successfully
            $message = 'Created group ' . $group_name;
        }
        oci_free_statement($stid);
    }
}

if ($user == 'admin') {
    // Admin will be able to edit all groups
    $sql = 'SELECT group_name, group_id FROM groups';
}
else {
    // Non-admin can only edit groups they own
    $sql = 'SELECT group_name, group_id FROM groups WHERE user_name=\'' . $user . '\'';
}

$stid = oci_parse($conn, $sql);
oci_execute($stid);

$groups = '';
while($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
    // Loop through all groups that current user can edit
    $group_id = $row['GROUP_ID'];
    $group_name = $row['GROUP_NAME'];
    
    // The HTML selector will have each group as an option
    $groups .= '<option value="'.$group_id.'">'.$group_name.'</option>';
}

oci_free_statement($stid);

// To find all groups that current user is a member of
$sql = 'SELECT g.group_id, g.group_name, g.user_name, l.notice FROM groups g left outer join group_lists l on g.group_id=l.group_id WHERE l.friend_id=\'' . $user . '\'';

$stid = oci_parse($conn, $sql);
oci_execute($stid);

$member_of = '';
while($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
    // Loop through all groups that the current user is a member of
    $group_id = $row['GROUP_ID'];
    $group_name = $row['GROUP_NAME'];
    $group_owner = $row['USER_NAME'];
    $notice = $row['NOTICE'];
    
    // Add a row in HTML table to display group name, group owner, and notice
    $member_of .= '<tr><td><b><i>' . $group_name . '</i></b></td><td><b><i>' . $group_owner . '</i></b></td><td><b><i>' . $notice . '</i></b></td></tr>';
}

oci_free_statement($stid);
oci_close($conn);
    
?>
<html>
<head>
<title>Groups</title>
</head>

<body>
    
<form name="HomeForm" action="home.php" method="get" >

<input type="submit" value="Home">

</form>
    
<h1><center>Groups</center></h1>

<?php echo $message . "<br/>" ?>

<form id='edit' action="edit_group.php" method='post'
    accept-charset='UTF-8'>
    
<table>
    <?php
    if ($groups) {
        echo 'Owner of Groups:';
        echo '<select name="group_id">';
        echo $groups;
        echo '</select>';
    ?>
    
    <input type='submit' name='editGroup' value='Edit' />
    <input type='submit' name='deleteGroup' value='Delete' />
    
    <?php
    }
    else {
        echo '<p>Owner of no groups</p>';
    }
    ?>
</table>

</form>

<form id='add' action="<?php echo $php_self?>" method='post'
    accept-charset='UTF-8'>

<table border="1">
    <caption>Create New Group</caption>
    <tr valign=top align=left>
    <td>
        <b><i>Group Name: </i></b></td>
    <td>
        <input type='text' name='group_name' value="" id='group_name' maxlength="24"/><br>
    </td>
    <td><input type="submit" name="addGroup" value="Create"></td>
    </tr>
</form>

<?php
if ($member_of) {
    echo '<table border="1">';
    echo '<caption>Member of Groups:</caption>';
    echo '<tr><td><b><i>Group Name</i></b></td><td><b><i>Group Owner</i></b></td><td><b><i>Notice</i></b></td></tr>';
    echo $member_of;
    echo '</table>';
}
else {
    echo '<b><i>Member of no groups</i></b>';
}
?>

</body>
</html>