<?php
/**
* Filename   : TicketTag.class.php
*
* @author    : Sheikh Iftekhar <siftekher@gmail.com>
* @project   : Issue tracking system
* @version   : 1.0.0
* @copyright : 
**/


class TicketTag
{
   private $dbLink; 
   private $tagName; 
   private $tagTitle; 
   private $tagId;    


   const EXCEPTION_NO_DB_LINK = 'Please provide database link.';

   public function __construct($params = null)
   {
 
      if (empty($params['db_link']))
      {
          throw new Exception(SELF::EXCEPTION_NO_DB_LINK );
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
   public function getTagId()
   {
      return $this->tagId;
   }
   /**
   * Set Tag Id
   * @access private
   * @param  $tagId
   * @return none
   */
   public function setTagId($tagId = null)
   {
      $this->tagId = $tagId;
   }

   /**
   * set Tag Name
   * @access private
   * @param  $tagName
   * @return none
   */
   public function setTagName($tagName = null)
   {
      $this->tagName = $tagName;
   }

   /**
   * get Tag Name
   * @access private
   * @param  none
   * @return $tagName
   */
   public function getTagName()
   {
      return $this->tagName;
   }
    /**
   * set Tag Title
   * @access private
   * @param  $tagTitle
   * @return none
   */
   public function setTagTitle($tagTitle = null)
   {
      $this->tagTitle = $tagTitle;
   }

   /**
   * get Tag Title
   * @access private
   * @param  none
   * @return $tagTitle
   */
   public function getTagTitle()
   {
      return $this->tagTitle;
   }
   
   /**
   * addTag
   * @access private
   * @param  $tagName, $ticketId
   * @return last_insert_id
   */
   public function addTag($tagName = null, $tagTitle = null, $ticketId = null)
   {
      if(empty($tagName)) return;
      
      $this->setTagName($tagName);
      $this->setTagTitle($tagTitle);
      
      $tagId = $this->saveTag();
      
      if($ticketId != null)
      {
         $params            = array();
         $params['table']   = TICKETS_TAG_TBL;
         $data              = array();
         $data['ticket_id']      = $ticketId;
         $data['tag_id']         = $tagId;
         $data['tagged_by_user'] = $_SESSION['LOGIN_USER']['userId'];
         $params['data']         = $data;

         $this->dbLink->insert($params);
      }
      
      return $tagId;
   }

   /**
   * save Tag
   * @access private
   * @param  none
   * @return $tagId
   */
   private function saveTag()
   {
      $existingTagId = $this->checkExistingTag();
      
      if($existingTagId)
      {
         return $existingTagId;
      }
      else
      {
         $params            = array();
         $params['table']   = TAGS_TBL;
         $data              = array();
         $data['tag']       = $this->tagName;
         $data['tag_title'] = $this->tagTitle;
         $params['data']    = $data;

         return $this->tagId = $this->dbLink->insert($params);
      }
   }

   /**
   * update Tag
   * @access public
   * @param  $tagId, $tagName 
   * @return none
   */
   public function updateTag($tagId = null, $tagName = null, $tagTitle = null)
   {
      $params            = array();
      $params['table']   = TAGS_TBL;
      $params['where']   = " tag_id = " . $tagId;
      $data              = array();
      $data['tag']       = $tagName;
      $data['tag_title'] = $tagTitle;
      
      $params['data'] = $data;
      
      $status = $this->dbLink->update($params);
      
      return (!empty($status)) ? true : false;      
   }

   /**
   * remove tag
   * @access public
   * @param  $tagName 
   * @return none
   */
   public function removeTag($tagId = null)
   {
      if(empty($tagId)) return;
      
      $this->setTagId($tagId);
      
      //$tagId = $this->checkExistingTag();
      
      $this->deleteTag();
      $this->deleteTicketTag();
      
      return true;
   }

   /**
   * check Existing Tag
   * @access private
   * @param  none
   * @return tag id or null
   */
   public function checkExistingTag()
   {
      $query = 'SELECT * FROM '. TAGS_TBL .' WHERE tag = "'. $this->tagName .'"';
      
      $row = $this->dbLink->select($query);
      
      if(count($row))
      {
         foreach($row as $key => $value)
         {
            return $value->tag_id;
         }
      }
      
      return null;
   }

   /**
   * delete Tag
   * @access private
   * @param  $tagId
   * @return none
   */
   private function deleteTag()
   {
      $query = "DELETE FROM " . TAGS_TBL . " WHERE tag_id = ". $this->tagId;
      
      $this->dbLink->query($query);
      
      return true;
   }
   
   /**
   * delete Ticket Tag
   * @access private
   * @param  $tagId
   * @return none
   */
   private function deleteTicketTag()
   {
      $query = "DELETE FROM " . TICKETS_TAG_TBL . " WHERE tag_id = ". $this->tagId;
      
      $this->dbLink->query($query);
      
      return true;
   }
   
   
} // End of TicketTag class

?>