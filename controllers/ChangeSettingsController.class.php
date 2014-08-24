<?php

/*
 * Filename   : ChangeSettingsController.class.php
 * Purpose    : 
 * @author    : Sheikh Iftekhar <siftekher@gmail.com>
 * @project   : Issue tracking system
 * @version   : 1.0.0
 */

require_once('TicketColor.class.php');

class ChangeSettings
{
   private $db;
   private $template;
   private $param;

   public function __construct($param)
   {
      $this->db = $param['db_link'];
      $this->template = new Template();
   }
   
   public function run()
   {   
      $userId = $_SESSION['LOGIN_USER']['userId'];

      if($userId == null)
      {
         header('location: '.SUPER_CONTROLLER_URL_PREFIX . 'Login');
         exit;
      }      
      $cmd = $_REQUEST['cmd'];

      switch ($cmd)
      {
         case 'save_user_settings'   : $contents = $this->saveTicketUserSettings();
         default                     : $contents = $this->showTicketUserSettings();
        
      }

      $data            = array();
      $data['topnav']  = 'changesettings';
      $data['tagList'] = Utils::getAllTagList($this->db);
      
      $userList = new UserList($this->db);
      $data['sourceUser']  = $userList->getUsersFromMySources();
      $data['source_id']   = $_SESSION['source_id'];
      $data['source_name'] = Utils::getSourceName($this->db , $_SESSION['source_id']);
      echo $this->template->createScreen($contents, $data);
      exit;
   }

   
  /**
   * show user's existing  settings
   *
   * @access public
   * @param  none
   * @return none
   */
   
   function showTicketUserSettings()
   {
     $data = array();
     $user_id = $_SESSION['LOGIN_USER']['userId'];

     
     $sql             = "SELECT * FROM " . TICKET_USER_SETTINGS_TBL . " WHERE `user_id` = $user_id";
     $ticketUserData  = $this->db->select($sql);

     $sql             = "SELECT * FROM " . USERS_TBL . " WHERE `user_id` = $user_id";
     $userData        = $this->db->select($sql);

     $sql             = "SELECT * FROM " . TICKET_COLOR_SETTINGS_TBL . " WHERE `user_id` = $user_id AND priority = 3";
     $critical_color_settings = $this->db->select($sql);
     if(!empty($critical_color_settings[0]))
     {
        $data['critical_color_settings'] = $critical_color_settings[0];
     }
     else
     {
        
        $data['critical_color_settings']->color = $GLOBALS['TICKET_COLOR_SETTINGS']['critical'];
     }

     $sql                 = "SELECT * FROM " . TICKET_COLOR_SETTINGS_TBL . " WHERE `user_id` = $user_id AND priority = 2";
     $high_color_settings = $this->db->select($sql);
     if(!empty($high_color_settings[0]))
     {
        $data['high_color_settings'] = $high_color_settings[0];
     }          
     else
     {
        $data['high_color_settings']->color = $GLOBALS['TICKET_COLOR_SETTINGS']['high'];
     }

     $sql             = "SELECT * FROM " . TICKET_COLOR_SETTINGS_TBL . " WHERE `user_id` = $user_id AND priority = 1";
     $normal_color_settings = $this->db->select($sql);
     if(!empty($normal_color_settings[0]))
     {
        $data['normal_color_settings'] = $normal_color_settings[0];
     }
     else
     {
        $data['normal_color_settings']->color = $GLOBALS['TICKET_COLOR_SETTINGS']['normal'];
     }

     $sql             = "SELECT * FROM " . TICKET_COLOR_SETTINGS_TBL . " WHERE `user_id` = $user_id AND priority = 0";
     $low_color_settings = $this->db->select($sql);
     if(!empty($normal_color_settings[0]))
     {
        $data['low_color_settings'] = $low_color_settings[0];
     }
     else
     {
        $data['low_color_settings']->color = $GLOBALS['TICKET_COLOR_SETTINGS']['low'];
     }
     
     $data['ticket_user_settings_info'] = $ticketUserData[0];
     $data['user_settings_info']       = $userData[0];     
     $data['ticket_color_settings']   = $ticketColorData;
     $data['SITE_URL']                = SITE_URL; 
     
     
     if(isset($_SESSION['TICKET_USER_SETTINGS_SAVE_MSG']))
     {        
        $data['message'] = $_SESSION['TICKET_USER_SETTINGS_SAVE_MSG'];
        unset($_SESSION['TICKET_USER_SETTINGS_SAVE_MSG']);
     }

     return $this->template->parseTemplate(TICKET_USER_SETTINGS_TEMPLATE, $data);
   }
   
   //Method Add By Pushan  
   private function getTicketUserSettings($userId = null)
   {
      $sql = "SELECT * FROM " . TICKET_USER_SETTINGS_TBL . 
             " WHERE `user_id` = " . $userId;

      $result = $this->db->select($sql);
      
      $data = array();
      if($result)
      {
         foreach($result as $key => $value)
         {
            foreach($value as $key1 => $value1)
            $data[$key1] = $value1;
         }
      }
      
      return $data;
   }
   
