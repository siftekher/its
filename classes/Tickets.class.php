<?php
/**
* Filename   : Tickets.class.php
*
* @author    : Sheikh Iftekhar <siftekher@gmail.com>
* @project   : Issue tracking system
* @version   : 1.0.0
* @copyright : 
**/


class Tickets
{
   private $dbLink;
   private $ticketId;
   private $ticketTitle;
   private $priority;
   private $status;
   private $executiveComplaint;
   
   const EXCEPTION_NO_DB_LINK = 'Please provide database link.';
   
   public function __construct($params = null)
   {
      if (empty($params['db_link']))
      {
          throw new Exception(SELF::EXCEPTION_NO_DB_LINK );
          return;
      }
      
      $this->dbLink = $params['db_link'];
      
      
      if(isset($params['ticket_id']))
      {
         $this->ticketId = $params['ticket_id'];
         $this->loadUsingTicketId();
      }
   }
   
   
   /**
   * 
   * @access public
   * @param  none
   * @return none
   */
   public function getTicketId()
   {
      return $this->ticketId;
   }
   

   /**
   * set Ticket Id
   * @access public
   * @param  $ticketId
   * @return none
   */
   public function setTicketId($ticketId = null)
   {
      $this->ticketId = $ticketId;
   }

   /**
   * get Ticket Title
   * @access public
   * @param  none
   * @return $ticketTitle
   */
   public function getTicketTitle()
   {
      return $this->ticketTitle;
   }

   /**
   * set Ticket Title
   * @access public
   * @param  $ticketId
   * @return none
   */
   public function setTicketTitle($ticketTitle = null)
   {
      $this->ticketTitle = $ticketTitle;
   }

   /**
   * get Ticket Title
   * @access public
   * @param  none
   * @return $ticketTitle
   */
   public function getPriority()
   {
      return $this->priority;
   }

   /**
   * set Ticket Title
   * @access public
   * @param  $ticketId
   * @return none
   */
   public function setPriority($priority = null)
   {
      $this->priority = $priority;
   }


   /**
   * get Ticket Title
   * @access public
   * @param  none
   * @return $ticketTitle
   */
   public function getStatus()
   {
      return $this->status;
   }

   /**
   * set Ticket Title
   * @access public
   * @param  $ticketId
   * @return none
   */
   public function setStatus($status = null)
   {
      $this->status = $status;
   }
   
   /**
   * get ExecutiveComplaint
   * @access public
   * @param  none
   * @return $executiveComplaint
   */
   public function getExecutiveComplaint()
   {
      return $this->executiveComplaint;
   }

   /**
   * get ExecutiveComplaint
   * @access public
   * @param  $executiveComplaint
   * @return none
   */
   public function setExecutiveComplaint($executiveComplaint = null)
   {
      $this->executiveComplaint = $executiveComplaint;
   }

   /**
   * load using Ticket id
   * @access private
   * @param  none
   * @return none
   */
   private function loadUsingTicketId()
   {
      $query = "SELECT  ticket_id,
                 title,
                 priority,
                 status,
                 executive_complaint
                 FROM " . TICKETS_TBL .
                 " WHERE ticket_id = " . $this->ticketId;

      $row = $this->dbLink->select($query);

      if(count($row))
      {
         foreach($row as $key => $value)
         {
            $this->ticketId    = $value->ticket_id;
            $this->ticketTitle = $value->title;
            $this->priority    = $value->priority;
            $this->status      = $value->status;
            $this->executiveComplaint = $value->executive_complaint;
         }
      }
   }
   
   /**
   * 
   * @access public
   * @param  none
   * @return none
   */
   public function create()
   {
      $params          = array();
      $params['table'] = TICKETS_TBL;
      $data            = array();
      $data['title']               = $this->ticketTitle;
      $data['priority']            = $this->priority;
      $data['status']              = $this->status;
      $data['executive_complaint'] = $this->executiveComplaint;
      $data['create_date']         = date("Y-m-d H:i:s");

      $params['data'] = $data;

      return $this->dbLink->insert($params);
   }
   
   /**
   * 
   * @access public
   * @param  none
   * @return none
   */
   public function update()
   {
      $params          = array();
      $params['table'] = TICKETS_TBL;
      $params['where'] = " ticket_id = " . $this->ticketId;
      $data            = array();
      $data['title']               = $this->ticketTitle;
      $data['priority']            = $this->priority;
      $data['status']              = $this->status;
      $data['executive_complaint'] = $this->executiveComplaint;
      $data['update_date']         = date("Y-m-d H:i:s");
      
      $params['data'] = $data;
      
      $this->dbLink->update($params);

   }

   /**
   * delete
   * @access public
   * @param  none
   * @return none
   */
   public function delete()
   {
      $params   = array();
      $params['table'] = TICKETS_TBL;
      $params['where'] = " ticket_id = " . $this->ticketId;
      
      $this->dbLink->delete($params);
   }

