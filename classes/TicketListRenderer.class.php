<?php

/*
 * Filename   : TicketListRenderer.class.php
 * Purpose    : 
 *
 * @author    : Sheikh Iftekhar <siftekher@gmail.com>
 * @project   : Issue tracking system
 * @version   : 1.0.0
 * @copyright : 
 */

class TicketListRenderer 
{
   private $database;
   private $template;

   public function __construct($database, $template)
   {
      $this->database = $database;
      $this->template = $template;
   }

  /**
   * Purpose : Counts number of ticket
   *
   * @access : public
   * @param  : $sql - string - sql query
   * @return : int - ticket count
   */
   public function countTicketList($sql)
   {
      $count = 0;

      $sql = strtolower($sql);

      if(!strpos($sql, 'sql_calc_found_rows'))
      {
         $sql = str_replace('select', 'select sql_calc_found_rows ', $sql);
      }

      $pos = strpos($sql, 'limit');
      if($pos)
      {
         $sql = substr($sql, 0, $pos);
      }
      $sql .= 'limit 1';

      try
      {
         $this->database->select($sql);
         $rows = $this->database->select("SELECT FOUND_ROWS() as total_rows");
         
         if(isset($rows[0]))
         {
            $count = $rows[0]->total_rows;
         }
      }
      catch(Exception $Exception){}
      
      return $count;
   }

  /**
   * Purpose : Gets ticket list in html formated
   *
   * @access : public
   * @param  : $sql - sql query
   * @return : array - list of ticket as html view
   */
   public function getTicketList($sql)
   {
      $my_sources = array();
      foreach($_SESSION['LOGIN_USER']['sources'] as $index => $source)
      {
         $my_sources[$source->source_id] = $source->name;
      }
      
      try
      {
         $rows = $this->database->select($sql);
      }
      catch(Exception $Exception){}

      $list = array();
      if($rows)
      {
         $priorityColorSettings = Utils::getPriorityColorSettings($this->database,$_SESSION['LOGIN_USER']['userId']);         
         
         foreach($rows as $index => $row)
         {            
            $row->status            = $GLOBALS['TICKET_STATUS_TYPE'][$row->status];
            $row->create_date       = date('l jS F Y @ h:i A (T)', 
                                           strtotime($row->create_date));
            $row->color                = $priorityColorSettings[$row->priority];
            $param                     = array();
            $param['ticket']           = $row;
            $param['ticket']->notes    = $this->getNotes($row->ticket_id);
            $param['source_list']      = $my_sources;
            $param['tag_list']         = Utils::getTagListByTicketId($this->database,$row->ticket_id);     
            $param['self_assign_flag'] = Utils::isResolverUser($row->source_id);   
          // Utils::dumpvar($param);
            $list[] = $this->template->parseTemplate(TICKET_SUMMARY_TEMPLATE, $param);
         }        
      }
      return $list;
   }
   
  

  /**
   * Purpose : return html view for ticket list screen
   *
   * @access : public
   * @param  : $param - additional values
   * @return : string - html output
   */

   public function getTicketListView($param = array())
   {
      $data = array();

      //$data['total_ticket'] = $param['total_ticket'];
      //$data['pagger_action_url'] = $param['pagger_action_url']; //controller/cmd
      
      $data['ticket_status_list'] = $GLOBALS['TICKET_STATUS_TYPE'];

      $data['login_user_id']      = $_SESSION['LOGIN_USER']['userId'];
      $data['is_resolver']        = Utils::isResolverUser(1);
      $data['add_detail_dialog']  = $this->template
                                    ->parseTemplate(TICKET_DETAIL_DIALOG_TEMPLATE,$data);

      $data['add_tag_dialog']     = $this->template
                                      ->parseTemplate(TICKET_TAG_DIALOG_TEMPLATE);

      $data['message_box_cyan']     = $this->template
                                      ->parseTemplate(MESSAGE_BOX_CYAN);
                                      
      $data['pagger_action_url']  = $param['pagger_action_url'];

      
      $settings = Utils::getPersonalizedSettings($this->database);
      $data['sql_page_size']  =  $settings->show_issues_per_page ? 
                                 $settings->show_issues_per_page : 
                                 SQL_PAGE_SIZE;

      $data = array_merge($data, $param);

      return $this->template->parseTemplate(TICKET_LIST_TEMPLATE, $data);
   }
   
   public function getNotes($ticketId)
   {
       $query  = "SELECT d.*, u.first_name, u.last_name  
                FROM " . TICKETS_DETAILS_TBL . " as d, " . USERS_TBL . " as u 
                WHERE d.user_id = u.user_id AND type = 1
                AND d.ticket_id = $ticketId";
               
        $row   = $this->database->select($query);
        return $row[0]->notes;
   }
   
   
} // end of class