  /**
   * save user's settings
   *
   * @access public
   * @param  none
   * @return none
   */
   function saveTicketUserSettings()
   {
      $user_id = $_SESSION['LOGIN_USER']['userId'];
      
      $ticketUserSettings   = array();
      $userSettings         = array();
      $ticketColorSettings  = array();
      
      $ticketUserSettings['enable_issues_created_by_me']       = $_REQUEST['enable_issues_created_by_me'];
      $ticketUserSettings['enable_issues_assigned_to_me']      = $_REQUEST['enable_issues_assigned_to_me'];
      $ticketUserSettings['enable_issues_has_my_involvement']  = $_REQUEST['enable_issues_has_my_involvement'];
      $ticketUserSettings['enable_issues_submitted_by_anyone'] = $_REQUEST['enable_issues_submitted_by_anyone'];
      $ticketUserSettings['show_issues_per_page']              = $_REQUEST['show_issues_per_page'];
      $ticketUserSettings['enable_auto_reminder']              = $_REQUEST['enable_auto_reminder'];      
      $ticketUserSettings['enable_rss_feed']                   = $_REQUEST['enable_rss_feed'];
      $ticketUserSettings['include_rss']                       = $_REQUEST['include_rss'];
      $ticketUserSettings['number_of_issues_for_rss']          = $_REQUEST['number_of_issues_for_rss'];
      $ticketUserSettings['show_tag_type']                     = $_REQUEST['show_tag_type'];
      $ticketUserSettings['no_of_shown_tags']                  = $_REQUEST['no_of_shown_tags'];            

      $ticketColorSettings['critical_color_choice']             = $_REQUEST['critical_color_choice'];            
      $ticketColorSettings['high_color_choice']                 = $_REQUEST['high_color_choice'];            
      $ticketColorSettings['normal_color_choice']               = $_REQUEST['normal_color_choice'];            
      $ticketColorSettings['low_color_choice']                  = $_REQUEST['low_color_choice'];            
      
      $userSettings['email']   = $_REQUEST['email_address'];
      $userNewPassword         = $_REQUEST['new_password'];
      $confirmUserNewPassword  = $_REQUEST['confirm_new_password'];
      
      if($userNewPassword && $confirmUserNewPassword)
      {
         if($userNewPassword == $confirmUserNewPassword)
         {
            $userSettings['password'] = md5($userNewPassword);         
         }
      }
      
      $params['table']  = TICKET_USER_SETTINGS_TBL;
      $params['where']  = "user_id = $user_id";
      $params['data']   = $ticketUserSettings;
      
      if(!empty($_REQUEST['ticket_user_settings_id']))
      {
         $this->db->update($params);
      }
      else
      {
         unset($params['where']);
         $params['data']['user_id'] = $_SESSION['LOGIN_USER']['userId'];
         $this->db->insert($params);
      }      
      
      
      //update user settings in user table
      $this->updateUserSettings($user_id, $userSettings);
      
      //update Color settings in user table
      $this->saveTicketColorSettings($user_id, $ticketColorSettings);
      
      $_SESSION['TICKET_USER_SETTINGS_SAVE_MSG'] = TICKET_USER_SETTINGS_SAVE_MSG;            
      $_SESSION['LOGIN_USER']['settings'] = $this->getTicketUserSettings($user_id);

      header('location: '.SUPER_CONTROLLER_URL_PREFIX . 'ChangeSettings');                      
      exit;                    
   }
   
  /**
   * update user's settings in user table
   * @access public
   * @param  user_id
             user settings array
   * @return none
   */
   function updateUserSettings($user_id, $userSettings)
   {
      $params['table']  = USERS_TBL;
      $params['where']  = "user_id = $user_id";
      $params['data']   = $userSettings;
      
      $this->db->update($params);
      
   }
   
  /**
   * update user's ticket color settings
   * @access public
   * @param  user_id
             priority
             color
   * @return none
   */
   
   function saveTicketColorSettings($user_id, $ticketColorSettings)
   {
      $ticketColor = new TicketColor(array('db_link' => $this->db));
      $params = array();
      $params['user_id'] = $user_id;

      //priority [0=Low, 1 = Normal, 2 = High, 3= Critical]
      $params['priority'] = 3;
      $params['color'] = $ticketColorSettings['critical_color_choice'];
      $ticketColor->insertColor($params);
      
      //priority [0=Low, 1 = Normal, 2 = High, 3= Critical]
      $params['priority'] = 2;
      $params['color'] = $ticketColorSettings['high_color_choice'];
      $ticketColor->insertColor($params);

      //priority [0=Low, 1 = Normal, 2 = High, 3= Critical]
      $params['priority'] = 1;
      $params['color'] = $ticketColorSettings['normal_color_choice'];
      $ticketColor->insertColor($params);

      //priority [0=Low, 1 = Normal, 2 = High, 3= Critical]
      $params['priority'] = 0;
      $params['color'] = $ticketColorSettings['low_color_choice'];
      $ticketColor->insertColor($params);      
   }   

}
?>