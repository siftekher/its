<?php
/*
* Filename   : Email.class.php
* Purpose    : Sends a SMTP email using PHPMailer
*
* @author    : Sheikh Iftekhar <siftekher@gmail.com>
* @project   : Issue tracking system
* @version   : 1.0.0
*/

class Email
{
   private $org_domain;
   private $mail;
   private $dbObj;
   private $host       = SMTP_HOST;         //smtp host
   private $priority   = EMAIL_PRIORITY;    //priority
   private $from       = EMAIL_FROM;        //email from
   private $sender     = EMAIL_SENDER;      //indicates ReturnPath header
   private $addReplyTo = EMAIL_REPLAY;      //indicates ReplyTo headers
   private $fromName   = EMAIL_FROM_NAME;   //email sender name
   private $charSet    = CHAR_SET;          //specify the charset used
   private $smtpFlag   = SMTP_FLAG;         //boolea flag, true or false
   private $appName;
   
   const EXCEPTION_NO_DB_LINK = 'Please provide database link.';
   
   public function __construct($params = null)
   {      
      if (empty($params['db_obj']))
      {
          throw new Exception(SELF::EXCEPTION_NO_DB_LINK );
          return;
      }
      
      $this->dbObj   = $params['db_obj'];
      $this->appName = $params['app_name'];
      $this->mail    = new PHPMailer();
   }

   /**
   * Get Org Domain name
   * @access public
   * @param  none
   * @return none
   */
   public function getOrgDomain()
   {
      return $this->org_domain ;
   }

   /**
   * Set Org Domain name
   * @access public
   * @param  $org - string
   * @return none
   */
   public function setOrgDomain($org = null)
   {
      $this->org_domain = trim($org);
   }

   /**
   * Get Host name
   * @access public
   * @param  none
   * @return none
   */
   public function getHost()
   {
      return $this->host;
   }

   /**
   * Set Host name
   * @access public
   * @param  $host - name of the host
   * @return none
   */
   public function setHost($host = null)
   {
      $this->host = $host ? $host : $this->host;
   }

   /**
   * Get Priority
   * @access public
   * @param  none
   * @return none
   */
   public function getPriority()
   {
      return $this->priority;
   }

   /**
   * Set Priority
   * @access public
   * @param  $priority - integer
   * @return none
   */
   public function setPriority($priority = null)
   {
      $this->priority = $priority ? $priority : $this->priority;
   }

   /**
   * Get From
   * @access public
   * @param  none
   * @return none
   */
   public function getFrom()
   {
      return $this->from;
   }

   /**
   * Set From
   * @access public
   * @param  $from - string
   * @return none
   */
   public function setFrom($from = null)
   {
      $this->from = $from ? $from : $this->from;
   }

   /**
   * Get From Name
   * @access public
   * @param  none
   * @return none
   */
   public function getFromName()
   {
      return $this->fromName;
   }

   /**
   * Set From Name
   * @access public
   * @param  $fromName - string
   * @return none
   */
   public function setFromName($fromName = null)
   {
      $this->fromName = $fromName ? $fromName : $this->fromName;
   }

   /**
   * Get Sender
   * @access public
   * @param  none
   * @return none
   */
   public function getSender()
   {
      return $this->sender;
   }

   /**
   * Set Sender
   * @access public
   * @param  $sender - string
   * @return none
   */
   public function setSender($sender = null)
   {
      $this->sender = $sender ? $sender : $this->sender;
   }

   /**
   * Get add reply to
   * @access public
   * @param  none
   * @return none
   */
   public function getAddReplyTo()
   {
      return $this->addReplyTo;
   }

   /**
   * Set add reply to
   * @access public
   * @param  $addReplyTo - string
   * @return none
   */
   public function setAddReplyTo($addReplyTo = null)
   {
      $this->addReplyTo = $addReplyTo ? $addReplyTo : $this->addReplyTo;
   }

   /**
   * Get Character Set
   * @access public
   * @param  none
   * @return none
   */
   public function getCharSet()
   {
      return $this->charSet;
   }

