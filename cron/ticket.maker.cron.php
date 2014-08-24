<?php
/**
*  FileName : ticket.maker.cron.php
*  Purpose  : 
*
*  @author  : Sheikh Iftekhar <siftekher@gmail.com>
*  @project :
*  @version : 1.0.0
**/

require_once('cron.config.php');
require_once(CRON_CLASS_DIR.'/CronJob.class.php');
require_once(EXT_DIR.'/phpmailer/class.phpmailer.php');
require_once('DB.class.php');
require_once('POPMailClient.class.php');
require_once('Message.class.php');
require_once('Attachment.class.php');
require_once('Email.class.php');
require_once('Tickets.class.php');
require_once('TicketAuth.class.php');
require_once('Email.class.php');


class TicketMaker extends CronJob
{
   private $dbLink;

   public function __construct()
   {
      parent:: __construct();
      parent:: setJobID('ticket_maker_cron');

      try
      {
         $this->dbLink = $this->getDBConnection();
      }
      catch(Exception $e)
      {
         die($e->getMessage());
      }
   }
   
   public function run()
   {

      if($this->checkCurrentCronStatus())
      {
         echo "Cron is running.\n";
         return;
      }
      else
      {
         $this->setCronStatusOn();
      }

      $this->readNewMail();

   }

   /**
   * read New Mail
   * @access private
   * @param  none
   * @return none
   */
   private function readNewMail()
   {
      $sourceList = $this->getActiveSourceUser();

      foreach($sourceList as $key => $source)
      {
         echo "Checking email account: {$source->pop_email} ... for new issues .. \n";

         $params  = array('username'    => $source->pop_email, 
                          'password'    => $source->pop_password, 
                          'mailServer'  => $source->pop_server,
                          'serverType'  => SERVERTYPE,
                          'port'        => SERVERPORT,
                          'folder'      => FOLDER_PREFIX
                         );

         //$sourceId = $source->source_id;

         $popClient = new POPMailClient($params);
         
         $messages = $popClient->getNewMail();

         foreach($messages as $thisMsg)
         {
            $subject = $thisMsg->getHeader(MSG_HEADER_SUBJECT);
            $from    = $thisMsg->getFromAddress();
            $to      = $thisMsg->getToAddress(); 
            
            $userId   = $this->getUserId($from);
            //$sourceId = $this->getSourceId($userId);
            $sourceId = $source->source_id;
            
            if(empty($sourceId))
            {
               $this->replyToUnknownUser($from);
               continue;
            }
            
            if($userId == null  && ($this->getUserStatus($userId) != 1))
            {
               $this->replyToUnknownUser($from);
               continue;
            }

            $ticketId = $this->parseSubject($subject);
            $action   = $this->getListingAction($subject);

            echo "Found mail from $from to $to subject: $subject ticket: $ticketId action=$action\n"; 

            if($action != null)
            {
               $actionType = $this->makeQueryDate($action);

               if($actionType['action'] == 1)
               {
                  $ticketList = $this->getAllTicketList($sourceId);
               }
               elseif($actionType['action'] == 2 ||
                      $actionType['action'] == 3 ||
                      $actionType['action'] == 4 ||
                      $actionType['action'] == 5
                     )
               {
                  $ticketList = $this->getTicketListByDate($sourceId, 
                                                           $actionType['startDate'],
                                                           $actionType['endDate']
                                                          );
               }
               elseif($actionType['action'] == 6 ||
                      $actionType['action'] == 7 ||
                      $actionType['action'] == 8 
                     )
               {
                  $ticketList = $this->getTicketListByStatus($sourceId, $actionType['action']);
               }
               else
               {
                  $ticketList = $this->getOtherTypeTicketList($sourceId);
               }
               
               if(count($ticketList))
               {
                  foreach($ticketList as $key => $value)
                  {
                     $value->authKeys = $this->getTicketAuthKeys($userId, $value->ticket_id);
                  }
               }
               
               //MJK
               $this->mailToAuthorizedSource($from, $ticketList, $userId, $source);
               
               $this->makeEmailAsRead($thisMsg, $sourceId);
               
               $notes = strip_tags($thisMsg->getTextBody(FLAG_EXCLUDE_ATTACHMENT));
               $attachments  = $thisMsg->getAttachments($sourceId, $ticketId);

            }
            elseif(($ticketId != null) && $this->checkExistingTicket($ticketId))
            {
               $action = $this->getAction($subject);
               
               if($action == 'delete' || $action == 'close')
               {
                  $this->saveTicketStatus($ticketId, $action);
                  $this->updateTicketAssignment($ticketId, $userId, $action);
                  $this->saveTicketHistory($ticketId, $action, $userId);
                  
                  $this->makeEmailAsRead($thisMsg, $sourceId);
               }
               else
               {
                   // User is adding details to an existing ticket
                   $action = 'update_ticket';
               }
            }
            else
            {
               //save as new ticket
               $ticketId = $this->saveNewTicket($subject, 0, 0, 0);
               $action = 'new_ticket';
            }
            
            //echo 'action : => ' .$action; exit;
         
            if ($action == 'new_ticket' || $action == 'update_ticket')
            {
            
               $isHTML  = $thisMsg->isHTML(); 
               if($isHTML)
               {
                  $notes = strip_tags($thisMsg->getHTMLBody(FLAG_EXCLUDE_ATTACHMENT));
               }
               else 
               {
                  $notes = strip_tags($thisMsg->getTextBody(FLAG_EXCLUDE_ATTACHMENT));
               }
         
               if($action == 'update_ticket')
                  $updateTicketStatus = 0;
               else if($action == 'new_ticket')
                  $updateTicketStatus = 1;
               
               $detailsId = $this->addDetails($ticketId, $subject, $notes, $userId, $updateTicketStatus );
               
               if($action == 'new_ticket')
               {
                  $attachments  = $thisMsg->getAttachments($sourceId, $ticketId);
                  foreach($attachments as $thisAttachment)
                  {
                     $filename = $thisAttachment->getFilename();
                     $filePath = $thisAttachment->getFQPN();  
                     if($filename != null)
                     {
                        $this->saveTicketAttachments($ticketId, $detailsId, $filename, $filePath);
                     }
                  }
                  
                  $this->saveTicketSource($ticketId, $sourceId);
                  
                  $autoAssignStaff = $this->getAutoAssignStaff($sourceId);
                  
                  if($autoAssignStaff == 1)
                  {
                     $this->assignTicketToStaff($ticketId, $sourceId);
                  }
               }
               
               $authKeys = $this->getTicketAuthKeys($userId, $ticketId);
               
               $this->sendReplyToSource($from, $subject, $ticketId, $authKeys, $userId, $source, $action);
               
            }

            //$sourceConfig = $this->getSourceConfig($userId);
         
         }
      }
      
      return;
   }
   
