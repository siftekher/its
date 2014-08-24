<?php
/*
 * Array
 * (
 *     [Filedata] => Array
 *         (
 *             [name] => Winter.jpg
 *             [type] => application/octet-stream
 *             [tmp_name] => /tmp/phpAxOO2e
 *             [error] => 0
 *             [size] => 105542
 *         )
 * 
 * )
 */

require_once();
$file_name = strtolower($_FILES['Filedata']['name']);
$file_name = str_replace(array(' ', '-'), array('_', '_'), $file_name);
$src = $_FILES['Filedata']['tmp_name'];
$dst = $_SERVER['DOCUMENT_ROOT'] . $_REQUEST['folder'] . '/' . $file_name;

@move_uploaded_file($src, $dst);

echo base64_encode($dst);


//$_SESSION['ITS_UPLOADED_TICKET_ATTACHMENT'][] = $dst;
//w($_REQUEST);


function w($data)
{
   file_put_contents('log.txt', serialize($data));
}
?>