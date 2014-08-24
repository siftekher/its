<?php
/*
 * Filename   : NewTicketController.class.php
 * Purpose    : It is used to add a new ticket
 *
 * @author    : Sheikh Iftekhar <siftekher@gmail.com>
 * @project   : Issue Tracking System
 * @version   : 1.0
 */
require_once('Tickets.class.php');
class NewTicket
{
   private $db;
   private $template;
   private $params;
   private $cmdList;

   public function __construct($params)
   {
      $this->db       = $params['db_link'];
      $this->cmdList  = $params['cmdList'];
      $this->template = new Template();
   }
   

   public function run()
   {
      $cmd = $this->cmdList[1];
      $userId = $_SESSION['LOGIN_USER']['userId'];

      if($cmd != 'upload')
      {
         // Redirects to Login controller if user is not logged in
         if($userId == null)
         {
            header('location: '.SUPER_CONTROLLER_URL_PREFIX.'Login');
            exit;
         }
      }


      switch ($cmd)
      {
         case 'upload'    : $screen = $this->uploadAttachment();   break;
         case 'rm_upload' : $screen = $this->removeUploadedFile(); break;
         case 'add'       : $screen = $this->addNewTicket();       break;       
         default          : $screen = $this->showTicketEditor();
      }

      $data            = array();
      $data['topnav']  = 'ticket';
      $data['tagList'] = Utils::getAllTagList($this->db);

      $userList = new UserList($this->db);
      $data['sourceUser']  = $userList->getUsersFromMySources();
      $data['source_id']   = $_SESSION['source_id'];
      $data['source_name'] = Utils::getSourceName($this->db , $_SESSION['source_id']);
      echo $this->template->createScreen($screen, $data);
      exit;
   }
   
  

 /**
   * function showTicketEditor - show add new ticket editor
   *
   * @param  none
   * @return  string - html content output
   */
   function showTicketEditor($msg=null)
   {
      $data        = array();
      $sourceArray = array();
      $isExecutive        = $this->isExecutiveUser();
      $sourceSessionArray = $_SESSION['LOGIN_USER']['sources'];
      if(count($sourceSessionArray) > 1)
      {
         foreach($sourceSessionArray as $key => $source)
         {
            $sourceArray[$source->source_id] = stripslashes($source->name);
         }
      }
      else if(count($sourceSessionArray) == 1)
      {
         /*
           Since we have only one source. So we need not the array
           for source option list. We need just source id
         */
          $sourceArray = $sourceSessionArray[0]->source_id;
      }

      $data['message']      = $msg;
      $data['is_executive'] = $isExecutive;
      $data['source_array'] = $sourceArray;
      
      return $this->template->parseTemplate(NEW_TICKET_EDITOR,$data);
   }

 /**
   * function isExecutiveUser - check user level for executive
   *
   * @param  none
   * @return  boolean -true/false
   */
   function isExecutiveUser()
   {
      $userTypeArr = $_SESSION['LOGIN_USER']['user_types'];
      if($userTypeArr)
      {
         foreach($userTypeArr as $userTypeCode)
         {
             if($GLOBALS['SOURCE_RESOLVERS_TYPE'][$userTypeCode] == 'executive')
             {
               return true;
             }
         }
      }
      return false;
   }

 /**
   * function addNewTicket - save the ticket
   *
   * @param  none
   * @return  boolean -add new ticket editor
   */
   function addNewTicket()
   {
      $ticketId = $this->addOnTicket();
      $this->addOnTicketSource($ticketId);
      $this->addOnTicketDetails($ticketId);
      $this->addTag($ticketId);      

      $msg = (!empty($ticketId)) ? TICKET_SAVE_MSG : TICKET_SAVE_FAIL_MESSAGE ;

      if(!empty($ticketId))
      {
         header("location: /its/run.php/Ticket");         
      }
      else
      {
         return $this->showTicketEditor($msg);
      }      
   }
   
   
  /**
   * Purpose : Adds tag to a ticket 
   *           via jQuery/Ajax
   *
   * @access : private
   * @param  : none
   * @return : void - prints string 
   *           in json format
   */
   private function addTag($ticketId = null)
   {
      $params              = array();
      
      $params['db_link']   = $this->db;
      $params['ticket_id'] = $ticketId;
      
      $data = array();
      $tagTitle  = stripslashes(trim($_REQUEST['tag']));
      $tag  = Utils::sanitize($tagTitle);
      
      $Tickets = new Tickets($params);
      $Tickets->addTag($tag, $tagTitle);      
   }

   /**
   * function addOnTicket - insert on ticket table
   *
   * @param  none
   * @return  int - ticket id
   */
   function addOnTicket()
   {
       $executiveComplaint = $_REQUEST['executive_complaint'] ? 1 :0;

       $params             = array();
       $params['db_link']  = $this->db;
       $ticketsObj         = new Tickets($params);

       $ticketsObj->setTicketTitle(stripslashes(trim($_REQUEST['title'])));
       $ticketsObj->setPriority($_REQUEST['priority']);
       $ticketsObj->setStatus(0);
       $ticketsObj->setExecutiveComplaint($executiveComplaint);

       $ticketId = $ticketsObj->create();

       return $ticketId;

   }

