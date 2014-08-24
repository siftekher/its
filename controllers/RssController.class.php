<?php
/*
 * Filename   : RssController.class.php
 * Purpose    : 
 *
 * @author    : Sheikh Iftekhar <siftekher@gmail.com>
 * @project   : Issue tracking system
 * @version   : 1.0.0
 */

class Rss
{
   private $db;
   private $template;
   private $param;
   
   public function __construct($param)
   {
      $this->db = $param['db_link'];
      $this->template = new Template();
   }

   function run()
   {

      if(!isset($_SESSION['LOGIN_USER']['userId']))
      {
         // Redirects to Login controller if user is not logged in
         header('location: ' . SUPER_CONTROLLER_URL_PREFIX . 'Login');
         exit;
      }
            
      $screen = $this->showRss();
      echo $screen;
      exit;
   }
   
   private function showRss()
   {


      $userId = $_SESSION['LOGIN_USER']['userId'];
      
      $param = array(
         'user_id' => $userId,
         'source_id' => null,
         'start' => 0,
         'size' => 1
      );
      
      $sql         = $this->getTicketListSQL($param);
      $ticket_list = $this->getTicketList($sql);
      $rss         = $this->getTicketRss($ticket_list);
      
      return $rss;
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
                                     
      $my_sources = array();

      $status_type = Utils::arrayAlterKeyValue(
         $GLOBALS['TICKET_STATUS_TYPE']
      );
      
      unset($status_type['closed']);
      unset($status_type['deleted']);
      unset($status_type['completed']);
      unset($status_type['archived']);

      $status_type_str = implode(',', $status_type);
      
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
            WHERE ts.source_id IN {$_SESSION['SOURCES_ID_STRING']} AND 
                  t.status IN ($status_type_str) 
                  $where 
            GROUP BY t.ticket_id 
            ORDER BY t.ticket_id DESC, d.create_date ASC 
            "; 
      
               

      return $sql;        
   }   

  /**
   * Purpose : Gets ticket list
   *
   * @access : public
   * @param  : $sql - sql query
   * @return : array - list of ticket 
   */
   public function getTicketList($sql)
   {
      
      $tickets = array();
      
      try
      {
         $tickets = $this->db->select($sql);
      }
      catch(Exception $Exception){}

      return $tickets;
   }

  /**
   * Purpose : return rss for tickets 
   *
   * @access : public
   * @param  : $param - additional values
   * @return : string - xml output
   */

   public function getTicketRss($ticketList = array())
   {
      $data            = array();
      $data['tickets'] = $ticketList;
      
      return $this->template->parseTemplate(TICKET_RSS_TEMPLATE, $data);
   }
   
}

?>