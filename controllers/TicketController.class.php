<?php

/*
 * Filename   : TicketController.class.php
 * Purpose    : 
 *
 * @author    : Sheikh Iftekhar <siftekher@gmail.com>
 * @project   : Issue tracking system
 * @version   : 1.0.0
 */
require_once('Tickets.class.php');
require_once('TicketListRenderer.class.php');

class Ticket
{
   private $db;
   private $template;
   private $param;
   
   public function __construct($param)
   {
      $this->db = $param['db_link'];
      $this->template = new Template();
      $this->param = $param['cmdList'];
   }

  /**
   * Decision maker function to call 
   * different methods based on 
   * different requests.
   *
   * @access public
   * @param none
   * @return string - html screen output
   */
   function run()
   {
      $cmd = $this->param[1];
      
      $userId = $_SESSION['LOGIN_USER']['userId'];
      
      //$this->sendEmail(5);
      //$this->getTicketPriority(5);
      //echo "<PRE>";
      //print_r($_SESSION);
      
      if($cmd != 'upload')
      {
         // Redirects to Login controller if user is not logged in
         if($userId == null)
         {
            header('location: ' . SUPER_CONTROLLER_URL_PREFIX . 'Login');
            exit;
         }
      }

      
      switch($cmd)
      {
         case 'attachment'        : $screen = $this->downloadAttachment();      break;
         case 'upload'            : $screen = $this->uploadFile();              break;
         case 'rm_upload'         : $screen = $this->removeUploadedFile();      break;
         case 'add_detail'        : $screen = $this->addDetail();               break;
         case 'details'           : $screen = $this->showTicketDetails();       break;
         case 'search_tag'        : $screen = $this->searchTag();               break;
         case 'add_tag'           : $screen = $this->addTag();                  break;
         case 'assign_self'       : $screen = $this->assignSelf();              break;
         case 'mark_executive'    : $screen = $this->markExecutive();           break;
         case 'delete_ticket'     : $screen = $this->deleteTicket();            break;
         case 'close_ticket'      : $screen = $this->closeTicket();             break;
         case 'list'              : $screen = $this->showTicketList();          break;
         case 'ajax_list'         : $screen = $this->getTicketListForAjax();    break;   
         case 'priority'          : $screen = $this->getTicketListByPriority(); break;   
         case 'print'             : $screen = $this->printTicketDetails();      break;
         case 'piority_ajax_list' : $screen = $this->getTicketListForPriorityAjax();    break;   
         case 'gen_dialog_form'   : $screen = $this->isResolverByTicket();      break;
         case 'merge_ticket'      : $screen = $this->mergeTicket();             break;
         case 'rate_ticket'       : $screen = $this->saveTicketRating();        break;
         case 'log'               : $screen = $this->activityLogReport();       break;
         case 'correct'           : $screen = $this->correctDB();               break;
         default                  : $screen = $this->showTicketList();          
      }
           
      $data            = array();
      $data['topnav']  = 'home';
      $data['tagList'] = Utils::getAllTagList($this->db);
      $userList = new UserList($this->db);
      $data['sourceUser'] = $userList->getUsersFromMySources();
      $data['source_id']  = $_SESSION['source_id'];
      $data['source_name'] = Utils::getSourceName($this->db , $_SESSION['source_id']);
      //echo "<pre>";
      //print_r($_SESSION);
      echo $this->template->createScreen($screen, $data);
      exit;
   } 
   
   function isResolverByTicket()
   {
       $ticketId = $_REQUEST['ticket_id'];
       $sourceId = Utils::getSourceIdByTicketId($this->db, $ticketId);
       echo Utils::isResolverUser($sourceId);
       exit;
   }

  /**
   * Purpose : gets sql for ticket list
   *
   * @access : private
   * @param  : $user_id - if tickets for specfic user
   * @param  : $source_id - if tickets for specfic source
   * @param  : $start - starting index for sql page
   * @param  : $size - number of records in sql page
   * @return : string - sql
   */
   private function getTicketListSQL($param = array())
   {
      $user_id   = $param['user_id']; 
      $source_id = $param['source_id']; 
      $priority  = $param['priority']; 
      $start     = $param['start']; 
      $size      = $param['size'];
                                     
      $my_sources = array();
      //foreach($_SESSION['LOGIN_USER']['sources'] as $index => $source)
      //{
      //   $my_sources[$source->source_id] = $source->name;
      //}
      //$source_id_str = implode(",", array_keys($my_sources));

      $status_type = Utils::arrayAlterKeyValue(
         $GLOBALS['TICKET_STATUS_TYPE']
      );

      unset($status_type['closed']);
      unset($status_type['deleted']);
      unset($status_type['completed']);
      unset($status_type['archived']);
      $status_type_str = implode(',', $status_type);
      
      $where  = "";
      $where .= $user_id ? " AND d.user_id = $user_id" : "";
      $where .= !is_null($source_id) ? " AND ts.source_id = $source_id" : "";
      $where .= isset($priority) ? " AND t.priority = $priority" : ""; 
      $limit = ($start !== false && $size) ? " LIMIT $start, $size" : "";

      $sql = "SELECT SQL_CALC_FOUND_ROWS ts.source_id, 
                     t.ticket_id, 
                     t.title,
                     t.priority, 
                     t.status, 
                     t.create_date, 
                     d.notes, 
                     d.user_id, 
                     u.user_id, 
                     u.first_name, 
                     u.last_name 
             FROM " . TICKET_SOURCES_TBL . " as ts 
             LEFT JOIN " . TICKETS_TBL . " as t 
             ON (ts.ticket_id = t.ticket_id) 
             LEFT JOIN " . TICKETS_DETAILS_TBL . " as d 
             ON (t.ticket_id = d.ticket_id) 
             LEFT JOIN " . USERS_TBL . " as u 
             ON (d.user_id = u.user_id) 
             WHERE ts.source_id IN {$_SESSION['SOURCES_ID_STRING']} AND 
                   t.status IN ($status_type_str) 
                   $where 
             GROUP BY t.ticket_id 
             ORDER BY t.ticket_id DESC, d.create_date ASC 
             $limit"; 

      return $sql;  
   }