   private function getActiveSourceUser()
   {
      $query = "SELECT * FROM source_settings WHERE status = 1";
      
      return $this->dbLink->select($query);
   }

   /**
   * reply To Unknown User
   * @access private
   * @param  $toEmailAddress 
   * @return none
   */
   private function replyToUnknownUser($toEmailAddress = null)
   {
      if(empty($toEmailAddress))  return;
      
      $params   = array();
      $params['db_obj']   = $this->dbLink;
      $params['app_name'] = 'TicketMakerCron';
      
      try
      {
         $mailObj = new Email($params);
         
         $mailData  = array();
         $mailData['to'] = $toEmailAddress;
         $mailData['subject'] = UNKNOWN_SOURCE_EMAIL_SUBJECT;
         $mailData['body']    = $this->createPage(UNKNOWN_SOURCE_EMAIL_REPLY_TEMPLATE);
         
         $mailObj->send($mailData);
      }
      catch(Exception $e)
      {
         echo $e->getMessage();
      }
      
      return true;
   }
   
   /**
   * read New Mail
   * @access private
   * @param  none
   * @return none
   */
   private function getUserId($sourceEmailId = null)
   {
      if(empty($sourceEmailId)) return;
      
      $query = "SELECT user_id FROM " . USERS_TBL . 
               " WHERE email = '" . $sourceEmailId ."'";
      
      $result = $this->dbLink->select($query);
      
      return $result[0]->user_id;
   }
   /**
   * read New Mail
   * @access private
   * @param  none
   * @return none
   */
   private function getSourceId($userId = null)
   {
      if(empty($userId)) return;
      
      $query = "SELECT source_id FROM " . AUTHORIZED_SOURCES_TBL . 
               " WHERE user_id =" . $userId;
      
      $result = $this->dbLink->select($query);
      
      return $result[0]->source_id;
   }
   /**
   * parse Subject
   * @access private
   * @param  $subject
   * @return $matches[1]
   */
   private function parseSubject($subject = null)
   {
      if(empty($subject)) return;

      preg_match('[\s*#(\d+)#\s*]', $subject, $matches);
      
      return $matches[1];
   }

