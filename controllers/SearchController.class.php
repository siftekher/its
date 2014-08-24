<?php

/*
 * Filename   : SearchController.class.php
 * Purpose    : 
 *
 * @author    : Sheikh Iftekhar <siftekher@gmail.com>
 * @project   : Issue tracking system
 * @version   : 1.0.0
 */

require_once('TicketListRenderer.class.php'); 
require_once('TicketListRenderer.class.php');

class Search
{
   private $dbLink;
   private $templateObj;
   private $cmdList;
   
   public function __construct($param)
   {
      $this->dbLink      = $param['db_link'];
      $this->templateObj = new Template();
      $this->cmdList     = $param['cmdList'];
   }

   function run()
   {
      $userId = $_SESSION['LOGIN_USER']['userId'];

      if($userId == null)
      {
         header('location: '.SUPER_CONTROLLER_URL_PREFIX . 'Login');
         exit;
      }
      
      $cmd = $this->cmdList[1];

      switch($cmd)
      {
         case 'keyword'        : $screen = $this->showSearchResult();         break;
         case 'tag'            : $screen = $this->showSearchResultByTag();    break;
         case 'date'           : $screen = $this->showSearchResult();         break;
         case 'ajax_list'      : $screen = $this->getTicketListForAjax();     break;
         case 'advancedSearch' : $screen = $this->getAdvanceSearchResult();   break;
         case 'ajax_search'    : $screen = $this->getAdvanceSearchForAjax();  break;
         
      }


      $data            = array();
      $data['tagList'] = Utils::getAllTagList($this->dbLink);

      $userList = new UserList($this->dbLink);
      $data['sourceUser']  = $userList->getUsersFromMySources();
      $data['source_id']   = $_SESSION['source_id'];
      $data['source_name'] = Utils::getSourceName($this->dbLink, $_SESSION['source_id']);
      echo $this->templateObj->createScreen($screen, $data);
      exit;
   }
   
   private function getTagId($searchText)
   {
      $params  = array();
      $params['db_link'] = $this->dbLink;
      
      $ticketTagObj = new TicketTag($params);
      $ticketTagObj->setTagName($searchText);
      
      return $ticketTagObj->checkExistingTag();
   }
   
   private function showSearchResultByTag()
   {
      $searchText = $this->processSearchText($this->cmdList[2]);

      $tagId = $this->getTagId($searchText);
      
      $ticketIds  = $this->searchTicketByTagId($tagId);
      if(count($ticketIds))
      {
         $ticketIds = implode(",", $ticketIds);
      }
      
      $sourcesId  = Utils::getSourcesIds();

      return $this->getAllTickets($ticketIds, $sourcesId);
   }
   
   


   private function showSearchResult()
   {
      $sourcesId  = Utils::getSourcesIds();
      $this->cmdList[2] = $_REQUEST['keyword'] ? $_REQUEST['keyword'] : $this->cmdList[2];

      $searchText = $this->processSearchText( $this->cmdList[2]);

      $tagId = $this->getTagId($searchText);

      $ticketIds  = $this->searchTicketByTagId($tagId);
      $ticketIds1 = $this->searchTicketById($searchText);

      //$ticketIds1 = $this->searchTicketByTitle($searchText);
      //$ticketIds2 = $this->searchTicketByDetails($searchText);
      //$ticketIds3 = $this->searchTicketByFileName($searchText);
      
      $ticketIds = array_unique(array_merge($ticketIds, $ticketIds1));
      
      if(count($ticketIds))
      {
         $ticketIds = implode(",", $ticketIds);
      }

      return $this->getAllTickets($ticketIds, $sourcesId);
   }
   

   
   private function getAllTickets($ticketIds = null, $sourceIds = null)
   {
      $where = "t.ticket_id IN($ticketIds)";
      
      $query = "SELECT ts.source_id, 
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
             WHERE $where AND ts.source_id IN($sourceIds)
             AND t.status != 9
             GROUP BY t.ticket_id ORDER BY t.ticket_id DESC, d.create_date DESC "; 

      $_SESSION['search_sql'] = $query;
      $TicketListRenderer = new TicketListRenderer($this->dbLink, $this->templateObj);
      $totalTicket = $TicketListRenderer->countTicketList($query);
      
      $data = array();
      $data['pagger_action_url'] = 'Search/ajax_list';
      $data['total_ticket']      = $totalTicket;
      return $html = $TicketListRenderer->getTicketListView($data);
   }