  /**
   * Purpose : Prints ticket list for ajax request
   *
   * @access : private
   * @param  : none
   * @return : string - html output
   */
   private function getTicketListForAjax()
   {     
      $settings = Utils::getPersonalizedSettings($this->db);
      // Utils::dumpvar($settings);
      $page       = isset($this->param[2]) ? $this->param[2] : 0;

      $page_size  = $settings->show_issues_per_page ? 
                    $settings->show_issues_per_page : 
                    SQL_PAGE_SIZE;

      /*$user_id    = $settings->enable_issues_created_by_me ? 
                    $settings->enable_issues_created_by_me :
                    null;*/

      $param = array(
         'user_id' => null,
         'source_id' => null,
         'start' => $page,
         'size' => $page_size
      );
//print_r( $this->template);
      $TicketListRenderer = new TicketListRenderer($this->db, $this->template);
      $sql = $this->getTicketListSQL($param);
      
      $list = $TicketListRenderer->getTicketList($sql);
//utils::dumpvar($list);
      if($list)
      {
         echo implode("", $list);
      }
      else
      {
         echo "No results found.";
      }

      exit;  
   }
   
   private function getTicketListForPriorityAjax()
   {    
        $settings   = Utils::getPersonalizedSettings($this->db);
        $piority    = isset($this->param[2]) ? $this->param[2] : 3 ;     
        $source_id  = ($this->param[3]) ? $this->param[3] : null ;   
        $page       = isset($this->param[4]) ? $this->param[4] : 0;
        $page_size  = $settings->show_issues_per_page ? 
                        $settings->show_issues_per_page : 
                       SQL_PAGE_SIZE;

      
        $param = array(
           'user_id' => null,
           'source_id' => $source_id,
           'priority'  => $piority,
           'start' => $page,
           'size' => $page_size
        );

      $TicketListRenderer = new TicketListRenderer($this->db, $this->template);
      $sql = $this->getTicketListSQL($param);
      $list = $TicketListRenderer->getTicketList($sql);

      if($list)
      {
         echo implode("", $list);
      }
      else
      {
         echo "No results found.";
      }

      exit;  
   }

  /**
   * Purpose : Shows ticket list from 
   *           sources in which the 
   *           user is associated
   *
   * @access : private
   * @param  : none
   * @return : string - html output
   */
   private function showTicketList()
   {
      $settings = Utils::getPersonalizedSettings($this->db);

      /*$user_id    = $settings->enable_issues_created_by_me ? 
                    $settings->enable_issues_created_by_me :
                    null;*/

      $param = array(
         'user_id' => null,
         'source_id' => null,
         'start' => 0,
         'size' => 1
      );

      $TicketListRenderer = new TicketListRenderer($this->db, $this->template);
      $sql = $this->getTicketListSQL($param);
//echo $sql;
      $total_ticket = $TicketListRenderer->countTicketList($sql);

      $data = array();
      $data['pagger_action_url'] = 'Ticket/ajax_list';
      $data['total_ticket']      = $total_ticket;
      
      $html = $TicketListRenderer->getTicketListView($data);
      
      return $html;
   }
   
    /**
   * Purpose : Shows ticket list from 
   *           sources in which the 
   *           user is associated by priority
   *
   * @access : private
   * @param  : none
   * @return : string - html output
   */
   private function getTicketListByPriority()
   {
        $settings            = Utils::getPersonalizedSettings($this->db); 
        $params['priority']  = isset($this->param[2]) ? $this->param[2] : 3 ;     
        $params['source_id'] = isset($this->param[3]) ? $this->param[3] : 0 ;   
     
        $params['start']     = 0;
        $params['size']      = 1; 
        
           
        // $param = array(
        //    'user_id' => null,
        //    'source_id' => null,
        //    'start' => 0,
        //    'size' => 1
        // );
        // 
         $TicketListRenderer = new TicketListRenderer($this->db, $this->template);
         $sql                = $this->getTicketListSQL($params);
        
         $total_ticket       = $TicketListRenderer->countTicketList($sql);
         
         $data = array();
         $data['pagger_action_url'] = 'Ticket/piority_ajax_list/'.$this->param[2]."/".$this->param[3];
         $data['total_ticket']      = $total_ticket;
         
         $html = $TicketListRenderer->getTicketListView($data);
         
         return $html;
   }

  /**
   * Purpose : Uploads attachment file 
   *           via jQuery/Ajax uplodify
   *
   * @access : private
   * @param  : none
   * @return : void - prints string - 
   *           filename:path:size 
   *           for uploaded file
   */
   private function uploadFile()
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
   * @access : private
   * @param  : none
   * @return : void - prints message
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
   * Purpose : Save details for ticket 
   *           via jQuery/Ajax
   *
   * @access : private
   * @param  : none
   * @return : void - prints string 
   *           in json format
   */
   private function addDetail()
   {     
      $params = array();
      $params['db_link']   = $this->db;
      $params['ticket_id'] = $_REQUEST['ticket_id'];
      $Tickets             = new Tickets($params);
      
      if($_REQUEST['changed_status'] != 0)
      {
         
         $Tickets->setStatus($_REQUEST['changed_status']);
         $Tickets->update();
         //save log 
         $this->saveLog($_REQUEST['changed_status'], $_REQUEST['ticket_id'], null);
      }
      
      $data = array();
      $data['subject']     = stripslashes(trim($_REQUEST['subject']));
      $data['notes']       = stripslashes(trim($_REQUEST['notes']));
      $data['user_id']     = $_SESSION['LOGIN_USER']['userId'];
      
      //$Tickets             = new Tickets($params);
      $details_id          = $Tickets->addDetails($data);

      if($details_id)
      {
         $files = $this->getUploadedFiles();
         $this->saveAttachments($files, $_REQUEST['ticket_id'], $details_id);
      }
      
      //Send Email To resolvers, source, etc.
      $this->sendEmail($_REQUEST['ticket_id'], $data['subject']);
      
      
      
      echo '[{"isError":0, "message":"Details added Successfully."}]';
      exit;
   }
   
