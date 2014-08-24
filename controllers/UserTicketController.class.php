<?php
/*
 * Filename   : UserTicketController.class.php
 * Purpose    : 
 * @author    : Sheikh Iftekhar <siftekher@gmail.com>
 * @project   : Issue tracking system
 * @version   : 1.0.0
 */


require_once('Tickets.class.php');

class UserTicket
{
   private $dbLink;
   private $templateObj;
   private $cmdList;
   
   public function __construct($param)
   {
      $this->dbLink = $param['db_link'];
      $this->templateObj = new Template();
      $this->cmdList = $param['cmdList'];
   }

   function run()
   {
      $cmd = $this->cmdList[1];
      //$this->cmdList[2]; UserId
      //$this->cmdList[3]; Ticket Number
      //$this->cmdList[4]; AuthKey

      switch($cmd)
      {
         case 'create' : $screen = $this->showTicketEditor(); break;
         case 'update' : $screen = $this->showTicketEditor(); break;
         case 'delete' : $screen = $this->addDetail();        break;
         case 'close'  : $screen = $this->showTicketEditor(); break;
         case 'assign' : $screen = $this->assignTicket();     break;
         case 'list'   : $screen = $this->listTicket();       break;
         
      }

      /*
      $data            = array();
      $data['tagList'] = Utils::getAllTagList($this->dbLink);

      echo $this->templateObj->createScreen($screen, $data);
      exit;
      */
   }
   
   
   private function showTicketEditor()
   {
      $userId   =  $this->cmdList[2];
      $ticketId = $this->cmdList[3];
      $authKey  = $this->cmdList[4];
      
      //Check Auth Key, Return Otherwise

      if(! $this->verifyAuthKey($userId, $ticketId, $authKey))
      {
         echo 'WRONG';
         exit;
      }

      $userId =  $this->cmdList[2];
      //$userId = $this->getUserIdByTicketId($ticketId);

      $this->putUserInSession($userId);

      header('location: '.SUPER_CONTROLLER_URL_PREFIX.'Ticket/details/'.$ticketId);

   }

   private function verifyAuthKey($userId, $ticketId, $authKey)
   {
      //Varify The authKey
      $query = "SELECT * FROM ticket_auth_keys
                WHERE ticket_id = " . $ticketId . 
               " AND user_id = " . $userId . 
               " AND auth_key = '" . $authKey . "'";

      $result = $this->dbLink->select($query);

      if(count($result))
      {
         return true;
      }
      else
      {
         return false;
      }
   }
   
   private function putUserInSession($userId = null)
   {
      $sql        = " SELECT * FROM " . USERS_TBL .
                    " WHERE status = " . USER_STATUS_ACTIVE .
                    " AND user_id = " . $userId;

      $rows       = $this->dbLink->select($sql);

      $userObj = new User($this->dbLink, $rows[0]);

      $_SESSION['LOGIN_USER']        = $userObj->getData();
      $_SESSION['SOURCES_ID_STRING'] = Utils::getSourcesIdAsString();
   }
   
   private function getUserIdByTicketId($ticketId = null)
   {
      $query = "SELECT AUS.user_id FROM authorized_sources AS AUS 
                LEFT JOIN ticket_sources AS TS on(AUS.source_id = TS.source_id)
                WHERE TS.ticket_id = " . $ticketId;

      $result = $this->dbLink->select($query);

      return $result[0]->user_id;
   }
   
   private function assignTicket()
   {
      //$this->cmdList[2]; Ticket Number
      //$this->cmdList[3]; staff

   }






}
?>