   private function getTicketListForAjax()
   {
     // Utils::dumpvar($this->cmdList);
      $limit       = $this->cmdList[2] ? $this->cmdList[2] : 0;
      $sqlPageSize = $this->sqlPageSize();
      
      $query = $_SESSION['search_sql'] . " LIMIT $limit, " . $sqlPageSize ;

      $TicketListRenderer = new TicketListRenderer($this->dbLink, $this->templateObj);
      $ticket_list = $TicketListRenderer->getTicketList($query);
      
      if($ticket_list)
      {
         echo implode("", $ticket_list);
      }

      exit;  
   }
   
   private function processSearchText($searchText = null)
   {
      return Utils::sanitize($searchText);
   }
   
   private function searchTicketById($ticketId = null)
   {
      $data  = array();
      
      if(empty($ticketId))
      {
         return $data;
      }
      $ticketId = trim($ticketId);
      /*
      if(! is_numeric($ticketId))
      {
         return $data;
      }
      */
      $query = "SELECT T.ticket_id FROM (tickets as T LEFT JOIN ticket_details AS TD on (T.ticket_id = TD.ticket_id)) 
                WHERE T.ticket_id = '" . $ticketId . "' OR T.title LIKE '%" . $ticketId ."%' OR
                TD.subject LIKE '%" . $ticketId ."%' OR TD.notes LIKE '%" . $ticketId ."%'";

      $result = $this->dbLink->select($query);

      if(count($result))
      {
         foreach($result as $key => $value)
         {
            $data[] = $value->ticket_id;
         }
      }

      return $data;
   }


   private function searchTicketByTagId($tagId = null)
   {
      $data   = array();
      if(empty($tagId)) return $data;
      
      $query  = "SELECT ticket_id FROM " . TICKETS_TAG_TBL . 
                " WHERE tag_id = ". $tagId;
      $result = $this->dbLink->select($query);

      if(count($result))
      {
         foreach($result as $key => $value)
         {
            $data[] = $value->ticket_id;
         }
      }

      return $data;
   }

   
   private function searchTicketByTitle($searchText = null)
   {
      $query  = "SELECT ticket_id FROM " . TICKETS_TBL . 
                " WHERE title LIKE '%". $searchText . "%'";
      $result = $this->dbLink->select($query);
      $data   = array();

      if(count($result))
      {
         foreach($result as $key => $value)
         {
            $data[] = $value->ticket_id;
         }
      }

      return $data;
   }



