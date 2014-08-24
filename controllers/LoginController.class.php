<?php
/*
 * Filename   : LoginController.class.php
 * Purpose    : 
 *
 * @author    : Sheikh Iftekhar <siftekher@gmail.com>
 * @project   : Issue tracking system
 * @version   : 1.0.0
 */

require_once('UserAuthentication.class.php');

class Login
{
   private $db;
   private $template;
   private $param;
   
   public function __construct($param)
   {
      $this->db = $param['db_link'];
      $this->template = new Template();
      $this->param = $param['cmdList'];
   }

   function run()
   {
      $cmd = $this->param[1];
      $userId = $_SESSION['LOGIN_USER']['userId'];

      if($userId != null && $cmd != 'logout')
      {
         header('location: '.SUPER_CONTROLLER_URL_PREFIX.'Ticket');
         exit;
      }
      
      

      switch($cmd)
      {
         case 'login'  : $screen = $this->login(); break;
         case 'logout' : $screen = $this->logout(); break;
         default       : $screen = $this->showLoginForm();
      }
      
      //echo $this->template->createScreen($screen);
      echo $screen;
      exit;
   }

   private function login()
   {
      $email     = $_REQUEST['email'];
      $password  = $_REQUEST['password'];

      $UserAuthentication = new UserAuthentication($this->db, $email, $password);
      $user = $UserAuthentication->getUser();
      if($user)
      {
         $_SESSION['LOGIN_USER'] = $user->getData();
         $_SESSION['SOURCES_ID_STRING'] = Utils::getSourcesIdAsString();
         $this->saveLog(USER_LOGGING);
         header('location: '.SUPER_CONTROLLER_URL_PREFIX.'Ticket');
         exit;
      }
      else
      {
         $_SESSION['LOGIN_MSG'] = $UserAuthentication->getMessage();
         header('location: '.SUPER_CONTROLLER_URL_PREFIX.'Login');
         exit;
      }
   }
   
   private function logout()
   {
      $this->saveLog(USER_LOGOUT);
      session_destroy();
      header('location: '.SUPER_CONTROLLER_URL_PREFIX.'Login');
      exit;
   }
   
   //method add by pushan
   private function saveLog($activityType = null)
   {
      $params  = array();
      $params['db_obj'] = $this->db;
      $activityLogObj = new activityLog($params);
      
      $activityLogObj->saveLogInLogOut($activityType);
      
      return true;
   }
   
   private function showLoginForm()
   { 
      $data = array();

      $data['message'] = $_SESSION['LOGIN_MSG'];
      unset($_SESSION['LOGIN_MSG']);
      echo $html = $this->template->parseTemplate(LOGIN_TEMPLATE, $data);
      exit;
   }
   
}
?>