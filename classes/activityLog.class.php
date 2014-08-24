<?php
/*
* Filename   : activityLog.class.php
* Purpose    : 
*
* @author    : Sheikh Iftekhar <siftekher@gmail.com>
* @project   : ITS
* @version   : 1.0.0
*/

class activityLog
{
   private $dbObj;
   private $userId;
   private $ipAddr;

   const EXCEPTION_NO_DB_LINK = 'Please provide database link.';
   const EXCEPTION_NO_USER_ID = 'Please provide user id.';
   
   public function __construct($params = null)
   {      
      if (empty($params['db_obj']))
      {
          throw new Exception(SELF::EXCEPTION_NO_DB_LINK );
          return;
      }
      /*
      if (!isset($_SESSION['LOGIN_USER']['userId']))
      {
          throw new Exception(SELF::EXCEPTION_NO_USER_ID );
          return;
      }
      */

      $this->dbObj  = $params['db_obj'];
      $this->userId = $_SESSION['LOGIN_USER']['userId'];
      $this->ipAddr = sprintf("%u",ip2long($_SERVER['REMOTE_ADDR']));
   }
   
   
   /**
   * Get User Id
   * @access public
   * @param  none
   * @return $userId
   */
   public function getUserId()
   {
      return $this->userId;
   }

   /**
   * set User Id
   * @access public
   * @param  $userId int
   * @return none
   */
   public function setUserId($userId)
   {
      $this->userId = $userId;
   }
   

   /**
   * Get ip Address
   * @access public
   * @param  none
   * @return $ipAddr
   */
   public function getIpAddr()
   {
      return $this->ipAddr;
   }

   /**
   * set ip Address
   * @access public
   * @param  $ipAddr int
   * @return none
   */
   public function setIpAddr($ipAddr)
   {
      $this->ipAddr = $ipAddr;
   }
   

   
   /**
   * save LogIn LogOut
   * @access public
   * @param  $activityType int 

   * @return last insert id
   */
   public function saveLogInLogOut($activityType = null)
   {
      $params          = array();
      $params['table'] = 'activity_log';
      $data            = array();
      $data['log_date']        = date("Y-m-d H:i:s");
      $data['active_user']     = $this->userId;
      $data['activity_type']   = $activityType;
      $data['ipaddr']          = $this->ipAddr;

      $params['data'] = $data;

      return $this->dbObj->insert($params);
   }
   
   /**
   * save Activity Log
   * @access public
   * @param  $activityType int, 
             $ticketId     int,
             $affectedUser int,
             $sourceId     int

   * @return last insert id
   */
   public function saveActivityLog($activityType = null,
                                   $ticketId = null, 
                                   $affectedUser = null,
                                   $sourceId = null)
   {
      $params          = array();
      $params['table'] = 'activity_log';
      $data            = array();
      $data['log_date']        = date("Y-m-d H:i:s");
      $data['active_user']     = $this->userId;
      $data['activity_type']   = $activityType;
      $data['ipaddr']          = $this->ipAddr;
      $data['affected_user']   = $affectedUser;
      $data['affected_ticket'] = $ticketId;
      $data['source_id']       = $sourceId;
      
      $params['data'] = $data;

      return $this->dbObj->insert($params);
   }

} // End of activityLog class

?>