 /**
   * function addOnTicketDetails - insert on ticket details table
   *
   * @param  none
   * @return  int - details id
   */
   function addOnTicketDetails($ticketId)
   {
      $params              = array();
      $data                = array();
      $params['db_link']   = $this->db;
      $params['ticket_id'] = $ticketId;
      $data['subject']     = stripslashes(trim($_REQUEST['title']));
      $data['notes']       = stripslashes(trim($_REQUEST['notes']));
      $data['user_id']     = $_SESSION['LOGIN_USER']['userId'];
      $data['type']        = 1;
      $ticketsObj          = new Tickets($params);
      $detailsId           = $ticketsObj->addDetails($data);

      if($detailsId)
      {
         $files = $this->getUploadedFiles();
         $this->saveAttachments($files, $ticketId, $detailsId);
      }

      return $detailsId;
   }

 /**
   * function addOnTicketSource - insert on ticket-source table
   *
   * @param  none
   * @return  none
   */
   function addOnTicketSource($ticketId)
   {
      $params              = array();
      $params['db_link']   = $this->db;
      $params['ticket_id'] = $ticketId;
      $ticketsObj          = new Tickets($params);

      $ticketsObj->saveTicketSource($_REQUEST['source']);
   }

 /**
   * Purpose : Uploads attachment file
   *           via jQuery/Ajax uplodify
   *
   * @param  : none
   * @return : void - prints string -
   *           filename:path:size
   *           for uploaded file
   */
   function uploadAttachment()
   {
      $uid = $this->param[2];
      $src = $_FILES['Filedata']['tmp_name'];
      $dst = TEMP_DIR . '/' . $uid . $_FILES['Filedata']['name'];

      if(@move_uploaded_file($src, $dst))
      {
         $data = "{$_FILES['Filedata']['name']}:$dst:{$_FILES['Filedata']['size']}";
         echo $data;
      }
      exit;
   }

    /**
   * Purpose : Removes Uploaded attachment file
   *           via jQuery/Ajax uplodify
   *
   * @param  : none
   * @return : none
   */
   private function removeUploadedFile()
   {
      if(!isset($_REQUEST['file']))
      {
         exit;
      }

      list($file, $src, $size) = explode(':', $_REQUEST['file']);

      if(file_exists($src))
      {
         @unlink($src);
      }
      exit;
   }

   /**
   * Purpose : Gets uploaded files
   *           from encoded string
   *
   * @access : private
   * @param  : none
   * @return : array - files information
   */
   private function getUploadedFiles()
   {
      $list = array();

      if(isset($_REQUEST['uploaded_files']))
      {
         $files = explode('::', $_REQUEST['uploaded_files']);
         foreach($files as $index => $item)
         {
            list($name, $src, $size) = explode(':', $item);

            $list[] = array(
               'name'   => $name,
               'src'    => $src,
               'size'   => $size
            );
         }
      }

      return $list;
   }

     /**
   * Purpose : Save uploaded files and moves file to
   *           attachment directory
   *
   * @access : private
   * @param  : $files - array
   * @param  : $ticket_id - int -
   *           ticket id for the attachments
   * @param  : $details_id - int -
   *           detail id for the attachments
   * @return : void
   */
   private function saveAttachments($files, $ticket_id, $details_id)
   {
      ## not clear to me. - Mashuk
      $sql  = "SELECT *
               FROM " . TICKET_SOURCES_TBL . "
               WHERE ticket_id = $ticket_id";
      try
      {
         $rows = $this->db->select($sql);
      }
      catch(Exception $Exception){}

      if(!isset($rows[0]))
      {
         return false;
      }

      $source_id  = $rows[0]->source_id;

      $dst        = ATTACHMENT_DIR;
      $dst        = "$dst/$source_id";
      if(!is_dir($dst))
      {
         @mkdir($dst);
      }

      $dst = "$dst/$ticket_id";
      if(!is_dir($dst))
      {
         @mkdir($dst);
      }

      foreach($files as $index => $file)
      {
         $original_file_name = $file['name'];
         $src  = $file['src'];
         $size = $file['size'];

         $edited_file_name = strtolower(trim($original_file_name));
         $parts = explode('.', $edited_file_name);

         $ext_part_pos = count($parts)-1;
         $ext_part = $parts[$ext_part_pos];
         unset($parts[$ext_part_pos]);

         $name_part = implode('.', $parts);
         $name_part = Utils::sanitize($name_part);

         if(@copy($src, "$dst/$name_part.$ext_part"))
         {
            $data             = array();
            $data['table']    = TICKET_ATTACHMENTS_TBL;
            $data['data']     = array(
               'ticket_id'          => $ticket_id,
               'details_id'         => $details_id,
               'original_filename'  => $original_file_name,
               'server_fqpn'        => "$dst/$name_part.$ext_part",
               'deleted'            => 0
               );
            try
            {
               $this->db->insert($data);
            }
            catch(Exception $Exception){}

            @unlink($src);
         }

      }
   }
}
?>