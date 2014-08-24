<?php
/*
* Filename   : run.php
* Purpose    : 
*
* @author    : developer@evoknow.com
* @project   : 
* @version   : 1.0.0
* @copyright : www.evoknow.com
*/
  define('DOCUMENT_ROOT',    $_SERVER['DOCUMENT_ROOT']);
  define('CLASS_DIR',        DOCUMENT_ROOT . '/its/classes');
  define('TEMP_DIR',         DOCUMENT_ROOT . '/its/tmp');
  define('EXT_DIR',          DOCUMENT_ROOT . '/its/ext/');
  define('SUPER_CONTROLLER_URL_PREFIX',        '/its/run.php/');
  define('CONTROLLER_DIR',   DOCUMENT_ROOT . '/its/controllers/');
  define('TEMPLATE_DIR',     DOCUMENT_ROOT . '/its/views/' );
  define('CONFIG_DIR',       DOCUMENT_ROOT . '/its/config');
  
  set_include_path(get_include_path() . PATH_SEPARATOR . CLASS_DIR);
  set_include_path(get_include_path() . PATH_SEPARATOR . CONFIG_DIR);
  set_include_path(get_include_path() . PATH_SEPARATOR . CONTROLLER_DIR);

  require_once(EXT_DIR . 'phpmailer/class.phpmailer.php');
  require_once(EXT_DIR . 'smarty/libs/Smarty.class.php');
  
  require_once('DB.class.php');
  require_once('Template.class.php');
  require_once('its.common.config.php');
  require_once('its.web.config.php');
  require_once('TicketTag.class.php');
  require_once('TicketList.class.php');
  require_once('TicketAuth.class.php');
  require_once('User.class.php');
  require_once('Utils.class.php');
  require_once('Email.class.php');
  require_once('User.list.class.php');
  require_once('activityLog.class.php');

  session_start();
  
  try
  {
     $dbObj = new DB($dbInfo);
  }
  catch(Exception $e)
  {
     die($e->getMessage());
  }

  $params   =  array();
  $params['db_link'] = $dbObj;
  
  $className = str_replace(SUPER_CONTROLLER_URL_PREFIX, '', $_SERVER['REQUEST_URI']);
  
  $className = explode('/', $className);
  $params['cmdList'] = $className;

  /*
  if(strstr($className, '?'))
  {
     $className = substr($className, 0 , strpos($className, '?', 0));
  }
  */
  
  if(!empty($className[0]))
  {
     $className[0] = ucwords($className[0]);
  }
  
  $classFile = sprintf("%s/%sController.class.php", CONTROLLER_DIR, $className[0]);

  if (empty($className[0]) || (!file_exists($classFile)))
  {
     $className[0] = 'Login';
     $classFile    = CONTROLLER_DIR . '/LoginController.class.php';
  }

  require_once($classFile);
  
  $thisclassName = new $className[0]($params);
  $thisclassName->run();
?>
