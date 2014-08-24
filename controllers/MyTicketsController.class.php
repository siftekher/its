<?php

/*
 * Filename   : MyTicketsController.class.php
 * Purpose    : 
 *
 * @author    : Sheikh Iftekhar <siftekher@gmail.com>
 * @project   : Issue Tracking System
 * @version   : 1.0
 */

require_once('TicketListRenderer.class.php');

class MyTickets
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
      $userId = $_SESSION['LOGIN_USER']['userId'];

      if($userId == null)
      {
         header('location: '.SUPER_CONTROLLER_URL_PREFIX . 'Login');
         exit;
      }
      $data  = array();
      $cmd   = $this->cmdList[1];

      switch ($cmd)
      {         
         case 'ajax_list'     : $screen = $this->getTicketListForAjax();       break;
         case 'assigned_ajax' : $screen = $this->getAssignedTicketListForAjax; break;
         case 'myproject'     : $screen = $this->showMyProjects();             break;
         case 'assigned'      : $screen = $this->showAssignedTicketsList();    break;
         case 'list'          : $screen = $this->showMyTicketsList();          break;
         default              : $screen = $this->showMyTicketsList();
      }
      
      if($cmd == 'myproject')
      {
         if($this->cmdList[2] == null)
         {
            $_SESSION['SOURCES_ID_STRING'] = '(' . Utils::getSourcesIdAsString() . ')';
         }
         else
         {
            $_SESSION['SOURCES_ID_STRING'] = '(' . $this->cmdList[2] . ')';
         }
         
         $_SESSION['source_id'] = $this->cmdList[2];
         
      }
      
      
      $data['topnav']  = 'ticket';
      $data['tagList'] = Utils::getAllTagList($this->db);
      
      $userList = new UserList($this->db);
      $data['sourceUser']  = $userList->getUsersFromMySources();
      $data['source_id']   = $_SESSION['source_id'];
      $data['source_name'] = Utils::getSourceName($this->db , $_SESSION['source_id']);
      
      echo $this->template->createScreen($screen, $data);
      exit; 
   }


   private function getTicketListForAjax()
   {
      $page        = isset($this->cmdList[4]) ? $this->cmdList[4] : 0;
      $determinant = $this->cmdList[2]; 

      $user_id = $this->cmdList[3] ? $this->cmdList[3] : $_SESSION['LOGIN_USER']['userId'];
      $TicketListRenderer = new TicketListRenderer($this->db, $this->template);
      
      
      if($determinant == 'assigned')
      {
         $sql = $this->getAssignedTicketListSQL($user_id, null, $page, SQL_PAGE_SIZE);
      }      
      elseif($determinant == 'myticket')
      {
      	 $sql = $this->getTicketListSQL($user_id, null, $page, SQL_PAGE_SIZE);
      }      
      else
      {
      	 $sql = $this->getTicketListSQL(null, $determinant, $page, SQL_PAGE_SIZE);
      }
                  
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
   

   private function getAssignedTicketListForAjax()
   {
      $page = isset($this->cmdList[2]) ? $this->cmdList[2] : 0;

      $user_id = $_SESSION['LOGIN_USER']['userId'];
      $TicketListRenderer = new TicketListRenderer($this->db, $this->template);
      $sql = $this->getAssignedTicketListSQL($user_id, null, $page, SQL_PAGE_SIZE);
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
   * Show all tickets created by me
   *
   * @access public
   * @param  none
   * @return none
   */
   function showMyTicketsList($msg = null)
   {                             
      $data    = array();
      $user_id = $this->cmdList[2] ? $this->cmdList[2] : $_SESSION['LOGIN_USER']['userId'];
      
      
      $TicketListRenderer = new TicketListRenderer($this->db, $this->template);
      $sql = $this->getTicketListSQL($user_id);
      
      $total_ticket = $TicketListRenderer->countTicketList($sql);

      
      $data['pagger_action_url'] = 'MyTickets/ajax_list/myticket/' . $user_id;
      $data['total_ticket']      = $total_ticket;
      
      if($total_ticket > 0)      
      {
         $html = $TicketListRenderer->getTicketListView($data);
      }
      else
      {
      	 $data['msg']   = EMPTY_TICKET_MESSAGE;
      	 $data['title'] = 'My Tickets';
         $html = $this->template->parseTemplate(EMPTY_TICKET_TEMPLATE, $data);
      }

      return $html;
   }
   
  /**
   * Show all tickets assigned to me
   *
   * @access public
   * @param  none
   * @return none
   */
   function showAssignedTicketsList($msg = null)
   {                       
      $data    = array();   
      $user_id = $_SESSION['LOGIN_USER']['userId'];                   
      $source_id  = $this->cmdList[2];            
      
      $TicketListRenderer = new TicketListRenderer($this->db, $this->template);
      $sql = $this->getAssignedTicketListSQL($user_id,null,null,null);
      $total_ticket = $TicketListRenderer->countTicketList($sql);
            
      $data['pagger_action_url'] = 'MyTickets/ajax_list/assigned/' . $user_id;
      $data['total_ticket']      = $total_ticket;
      
      
      if($total_ticket > 0)      
      {
         $html = $TicketListRenderer->getTicketListView($data);
      }
      else
      {
      	 $data['msg']   = ASSIGNED_EMPTY_TICKET_MESSAGE;
      	 $data['title'] = 'Assigned Tickets';
          $html = $this->template->parseTemplate(EMPTY_TICKET_TEMPLATE, $data);
      }                     

      return $html;            
   }        
                 
   
   /**
   * Show all projects that I am involved with 
   *
   * @access public
   * @param  none
   * @return none
   */
   function showMyProjects($msg = null)
   {                       
      $data       = array();        
      $user_id    = $_SESSION['LOGIN_USER']['userId'];                    
      $source_id  = $this->cmdList[2];            
      
      $TicketListRenderer = new TicketListRenderer($this->db, $this->template);
      $sql = $this->getTicketListSQL(null,$source_id,null,null);
      $total_ticket = $TicketListRenderer->countTicketList($sql);
            
      $data['pagger_action_url'] = 'MyTickets/ajax_list/'. $source_id . '/' . $user_id;
      $data['total_ticket']      = $total_ticket;
      
      if($total_ticket > 0)      
      {
         $html = $TicketListRenderer->getTicketListView($data);
      }
      else
      {
      	$data['msg']   = SOURCE_EMPTY_TICKET_MESSAGE;
      	$data['title'] = 'Source Tickets';
         $html = $this->template->parseTemplate(EMPTY_TICKET_TEMPLATE, $data);
      }                     

      return $html;      
   }    
      

   private function getTicketListSQL($user_id = null, 
                                     $source_id = null, 
                                     $start = null, 
                                     $size = null)
   {
      $my_sources = array();
      foreach($_SESSION['LOGIN_USER']['sources'] as $index => $source)
      {
         $my_sources[$source->source_id] = $source->name;
      }

      $source_id_str = implode(",", array_keys($my_sources));

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
      $where .= $source_id ? " AND ts.source_id = $source_id" : "";
      $limit = ($start !== false && $size) ? " LIMIT $start, $size" : "";

      $sql = "SELECT ts.source_id, 
                     t.ticket_id, 
                     t.title, 
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
             WHERE ts.source_id IN ($source_id_str) AND 
                   t.status IN ($status_type_str) 
                   $where 
             GROUP BY t.ticket_id 
             ORDER BY t.ticket_id DESC, d.create_date ASC 
             $limit"; 

      return $sql;  
   }
   
   private function getAssignedTicketListSQL($user_id = null, 
                                     $source_id = null, 
                                     $start = null, 
                                     $size = null)
   {
      $my_sources = array();
      foreach($_SESSION['LOGIN_USER']['sources'] as $index => $source)
      {
         $my_sources[$source->source_id] = $source->name;
      }

      $source_id_str = implode(",", array_keys($my_sources));

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
      $where .= $source_id ? " AND ts.source_id = $source_id" : "";
      $limit = ($start !== false && $size) ? " LIMIT $start, $size" : "";

      $sql = "SELECT t.ticket_id, 
                     t.title, 
                     t.status, 
                     t.create_date, 
                     d.notes, 
                     d.user_id, 
                     u.user_id, 
                     u.first_name, 
                     u.last_name 
             FROM " . TICKET_ASSIGNMENTS_TBL . " AS ta
             LEFT JOIN " . TICKETS_DETAILS_TBL . " AS d
             ON ta.user_id = d.user_id
             LEFT JOIN " . TICKETS_TBL . " as t
             ON ta.ticket_id = t.ticket_id
             LEFT JOIN " . USERS_TBL . " AS u
             ON d.user_id = u.user_id
             WHERE t.status IN ($status_type_str)
                   $where
             GROUP BY t.ticket_id ORDER BY t.ticket_id DESC, d.create_date ASC $limit"; 

      return $sql;  
   }   
}
?>
