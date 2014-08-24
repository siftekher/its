<?php
/**
* Filename   : TicketAuth.class.php
*
* @author    : Sheikh Iftekhar <siftekher@gmail.com>
* @project   : Issue tracking system
* @version   : 1.0.0
* @copyright : www.evoknow.com
**/


class TicketAuth
{
   private $dbLink;
   private $authKey;

   const EXCEPTION_NO_DB_LINK = 'No Database link provided.';

   public function __construct($params = array())
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
   * @access public
   * @param  none
   * @return none
   */
   public function getAuthKey()
   {
      return $this->authKey;
   }

   /**
   * 
   * @access public
   * @param  none
   * @return none
   */
   public function setAuthKey($authKey = null)
   {
      $this->authKey = $authKey;
   }
   /**
   * create
   * @access private
   * @param  $uid, $ticketId
   * @return $authKey
   */

   public function create($uid, $ticketId)
   {
      $this->authKey = md5($uid . $ticketId . Time());
      
      $params          = array();
      $params['table'] = TICKET_AUTH_KEY;

      $data              = array();
      $data['ticket_id'] = $ticketId ;
      $data['user_id']   = $uid ;
      $data['auth_key']  = $this->authKey ;

      $params['data'] = $data;

      $this->dbLink->insert($params);

      return $this->authKey;
   }

   /**
   * get Ticket
   * @access private
   * @param  none
   * @return Ticket Object
   */
   public function getTicket()
   {
      $uri = str_replace(SUPER_CONTROLLER_URL_PREFIX , 
                         '' , $_SERVER['REQUEST_URI']);

      $urlParts = explode('/', $uri);

      $authKey  = base64(array_pop($urlParts));

      $query = 'SELECT * FROM '. TICKET_AUTH_KEY .' WHERE auth_key = "' . $authKey .'"';
      $row   = $this->dbLink->select($query);

      if($row)
      {
         $params              = array();
         $params['db_link']   = $this->dbLink;
         $params['ticket_id'] = $row->ticket_id;
         
         return new Ticket($params);
      }

   }

   /**
   * get Url
   * @access private
   * @param  $controller
   * @return url
   */
   public function getUrl($controller = null)
   {
      return sprintf("http://%s/%s/%s", 
                     SUPER_CONTROLLER_URL_PREFIX,
                     $controller,
                     $this->getAuthKey());
   }


}   // End of TicketAuth class

?>