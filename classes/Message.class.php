<?php
/*
 * Filename   : Message.class.php
 * Purpose    : Read mail message and its elements
 *
 * @author    : Sheikh Iftekhar <siftekher@gmail.com>
 * @project   : Issue tracking system
 * @version   : 1.0.0
  */

class Message 
{
   private $mailbox;
   private $msgno;
   private $header;
   private $structure;
   private $text_body;
   private $html_body;
   private $primary_mime_type;

  /**
   * Purpose : Message object initiator
   *
   * @access : public
   * @param  : $mailbox - connection stream to the mailbox
   * @param  : $header - header object of the email-message
   * @return : none
   */
   public function __construct($mailbox, $header)
   {
      $this->mailbox = $mailbox;
      $this->header  = $header;
      $this->msgno   = $this->header->Msgno;

      $this->primary_mime_type = array("text", "multipart", "message", 
                                       "application", "audio", "image", 
                                       "video", "other");
   }

  /**
   * Purpose : Returns structure of the email-message
   * 
   * @access : private
   * @param  : none
   * @return : Object - stdObject
   */
   private function getStructure()
   {
      if(!$this->structure)
      {
         $this->structure = imap_fetchstructure($this->mailbox, 
                                                $this->msgno);
      }

      return $this->structure;
   }

  /**
   * Purpose : Returns particular header element from email-message
   *           or all header elements from email-message
   * 
   * @access : public
   * @param  : $key - string - name of header element
   * @return : string | array
   */
   public function getHeader($key = null)
   {
      return $key ? $this->header->$key : $this->header;
   }

  /**
   * Purpose : Returns email-message no
   * 
   * @access : public
   * @param  : none
   * @return : int
   */
   public function getMessageNo()
   {
      return $this->msgno;
   }

  /**
   * Purpose : Returns email-message subject
   * 
   * @access : public
   * @param  : none
   * @return : string
   */
   public function getSubject()
   {
      return $this->getHeader(MSG_HEADER_SUBJECT);
   }

  /**
   * Purpose : Returns email-message date
   * 
   * @access : public
   * @param  : none
   * @return : string
   */
   public function getMessageDate()
   {
      return $this->getHeader(MSG_HEADER_DATE);
   }

  /**
   * Purpose : Returns from-address of email-message
   * 
   * @access : public
   * @param  : none
   * @return : string
   */
   public function getFromAddress()
   {
      $from  = $this->getHeader(MSG_HEADER_FROM);

      return $this->getFormattedAddress($from);
   }

  /**
   * Purpose : Returns to-address of email-message
   * 
   * @access : public
   * @param  : none
   * @return : string
   */
   public function getToAddress()
   {
      $to    = $this->getHeader(MSG_HEADER_TO);

      return $this->getFormattedAddress($to);
   }

  /**
   * Purpose : Returns reply-to address of email-message
   * 
   * @access : public
   * @param  : none
   * @return : string
   */
   public function getReplyToAddress()
   {
      $reply_to   = $this->getHeader(MSG_HEADER_REPLY_TO);

      return $this->getFormattedAddress($reply_to);
   }

  /**
   * Purpose : Returns mail address
   * 
   * @access : private
   * @param  : header element of address
   * @return : 
   */
   private function getFormattedAddress($data)
   {
      return $data[0]->mailbox."@".$data[0]->host;
   }
  
  /**
   * Purpose : Returns email-message body
   * 
   * @access : public
   * @param  : none
   * @return : string
   */
   public function getMessageBody()
   {
      $msgBody    = $this->extractBodyFromMessagePart("text/html");

      if (!$msgBody)
      {
         $msgBody = $this->extractBodyFromMessagePart("text/plain");
      }

      return $msgBody;
   }

   public function cleanupMessageBody($body = null)
   {
      // Remove forwarded content (if any)
      $body  = preg_replace("/\r\n/", "\n", $body);
      $lines = explode("\n", $body);

      foreach($lines as $thisLine)
      {
         $thisLine = trim($thisLine);

         if ( !preg_match('/^>\s+/', $thisLine) &&
             !preg_match('/^>$/', $thisLine) &&
             !preg_match('/^On\s+(.+)\s+wrote:\s*$/', $thisLine)
            )
         { 
            echo "SAVING $thisLine \n";
            $cleaned .= $thisLine . "\n";
         }
       }

echo "--- original msg ---\n";
echo $body;
echo "\n--- altered msg ---\n";
echo $cleaned;
       return $cleaned;
   }

