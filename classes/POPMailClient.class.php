<?php
/*
* Filename   : POPMailClient.class.php
* Purpose    : read pop or imap mail
*
* @author    : Sheikh Iftekhar <siftekher@gmail.com>
* @project   : Issue tracking system
* @version   : 1.0.0
*/

class POPMailClient
{
   private $strConnect;
   private $mailbox;
   private $mailServer ;
   private $username;
   private $password;
   private $serverType;
   private $port;
   private $folder;


   public function __construct($params = null)
   {
      if(isset($params['mailServer']))
      {
         $this->mailServer = $params['mailServer'];
      }
      if(isset($params['username']))
      {
         $this->username = $params['username'];
      }
      if(isset($params['password']))
      {
         $this->password = $params['password'];
      }
      if(isset($params['serverType']))
      {
         $this->serverType = $params['serverType'];
      }
      if(isset($params['port']))
      {
         $this->port = $params['port'];
      }
      if(isset($params['folder']))
      {
         $this->folder = $params['folder'];
      }

      $this->setConnection();
   }

   /**
   * set connecttion with pop3/imap server
   * @access public
   * @param  none
   * @return none
   */
   public function setConnection()
   {

      $user = substr($this->username, 0, strrpos($this->username, '@'));

      $this->strConnect ='{'.$this->mailServer . ':' . $this->port . $this->folder . $user .'}INBOX';  

      try
      {
         $this->mailbox = imap_open($this->strConnect, $this->username, $this->password);
      }
      catch(Exception $e)
      {
         echo "Unable to connect to mail server.";
      }

      
//      if(!$this->mailbox) echo 'Not Connected' . "\n";
//      else echo "Connected" . "\n";
      
      return true;
   }
   
   /**
   * Read new mail from pop3/imap server
   * @access public
   * @param  none
   * @return $mailData
   */
   public function getNewMail()
   {
      $headers = @imap_headers($this->mailbox);

      if(count($headers) < 1) return;

      $mailData = array();

      for($mid = 0; $mid <= count($headers); $mid++)
      {
         $mailHeader = @imap_header($this->mailbox, $mid);
          
         if ($mailHeader->Unseen != 'U') { continue; }

         $mailData[] = new Message($this->mailbox, $mailHeader);
         
         $mailHeader->Unseen = 'U';
      }
      
      return $mailData;
   }
   
   
   /**
   * get mail Server
   * @access public
   * @param  none
   * @return - name of the mail server
   */
   public function getMailServer()
   {
      return $this->mailServer;
   }
   
   
   /**
   * Get User name
   * @access public
   * @param  none
   * @return - username
   */
   public function getUsername()
   {
      return $this->username;
   }

   /**
   * Get password
   * @access public
   * @param  none
   * @return - password
   */
   public function getPassword()
   {
      return $this->password;
   }

   /**
   * Get Server Type
   * @access public
   * @param  none
   * @return - serverType
   */
   public function getServerType()
   {
      return $this->serverType;
   }

   /**
   * Get Port
   * @access public
   * @param  none
   * @return - port
   */
   public function getPort()
   {
      return $this->port;
   }
   
   /**
   * getFolder
   * @access public
   * @param  noner
   * @return - folder name
   */
   public function getFolder()
   {
      return $this->folder;
   }

   /**
   * Set Mail server
   * @access public
   * @param  $mailServar - name of the mail server
   * @return none
   */
   public function setMailServer($mailServer = null)
   {
      $this->mailServer = $mailServer;
   }
   /**
   * Set user name
   * @access public
   * @param  $username - name of the user
   * @return none
   */
   public function setUsername($username = null)
   {
      $this->username = $username;
   }

   /**
   * Set Password
   * @access public
   * @param  $password - password of the user
   * @return none
   */
   public function setPassword($password = null)
   {
      $this->password = $password;
   }

   /**
   * Set Server Type
   * @access public
   * @param  $serverType - Type of the Server (pop3/imap)
   * @return none
   */
   public function setServerType($serverType = null)
   {
      $this->serverType = $serverType;
   }

   /**
   * Set port
   * @access public
   * @param  $port - port no
   * @return none
   */
   public function setPort($port = null)
   {
      $this->port = $port;
   }

   /**
   * Set folder
   * @access public
   * @param  $folder - name of the folder
   * @return none
   */
   public function setFolder($folder = null)
   {
      $this->folder = $folder;
   }

   public function __destruct()
   {
      @imap_close($this->mailbox);
   }

}

?>
