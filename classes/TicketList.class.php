<?php
/**
* Filename   : TicketList.class.php
*
* @author    : Sheikh Iftekhar <siftekher@gmail.com>
* @project   : Issue tracking system
* @version   : 1.0.0
* @copyright : 
**/


class TicketList
{
   private $dbLink;

   const EXCEPTION_NO_DB_LINK = 'Please provide database link.';
   
   public function __construct($params = null)
   {
      if (empty($params['db_link']))
      {
          throw new Exception(SELF::EXCEPTION_NO_DB_LINK );
          return;
      }

      $this->dbLink = $params['db_link'];      
   }
   
   
   /**
   * 
   * @access private
   * @param  $status
   * @return none
   */
   private function getTicketsByStatus($status = null)
   {
      $query = "SELECT * FROM " . TICKETS_TBL . " WHERE status = " . $status ;
      
      return $this->dbLink->select($query);
   }

   /**
   * 
   * @access private
   * @param  none
   * @return none
   */
   private function getTicketsByDateRange($startDate, $endDate )
   {
      $query = "SELECT * FROM " . TICKETS_TBL . 
               " WHERE create_date BETWEEN " . 
               $startDate . " AND " . $endDate ;
      
      return $this->dbLink->select($query);
   }

   /**
   * 
   * @access private
   * @param  $keyword
   * @return none
   */
   private function getTicketsByKeyword($keyword = null)
   {
      
   }
   
  
   
   
}   // End of TicketList class

?>