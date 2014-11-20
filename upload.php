<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once("helper.php");

$message = 'Select an image for upload';
$registered = true;
$php_self = $_SERVER['PHP_SELF'];

if (!empty($_POST) && isset($_POST['submitUpload']) && isset($_FILES['userfile']))
    {
    
    $message = '<p>Submitted</p>';
    
    try    {
        
        $subject = $_POST['subject'];
	$place = $_POST['place'];
	$description = $_POST['description'];
        $date = date('d.M.y');
        
        if(is_uploaded_file($_FILES['userfile']['tmp_name']) && getimagesize($_FILES['userfile']['tmp_name']) != false)
            {
            /***  get the image info. ***/
            $size = getimagesize($_FILES['userfile']['tmp_name']);
            /*** assign our variables ***/
            $type = $size['mime'];
            $image = file_get_contents($_FILES['userfile']['tmp_name']);
            $size = $size[3];
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

                $message = '<p>Building query</p>';
                
                /*** our sql query ***/
                $sql = 'INSERT INTO images VALUES (1,\'gmeyer1\',1,\''.$subject.'\',\''.$place.'\',\''.$date.'\',\''.$description.'\',empty_blob(),empty_blob()) RETURNING thumbnail, photo INTO :thumbnail, :photo'; 
                
                $stid = oci_parse($conn, $sql );
                
                $thumbnail_blob = oci_new_descriptor($conn, OCI_D_LOB);
                $photo_blob = oci_new_descriptor($conn, OCI_D_LOB);
                
                oci_bind_by_name($stid, ':thumbnail', $thumbnail_blob, -1, OCI_B_BLOB);
                oci_bind_by_name($stid, ':photo', $photo_blob, -1, OCI_B_BLOB);

                //Execute a statement returned from oci_parse()

                $res=oci_execute($stid);
                
                if(!$thumbnail_blob->save($image) || !$photo_blob->save($image)) {
                    oci_rollback($conn);
                }
                else {
                    oci_commit($conn);
                }
                
                if (!$res) {
                $err = oci_error($stid); 
                echo htmlentities($err['message']);
                }
                else{
                    echo 'Row inserted into students';
                }

                // Free the statement identifier when closing the connection
                oci_free_statement($stid);
                
                $photo_blob->free();
                $thumbnail_blob->free();
                
                oci_close($conn);
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
        
                 
        
        echo '<p>Thank you for submitting</p>';
        }
    catch(Exception $e)
        {
        echo '<h4>'.$e->getMessage().'</h4>';
        }
    }
?>

<html>
<head><title>Upload Image</title></head>
<body>
    
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
			<b><i>Description*: </i></b></td>
		<td>
			<input type='text' name='description' id='description' maxlength="2048" /><br>
		</td>
	</tr>
	</table>

<input type='submit' name='submitUpload' value='Submit' />

</form>

</body></html>