  /**
   * Purpose : Returns raw body of email-message
   * 
   * @access : private
   * @param  : none
   * @return : string
   */
   private function getRawMessageBody()
   {
      return imap_body($this->mailbox, $this->getMessageNo());
   }

  /**
   * Purpose : determines if the email-message format is html or text
   * 
   * @access : public
   * @param  : none
   * @return : boolean - true/false
   */
   public function isHTML()
   {
      $mime_type        = $this->getMimeType($this->getStructure());
      
      return ($mime_type == 'text/plain') ? false : true;
   }

  /**
   * Purpose : Returns html body of email-message
   * 
   * @access : public
   * @param  : $FLAG_EXCLUDE_ATTACHMENT - true/false - optional
   * @return : string
   */
   public function getHTMLBody($FLAG_EXCLUDE_ATTACHMENT = false)
   {
      if(!$this->html_body)
      {
         $this->html_body = $FLAG_EXCLUDE_ATTACHMENT ? 
                            $this->getMessageBody() : 
                            $this->getRawMessageBody();
      }

      return $this->cleanupMessageBody(trim($this->html_body));
   }

  /**
   * Purpose : Returns text body of email-message
   * 
   * @access : public
   * @param  : $FLAG_EXCLUDE_ATTACHMENT - true/false - optional
   * @return : string
   */
   public function getTextBody($FLAG_EXCLUDE_ATTACHMENT = false)
   {
      if(!$this->text_body)
      {
         $this->text_body = $FLAG_EXCLUDE_ATTACHMENT ? 
                            $this->extractBodyFromMessagePart("text/plain") : 
                            $this->getRawMessageBody();
      }

      return $this->cleanupMessageBody(trim($this->text_body));
   }

  /**
   * Purpose : Returns type of email-message from mail header
   * 
   * @access : private
   * @param  : $structure - structure of the email-message
   * @return : string
   */
   private function getMimeType($structure)
   {
      if($structure->subtype)
      {
         return $this->primary_mime_type[$structure->type] . '/' . 
                strtolower($structure->subtype);
      }
      else
      {
         return "text/plain";
      }
   }

  /**
   * Purpose : Extract content from part of email-message
   * 
   * @access : private
   * @param  : $mime_type - content type of the email-message
   * @param  : $structure - structure of the email-message
   * @param  : $part_number - part number of email-message structure
   * @return : string
   */
   private function extractBodyFromMessagePart($mime_type, 
                                               $structure = false, 
                                               $part_number = false)
   {
      if(!$structure)
      {
         $structure = imap_fetchstructure($this->mailbox, 
                                          $this->getMessageNo());
      }

      if($structure)
      {
         if($mime_type == $this->getMimeType($structure))
         {
            if(!$part_number)
            {
               $part_number = "1";
            }
            $text = imap_fetchbody($this->mailbox, 
                                   $this->getMessageNo(), 
                                   $part_number);

            if($structure->encoding == 3)
            {
               return imap_base64($text);
            }
            else if($structure->encoding == 4)
            {
               return imap_qprint($text);
            }
            else
            {
               return $text;
            }
         }

         if($structure->type == 1) /* multipart */
         {
            while(list($index, $sub_structure) = each($structure->parts))
            {
               if($part_number)
               {
                  $prefix = $part_number . '.';
               }
               $data = $this->extractBodyFromMessagePart($mime_type, 
                                             $sub_structure, 
                                             $prefix . ($index + 1));
               if($data)
               {
                  return $data;
               }
            }
         }
      }

      return false;
   }

  /**
   * Purpose : Returns attachments of message
   * 
   * @access : public
   * @param  : none
   * @return : Object - Attachment object
   */
   public function getAttachments($sourceId, $ticketId)
   {
      $attachmentObj = array();
      $structure     = $this->getStructure();
      
      if(isset($structure->parts) && count($structure->parts)) 
      {
         for($i = 0; $i < count($structure->parts); $i++) 
         {
            $attachmentObj[] = new Attachment($this->mailbox, 
                                              $this->msgno, 
                                              $structure->parts[$i], 
                                              $i, 
                                              $sourceId, 
                                              $ticketId);
         }
      }
      
      return $attachmentObj;
   }
   


} // End of Message class

?>
