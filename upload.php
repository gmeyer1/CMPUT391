<?php

require_once("helper.php");

session_start();
if (!$_SESSION['username']) {
    redirect('login.php');
}

$user = $_SESSION['username'];


$message = 'Select an image for upload';
$registered = true;
$php_self = $_SERVER['PHP_SELF'];

$conn=connect();

$sql = 'SELECT group_name, group_id FROM groups WHERE user_name=\'' . $user . '\'';
$stid = oci_parse($conn, $sql);
oci_execute($stid);


$groups = '<option value="2">Private</option><option value="1">Public</option>';
while($row = oci_fetch_array($stid, OCI_ASSOC+OCI_RETURN_NULLS)) {
    $group_id = $row['GROUP_ID'];
    $group_name = $row['GROUP_NAME'];
    
    $groups .= '<option value="'.$group_id.'">'.$group_name.'</option>';
}

oci_free_statement($stid);
oci_close($conn);




if (!empty($_POST) && isset($_POST['submitUpload']) && isset($_FILES['userfile']))
    {
    
    $message = '<p>Submitted</p>';
    
    try    {
        
        $subject = $_POST['subject'];
	$place = $_POST['place'];
	$description = $_POST['description'];
        $date = $_POST['date'];//date('d.M.y');
        $date = str_replace('-', '/', $date);
        $group = $_POST['group_id'];
        
        if(is_uploaded_file($_FILES['userfile']['tmp_name']) && getimagesize($_FILES['userfile']['tmp_name']) != false)
            {
            /***  get the image info. ***/
            //$size = getimagesize($_FILES['userfile']['tmp_name']);
            /*** assign our variables ***/
            $image = file_get_contents($_FILES['userfile']['tmp_name']);
            $thumbnail = thumbnail($_FILES['userfile']['tmp_name']);
            
            //$size = $size[3];
            $name = $_FILES['userfile']['name'];
            $maxsize = 99999999;


            /***  check the file is less than the maximum file size ***/
            if($_FILES['userfile']['size'] < $maxsize )
                {
                ini_set('display_errors', 1);
                error_reporting(E_ALL);

                //establish connection
                $conn=connect();
                if (!$conn) {
                    $e = oci_error();
                    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
                }
                
//                $curr_id = 0;
//        
//                $sql = 'SELECT MAX(photo_id) FROM images';
//                $stid = oci_parse($conn, $sql);
//                oci_execute($stid);
//                
//                $row = oci_fetch_array($stid);
//
//                if($row) {
//                    $curr_id = $row['MAX(PHOTO_ID)'];
//                }
//
//                $curr_id++;
//
//                oci_free_statement($stid);
                
                $curr_id = hexdec(uniqid());

                $message = '<p>Building query</p>';
                
                /*** our sql query ***/
                // Need to assign a unique ID to every picture, and somehow let the uploader choose group for permission
                $sql = 'INSERT INTO images VALUES ('.$curr_id.',\''.$user.'\',\''.$group.'\',\''.$subject.'\',\''.$place.'\',TO_DATE(\''.$date.'\', \'yyyy/mm/dd\'),\''.$description.'\',empty_blob(),empty_blob()) RETURNING thumbnail, photo INTO :thumbnail, :photo'; 
                
                $message = $sql;
                
                $stid = oci_parse($conn, $sql);
                
                $thumbnail_blob = oci_new_descriptor($conn, OCI_D_LOB);
                $photo_blob = oci_new_descriptor($conn, OCI_D_LOB);
                
                oci_bind_by_name($stid, ':thumbnail', $thumbnail_blob, -1, OCI_B_BLOB);
                oci_bind_by_name($stid, ':photo', $photo_blob, -1, OCI_B_BLOB);

                //Execute a statement returned from oci_parse()

                $res=oci_execute($stid, OCI_NO_AUTO_COMMIT);
                
                if(!$thumbnail_blob->save($thumbnail) || !$photo_blob->save($image)) {
                    oci_rollback($conn);
                }
                else {
                    oci_commit($conn);
                }
                
                if (!$res) {
                $err = oci_error($stid); 
                echo htmlentities($err['message']);
                }

                // Free the statement identifier when closing the connection
                oci_free_statement($stid);
                
                $sql = 'BEGIN sync_index; END;';
                $stid = oci_parse($conn, $sql);
                oci_execute($stid);
                oci_free_statement($stid);
                
                oci_close($conn);
                
                $photo_blob->free();
                $thumbnail_blob->free();
                }
            else
                {
                /*** throw an exception is image is not of type ***/
                throw new Exception("File Size Error");
                }
            }
        else
            {
            // if the file is not less than the maximum allowed, print an error
            throw new Exception("Unsupported Image Format!");
            }
        
                 
        
        //$message = '<p>Thank you for submitting</p>';
        }
    catch(Exception $e)
        {
        echo '<h4>'.$e->getMessage().'</h4>';
        }
    }
    