   /**
   * 
   * @access public
   * @param  none
   * @return none
   */
   public function addDetails($param = array())
   {
      $params          = array();
      $params['table'] = TICKETS_DETAILS_TBL;
      //$params['where'] = " ticket_id = " . $this->ticketId;
      $data            = array();

      $data['ticket_id']   = $this->ticketId;
      $data['subject']     = $param['subject'];
      $data['notes']       = $param['notes'];
      $data['user_id']     = $param['user_id'];
      $data['type']        = $param['type'];
      $data['create_date'] = date("Y-m-d H:i:s");
      
      $params['data'] = $data;

      return $this->dbLink->insert($params);
   }
   
   

   /**
   * 
   * @access public
   * @param  none
   * @return none
   */
   public function addTag($tagName = null, $tagTitle = null)
   {
      $params = array();
      $params['db_link']  = $this->dbLink;

      $ticketTagObj = new TicketTag($params);
      $ticketTagObj->addTag($tagName, $tagTitle, $this->ticketId);
   }

   /**
   * remove Tag
   * @access private
   * @param  none
   * @return none
   */
   public function removeTag()
   {
      $params = array();
      $params['db_link']   = $this->dbLink;
      
      $ticketTagObj = new TicketTag($params);
      //For example
      //$ticketTagObj->removeTag('foobar123');
   }
   
   public function saveTicketSource($sourceId = null)
   {
      $params          = array();
      $params['table'] = TICKET_SOURCES_TBL;
      $data            = array();

      $data['ticket_id']   = $this->ticketId;
      $data['source_id']   = $sourceId;
      
      $params['data'] = $data;

      return $this->dbLink->insert($params);      
   }

   /**
   * 
   * @access private
   * @param  none
   * @return none
   */
   public function attachFile()
   {
      $doc_root = $_SERVER['DOCUMENT_ROOT'];
      //$doc = array_pop(explode('/', $doc_root));
      $attachement_dir =  str_replace(array_pop(explode('/', $doc_root)), 
                          '', $doc_root) . 'its_attachment/';
      
      echo $attachement_dir;
   }

   /**
   * 
   * @access private
   * @param  none
   * @return none
   */
   private function assignResolver()
   {
      
   }
    
   
   public function getTicketSource()
   {
       $query = "SELECT t.*, ts.source_id 
                  FROM " . TICKETS_TBL . " as t, " . TICKET_SOURCES_TBL . " as ts 
                  WHERE t.ticket_id = ts.ticket_id AND t.ticket_id = " . $this->ticketId;
                 
       $row   = $this->dbLink->select($query);
       
       return $row[0];
   }
   
   public function getTicketDetail($type)
   {
        $query  = "SELECT d.*, u.first_name, u.last_name  
                FROM " . TICKETS_DETAILS_TBL . " as d, " . USERS_TBL . " as u 
                WHERE d.user_id = u.user_id AND type = $type
                AND d.ticket_id = " . $this->ticketId;
               
        $row   = $this->dbLink->select($query);
        return $row;
   }
   
   public function getAttchments()
   {
        $query  = "SELECT * FROM " . TICKET_ATTACHMENTS_TBL . " 
                   WHERE deleted = 0 AND ticket_id = " . $this->ticketId;
                   
        $rows = $this->dbLink->select($query);
       
        return $rows;
   }
   
   public function getTag()
   {
        $query  = "SELECT t.*, tt.ticket_id  
                FROM " . TICKETS_TAG_TBL . " as tt , " . TAGS_TBL . " as t 
                WHERE tt.tag_id = t.tag_id 
                AND tt.ticket_id = " . $this->ticketId;
      
        $rows = $this->dbLink->select($query);
     
        return $rows;
   }
   
   public function updateTicketDetails($detailId,$data)
   {
      $params          = array();
      $params['table'] = TICKETS_DETAILS_TBL;
      $params['where'] = " details_id = " . $detailId;
      $params['data']  = $data;
      
      $this->dbLink->update($params);

   }
   
    public function updateTicketTags($param,$data)
    {
       $params            = array();
       $params['table']   = TICKETS_TAG_TBL;
       $params['where']   = " ticket_id = " . $param['ticket_id'] 
                            ." AND tag_id = " . $param['tag_id'] ;
       $params['data']    = $data;
       
       $this->dbLink->update($params);
    } 
   
    public function updateTicketAttachment($attachmentId, $data)
    {
       $params            = array();
       $params['table']   = TICKET_ATTACHMENTS_TBL;
       $params['where']   = " attachment_id = " . $attachmentId; 
       $params['data']    = $data;
       
       $this->dbLink->update($params);
    } 
    
    public function deleteMergeTicket()
    {
       $this->deleteTicketDetail();
       $this->delete();
    }
    
    public function deleteTicketDetail()
    {
       $params   = array();
       $params['table'] = TICKETS_DETAILS_TBL;
       $params['where'] = " ticket_id = " . $this->ticketId;
      
       $this->dbLink->delete($params);
    }                         
}   // End of Tickets class

?>