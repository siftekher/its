<?php
$sid = $_REQUEST['session_id'];
session_id($sid);
session_start();

require_once('../../../../config/its.web.config.php');




if($file = $_REQUEST['file'])
{
   $file = base64_decode($file);
   $_SESSION['ITS_UPLOADED_TICKET_ATTACHMENT'][$file] = $file;
}

echo "<pre>";
print_r($_SESSION);
?>