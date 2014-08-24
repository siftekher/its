<?php
/**
*  FileName : ticket.maker.cron.php
*  Purpose  : 
*
*  @author  : Sheikh Iftekhar <siftekher@gmail.com
*  @project :
*  @version : 1.0.0
**/

require_once('cron.config.php');
require_once(CRON_CLASS_DIR . '/CronJob.class.php');
require_once(EXT_DIR . '/phpmailer/class.phpmailer.php');
require_once('DB.class.php');
require_once('POPMailClient.class.php');
require_once('Message.class.php');
require_once('Attachment.class.php');
require_once('Email.class.php');
require_once('Tickets.class.php');
require_once('TicketAuth.class.php');
require_once('Email.class.php');

class TicketReminder extends CronJob
{
   private $dbLink;

   public function __construct()
   {
      parent:: __construct();
      parent:: setJobID('ticket_reminder_cron');

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
         echo 'Cron is running.';
         return;
      }
      else
      {
         $this->setCronStatusOn();
      }

      $this->processTicketReminder();
   }

   /**
   * process Ticket Reminder
   * @access private
   * @param  $toEmailAddress 
   * @return none
   */
   private function processTicketReminder()
   {
      $userList = $this->getActiveResolver();
      
      if(count($userList))
      {
         foreach($userList as $userId => $sourceId)
         {
            $autoReminder  = $this->getTicketUserSettings($userId);
            if ($autoReminder == 1)
            {
               $data  = array();
               $newTicketList  = $this->getNewTicket($sourceId);
               if(count($newTicketList))
               {
                  $data['newTicketList'] = $this->makeArrayUserIdWithTicket($newTicketList, $userId);
               }
               
               $existingTicket = $this->getExistingTicket($sourceId);
               if(count($existingTicket))
               {
                  $data['existingTicket'] = $this->makeArrayUserIdWithTicket($existingTicket, $userId);
               }
               
               $recCompleted = $this->getRecCompletedTicket($sourceId);
               if(count($recCompleted))
               {
                  $data['recCompleted'] = $this->makeArrayUserIdWithTicket($recCompleted, $userId);
               }
             
               if(count($newTicketList) || count($existingTicket) || count($recCompleted))
               {
                  $emailAddress = $this->getEmailAddress($userId);
                  $this->mailToResolver($emailAddress, $data);  
               }
            }
         }
      }
   }
   
   private function makeArrayUserIdWithTicket($data, $userId)
   {
      foreach($data as $row => $value)
      {
         $value->userId  = $userId;
         $value->authKey = $this->getTicketAuthKeys($userId, $value->ticket_id);
      }
      
      return $data;
   }

   /**
   * get Active Resolver
   * @access private
   * @param  $toEmailAddress 
   * @return List of active resolver
   */
   private function getActiveResolver()
   {
      //table name should comes from config
      $query = "SELECT SR.user_id, SR.source_id FROM source_resolvers AS SR LEFT JOIN  
                users AS U on (U.user_id = SR.user_id) WHERE U.status = 1";
      
      $result = $this->dbLink->select($query);

      $data       = array();
      $sourceList = array();
      
      if(count($result))
      {
         foreach($result as $row => $value)
         {
            $data[$value->user_id][] = $value->source_id;
         }

         foreach($data as $row => $value)
         {
            $sourceList[$row] = implode(',', $value);
         }
      }
      
      return $sourceList;

   }

   /**
   * get Ticket User Settings
   * @access private
   * @param  $userId 
   * @return $autoReminder
   */
   private function getTicketUserSettings($userId)
   {
      $query  = "SELECT enable_auto_reminder FROM " . TICKET_USER_SETTINGS_TBL . 
                " WHERE user_id = ". $userId;

      $result = $this->dbLink->select($query);
     
      if(count($result))
      {
         foreach($result as $row => $value)
         {
            $autoReminder = $value->enable_auto_reminder;      
         }
      }
      
      return $autoReminder;
   }

   /**
   * get New Ticket
   * @access private
   * @param  $toEmailAddress 
   * @return none
   */
   private function getNewTicket($sourceId = null)
   {
      $currentDate = date("Y-m-d");
      
      $query = "SELECT t.ticket_id, t.title
                FROM " . TICKET_SOURCES_TBL . " as ts 
                LEFT JOIN " . TICKETS_TBL . " as t 
                ON (ts.ticket_id = t.ticket_id) 
                WHERE ts.source_id IN($sourceId)
                AND t.status = 0 AND t.create_date LIKE '" . $currentDate . "%'
                ORDER BY t.ticket_id, t.create_date ASC ";

      return $this->dbLink->select($query);
   }

   /**
   * get Existing Ticket
   * @access private
   * @param  $toEmailAddress 
   * @return none
   */
   private function getExistingTicket($sourceId = null)
   {
      $startDate = $this->dateOfLastSevenDays();
      $endDate   = date("Y-m-d H:i:s" , mktime(0,0,0,date("m"),date("d")-1, date("Y")));
      
      $query = "SELECT t.ticket_id, t.title
                FROM " . TICKET_SOURCES_TBL . " as ts 
                LEFT JOIN " . TICKETS_TBL . " as t 
                ON (ts.ticket_id = t.ticket_id) 
                WHERE ts.source_id IN($sourceId)
                AND t.status = 0 AND t.create_date BETWEEN
                '" . $startDate . "' AND '" . $endDate. "'
                ORDER BY t.ticket_id, t.create_date ASC ";

      return $this->dbLink->select($query);
   }

   /**
   * date Of Last Seven Days
   * @access private
   * @param  none 
   * @return $date
   */
   private function dateOfLastSevenDays()
   {
      return date("Y-m-d H:i:s", mktime(date("H"),date("i"),date("s"),
                  date("m"),date("d")-7, date("Y")));
   }

   /**
   * get Recently Completed Ticket
   * @access private
   * @param  $toEmailAddress 
   * @return none
   */
   private function getRecCompletedTicket($sourceId = null)
   {
      $startDate = $this->dateOfLastSevenDays();
      $endDate   = date("Y-m-d H:i:s" , mktime(0,0,0,date("m"),date("d")-1, date("Y")));
      
      $query = "SELECT t.ticket_id, t.title
                FROM " . TICKET_SOURCES_TBL . " as ts 
                LEFT JOIN " . TICKETS_TBL . " as t 
                ON (ts.ticket_id = t.ticket_id) 
                WHERE ts.source_id IN($sourceId)
                AND t.status = 6 AND t.create_date BETWEEN
                '" . $startDate . "' AND '" . $endDate. "'
                ORDER BY t.ticket_id, t.create_date ASC ";
      
      return $this->dbLink->select($query);
   }

   /**
   * mail To Authorized Source
   * @access private
   * @param  $emailAddress, $ticketList, $userId
   * @return true
   */
   private function mailToResolver($emailAddress, $data)
   {
      $params   = array();
      $params['db_obj']   = $this->dbLink;
      $params['app_name'] = 'TicketReminderCron';

      try
      {
         $mailObj = new Email($params);

         $mailData  = array();
         $mailData['to']      = $emailAddress;
         $mailData['subject'] = NOTIFICATION_EMAIL_SUBJECT;
         $data  = $data;
         $mailData['body'] = $this->createPage(TICKET_REMINDER_TEMPLATE, $data);

         $mailObj->send($mailData);
      }
      catch(Exception $e)
      {
         echo $e->getMessage();
      }

      return true;
   }
   
   //getTicketAuthKeys method of maker cron should go CronJob.class.php
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
   
   private function getEmailAddress($userId = null)
   {
      $query  = "SELECT email FROM " . USERS_TBL . " WHERE `user_id` = " . $userId;
      $result = $this->dbLink->select($query);
     
      if(count($result))
      {
         foreach($result as $row => $value)
         {
            $email = $value->email;
         }      
      }
      
      return $email;
   }
} 


$ticketReminderObj = new TicketReminder();
$ticketReminderObj->run();
?>