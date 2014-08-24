<?php
/**
* Filename   : TicketTag.class.php
*
* @author    : Sheikh Iftekhar <siftekher@gmail.com>
* @project   : Issue tracking system
* @version   : 1.0.0
**/


class TicketColor
{
   private $dbLink; 
   private $id; 
   private $userId; 
   private $priority;
   private $color;


   const EXCEPTION_NO_DB_LINK = 'Please provide database link.';

   public function __construct($params = null)
   {
 
      if (empty($params['db_link']))
      {
          throw new Exception('Please provide database link.' );
          return;
      }
      
      $this->dbLink = $params['db_link'];
   }

   /**
   * Get Tag Id
   * @access private
   * @param  none
   * @return $tagId
   */
   public function getId()
   {
      return $this->id;
   }
   /**
   * Set Tag Id
   * @access private
   * @param  $tagId
   * @return none
   */
   public function setUserId($userId = null)
   {
      $this->userId = $userId;
   }

   /**
   * set Tag Name
   * @access private
   * @param  $tagName
   * @return none
   */
   public function setPriority($priority = null)
   {
      $this->priority = $priority;
   }

   /**
   * get Tag Name
   * @access private
   * @param  none
   * @return $tagName
   */
   public function getColor()
   {
      return $this->color;
   }
    /**
   * set Tag Title
   * @access private
   * @param  $tagTitle
   * @return none
   */
   public function setColor($color = null)
   {
      $this->color = $color;
   }

   /**
   * get Tag Title
   * @access private
   * @param  none
   * @return $tagTitle
   */
   public function getPriority()
   {
      return $this->priority;
   }
   
   /**
   * addTag
   * @access private
   * @param  $tagName, $ticketId
   * @return last_insert_id
   */
   public function insertColor($userData = array())
   {
      if(empty($userData['user_id'])) return;
      
      $this->setUserId($userData['user_id']);
      $this->setPriority($userData['priority']);
      $this->setColor($userData['color']);
      
      $id = $this->saveColor();
      
      return $id;
   }

   /**
   * save Tag
   * @access private
   * @param  none
   * @return $tagId
   */
   public function saveColor()
   {
      $existingId = $this->checkExistingColor();
      
      if($existingId)
      {
         return $this->updateColor($existingId);
      }
      else
      {
         $params            = array();
         $params['table']   = TICKET_COLOR_SETTINGS_TBL;
         $data              = array();
         $data['user_id']   = $this->userId;
         $data['priority']  = $this->priority;
         $data['color']     = $this->color;
         $params['data']    = $data;

         return $this->id = $this->dbLink->insert($params);
      }
   }

   /**
   * update Tag
   * @access public
   * @param  $tagId, $tagName 
   * @return none
   */
   public function updateColor($id)
   {
      $params            = array();
      $params['table']   = TICKET_COLOR_SETTINGS_TBL;
      $params['where']   = " id = " . $id;

      $data              = array();

      $data['user_id']   = $this->userId;
      $data['priority']  = $this->priority;
      $data['color']     = $this->color;
      
      $params['data'] = $data;
      
      $status = $this->dbLink->update($params);
      
      return (!empty($status)) ? true : false;      
   }

   /**
   * check Existing Tag
   * @access private
   * @param  none
   * @return tag id or null
   */
   public function checkExistingColor()
   {
      $query = 'SELECT * FROM '. TICKET_COLOR_SETTINGS_TBL .' WHERE user_id = "'. $this->userId .'" AND priority = ' . $this->priority;
      
      $row = $this->dbLink->select($query);
      
      if(count($row))
      {
         return $row[0]->id;
      }
      
      return false;
   }
   
   /**
   * Get ticket color from the DB Table
   * @access private
   * @param none
   * return ticket color 
   */
   
   public function getTicketColor($userId, $priority)
   {
      $query = 'SELECT * FROM '. TICKET_COLOR_SETTINGS_TBL .' WHERE user_id = "'. $userId .'" AND priority = ' . $priority;
      
      $row = $this->dbLink->select($query);
      
      return $row;
   }
   
} // End of TicketTag class

?>