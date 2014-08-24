<?php
/*
* Filename   : Attachment.class.php
* Purpose    : get all attachment
*
* @author    : Sheikh Iftekhar <siftekher@gmail.com>
* @project   : 
* @version   : 1.0.0
*/

class Attachment
{
   private $mailbox;
   private $messageId;
   private $fileName;
   private $FQPN;
   private $fileType;
   private $fileSize;
   private $structure;
   private $partNo;
   private $sourceId;
   private $ticketId;
   
   public function __construct($mailbox, $messageId, $structure, $partNo, $sourceId, $ticketId)
   {
      $this->mailbox    = $mailbox;
      $this->messageId  = $messageId;
      $this->structure  = $structure;
      $this->partNo     = $partNo;
      $this->sourceId   = $sourceId;
      $this->ticketId   = $ticketId;

      $this->getAttachInfo();
   }

   /**
   * get Attachment Information
   * @access public
   * @param  none
   * @return boolean
   */
   public function getAttachInfo()
   {
      $structure = $this->structure;
      
      if($structure->bytes) 
      {
         $this->fileType = $this->getAttachmentType($structure->type);
         $this->fileSize = $structure->bytes;
      }
      
      if($structure->ifdparameters) 
      {
         foreach($structure->dparameters as $object) 
         {
            if(strtolower($object->attribute) == 'filename') 
            {
               $this->fileName = $object->value;

               $message = imap_fetchbody($this->mailbox, $this->messageId , $this->partNo + 1 );
               $message = $this->getCodingType($structure->encoding, $message);
      
               $dir = ATTACHMENT_DIR .'/' . $this->sourceId .'/'. $this->ticketId ;
               $this->FQPN = $this->writeAttachmentsToDisk( $dir, $object->value, $message);
            }
         }
      }
      
      return true;
   }
   
   public function sanitizeFilename($original = null)
   {
      // Replace anything but 0-9, a-z with single underscore
      return preg_replace("/[^a-z0-9]+/", '_', strtolower($original));
   }
   
   public function findFileExts ($filename = null) 
   { 
      return substr($filename, strrpos($filename, '.'));
   }
   
   
   /**
   * write Attachments To Disk
   * @access public
   * @param  $dir, $filename, $message
   * @return $emailFile
   */
   public function writeAttachmentsToDisk($dir, $filename, $message)
   {
      if (!is_dir($dir))
      {
         mkdir($dir, 0777, true);
      }
      
      $fileExt  = $this->findFileExts($filename);
      $filename = substr($filename, 0, strpos($filename, $fileExt));
      $filename = $this->sanitizeFilename($filename);

      $emailFile = $dir . "/" . $filename . $fileExt;

      $fileHandle = fopen($emailFile, "w");
      fwrite($fileHandle , $message);
      fclose ($fileHandle);
      
      return $emailFile;
   }

   /**
   * Get Coding Type
   * @access public
   * @param  $coding, $messag
   * @return $message
   */
   public function getCodingType($coding, $message)
   {
      if ($coding == 1)
      {
         $wiadomsoc = imap_8bit($message);
      }
      elseif ($coding == 2)
      {
         $message = imap_binary($message);
      }
      elseif ($coding == 3)
      {
         $message = imap_base64($message);
      }
      elseif ($coding == 4)
      {
         $message = quoted_printable_decode($message);
      }
      elseif ($coding == 5)
      {
         $message = $message;
      }
      
      return $message;
   }
   
   /**
   * Get Attachment Type
   * @access public
   * @param  $encoding Type
   * @return $type
   */
   public function getAttachmentType($encodingType = null)
   {
      if ($encodingType == 0) 
      { 
         $type = "text"; 
      } 
      elseif ($encodingType == 1) 
      { 
         $type = "multipart"; 
      } 
      elseif ($encodingType == 2) 
      { 
         $type = "message"; 
      } 
      elseif ($encodingType == 3) 
      { 
         $type = "application"; 
      } 
      elseif ($encodingType == 4) 
      { 
         $type = "audio"; 
      } 
      elseif ($encodingType == 5) 
      { 
         $type = "image"; 
      } 
      elseif ($encodingType == 6) 
      { 
         $type = "video"; 
      } 
      elseif($encodingType == 7) 
      { 
         $type = "other"; 
      } 
      
      return $type;
   }
   
  /**
   * Get the attach file name
   * @access public
   * @param  none
   * @return filename
   */
   public function getFilename()
   {
      return $this->fileName;
   }
  
  /**
   * Get the attach file FQPN
   * @access public
   * @param  none
   * @return FQPN
   */
   public function getFQPN()
   {
      return $this->FQPN;
   }
  
  /**
   * Get the attach file type
   * @access public
   * @param  none
   * @return - file type
   */
   public function getFileType()
   {
      return $this->fileType;
   }
  
  /**
   * Get the attach file size
   * @access public
   * @param  $flag
   * @return - file size
   */
   public function getFileSize($flag = null)
   {
      if($flag == 'MB')
      {
         return ($this->fileSize/1024)/1024;
      }
      elseif($flag == 'KB')
      {
         return $this->fileSize/1024;
      }
   }
} 


?>