// https://docs.oracle.com/cd/B28359_01/appdev.111/b28845/ch7.htm    
function thumbnail($imgfile) {  
    define('MAX_THUMBNAIL_DIMENSION', 100);
    list($w, $h, $type) = getimagesize($imgfile);
    
    switch ($type) 
    {
        case IMAGETYPE_GIF: 
            $src_img = imagecreatefromgif($imgfile); 
            break;   
        case IMAGETYPE_JPEG:  
            $src_img = imagecreatefromjpeg($imgfile); 
            break;   
        case IMAGETYPE_PNG:  
            $src_img = imagecreatefrompng($imgfile);
            break; 
        default:
            throw new Exception('Unrecognized image type ' . $type);
    }
    
    if ($w > MAX_THUMBNAIL_DIMENSION || $h > MAX_THUMBNAIL_DIMENSION)
    {
      $scale =  MAX_THUMBNAIL_DIMENSION / (($h > $w) ? $h : $w);
      $nw = $w * $scale;
      $nh = $h * $scale;

      $dest_img = imagecreatetruecolor($nw, $nh);
      imagecopyresampled($dest_img, $src_img, 0, 0, 0, 0, $nw, $nh, $w, $h);

      imagejpeg($dest_img, $imgfile);  // overwrite file with new thumbnail

      imagedestroy($src_img);
      imagedestroy($dest_img);
    }
    
    return file_get_contents($imgfile);
}
    
?>

<html>
<head><title>Upload Image</title></head>
<body>

<form name="HomeForm" action="home.php" method="get" >

<input type="submit" value="Home">

</form>
    
<h1><center>Upload</center></h1>

<form enctype="multipart/form-data" id='upload' action="<?php echo $php_self?>" method='post'
    accept-charset='UTF-8'>
	
<p><?php echo $message ?></p>

<input type='hidden' name='MAX_FILE_SIZE' value='99999999' />
<input type='file' name='userfile' id='userfile'/>

<table>
	<tr valign=top align=left>
		<td>
			<b><i>Subject*: </i></b></td>
		<td>
			<input type='text' name='subject' id='subject' maxlength="128" /><br>
		</td>
	</tr>
	<tr valign=top align=left>
		<td>
			<b><i>Place*: </i></b></td>
		<td>
			<input type='text' name='place' id='place' maxlength="128" /><br>
		</td>
	</tr>
        <tr valign=top align=left>
		<td>
			<b><i>Date*: </i></b></td>
		<td>
                        <!-- CHECK TO SEE IF THIS CAN BE CHANGED TO INPUT TYPE = DATE IN CHROME maxlength="12" -->
			<input type='date' name='date' id='date'/><br>
		</td>
	</tr>
	<tr valign=top align=left>
		<td>
			<b><i>Description*: </i></b></td>
		<td>
			<input type='text' name='description' id='description' maxlength="2048" /><br>
		</td>
	</tr>
        <tr valign=top align=left>
		<td>
			<b><i>Permission*: </i></b></td>
		<td>
                    <select name="group_id">
                        <?php
                        echo $groups;
                        
                        ?>
                    </select>
                    <br>
		</td>
	</tr>
	</table>

<input type='submit' name='submitUpload' value='Upload' />

</form>

</body></html>