   //method add by pushan
   private function sendEmail($ticketId = null,$issueSubject=null)
   {
      $params             = array();
      $params['db_obj']   = $this->db;
      $params['app_name'] = 'TicketMakerWeb';      

      $logInUser  = $_SESSION['LOGIN_USER']['userId'];
      $logInEmail = $_SESSION['LOGIN_USER']['email'];
      
      $allUser   = $this->getAllUser($ticketId);
      
      $fromName  = 'ITS';
      $from      = 'jbplsupport@evoknow.com';
     
      foreach($allUser as $key)
      {
         if($logInEmail == $key->email)
         {
            continue;
         }
         
         try
         {
            $data   = array();
            $data['user_id']       = $key->user_id;
            $data['ticket_id']     = $ticketId;
            // $data['subject']       = 'Ticket No# $ticketId has been updated';
            $data['auth_keys']     = $this->getTicketAuthKeys($key->user_id, $ticketId);
            $data['issue_subject'] = $issueSubject;
            $parsedTemplate        = $this->template->parseTemplate(UPDATE_TEMPLATE, $data);

            // $lines               = explode("\n", $parsedTemplate);
            // $mailData            = array();
            // $mailData['subject'] = array_shift($lines);
            // $mailData['body']    = join("\n", $lines);
            
            $mailData['subject'] = "Ticket No# $ticketId has been updated.";
            $mailData['body']    = $parsedTemplate;
            
            $mailObj = new Email($params);
            
            $mailData['is_html'] = true; 
            $mailData['to']  = $key->email;
            $mailObj->setFrom($from);
            $mailObj->setFromName($fromName);
            //priority should set here
            $priority = $this->getTicketPriority($ticketId);
            $mailObj->setPriority($priority);
            $mailObj->send($mailData);
         }
         catch(Exception $e)
         {
            echo $e->getMessage();
         }
      }
      
      return true;
   }
   //method add by pushan
   private function getTicketPriority($ticketId = null)
   {
      $params              = array();
      $params['db_link']   = $this->db;
      $params['ticket_id'] = $ticketId;

      $Tickets  = new Tickets($params);
      $priority = $Tickets->getPriority();
      
      $ticketPriority = array(0 => '5', // low
                              1 => '3', // normal
                              2 => '1', // high
                              3 => '1'  // critical is not supported, so set to high
                              );
      
      return $ticketPriority[$priority];
   }
   //method add by pushan
   private function getTicketAuthKeys($userId, $ticketId)
   {
      $params            = array();
      $params['db_link'] = $this->db;

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
   //method add by pushan
   private function getAllUser($ticketId = null)
   {
      $resolver   = $this->getResolver($ticketId);
      $sourceUser = $this->getAuthorisedSource($ticketId);
      
      $allUsers = array_merge($resolver, $sourceUser);
      
      return $allUsers;
   }
   
   //method add by pushan
   private function getResolver($ticketId = null)
   {
      $data = array();

      if(empty($ticketId))
      {
         return $data;
      }

      $query = "SELECT distinct U.user_id, U.email FROM (ticket_sources as TS LEFT JOIN source_resolvers AS SR on (TS.source_id = SR.source_id)) 
                LEFT JOIN users AS U on (U.user_id = SR.user_id) 
                WHERE TS.ticket_id = ". $ticketId;

      
      $result = $this->db->select($query);
      
      if(count($result))
      {
         foreach($result as $key => $value)
         {
            $thisStdObj  = new stdClass();
            $thisStdObj->user_id = $value->user_id;
            $thisStdObj->email   = $value->email;
            $data[] = $thisStdObj;
         }
      }
      
      return $data;
   }
   //method add by pushan
   private function getAuthorisedSource($ticketId = null)
   {
      $data = array();
      
      if(empty($ticketId))
      {
         return $data;
      }
      
      $query = "SELECT distinct U.user_id, U.email FROM (ticket_sources as TS LEFT JOIN authorized_sources AS AUS on (TS.source_id = AUS.source_id)) 
                LEFT JOIN users AS U on (U.user_id = AUS.user_id) 
                WHERE TS.ticket_id = ". $ticketId;

      
      $result = $this->db->select($query);
      
      if(count($result))
      {
         foreach($result as $key => $value)
         {
            $thisStdObj  = new stdClass();
            $thisStdObj->user_id = $value->user_id;
            $thisStdObj->email   = $value->email;
            $data[] = $thisStdObj;
         }
      }
      
      return $data;
   }

  /**
   * Purpose : Shows ticket details
   *
   * @access : private
   * @param  : none
   * @return : string - html output
   */
   private function showTicketDetails()
   {
      $data = array();

      $ticket_id = $this->param[2];

      $data['ticket_status_type'] = Utils::arrayAlterKeyValue(
         $GLOBALS['TICKET_STATUS_TYPE']
      );
      
      $my_sources = array();
      foreach($_SESSION['LOGIN_USER']['sources'] as $index => $source)
      {
         $my_sources[$source->source_id] = $source->name;
      }
      $data['source_list'] = $my_sources;
      
      $sql  = "SELECT t.*, ts.source_id 
               FROM " . TICKETS_TBL . " as t, " . TICKET_SOURCES_TBL . " as ts 
               WHERE t.ticket_id = ts.ticket_id AND t.ticket_id = $ticket_id";
      try
      {
         $ticket = $this->db->select($sql);
      }
      catch(Exception $Exception){}
            
      $ticket              = isset($ticket[0]) ? $ticket[0] : new stdClass();
      $ticket->status_text = $GLOBALS['TICKET_STATUS_TYPE'][$ticket->status];
      $ticket->create_date = date('l jS F Y @ h:i A (T)', 
                                  strtotime($ticket->create_date));
                                  
      $priorityColorSettings = Utils::getPriorityColorSettings($this->db,$_SESSION['LOGIN_USER']['userId']);               
      
      $ticket->color       = $priorityColorSettings[$ticket->priority];
      $data['ticket']      = $ticket;      

      $sql  = "SELECT d.*, u.first_name, u.last_name  
               FROM " . TICKETS_DETAILS_TBL . " as d, " . USERS_TBL . " as u 
               WHERE d.user_id = u.user_id 
               AND d.ticket_id = $ticket_id 
               ORDER BY d.create_date desc";
      try
      {
         $list = $this->db->select($sql);
      }
      catch(Exception $Exception){}

      // if($list)
      // {
      //    foreach($list as $index => $row)
      //    {
      //       $list[$index]->create_date = date('l jS F Y @ h:i A (T)', 
      //                                         strtotime($row->create_date));
      //    }
      // }
      // 
      // $first_detail_pos = count($list)-1;
      // $first_detail = $list[$first_detail_pos];
      // unset($list[$first_detail_pos]);
      // 
      // $data['first_detail'] = $first_detail;
      // $data['detail_list'] = $list;
      
       if($list)
       {
          foreach($list as $index => $row)
          {
             $row->create_date = date('l jS F Y @ h:i A (T)', 
                                            strtotime($row->create_date));
             if($row->type == 1)
             {
                 $description = $row;
             }
             else
             {
                $res[] = $row;
             }
          }
       }
       $data['first_detail'] = $description;
       $data['detail_list']  = $res;

      $list = array();
      $sql  = "SELECT * 
               FROM " . TICKET_ATTACHMENTS_TBL . " 
               WHERE deleted = 0 AND ticket_id = $ticket_id";
      try
      {
         $rows = $this->db->select($sql);
      }
      catch(Exception $Exception){}
      
      if($rows)
      {
         foreach($rows as $index => $row)
         {
            $extArray = split("[.]",$row->original_filename);
            $row->extension = strtolower($extArray[count($extArray)-1]);
            
            $list[$row->details_id][] = $row;
         }
      }
      $data['attachments_by_details_id'] = $list;      
      
      $sql  = "SELECT * 
               FROM " . TICKET_RATING_SUMMARY_TBL . " 
               WHERE ticket_id = $ticket_id";
      try
      {
         $rows = $this->db->select($sql);
      }
      catch(Exception $Exception){}
      
      if(count($rows) > 0)
      {
         $data['ticket_rating_submissions'] = $rows[0]->rating_submissions;
         if($rows[0]->rating_sum > 0)
         {
            $data['ticket_average_rating']     = number_format((float)$rows[0]->rating_sum / (float)$data['ticket_rating_submissions'], 2, '.', '');
         }
         else
         {
            $data['ticket_average_rating']     = 0;
         }
      }
      else
      {
         $data['ticket_rating_submissions'] = 0;
         $data['ticket_average_rating']     = 0;
      }

      $data['ticket_status_list']   = $GLOBALS['TICKET_STATUS_TYPE'];
      $data['login_user_id']        = $_SESSION['LOGIN_USER']['userId'];
      $data['is_resolver']          = Utils::isResolverUser($ticket->source_id);            
      
      $data['add_detail_dialog'] = $this->template
                                   ->parseTemplate(TICKET_DETAIL_DIALOG_TEMPLATE, $data);

      $data['add_tag_dialog'] = $this->template
                                 ->parseTemplate(TICKET_TAG_DIALOG_TEMPLATE, $data);
                                 
       $data['add_merge_dialog'] = $this->template
                                 ->parseTemplate(TICKET_MERGE_DIALOG_TEMPLATE, $data);                                 

      $data['is_source'] = count($_SESSION['LOGIN_USER']['authorized_sources']);
      $data['is_executive'] = Utils::isExecutive($ticket->source_id);
     // Utils::dumpvar($data);
      return $this->template->parseTemplate(TICKET_DETAIL_TEMPLATE, $data);
   }
   
  /**
   * Purpose : Searches tags via jQuery/Ajax 
   *           for autocomplete
   *
   * @access : private
   * @param  : none
   * @return : void - prints string 
   *           in json format
   */
   private function searchTag()
   {
      $term = $_REQUEST['term'];
      
      $list = array();
      $sql  = "SELECT * 
               FROM " . TAGS_TBL . " 
               WHERE tag_title LIKE '$term%'";
      try
      {
         $rows = $this->db->select($sql);
      }
      catch(Exception $Exception){}
      
      if($rows)
      {
         foreach($rows as $index => $row)
         {
            $list[] = stripslashes(trim(ucwords(strtolower($row->tag_title))));
         }
      }
      
      $str = implode('","', $list);
      if($str)
      {
         echo '["' . $str . '"]';
      }
      else
      {
         echo '[]';
      }
      
      exit;
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
   private function addTag()
   {
      $params              = array();
      $params['db_link']   = $this->db;
      $params['ticket_id'] = $_REQUEST['ticket_id'];
      
      $data = array();
      $tagTitle  = stripslashes(trim($_REQUEST['tag']));
      $tag  = Utils::sanitize($tagTitle);
      
      $Tickets = new Tickets($params);
      $Tickets->addTag($tag, $tagTitle);
      
      echo '[{"isError":0, "message":"Successfully added tag"}]';
      exit;
   }
   
   private function mergeTicket()
   {
      $params              = array();
      $params['db_link']   = $this->db;
      $params['ticket_id'] = $_REQUEST['ticket_id'];
      $currentTicketObj    = new Tickets($params);
      $params['ticket_id'] = $_REQUEST['merge_ticket_id'];
      $mergedTicketObj     = new Tickets($params);
      
      $currentTicket       = $currentTicketObj->getTicketSource();
      $mergedTicket        = $mergedTicketObj->getTicketSource();
      
      if(!$this->isValidTicket($currentTicket,$mergedTicket))
      {
         echo '[{"isError":1, "message":"' . MERGE_ERROR . '"}]';
         exit; 
      }
      $currentTicketDetail   = $currentTicketObj->getTicketDetail(1);   
      $mergedTicketDetail    = $mergedTicketObj->getTicketDetail(1); 
      $currentNoteId         = $currentTicketDetail[0]->details_id;
      $data['notes']         = $currentTicketDetail[0]->notes
                                ."\n".$mergedTicketDetail[0]->notes;
      
      # Update current ticket description                              
       $currentTicketObj->updateTicketDetails($currentNoteId,$data);
      
       # Update Ticket Responses
      $mergedTicketResponses   = $mergedTicketObj->getTicketDetail(0);     
      
      if($mergedTicketResponses)
      {
          foreach($mergedTicketResponses as $k=>$details)
          {
              $param['ticket_id'] = $_REQUEST['ticket_id'];   
              $mergedTicketObj->updateTicketDetails($details->details_id,$param);
          }
      }
      
      # update tag 
      $mergedTags = $mergedTicketObj->getTag();
      if($mergedTags)
      {
          foreach($mergedTags as $tag)
          {
              unset($data);
              unset($param);
              $data['ticket_id']  = $_REQUEST['ticket_id'];   
              $param['ticket_id'] = $tag->ticket_id;   
              $param['tag_id']    = $tag->tag_id;   
              $mergedTicketObj->updateTicketTags($param,$data);
          }
      } 
     
     # update attachments
      $mergedAttachments = $mergedTicketObj->getAttchments(); 
     
      if($mergedAttachments)
      {
          foreach($mergedAttachments as $attachment)
          {
              unset($data);
              $attachmentId       = $attachment->attachment_id;
              $data['ticket_id']  = $_REQUEST['ticket_id'];   
              $mergedTicketObj->updateTicketAttachment($attachmentId,$data);
          }
      }
      
     # delete merge ticket_id
     $mergedTicketObj->deleteMergeTicket();
     
      echo '[{"isError":0}]';
      exit;
      
   }
   
   function isValidTicket($currentTicket,$mergedTicket)
   {  
      if(!empty($mergedTicket))
      {
          if($currentTicket->source_id == $mergedTicket->source_id)
          {
             return true;
          }
      }
      
      return false;
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
   
      if(isset($_REQUEST['files']))
      {
         $files = explode('::', $_REQUEST['files']);
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

  /**
   * Purpose : Assigns ticket to login 
   *           user via jQuery/Ajax
   *
   * @access : private
   * @param  : none
   * @return : void - prints string 
   *           in json format
   */
   private function assignSelf()
   {
      $user_id = $_SESSION['LOGIN_USER']['userId'];
      
      $ticket_id = $_REQUEST['ticket_id'];
      if(!$ticket_id)
      {
         echo '[{"isError":1, "message":"No ticket ID given"}]';
         exit;
      }
      
      $status_type = Utils::arrayAlterKeyValue(
         $GLOBALS['TICKET_STATUS_TYPE']
      );

      $sql = "SELECT count(*) as total 
              FROM " . TICKET_ASSIGNMENTS_TBL . " 
              WHERE ticket_id = $ticket_id AND user_id = $user_id";
      try
      {
         $rows = $this->db->select($sql);
      }
      catch(Exception $Exception){}
      if($rows)
      {
         if($rows[0]->total)
         {
            echo '[{"isError":1, "message":"The ticket is already being assigned"}]';
            exit;
         }
      }

      $data          = array();
      $data['table'] = TICKETS_TBL;
      $data['data']  = array(
         'status'  => $status_type['assigned']
      );
      $data['where'] = "ticket_id = " . $ticket_id;
      try
      {
         $this->db->update($data);
      }
      catch(Exception $Exception){}
      
      $data          = array();
      $data['table'] = TICKET_ASSIGNMENTS_TBL;
      $data['data']  = array(
         'ticket_id'  => $ticket_id,
         'user_id'  => $user_id,
         'assigned_date'  => date('Y-m-d H:i:s')
      );
      try
      {
         $this->db->insert($data);
      }
      catch(Exception $Exception){}
      
      //save log 
      $this->saveLog(TICKET_ASSIGNMENT, $ticket_id, $user_id);
      
      echo '[{"isError":0, "message":"The ticket has been assigned successfully"}]';
      exit;
   }

  /**
   * Purpose : Marks a ticket as executive 
   *           complaint via jQuery/Ajax
   *
   * @access : private
   * @param  : none
   * @return : void - prints string 
   *           in json format
   */
   private function markExecutive()
   {
      $ticket_id = $_REQUEST['ticket_id'];
      $mark = '0';
      
      if(!$ticket_id)
      {
         echo '[{"isError":1, "message":"No ticket ID given"}]';
         exit;
      }

      $sql = "SELECT executive_complaint 
              FROM " . TICKETS_TBL . " 
              WHERE ticket_id = $ticket_id";
      try
      {
         $rows = $this->db->select($sql);
      }
      catch(Exception $Exception){}
      if($rows)
      {
         $mark = $rows[0]->executive_complaint ? '0' : '1';
      }
      
      
      $data          = array();
      $data['table'] = TICKETS_TBL;
      $data['data']  = array(
         'executive_complaint'  => $mark
      );
      $data['where'] = "ticket_id = " . $ticket_id;
      try
      {
         $this->db->update($data);
      }
      catch(Exception $Exception){}
      
      echo '[{"isError":0, "message":"The ticket has been marked successfully", "marked":"'.$mark.'"}]';
      exit;
   }

  /**
   * Purpose : Deletes a ticket via jQuery/Ajax
   *
   * @access : private
   * @param  : none
   * @return : void - prints string 
   *           in json format
   */
   private function deleteTicket()
   {
      $ticket_id = $_REQUEST['ticket_id'];
      if(!$ticket_id)
      {
         echo '[{"isError":1, "message":"No ticket ID given"}]';
         exit;
      }
      
      $status_type = Utils::arrayAlterKeyValue(
         $GLOBALS['TICKET_STATUS_TYPE']
      );


      $data          = array();
      $data['table'] = TICKETS_TBL;
      $data['data']  = array(
         'status'  => $status_type['deleted']
      );
      $data['where'] = "ticket_id = " . $ticket_id;
      try
      {
         $this->db->update($data);
      }
      catch(Exception $Exception){}
      
      
      $data          = array();
      $data['table'] = TICKET_ASSIGNMENTS_TBL;
      $data['data']  = array(
         'deleted_date'  => date('Y-m-d H:i:s')
      );
      $data['where'] = "ticket_id = $ticket_id";
      try
      {
         $this->db->update($data);
      }
      catch(Exception $Exception){}
      
      $change_method = Utils::arrayAlterKeyValue(
         $GLOBALS['TICKET_STATUS_CHANGE_METHOD']
      );
      $data             = array();
      $data['table']    = TICKET_HISTORY_TBL;
      $data['data']     = array(
         'ticket_id'          => $ticket_id,
         'status'             => $status_type['deleted'],
         'status_date'        => date('Y-m-d H:i:s'),
         'changed_by_user_id' => $_SESSION['LOGIN_USER']['userId'],
         'change_method'      => $change_method['web']
         );
      try
      {
         $this->db->insert($data);
      }
      catch(Exception $Exception){}
      
      //save log 
      $this->saveLog(TICKET_STATUS_DELETE, $ticket_id, null);

      $_SESSION['TICKET_CONTROLLER_MSG'] = "The ticket has been deleted successfully";
      echo '[{"isError":0, "message":"'.$_SESSION['TICKET_CONTROLLER_MSG'].'"}]';
      exit;
   }

  /**
   * Purpose : Closes a ticket via jQuery/Ajax
   *
   * @access : private
   * @param  : none
   * @return : void - prints string 
   *           in json format
   */
   private function closeTicket()
   {
      $ticket_id = $_REQUEST['ticket_id'];
      if(!$ticket_id)
      {
         echo '[{"isError":1, "message":"No ticket ID given"}]';
         exit;
      }
      
      $status_type = Utils::arrayAlterKeyValue(
         $GLOBALS['TICKET_STATUS_TYPE']
      );

      $data          = array();
      $data['table'] = TICKETS_TBL;
      $data['data']  = array(
         'status'  => $status_type['closed']
      );
      $data['where'] = "ticket_id = " . $ticket_id;
      try
      {
         $this->db->update($data);
      }
      catch(Exception $Exception){}

      $data          = array();
      $data['table'] = TICKET_ASSIGNMENTS_TBL;
      $data['data']  = array(
         'closed_date'  => date('Y-m-d H:i:s')
      );
      $data['where'] = "ticket_id = $ticket_id";
      try
      {
         $this->db->update($data);
      }
      catch(Exception $Exception){}


      $change_method = Utils::arrayAlterKeyValue(
         $GLOBALS['TICKET_STATUS_CHANGE_METHOD']
      );
      $data             = array();
      $data['table']    = TICKET_HISTORY_TBL;
      $data['data']     = array(
         'ticket_id'          => $ticket_id,
         'status'             => $status_type['closed'],
         'status_date'        => date('Y-m-d H:i:s'),
         'changed_by_user_id' => $_SESSION['LOGIN_USER']['userId'],
         'change_method'      => $change_method['web']
         );
      try
      {
         $this->db->insert($data);
      }
      catch(Exception $Exception){}
      
      //save log 
      $this->saveLog(TICKET_STATUS_CLOSE, $ticket_id, null);
      
      $_SESSION['TICKET_CONTROLLER_MSG'] = "The ticket has been closed successfully";
      echo '[{"isError":0, "message":"'.$_SESSION['TICKET_CONTROLLER_MSG'].'"}]';
      exit;
   }
   
   private function downloadAttachment()
   {
      $attachment_id = $this->param[2];

      $list = array();
      $sql  = "SELECT * 
               FROM " . TICKET_ATTACHMENTS_TBL . " 
               WHERE attachment_id = $attachment_id";
      try
      {
         $rows = $this->db->select($sql);
      }
      catch(Exception $Exception){}
      
      if($rows)
      {
         $file_name = $rows[0]->original_filename;
         $src = $rows[0]->server_fqpn;
         
         Utils::startDownloadStream($file_name, $src);
      }

      exit;
   }

  /**
   * Purpose : Shows ticket details
   *
   * @access : private
   * @param  : none
   * @return : string - html output
   */
   private function printTicketDetails()
   {
      $data = array();

      $ticket_id = $this->param[2];

      $data['ticket_status_type'] = Utils::arrayAlterKeyValue(
         $GLOBALS['TICKET_STATUS_TYPE']
      );
      
      $my_sources = array();
      foreach($_SESSION['LOGIN_USER']['sources'] as $index => $source)
      {
         $my_sources[$source->source_id] = $source->name;
      }
      $data['source_list'] = $my_sources;
      
      $sql  = "SELECT t.*, ts.source_id 
               FROM " . TICKETS_TBL . " as t, " . TICKET_SOURCES_TBL . " as ts 
               WHERE t.ticket_id = ts.ticket_id AND t.ticket_id = $ticket_id";
      try
      {
         $ticket = $this->db->select($sql);
      }
      catch(Exception $Exception){}

      $ticket              = isset($ticket[0]) ? $ticket[0] : new stdClass();
      $ticket->status_text = $GLOBALS['TICKET_STATUS_TYPE'][$ticket->status];
      $ticket->create_date = date('l jS F Y @ h:i A (T)', 
                                  strtotime($ticket->create_date));
      $data['ticket']      = $ticket;

      $sql  = "SELECT d.*, u.first_name, u.last_name  
               FROM " . TICKETS_DETAILS_TBL . " as d, " . USERS_TBL . " as u 
               WHERE d.user_id = u.user_id 
               AND d.ticket_id = $ticket_id 
               ORDER BY d.create_date desc";
      try
      {
         $list = $this->db->select($sql);
      }
      catch(Exception $Exception){}

       // if($list)
       // {
       //    foreach($list as $index => $row)
       //    {
       //       $list[$index]->create_date = date('l jS F Y @ h:i A (T)', 
       //                                         strtotime($row->create_date));
       //    }
       // }
       // 
       // $first_detail_pos = count($list)-1;
       // $first_detail = $list[$first_detail_pos];
       // unset($list[$first_detail_pos]);
       // 
       // $data['first_detail'] = $first_detail;
       // $data['detail_list'] = $list;
      
        if($list)
        {
           foreach($list as $index => $row)
           {
              $row->create_date = date('l jS F Y @ h:i A (T)', 
                                             strtotime($row->create_date));
              if($row->type == 1)
              {
                  $description = $row;
              }
              else
              {
                 $res[] = $row;
              }
           }
        }
        $data['first_detail'] = $description;
        $data['detail_list']  = $res;
      
      $list = array();
      $sql  = "SELECT * 
               FROM " . TICKET_ATTACHMENTS_TBL . " 
               WHERE deleted = 0 AND ticket_id = $ticket_id";
      try
      {
         $rows = $this->db->select($sql);
      }
      catch(Exception $Exception){}
      
      if($rows)
      {
         foreach($rows as $index => $row)
         {
            $list[$row->details_id][] = $row;
         }
      }
      $data['attachments_by_details_id'] = $list;

      $data['ticket_status_list']   = $GLOBALS['TICKET_STATUS_TYPE'];
      $data['login_user_id']        = $_SESSION['LOGIN_USER']['userId'];
      
      $data['add_detail_dialog'] = $this->template
                                   ->parseTemplate(TICKET_DETAIL_DIALOG_TEMPLATE, $data);

      $data['add_tag_dialog'] = $this->template
                                 ->parseTemplate(TICKET_TAG_DIALOG_TEMPLATE, $data);

      $data['is_source'] = count($_SESSION['LOGIN_USER']['authorized_sources']);
      $data['is_executive'] = Utils::isExecutive($ticket->source_id);
      
      //return $this->template->parseTemplate(TICKET_DETAIL_TEMPLATE, $data);
      echo $this->template->parseTemplate(PRINT_TICKET_DETAIL_TEMPLATE, $data);
      exit;
   }
   
   /**
   * save ticket rating
   *
   * @access public
   * @param  none
   * @return none
   */
   function saveTicketRating()
   {
      $ticketRating   = array();
      
      $sourceId       = Utils::getSourceIdByTicketId($this->db, $_REQUEST['ticket_id']);
      
      $isSuppervisor  = Utils::isSupervisor($sourceId);
      $isStaff        = Utils::isStaff($sourceId);
      if($isStaff && !$isSuppervisor)
      {
         echo '[{"isError":1, "message":"Staff can not rate this ticket.", "avgRating":0, "ratingSubmission":0}]';
         exit;
      }
      
      $user_id =  $_SESSION['LOGIN_USER']['userId'];
      
      $list = array();
      $sql  = "SELECT * FROM " . TICKET_RATING_TBL . " WHERE rated_by_uid = $user_id AND ticket_id = ".$_REQUEST['ticket_id']." LIMIT 1";
      try
      {
         $rows = $this->db->select($sql);
      }
      catch(Exception $Exception){}            
      
      $ticketRating['ticket_id']     = $_REQUEST['ticket_id'];
      $ticketRating['rating']        = $_REQUEST['rating'];
      $ticketRating['rated_by_uid']  = $user_id;
      $ticketRating['rated_on']      = date("Y-m-d H:i:s");
      $ticketRating['ipaddr']        = ip2long($_SERVER['REMOTE_ADDR']);
      
      $params['table']  = TICKET_RATING_TBL;
      $params['data']   = $ticketRating;
      
      if($rows[0]->ticket_id)
      {
         $params['where'] = " rated_by_uid = ".$_SESSION['LOGIN_USER']['userId']." AND ticket_id = ".$_REQUEST['ticket_id'];
         $this->db->update($params);         
      }
      else
      {
         $this->db->insert($params);
      }
      
      // select existing ticket rating of given ticket id
      $sql = "SELECT * FROM " . TICKET_RATING_SUMMARY_TBL . 
               " WHERE `ticket_id` = " . $_REQUEST['ticket_id'];
      
      $result = $this->db->select($sql);
      
      //utils::dumpvar($result);
      if(!empty($rows[0]->ticket_id))
      {
         $ratingSubmissions = $result[0]->rating_submissions;
         $result[0]->rating_sum = $result[0]->rating_sum - $rows[0]->rating;
      }
      else
      {
         $ratingSubmissions = $result[0]->rating_submissions + 1;         
      }
      $result[0]->rating_sum = $result[0]->rating_sum + $_REQUEST['rating'];
      $avgRating             = number_format((float)($result[0]->rating_sum)/(float)$ratingSubmissions, 2, '.', '');
      
      $ticketRatingSummary                       = array();
      $ticketRatingSummary['ticket_id']          = $_REQUEST['ticket_id'];
      $ticketRatingSummary['rating_submissions'] = $ratingSubmissions;
      $ticketRatingSummary['rating_sum']         = $result[0]->rating_sum;
      
      $params['table']  = TICKET_RATING_SUMMARY_TBL;    
      $params['data']   = $ticketRatingSummary;
              
      if(!empty($result[0]->ticket_id))
      {
         $params['where'] = " ticket_id = ".$_REQUEST['ticket_id'];
         $this->db->update($params);
      }
      else
      {
        $this->db->insert($params);
      }
      
      echo '[{"isError":0, "message":"Ticket has been rated successfully.", "avgRating":'.$avgRating.', "ratingSubmission":'.$ratingSubmissions.'}]';
      exit;
   }
   
   
   //method add by pushan
   private function saveLog($activityType = null,
                            $ticketId = null, 
                            $userId = null)
   {
      //get source id of ticket id
      $sourceId = Utils::getSourceIdByTicketId($this->db, $ticketId);
      
      $params  = array();
      $params['db_obj'] = $this->db;
      $activityLogObj   = new activityLog($params);
      
      if(empty($userId))
      {
         $userId = $this->getAssignedUserIdByTicketId($ticketId);
      }
      
      $activityLogObj->saveActivityLog($activityType,$ticketId, $userId, $sourceId );

      return true;
   }
   
   //method add by pushan
   private function getAssignedUserIdByTicketId($ticketId = null)
   {
      $userId = null;
      $sql = "SELECT user_id 
              FROM " . TICKET_ASSIGNMENTS_TBL . " 
              WHERE ticket_id = $ticketId";
      try
      {
         $rows = $this->db->select($sql);
      }
      catch(Exception $Exception){}
      if($rows)
      {
         foreach($rows as $index => $row)
         {
            $userId = $row;
         }
      }
      
      return $userId;
   }
   
   private function activityLogReport()
   {      
      //Utils::dumpvar($_SESSION);
      $where = 1;
      if(empty($this->param[2]))
      {
         $where .= " AND activity_type NOT IN(".USER_LOGGING. ",".USER_LOGOUT.") ";         
         if(!empty($_SESSION['SOURCES_ID_STRING']))
         {
            $where .=  " AND al.source_id IN ".$_SESSION['SOURCES_ID_STRING'];
         }
      }
      else
      {
         
      }
      
      $lastThirtyDay = date("Y-m-d", mktime(0, 0, 0, date("m")  , date("d")-30, date("Y")));
      
      $where .= " AND al.log_date >='$lastThirtyDay'";
      
      
      $sql = "SELECT al.log_date,
                     al.active_user,
                     al.activity_type,
                     al.affected_user, 
                     al.affected_ticket,
                     al.ipaddr,
                     al.source_id,
                     usr1.first_name as active_first_name,
                     usr1.last_name as active_last_name,
                     usr2.first_name as affected_first_name,
                     usr2.last_name as affected_last_name
              FROM " . ACTIVITY_LOG_TBL ." as al LEFT JOIN ".USERS_TBL." as usr1 ON(al.active_user =	usr1.user_id) LEFT JOIN ".USERS_TBL." as usr2 ON(al.affected_user = usr2.user_id) WHERE ".$where." ORDER BY log_date";      
      //echo $sql;
      try
      {
         $rows = $this->db->select($sql);
      }
      catch(Exception $Exception){}    
      
      $data['today'] = 'no';
      $data['yesterday'] = 'no';
      
      if($rows)
      {
         foreach($rows as $key => $value)
         {
            $date = date("m/d/Y",strtotime($value->log_date));
            if($date == date("m/d/Y"))
            {
               $date = 'Today';            
               $data['today'] = 'yes';
            }
            if($date == date("m/d/Y", mktime(0, 0, 0, date("m")  , date("d")-1, date("Y"))))
            {
               $date = 'Yesterday';
               $data['yesterday'] = 'yes';
            }         
            
            $logDate = date('l jS F Y @ h:i A (T)', strtotime($value->log_date));
            
            list($dateP, $timeP) = split("[@]",$logDate);
            
            $value->log_time = trim($timeP);
            $activityLog[$date][] = $value;
            krsort($activityLog[$date]);            
         }
         $data['activityLog'] = array_reverse($activityLog);
      }            
            
      return $this->template->parseTemplate(ACTIVITY_LOG_DETAIL_TEMPLATE, $data);
   }
   
   function correctDB()
   {
       $query = "SELECT ticket_id, details_id
                 FROM `ticket_details` GROUP BY ticket_id";
       $rows   = $this->db->select($query);
       if($rows)
       {
          foreach($rows as $row)
          {
             $params['db_link']   = $this->db;
             $params['ticket_id'] = 1;
             $obj                 = new Tickets($params);
             $data['type']        = 1;
             $obj->updateTicketDetails($row->details_id, $data);
          }
       }
   }

} //end of class
?>
