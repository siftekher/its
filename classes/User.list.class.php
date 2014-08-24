<?php
/*
 * Filename   : User.list.class.php
 * Purpose    : 
 *
 * @author    : Sheikh Iftekhar <siftekher@gmail.com>
 * @project   : Issue tracking system
 * @version   : 1.0.0
 * @copyright : 
 */

require_once('User.class.php');

class UserList
{
   private $db;

  /**
   * Purpose : UserList object initiator
   *
   * @access : public
   * @param  : $db
   * @return : none
   */
   public function __construct($db)
   {
      $this->db = $db;
   }

  /**
   * Purpose : Selects users who have status active
   *
   * @access : public
   * @param  : none
   * @return : array
   */
   public function getActiveUsers()
   {
      $list = array();
      
      $sql  = "SELECT * FROM " . USERS_TBL . " 
               WHERE `status` = " . USER_STATUS_ACTIVE . "";
      $rows = $this->db->select($sql);
      
      foreach($rows as $index => $row)
      {
         $list[] = new User($this->db, $row);
      }
      
      return $list;
   }

  /**
   * Purpose : Selects users based on given status
   *
   * @access : public
   * @param  : $status
   * @return : array
   */
   public function getUsersByStatus($status)
   {
      $list = array();
      
      $sql  = "SELECT * FROM " . USERS_TBL . " 
               WHERE `status` = $status";
      $rows = $this->db->select($sql);
      
      foreach($rows as $index => $row)
      {
         $list[] = new User($this->db, $row);
      }
      
      return $list;
   }

  /**
   * Purpose : Selects sources users for loggedin user
   *
   * @access : public
   * @param  : none
   * @return : array
   */
   public function getUsersFromMySources()
   {
      $sourceid_list = array();
      
      foreach($_SESSION['LOGIN_USER']['sources'] as $index => $my_sources)
      {
         $sourceid_list[$my_sources->source_id] = $my_sources->source_id;
      }
      $sourceid_list = implode(",", $sourceid_list);

      if(!$sourceid_list)
      {
         return array();  
      }

      $list = array();

      $sql  = "SELECT DISTINCT u.user_id, u.first_name, u.last_name FROM " . 
               SOURCE_RESOLVERS_TBL . " as s 
               LEFT JOIN " . USERS_TBL . " u 
               ON (s.user_id = u.user_id) 
               WHERE s.source_id IN ($sourceid_list) AND u.user_id is not null";
               
      $rows = $this->db->select($sql);
      
      if (count($rows) > 0)
      {
         foreach($rows as $index => $row)
         {
            $list[$row->user_id] = $row;
         }

         $list = array_values($list);
      }
      
      return $list;
   }

}