   /**
   * Set Character Set
   * @access public
   * @param  $charset - string
   * @return none
   */
   public function setCharSet($charset = null )
   {
      $this->charSet = $charset ? $charset : $this->charSet;
   }

   /**
   * Get SMTP Flag
   * @access public
   * @param  none
   * @return none
   */
   public function getSmtpFlag()
   {
      return $this->smtpFlag;
   }

   /**
   * Set SMTP Flag
   * @access public
   * @param  $smtpFlag - boolean(true or false)
   * @return none
   */
   public function setSmtpFlag($smtpFlag = null)
   {
      $this->smtpFlag = $smtpFlag ? $smtpFlag : $this->smtpFlag;
   }
   /**
   * @access public
   * @param  $mailData - mixed array
   * @return - 1 (success) or 0 (failed) 
   */
   public function send($mailData = null)
   {
      return $this->createEnvelope($mailData);
   }

   /**
   * This method Sends a SMTP email
   * @access private
   * @param  $mailData - mixed array
   * @return $status   - 1 (success) or 0 (failed)
   */
   private function createEnvelope($params = null)
   {
      $toList  = (is_array($params['to']))  
                 ? $params['to']  
                 : array($params['to']  => $params['to']);
      $ccList  = (is_array($params['cc']))  
                 ? $params['cc']  
                 : array($params['cc']  => $params['cc']);
      $bccList = (is_array($params['bcc'])) 
                 ? $params['bcc'] 
                 : array($params['bcc'] => $params['bcc']);
      
      $attachList = (is_array($params['attachment'])) 
                  ? $params['to'] 
                  : array($params['attachment'] => $params['attachment']);

      $params['is_html'] ? $this->mail->IsHTML(true) : $this->mail->IsHTML(false);

      $this->mail->Host       = $this->host;
      $this->mail->Prority    = $this->priority;
      $this->mail->From       = $this->from;
      $this->mail->Sender     = $this->sender;
      $this->mail->FromName   = $this->fromName;
      $this->mail->AddReplyTo = $this->addReplyTo;
      $this->mail->Subject    = $params['subject'];
      $this->mail->Body       = $params['body'];
      $this->mail->AltBody    = $params['alt_body'];
      $this->mail->IsSMTP($this->smtpFlag);

      while (isset($params['to']) && list($toName, $toEmail) = each($toList))
      {
         $this->mail->AddAddress($toEmail, $toName);
      }
      
      while (isset($params['cc']) && list($ccName, $ccEmail) = each($ccList))
      {
         $this->mail->AddCC($ccEmail, $ccName);
      }

      while (isset($params['bcc']) && list($bccName, $bccEmail) = each($bccList))
      {
         $this->mail->AddBCC($bccEmail, $bccName);
      }
      
      while (isset($params['attachment']) && list($attachFile) = each($attachList))
      {
         $this->mail->AddAttachment($attachFile);
      }

      $status = $this->mail->Send();

      //$this->log($params);
      
      return $status;
   }

   public function read($params = null)
   {
      // read pop or imap mail (least priority for implementation)
      // might use third-party pop/imap client classes
   }


   /**
   * This method put a log in DB
   * @access private
   * @param  $emailAddress - string
   *         $status       - int
   * @return none
   */
   private function log($mailData = null)
   {
      try
      {
         $headers = array();
         $headers['to']     = $mailData['to'];
         $headers['cc']     = $mailData['cc'];
         $headers['bcc']    = $mailData['bcc'];
         $headers['from']   = $this->from;
         $headers['sender'] = $this->sender;
         
         $data    = array();
         $data['headers']   = serialize($headers);
         $data['subject']   = $mailData['subject'];
         $data['body']      = $mailData['body'];
         $data['send_date'] = date('Y-m-d');
         $data['app_name']  = $this->appName;
         
         $params  = array();
         $params['table'] = EMAIL_LOGS;
         $params['data']  = $data;

         $this->dbObj->insert($params);
      }
      catch(Exception $e)
      {
         echo "Log failed: " . $e->getMessage() . "\n";
      }
   }
   
   
   public function __destruct()
   {
      //$this->mail->ClearAddresses();
      //$this->mail->ClearAttachments();
   } 
   

} // End of Email class

?>
