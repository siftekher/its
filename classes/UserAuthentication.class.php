<?php

/*
 * Filename   : UserAuthentication.class.php
 * Purpose    : Authenticates user via email and 
 *              password for web login session
 *
 * @author    : Sheikh Iftekhar <siftekher@gmail.com>
 * @project   : Issue tracking system
 * @version   : 1.0.0
 * @copyright : 
 */

require_once('User.class.php');

class UserAuthentication 
{
   private $db;
   private $email;
   private $password;
   private $user;
   private $message;

  /**
   * Purpose : UserAuthentication object initiator
   *
   * @access : public
   * @param  : $email - user's login name
   * @param  : $password - user's password
   * @return : void
   */
   public function __construct($db, $email, $password)
   {
      $this->db         = $db;
      $this->email      = $email;
      $this->password   = md5($password);

      $this->login();
   }

  /**
   * Purpose : Authenticate user based on email and password
   *           Static function to get user object
   *
   * @access : public, static
   * @param  : $db
   * @param  : $email
   * @param  : $password
   * @return : array
   */
   public static function authenticate($db, $email, $password)
   {
      $UserAuthentication = new UserAuthentication($db, $email, $password);
      return $UserAuthentication->getUser();
   }

  /**
   * Purpose : Gets authenticated user
   *
   * @access : public
   * @param  : none
   * @return : Object - User.class.php
   */
   public function getUser()
   {
      return $this->user;
   }

  /**
   * Purpose : Checks user into database by email/password
   *
   * @access : private
   * @param  : none
   * @return : void
   */
   private function login()
   {
      $sql        = "SELECT * FROM " . USERS_TBL . " 
                     WHERE `status` = " . USER_STATUS_ACTIVE . " 
                        AND `email` = '{$this->email}' 
                        AND `password` = '{$this->password}'";
                        
      $rows       = $this->db->select($sql);

      $this->user = isset($rows[0]) ? new User($this->db, $rows[0]) : null;

      if(!$this->user)
      {
         $this->message = LOGIN_MSG;
      }
   }

  /**
   * Purpose : Gets message while 
   *           checking user into database
   *
   * @access : public
   * @param  : none
   * @return : string
   */
   public function getMessage()
   {
      return $this->message;  
   }
}