   /**
   * get Action
   * @access private
   * @param  $subject
   * @return $action - if success | null
   */
   private function getAction($subject = null)
   {
      if(empty($subject)) return;
      
      preg_match('[delete|close]', strtolower($subject), $action);
      
      return $action[0];
   }
   
   private function getTicketId()
   {
      
   }
   /**
   * get Source Config
   * @access private
   * @param  $userId
   * @return mixed Array
   */
   private function getSourceConfig($userId = null)
   {
      if(empty($userId)) return;
      
      $query = "SELECT * FROM " . TICKET_USER_SETTINGS_TBL . 
               " WHERE user_id = ". $userId;
      
      return $this->dbLink->select($query);
   }

   /**
   * get Auto Assign Staff
   * @access private
   * @param  $sourceId
   * @return auto_assign_staff
   */
   private function getAutoAssignStaff($sourceId = null)
   {
      if(empty($userId)) return;
      
      $sourceId = "SELECT auto_assign_staff FROM " . SOURCE_SETTINGS_TBL . 
                  " WHERE source_id = ". $sourceId;
      
      $result = $this->dbLink->select($query);
      
      return $result[0]->auto_assign_staff;
   }

   /**
   * assign Ticket To Staff
   * @access private
   * @param  $ticketId, $sourceId 
   * @return none
   */
   private function assignTicketToStaff($ticketId = null, $sourceId = null)
   {
      $userList = array();
      $userList = $this->getAvailableUser($sourceId);
      
      if(empty($userList))
      {
         $supervisor = $this->getSourceSupervisor($sourceId);
         return;
      }
      
      $userId = $userList[rand(0, count($userList)-1)];
      
      $this->saveTicketAssignment($ticketId, $userId);
      
      return;
   }

   /**
   * get Available User
   * @access private
   * @param  $sourceId
   * @return $freeUserList
   */
   private function getAvailableUser($sourceId)
   {
      $query = " SELECT user_id FROM " . SOURCE_RESOLVERS_TBL . 
               " WHERE source_id = $sourceId AND resolver_type = 1";
      
      $result = $this->dbLink->select($query);
      
      $sourceUserList = array();
      foreach($result as $key => $value)
      {
         $sourceUserList[] = $value->user_id;
      }

      $query = "SELECT SR.user_id FROM " . SOURCE_RESOLVERS_TBL . 
               " AS SR LEFT JOIN " . TICKET_ASSIGNMENTS_TBL . 
               " AS TA on(SR.user_id = TA.user_id) WHERE SR.source_id = $sourceId 
                AND (TA.completion_date = null OR TA.closed_date = null 
                OR TA.deleted_date = null)";

      $row = $this->dbLink->select($query);
      
      $assignUserList = array();
      foreach($row as $key => $value)
      {
         $assignUserList[] = $value->user_id;
      }
      
      $freeUserList = array();
      $freeUserList = array_diff($sourceUserList, $assignUserList);
      
      return $freeUserList;
   }

   /**
   * check Existing Ticket
   * @access private
   * @param  $ticketId
   * @return boolean -- true | false
   */
   private function getSourceSupervisor($sourceId = null)
   {
      $query = "SELECT user_id FROM " . SOURCE_RESOLVERS_TBL . 
               " WHERE source_id = $sourceId AND resolver_type = 2";
      
      $result = $this->dbLink->select($query);
      
      return $rersult[0]->user_id;
   }

