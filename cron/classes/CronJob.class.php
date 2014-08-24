<?php

class CronJob
{
   private $id;
   private $dbLink;      
   protected $endTime;
   protected $startTime;
   
   const EXCEPTION_NO_DB_LINK = 'Please provide database link.';
   
   function __construct()
   {
      $this->startTime = microtime(true);
   }

   /**
   * get Database Connection
   * @access public
   * @param  none
   * @return none
   */
   public function getDBConnection()
   {
       if ($this->dbLink)
       {
          return $this->dbLink;
       }
       
       $dbInfo         = array();
       $dbInfo['db']   = DB_NAME;
       $dbInfo['user'] = DB_USER;
       $dbInfo['pass'] = DB_PASS;
       $dbInfo['host'] = DB_HOST;
       
       $this->dbLink = new DB($dbInfo);

       if (! $this->dbLink)
       {
           throw new Exception(SELF::EXCEPTION_NO_DB_LINK);
           return null;
       }

       return $this->dbLink;
   }

   /**
   * set Job ID
   * @access public
   * @param  $id
   * @return none
   */
   public function setJobID($id = null)
   {
      $this->id = $id;
   }

   /**
   * get Job ID
   * @access public
   * @param  none
   * @return $id
   */
   public function getJobId()
   {
      return $this->id;
   }

   /**
   * set Cron Status On
   * @access public
   * @param  none
   * @return $id
   */
   public function setCronStatusOn()
   {
      $params          = array();
      $params['table'] = LAST_RUN;
      $params['where'] = " cron_name = '" . $this->id . "'";
      $data            = array();
      $data['status']  = 1;
      $params['data']  = $data;
      
      $this->dbLink->update($params);
   }

   /**
   * set Cron Status Off
   * @access public
   * @param  none
   * @return $id
   */
   public function setCronStatusOff()
   {
      $params          = array();
      $params['table'] = LAST_RUN;
      $params['where'] = " cron_name = '" . $this->id . "'";
      $data            = array();
      $data['status']  = 0;
      $params['data']  = $data;
      
      $this->dbLink->update($params);
   }
   
   /**
   * check current Cron Status
   * @access public
   * @param  none
   * @return $id
   */
   public function checkCurrentCronStatus()
   {
      $query = "SELECT status FROM ". LAST_RUN . " WHERE cron_name = '" . $this->id . "'";

      $row   = $this->dbLink->select($query);

      if(count($row))
      {
         foreach($row as $key => $value)
         {
            if($value->status == 1)
            {
               return true;
            }
            else
            {
               return false;
            }
         }
      }
      
      return false;
   }
   /**
   * create Page
   * @access public
   * @param  $templateFile, $data
   * @return parsed template
   */
   public function createPage($templateFile = null, $data = null)
   {
      $smarty              = new Smarty();
      $smarty->compile_dir = SYSTEM_TEMPLATE_COMPILE_DIR;
      $smarty->cache_dir   = SYSTEM_TEMPLATE_COMPILE_DIR;
      $smarty->cache       = false;

      if (!empty($data))
      {
         foreach ($data as $key => $value)
         {
            $smarty->assign($key ,$value);
         }
      }

      return $smarty->fetch($templateFile);
   }

   /**
   * Write Page
   * @access public
   * @param  $filename, $contents
   * @return none
   */
   public function writeFile($filename = null, $contents = null)
   {
      $fp = fopen($filename, 'w');

      if ($fp)
      {
          fwrite($fp, $contents);
          fclose($fp);
      }
   }

   /**
   * get File Contents
   * @access public
   * @param  $file
   * @return $contents
   */
   public function getFileContents($file )
   {
      $fp       = fopen($file, 'r');
      $contents =  fread($fp, filesize($file));
      fclose($fp);
      
      return $contents;
   }

   /**
   * get Common Template Path
   * @access public
   * @param  $fileName
   * @return $path
   */
   public function getCommonTemplatePath($fileName)
   {
       $path = sprintf("%s/%s/%s/%s", DOCUMENT_ROOT,
                                      SYSTEM_VIEW_DIR,
                                      SYSTEM_VIEW_COMMON_DIR,
                                      $fileName
                      );

       return $path;
   }
   
   /**
   * get Template Path
   * @access public
   * @param  $fileName
   * @return $path
   */
   public function getTemplatePath($fileName)
   {     
       $path = sprintf("%s/%s", DOCUMENT_ROOT, $fileName);

       return $path;
   }
   
   /**
   * comma
   * @access public
   * @param  $str
   * @return string with single quote
   */
   public function q($str = null)
   {
       return "'" . $str . "'";
   }

   /**
   * save Last Run Time
   * @access public
   * @param  none
   * @return boolean  -- true or false
   */
   public function saveLastRunTime()
   {
       if (! $this->dbLink)
       {
           $this->getDBConnection();
       }

       $fields['cron_name']    = $this->q($this->id);
       $fields['start_time']   = $this->q(date("Y-m-d H:i:s", $this->startTime));
       $fields['end_time']     = $this->q(date("Y-m-d H:i:s", $this->endTime));
       $fields['elapsed_sec']  = $this->endTime - $this->startTime;
       $fields['last_runtime'] = $fields['start_time'];
       $fields['status']       = $this->status;
       $fields['pid']          = posix_getpid();

       $fieldCSV = implode(',', array_keys($fields));
       $valueCSV = implode(',', array_values($fields));

       $stmt = "REPLACE LOW_PRIORITY last_run ($fieldCSV) values($valueCSV)";

       if(!($results = $this->dbLink->query($stmt)))
       {
          echo "Failed to save last run time for $this->id Error:".$this->dbLink->error;
       }

       return ($results) ? true : false;
   }

   /**
   * get Last Run Time
   * @access public
   * @param  none
   * @return $last run time
   */
   public function getLastRunTime()
   {
      if (!isset($this->id)) return;

      $query = "SELECT last_runtime FROM last_run WHERE cron_name = '$this->id'";
      
      if(($result = $this->dbLink->query($query)) === FALSE)
      {
          echo "Failed to get last run time for $this->id Error:".$this->dbLink->error;
      }
      
        return $result[0]->last_runtime;
   }

   public function __destruct()
   {
      $this->endTime = microtime(true);

      $runTime = $this->endTime - $this->startTime; 
      echo "End Time: $this->endTime\n";
      echo "Total Run Time: $runTime\n";

      $this->status  = 0;
      $this->saveLastRunTime();
      //$this->setCronStatusOff();
   }
}
?>