   private function searchTicketByDetails($searchText = null)
   {
      $query  = "SELECT ticket_id FROM " . TICKETS_DETAILS_TBL . 
                " WHERE notes LIKE '%". $searchText . "%'";
      $result = $this->dbLink->select($query);
      $data   = array();
      if(count($result))
      {
         foreach($result as $key => $value)
         {
            $data[] = $value->ticket_id;
         }
      }

      return $data;
   }
   
   
   private function searchTicketByFileName($searchText = null)
   {
      $query  = "SELECT ticket_id FROM " . TICKET_ATTACHMENTS_TBL . 
                " WHERE server_fqpn LIKE '%". $searchText . "%'";
      $result = $this->dbLink->select($query);
      $data   = array();
      if(count($result))
      {
         foreach($result as $key => $value)
         {
            $data[] = $value->ticket_id;
         }
      }

      return $data;
   }
   
   
   private function searchTicketByDate($startDate = null, $endDate = null)
   {
      $query  = " SELECT ticket_id FROM " . TICKETS_TBL . 
                " WHERE create_date BETWEEN $startDate AND $endDate";
      $result = $this->dbLink->select($query);
      $data   = array();
      if(count($result))
      {
         foreach($result as $key => $value)
         {
            $data[] = $value->ticket_id;
         }
      }

      return $data;
   }
   
   
   private function getAdvanceSearchResult()
   {
      $keywords   = $_REQUEST['search_keywords'];
      $ticketIds  = array();
      $ticketIds1 = array();
      $ticketIds2 = array();
      $ticketIds3 = array();
      $ticketIds4 = array();
      
      if($_REQUEST['search_title'] == 'on')
      {
         $ticketIds1 = $this->searchTicketByTitle($keywords);
      }

      if($_REQUEST['search_details'] == 'on')
      {
         $ticketIds2 = $this->searchTicketByDetails($keywords);
      
      }

      if($_REQUEST['search_tags'] == 'on')
      {
         $ticketIds3  = array();
         $tagId       = $this->getTagId($keywords);
         
         if($tagId != null)
         {
            $ticketIds3  = $this->searchTicketByTagId($tagId);
         }
      }

      if($_REQUEST['search_filename'] == 'on')
      {
         $ticketIds4 = $this->searchTicketByFileName($keywords);
      }
      
      $ticketIds = array_unique(array_merge($ticketIds1, $ticketIds2, $ticketIds3, $ticketIds4));

      if(count($ticketIds) > 0)
      {
         $ticketIds = implode(",", $ticketIds);
      }
      
      $startDate = ($_REQUEST['start_date'])? date("Y-m-d", strtotime($_REQUEST['start_date'])) : null;
      $endDate   = ($_REQUEST['end_date'])  ? date("Y-m-d", strtotime($_REQUEST['end_date']))   : null;
      
      
      return $this->getAdvanceSearchAllTickets($ticketIds, $sourcesId, $startDate, $endDate);
   }
   

   private function getAdvanceSearchAllTickets($ticketIds = null, $sourceIds = null, 
                                               $startDate = null, $endDate = null)
   {
      if(count($_REQUEST['userIds']))
      {
         $userIds  = implode(",",$_REQUEST['userIds']);
      }
      
      $where = "t.ticket_id IN($ticketIds)";
      if($startDate != null && $endDate != null)
      {
         $where .= " AND d.create_date BETWEEN '" . $startDate ."' AND '".$endDate."' ";
      }
      $sourceIds = Utils::getSourcesIds();
      
      $query = "SELECT ts.source_id, 
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
             WHERE $where AND ts.source_id IN($sourceIds)
             AND t.status != 9 OR d.user_id IN($userIds)
             GROUP BY t.ticket_id ORDER BY t.ticket_id, d.create_date ASC "; 

      $_SESSION['search_query'] = $query;
      $TicketListRenderer = new TicketListRenderer($this->dbLink, $this->templateObj);
      $totalTicket = $TicketListRenderer->countTicketList($query);
      
      $data = array();
      $data['pagger_action_url'] = 'Search/ajax_search';
      $data['total_ticket']      = $totalTicket;
      return $html = $TicketListRenderer->getTicketListView($data);
   }
   
   
   private function getAdvanceSearchForAjax()
   {
      $limit        = $this->cmdList[2] ? $this->cmdList[2] : 0;
      $sqlPageSize  = $this->sqlPageSize();
      
      $query = $_SESSION['search_query'] . " LIMIT $limit, " . $sqlPageSize ;
      
      $TicketListRenderer = new TicketListRenderer($this->dbLink, $this->templateObj);
      $ticket_list = $TicketListRenderer->getTicketList($query);
      
      if($ticket_list)
      {
         echo implode("", $ticket_list);
      }

      exit;  
   }
   
   private function sqlPageSize()
   {
      $settings    = Utils::getPersonalizedSettings($this->dbLink);
      $sqlPageSize = $settings->show_issues_per_page ? 
                     $settings->show_issues_per_page : SQL_PAGE_SIZE;
      
      return $sqlPageSize;

   }
}

?>