   /**
   * check Existing Ticket
   * @access private
   * @param  $ticketId
   * @return boolean -- true | false
   */
   private function checkExistingTicket($ticketId = null)
   {
      if(empty($ticketId)) return;
      
      $query = "SELECT * FROM " . TICKETS_TBL . 
               " WHERE ticket_id = " . $ticketId;
      
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
   
   /**
   * add Details of a Ticket
   * @access private
   * @param  $ticketId, $subject, $notes, $userId
   * @return boolean -- true 
   */
   private function addDetails($ticketId = null, $subject = null, 
                                  $notes = null, $userId = null, $updateTicketStatus )
   {
      $data             = array();
      $data['subject']  = stripslashes(trim($subject));
      $data['notes']    = stripslashes(trim($notes));
      $data['type']     = $updateTicketStatus;
      $data['user_id']  = $userId;
      
      $params              = array();
      $params['db_link']   = $this->dbLink;
      $params['ticket_id'] = $ticketId;
      
      try
      {
         $ticketsObj = new Tickets($params);
         return $ticketsObj->addDetails($data);
      }
      catch(Exception $e)
      {
         echo $e->getMessage();
      }
   }

   /**
   * save a New Ticket
   * @access private
   * @param  $subject
   * @return last insert id
   */
   private function saveNewTicket($subject, $priority, $status, $exComp)
   {
      $params            = array();
      $params['db_link'] = $this->dbLink;
      
      try
      {
         $ticketsObj = new Tickets($params);
         $ticketsObj->setTicketTitle($subject);
         $ticketsObj->setPriority($priority);
         $ticketsObj->setStatus($status);
         $ticketsObj->setExecutiveComplaint($exComp);
      
         return $ticketsObj->create();
      }
      catch(Exception $e)
      {
         echo $e->getMessage();
      }
   }

   /**
   * save Ticket Source
   * @access private
   * @param  $ticketId, $sourceId
   * @return none
   */
   private function saveTicketSource($ticketId = null, $sourceId = null)
   {
      $params          = array();
      $params['table'] = TICKET_SOURCES_TBL;
      
      $data            = array();
      
      $data['ticket_id'] = $ticketId;
      $data['source_id'] = $sourceId;
      
      $params['data']    = $data;

      $this->dbLink->insert($params);
      return;
   }
   
   /**
   * save Ticket Attachments
   * @access private
   * @param  $ticketId, $sourceId
   * @return last insert id
   */
   private function saveTicketAttachments($ticketId, $detailsId, $fileName, $fqpn)
   {
      $params          = array();
      $params['table'] = TICKET_ATTACHMENTS_TBL;

      $data            = array();

      $data['ticket_id']         = $ticketId;
      $data['details_id']        = $detailsId;
      $data['original_filename'] = $fileName;
      $data['server_fqpn']       = $fqpn;
      $data['deleted']           = '0';

      $params['data']    = $data;

      return $this->dbLink->insert($params);
   }

   /**
   * get Ticket AuthKeys
   * @access private
   * @param  $userId, $ticketId
   * @return $authKeys
   */
   private function getTicketAuthKeys($userId = null, $ticketId = null)
   {
      $params            = array();
      $params['db_link'] = $this->dbLink;

      try
      {
         $ticketAuthObj = new TicketAuth($params);

         $authKeys = $ticketAuthObj->create($userId, $ticketId);
      }
      catch(Exception $e)
      {
         echo $e->getMessage();
      }

      return $authKeys;
   }

   /**
   * get User Status
   * @access private
   * @param  $userId
   * @return $status
   */
   private function getUserStatus($userId = null)
   {
      if(empty($userId)) return null;

      $query  = "SELECT status FROM " . USERS_TBL . 
                " WHERE user_id = " . $userId ;

      $result = $this->dbLink->select($query);

      return $result[0]->status;
   }

   /**
   * save Ticket Status
   * @access private
   * @param  $ticketId, $action
   * @return true
   */
   private function saveTicketStatus($ticketId, $action)
   {
      if($action == 'close')  $status = 7;
      if($action == 'deleted') $status = 9;

      $params              = array();
      $params['db_link']   = $this->dbLink;
      $params['ticket_id'] = $ticketId;
      
      try
      {
         $ticketObj = new Tickets($params);
         $ticketObj->setStatus($status);
         $ticketObj->update();
      }
      catch(Exception $e)
      {
         echo $e->getMessage();
      }
      
      return true;
   }

   /**
   * save Ticket History
   * @access private
   * @param  $ticketId, $status, $userId
   * @return last insert id
   */
   private function saveTicketHistory($ticketId, $status, $userId)
   {
      $params          = array();
      $params['table'] = TICKET_HISTORY_TBL;
      
      $data            = array();
      
      $data['ticket_id']          = $ticketId;
      $data['status']             = $status;
      $data['status_date']        = date("Y-m-d");
      $data['changed_by_user_id'] = $userId;
      $data['change_method']      = 0;
      
      $params['data']    = $data;

      return $this->dbLink->insert($params);
   }

   /**
   * update Ticket Assignment
   * @access private
   * @param  $ticketId, $userId, $action
   * @return none
   */
   private function updateTicketAssignment($ticketId, $userId, $action)
   {
      $params          = array();
      $params['table'] = TICKET_ASSIGNMENTS_TBL;
      $params['where'] = " ticket_id = " . $ticketId ;
      $data            = array();
      if($action == 'closed')
      {
         $data['closed_date'] = date("Y-m-d H:i:s");
      }
      elseif($action == 'deleted')
      {
         $data['deleted_date'] = date("Y-m-d H:i:s");
      }
      
      $params['data'] = $data;
      
      $this->dbLink->update($params);
      
      return true;
   }

   /**
   * save Ticket Assignment
   * @access private
   * @param  $ticketId, $status, $userId
   * @return none
   */
   private function saveTicketAssignment($ticketId, $userId)
   {
      $params          = array();
      $params['table'] = TICKET_ASSIGNMENTS_TBL;
      
      $data            = array();
      
      $data['ticket_id']     = $ticketId;
      $data['user_id']       = $userId;
      $data['assigned_date'] = date("Y-m-d H:i:s");
      
      $params['data'] = $data;
      
      $this->dbLink->insert($params);
      
      return true;
   }

   /**
   * send Reply To Source
   * @access private
   * @param  $emailAddress, $ticketId, $authKeys
   * @return true
   */
   private function sendReplyToSource($emailAddress, $emailSubject, $ticketId, $authKeys, $userId, $source, $ticketStatus)
   {
      $params   = array();
      $params['db_obj']   = $this->dbLink;
      $params['app_name'] = 'TicketMakerCron';

      $fromName  = $source->reply_from_name;
      $from      = $source->reply_from_address;
      
      switch($ticketStatus)
      {
         case 'new_ticket' : $subject    = $source->new_ticket_email_subject;
                             $body       = $source->new_ticket_email_template;
                             break;

         default           : $subject    = $source->existing_ticket_email_subject;
                             $body       = $source->existing_ticket_email_template;
                             break;

      }

      // Create a temp message template
      $msgTemplate  = sprintf("%s/%d.%s.html", TEMP_DIR, $ticketID, $ticketStatus);

      $fp = fopen($msgTemplate, 'w');
      fwrite($fp, $subject ."\n"); // Our new subject with ticket id
      fwrite($fp, $body ."\n");
      fclose($fp);

      $data   = array();
      $data['user_id']   = $userId;
      $data['ticket_id'] = $ticketId;
      $data['subject']   = $emailSubject; // Original subject found in sender's email
      $data['auth_keys'] = $authKeys;
      $parsedTemplate    = $this->createPage($msgTemplate, $data);

      $lines               = explode("\n", $parsedTemplate);

      $mailData            = array();
      $mailData['subject'] = array_shift($lines);
      $mailData['body']    = join("\n", $lines);

      try
      {
         $mailObj = new Email($params);
         
         $mailData['to']      = $emailAddress;
         $mailObj->setFrom($from);
         $mailObj->setFromName($fromName);
         $mailObj->send($mailData);
      }
      catch(Exception $e)
      {
         echo $e->getMessage();
      }
      
      return true;
   }

   /**
   * get Listing Action
   * @access private
   * @param  $subject
   * @return matcehd string | null
   */
   private function getListingAction($subject = null)
   {
      if(empty($subject)) return;
      
      $subject = strtolower($subject);
      
      if(preg_match("/list/i", $subject))
      {
         $subject = preg_replace("/list/", "", $subject);
         
         return $this->preg_loop($subject);
      }
      
      return null;
   }

   /**
   * preg loop
   * @access private
   * @param  $string
   * @return $key
   */
   private function preg_loop($str)
   {
      $searchArray = array("all", "this week", "this month", 
                           "last month", "this year", "open",
                           "in progress", "completed");
      
      foreach($searchArray as $key => $value) 
      {
         if(preg_match("/$value/i", $str)) 
         {
            return $key+1;
         }
      }
      
      return 8;
   }

   /**
   * make Query Date
   * @access private
   * @param  $action
   * @return $returnArray = array('action'   => $action,
                                  'startDate' => $startDate,
                                  'endDate'   => $endDate,
                                  'status'    => $status
                                  );
   */
   private function makeQueryDate($action)
   {
      if($action == 1)
      {
         //return nothing
      }
      elseif($action == 2)
      {
         $startDate = $this->getWeekStartDate();
         $endDate   = date("Y-m-d H:i:s");
      }
      elseif($action == 3)
      {
         $startDate = $this->getStartDateOfMonth();
         $endDate   = date("Y-m-d H:i:s");
      }
      elseif($action == 4)
      {
         $startDate = $this->getStartDateOfLastMonth();
         $endDate   = $this->getEndDateOfLastMonth();
      }
      elseif($action == 5)
      {
         $startDate = $this->getStartDateOfYear();
         $endDate   = date("Y-m-d H:i:s");
      }
      elseif($action == 6)
      {
         $status = OPEN_STATUS;
      }
      elseif($action == 7)
      {
         $status = IN_PROGRESS_STATUS;
      }
      elseif($action == 8)
      {
         $status = COMPLETED_STATUS;
      }
      elseif($action == 9)
      {
         $status = OTHER_STATUS;
      }

      $returnArray = array('action'   => $action,
                          'startDate' => $startDate,
                          'endDate'   => $endDate,
                          'status'    => $status
                          );

      return $returnArray;
   }

   /**
   * get Week Start Date
   * @access private
   * @param  $subject
   * @return -Week Start Date
   */
   private function getWeekStartDate()
   {
      $weekNo = $this->week_of_year(date("m"), date("d"), date("Y"));

      return $this->week_start_date($weekNo-1, date("Y"));
   }

   /**
   * get Start Date Of Year
   * @access private
   * @param  none
   * @return - Start Date Of Current Year
   */
   private function getStartDateOfYear()
   {
      return date("Y-m-d  H:i:s", mktime(0,0,0,1,1,date("Y")));
   }

   /**
   * get Start Date Of Month
   * @access private
   * @param  none
   * @return -Start Date Of Current Month
   */
   private function getStartDateOfMonth()
   {
      return date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), 1, date("Y")));
   }

   /**
   * get Start Date Of Last Month
   * @access private
   * @param  none
   * @return - Start Date Of Last Month
   */
   private function getStartDateOfLastMonth()
   {
      return date('Y-m-d H:i:s', mktime(0, 0, 0, date("m")-1, 1, date("Y")));

   }

   /**
   * get End Date Of Last Month
   * @access private
   * @param  none
   * @return - End Date Of Last Month
   */
   private function getEndDateOfLastMonth()
   {
      $noOfDays = date('t',mktime(0,0,0, date("m")-1, 1, date("Y"))); 
      
      return date('Y-m-d H:i:s', mktime(0, 0, 0, date("m")-1, $noOfDays, $year));
   }

   /**
   * week start date
   * @access private
   * @param  $weekNum, $year, $first = 1, $format = 'Y-m-d'
   * @return start Date of a week
   */
   private function week_start_date($weekNum, $year, $first = 1, $format = 'Y-m-d')
   {
      $weekTs  = strtotime('+' . $weekNum . ' weeks', strtotime($year . '0101')); 

      return date($format, strtotime('-' . date('w', $weekTs) + $first . ' days', $weekTs)); 
   }

   /**
   * week of year
   * @access private
   * @param  $month, $day, $year
   * @return - no of week in a year
   */
   private function week_of_year($month, $day, $year) 
   { 
      $thisdate = mktime(0,0,0,$month,$day,$year);

      if (date("D", mktime(0,0,0,1,1,$year)) == "Mon")
      {
         $day1 = mktime (0,0,0,1,1,$year);
      } 
      else 
      {
         if (date("z", mktime(0,0,0,$month,$day,$year)) >= "361")
         {
            $day1 = strtotime("last Monday", mktime(0,0,0,1,1,$year+1));
         } 
         else 
         {
            $day1=strtotime("last Monday", mktime(0,0,0,1,1,$year));
         }
      }

      $dayspassed = (($thisdate - $day1)/60/60/24);

      if (date("D", mktime(0,0,0,$month,$day,$year)) == "Sun")
      {
         $sunday = mktime(0,0,0,$month,$day,$year);
      }
      else 
      {
         $sunday = strtotime("next Sunday", mktime(0,0,0,$month,$day,$year));   
      }

      $daysleft = (($sunday - $thisdate)/60/60/24);

      return ($dayspassed + $daysleft+1)/7;
   }

   /**
   * get All Ticket List
   * @access private
   * @param  $sourceId
   * @return list of Ticket
   */
   private function getAllTicketList($sourceId)
   {
      $query = " SELECT TS.ticket_id FROM " . TICKET_SOURCES_TBL . 
               " AS TS LEFT JOIN " . TICKETS_TBL . " AS T 
                 on(TS.ticket_id = T.ticket_id)
                 WHERE TS.source_id =" . $sourceId ;
      
      return $this->dbLink->select($query);
   }

   /**
   * get Ticket List By Date
   * @access private
   * @param  $sourceId, $startDate,$endDate
   * @return list of Ticket
   */
   private function getTicketListByDate($sourceId, $startDate,$endDate)
   {
      $query = " SELECT TS.ticket_id FROM " . TICKET_SOURCES_TBL . 
               " AS TS LEFT JOIN " . TICKETS_TBL . " AS T 
                on(TS.ticket_id = T.ticket_id)
                WHERE TS.source_id =" . $sourceId . "
                AND T.create_date BETWEEN '" . $startDate . "' AND '" . $endDate . "'";

      return $this->dbLink->select($query);
   
   }
   /**
   * get Ticket List By Status
   * @access private
   * @param  $sourceId, $action
   * @return list of Ticket
   */
   private function getTicketListByStatus($sourceId, $action)
   {
      $query = " SELECT TS.ticket_id FROM " . TICKET_SOURCES_TBL . 
               " AS TS LEFT JOIN " . TICKETS_TBL . " AS T 
                on(TS.ticket_id = T.ticket_id)
                WHERE TS.source_id =" . $sourceId . "
                AND T.status =" . $action ;

      return $this->dbLink->select($query);
   }
   /**
   * get Other Type Ticket List
   * @access private
   * @param  $sourceId
   * @return list of Ticket
   */
   private function getOtherTypeTicketList($sourceId)
   {
      $query = " SELECT TS.ticket_id FROM " . TICKET_SOURCES_TBL . 
               " AS TS LEFT JOIN " . TICKETS_TBL . " AS T 
                on(TS.ticket_id = T.ticket_id)
                WHERE TS.source_id =" . $sourceId . "
                AND T.status IN (7,8,9,10,11)" ;

      return $this->dbLink->select($query);
   }
   
   /**
   * mail To Authorized Source
   * @access private
   * @param  $emailAddress, $ticketList, $userId
   * @return true
   */
   private function mailToAuthorizedSource($emailAddress, $ticketList, $userId, $source)
   {
      $params   = array();
      $params['db_obj']   = $this->dbLink;
      $params['app_name'] = 'TicketMakerCron';
      
      print_r($source);

      try
      {
         $mailObj = new Email($params);
         
         $mailData  = array();
         $mailData['to']      = $emailAddress;
         $mailData['subject'] = KNOWN_SOURCE_EMAIL_SUBJECT;
         $data   = array();
         $data['ticketList'] = $ticketList;
         $data['userId']     = $userId;
         $mailData['body']   = $this->createPage(EMAIL_REPLY_LIST_TEMPLATE, $data);
         $mailObj->send($mailData);
      }
      catch(Exception $e)
      {
         echo $e->getMessage();
      }
      
      return true;
      
   }

   /**
   * make Email As Read
   * @access private
   * @param  $emailAddress, $ticketList, $userId
   * @return null
   */
   private function makeEmailAsRead($thisMsg, $sourceId)
   {
       strip_tags($thisMsg->getTextBody(FLAG_EXCLUDE_ATTACHMENT));
       $thisMsg->getAttachments($sourceId, null);
       
       return true;
   }
   
   public function __destruct()
   {
      parent::__destruct();
   }

}


$ticketMakerObj = new TicketMaker();
$ticketMakerObj->run();

?>
