<?php
/*
 * Filename   : Utils.class.php
 * Purpose    : 
 *
 * @author    : Sheikh Iftekhar <siftekher@gmail.com>
 * @project   : Issue tracking system
 * @version   : 1.0.0
 * @copyright : 
 */

class Utils 
{
   public static function dumpvar($data)
   {
      echo "<pre>";
      print_r($data);
      echo "</pre>";
   }
   
   public static function sanitize($text)
   {
     // Lowercase everything 
     $text = strtolower($text);
    
     // Replace anything but 0-9, a-z with single underscore  
     $text = preg_replace("/[^a-z0-9]+/", '_', $text);
     
     return $text;
   }

  /**
   * Purpose : loads users settings
   *
   * @access : public, static
   * @param  : none
   * @return : void
   */
   public static function getPersonalizedSettings($databse)
   {
      $sql  = "SELECT * 
               FROM " . TICKET_USER_SETTINGS_TBL . " 
               WHERE user_id = {$_SESSION['LOGIN_USER']['userId']}";
      try
      {
         $settings = $databse->select($sql);
      }
      catch(Exception $Exception){}
      
      return isset($settings[0]) ? $settings[0] : array();
   }
   
   public function getAllTagList($dbLink)
   {
      $userId = $_SESSION['LOGIN_USER']['userId'];
      $query  = "SELECT * FROM " . TICKET_USER_SETTINGS_TBL . " WHERE `user_id` = $userId";

      $userSettings = $dbLink->select($query);
      
      $sourceIds = Utils::getSourcesIds();

      if (count($sourceIds) < 1)
      {
          return array();
      }

      //Table Name Should Comes From Config, Doing Later @Pushan.
      $query = "SELECT count(TT.tag_id) As Total, TG.tag, TG.tag_title as tag_title, TT.tag_id FROM 
              (
                (ticket_sources as TS left join ticket_tags AS TT on (TS.ticket_id = TT.ticket_id))
                left join tags AS TG on (TG.tag_id = TT.tag_id)
              )
              
              WHERE TS.source_id IN ($sourceIds)
              GROUP BY TT.tag_id 
              ORDER BY Total DESC ";


      if($userSettings[0]->show_tag_type == 'top_tags' )
      {
         $query .=  " LIMIT 0,  " .$userSettings[0]->no_of_shown_tags;
      }
      else if($userSettings[0]->show_tag_type == 'most_used_tags' )
      {
         $query .=  " LIMIT 0, " . MOST_TAGS_LIMIT;
      }
      
      $row   = $dbLink->select($query);

      if(count($row))
      {
         foreach($row as $key => $value)
         {
            $row[$key]->tag_title = ucwords(strtolower($value->tag_title));            
         }
      }
      
      return $row;
   }
   
   public function getSourcesIds()
   {
      $authorizedSources = $_SESSION['LOGIN_USER']['authorized_sources'];
      
      $sourceIds = array();
      foreach($authorizedSources as $key => $value)
      {
         $sourceIds[] = $value->source_id;
      }
      
      $resolverSources = $_SESSION['LOGIN_USER']['resolver_sources'];
      foreach($resolverSources as $key => $value)
      {
         $sourceIds[] = $value->source_id;
      }
      $sourceIds = array_unique($sourceIds);
      if(count($sourceIds))
      {
         $sourceId = implode( ',', $sourceIds);
      }
      
      return $sourceId;
   }

   public function getResolverSourcesIds()
   {
      $sourceIds = array();
      
      $resolverSources = $_SESSION['LOGIN_USER']['resolver_sources'];
      foreach($resolverSources as $key => $value)
      {
         $sourceIds[] = $value->source_id;
      }

      if(count($sourceIds))
      {
         $sourceId = implode( ',', $sourceIds);
      }
      
      return $sourceId;
   }
   
   public function getAuthorisedSourcesIds()
   {
      $authorizedSources = $_SESSION['LOGIN_USER']['authorized_sources'];
      
      $sourceIds = array();
      foreach($authorizedSources as $key => $value)
      {
         $sourceIds[] = $value->source_id;
      }

      if(count($sourceIds))
      {
         $sourceId = implode( ',', $sourceIds);
      }
      
      return $sourceId;
   }
   
   public function getSourcesIdAsString()
   {
      $str = Utils::getSourcesIds();
      if($str)
      {
         return "($str)";
      }
      else
      {
         return null;
      }
   }
   
   public static function arrayAlterKeyValue($haystack)
   {
      $list = array();
      
      if(count($haystack))
      {
         foreach($haystack as $key => $value)
         {
            $list[$value] = $key;
         }
      }
      
      return $list;
   }
   
   public static function isExecutive($source_id = 0)
   {
      $is_admin = false;
      
      $resolver_types = Utils::arrayAlterKeyValue(
         $GLOBALS['SOURCE_RESOLVERS_TYPE']
      );
      
      if($source_id)
      {
         if($_SESSION['LOGIN_USER']['user_types'][$source_id] 
            == $resolver_types['executive'])
         {
            return true;
         }
         else
         {
            return false;
         }
      }
      
      if(in_array($resolver_types['executive'], 
                  $_SESSION['LOGIN_USER']['user_types']))
      {
         $is_admin = true;
      }
      
      return $is_admin;
   }
   public static function isStaff($source_id = 0)
   {
      $is_staff = false;
      
      $resolver_types = Utils::arrayAlterKeyValue(
         $GLOBALS['SOURCE_RESOLVERS_TYPE']
      );
      
      if($source_id)
      {
         if($_SESSION['LOGIN_USER']['user_types'][$source_id] 
            == $resolver_types['staff'])
         {
            return true;
         }
         else
         {
            return false;
         }
      }
      
      if(in_array($resolver_types['staff'], 
                  $_SESSION['LOGIN_USER']['user_types']))
      {
         $is_staff = true;
      }
      
      return $is_staff;
   }

   public static function isSupervisor($source_id = 0)
   {
      $is_admin = false;
      
      $resolver_types = Utils::arrayAlterKeyValue(
         $GLOBALS['SOURCE_RESOLVERS_TYPE']
      );
      
      if($source_id)
      {
         if($_SESSION['LOGIN_USER']['user_types'][$source_id] 
            == $resolver_types['supervisor'])
         {
            return true;
         }
         else
         {
            return false;
         }
      }
      
      if(in_array($resolver_types['supervisor'], 
                  $_SESSION['LOGIN_USER']['user_types']))
      {
         $is_admin = true;
      }
      
      return $is_admin;
   }
   
   public static function startDownloadStream($file_name, $src)
   {
      $extArray = split("[.]",$file_name);
      $file_name = str_replace(' ', '-', $file_name);
            
      if(strtolower($extArray[count($extArray)-1]) == 'png' || strtolower($extArray[count($extArray)-1]) == 'jpg' || strtolower($extArray[count($extArray)-1]) == 'jpeg' || strtolower($extArray[count($extArray)-1]) == 'bmp' || strtolower($extArray[count($extArray)-1]) == 'gif')
      {
         // send the right headers
         header("Content-Type: image/".strtolower($extArray[count($extArray)-1]));
         utils::passThroughBrowser($src);
      }
      elseif(strtolower($extArray[count($extArray)-1]) == 'pdf')
      {
         // We'll be outputting a PDF
         header('Content-type: application/pdf');         
         header("Content-Transfer-Encoding: binary");
         header("Expires: 0");
         header("Cache-Control:  maxage=1");
         header("Pragma: public");
         header("Cache-Control: private");
         
         utils::passThroughBrowser($src);
      }
      elseif(strtolower($extArray[count($extArray)-1]) == 'txt' || strtolower($extArray[count($extArray)-1]) == 'text' || strtolower($extArray[count($extArray)-1]) == 'log')
      {
         // send the right headers
         header("Content-Type: text/plain");
         utils::passThroughBrowser($src);
      }
      elseif(strtolower($extArray[count($extArray)-1]) == 'doc' || strtolower($extArray[count($extArray)-1]) == 'dot')
      {
         // send the right headers
         header("Content-type: application/msword; filename=$file_name");
         utils::passThroughBrowser($src);
      }
      else
      {         
         header("Content-disposition: attachment; filename=\"$file_name\"");         
         readfile($src);         
         exit;
      }
   }
   
   private function passThroughBrowser($src = null)
   {
      if($src)
      {
         // open the file in a binary mode      
         $fp = fopen($src, 'rb');
                                    
         header("Content-Length: " . filesize($src));
         
         // dump the picture and stop the script
         fpassthru($fp);
         exit;        
      }
   }
   
   
   public static function getSourceTickets($database, 
                                           $template, 
                                           $user_id = null, 
                                           $source_id = null, 
                                           $param = array()
                                           )
   {
      $my_sources = array();
      foreach($_SESSION['LOGIN_USER']['sources'] as $index => $source)
      {
         $my_sources[$source->source_id] = $source->name;
      }

      $source_id_str = implode(",", array_keys($my_sources));

      $status_type = Utils::arrayAlterKeyValue(
         $GLOBALS['TICKET_STATUS_TYPE']
      );

      unset($status_type['closed']);
      unset($status_type['deleted']);
      unset($status_type['completed']);
      unset($status_type['archived']);
      $status_type_str = implode(',', $status_type);

      
      $where  = "";
      $where .= $user_id ? " AND d.user_id = $user_id" : "";
      $where .= $source_id ? " AND ts.source_id = $source_id" : "";
      
      $start = "";
      if(isset($param['start']))
      {
         $start = $param['start'];  
      }
      $size = "";
      if(isset($param['size']))
      {
         $size = $param['size'];  
      }
      
      $limit = ($start !== false && $size) ? " LIMIT $start, $size" : "";

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
             WHERE ts.source_id IN ($source_id_str) AND 
                   t.status IN ($status_type_str) 
                   $where 
             GROUP BY t.ticket_id ORDER BY t.ticket_id DESC, d.create_date ASC $limit"; 
      try
      {
         $rows = $database->select($sql);
      }
      catch(Exception $Exception){}
      
      $list = array();
      if($rows)
      {
         foreach($rows as $index => $row)
         {
            $row->status            = $GLOBALS['TICKET_STATUS_TYPE'][$row->status];
            $row->create_date       = date('l jS F Y @ h:i A (T)', 
                                           strtotime($row->create_date));
            
            $param                  = array();
            $param['ticket']        = $row;
            $param['source_list']   = $my_sources;
            
            $list[] = $template->parseTemplate(TICKET_SUMMARY_TEMPLATE, $param);
         }
      }
      
      return $list;
   }
   
   public static function getMyAssignedTickets($database, 
                                           $template, 
                                           $user_id = null,                                            
                                           $param = array()
                                           )
   {
      $my_sources = array();
      foreach($_SESSION['LOGIN_USER']['sources'] as $index => $source)
      {
         $my_sources[$source->source_id] = $source->name;
      }

      $source_id_str = implode(",", array_keys($my_sources));

      $status_type = Utils::arrayAlterKeyValue(
         $GLOBALS['TICKET_STATUS_TYPE']
      );

      unset($status_type['closed']);
      unset($status_type['deleted']);
      unset($status_type['completed']);
      unset($status_type['archived']);
      $status_type_str = implode(',', $status_type);

      
      $where  = "";
      $where .= $user_id ? " AND d.user_id = $user_id" : "";
      $where .= $source_id ? " AND ts.source_id = $source_id" : "";
      
      $start = "";
      if(isset($param['start']))
      {
         $start = $param['start'];  
      }
      $size = "";
      if(isset($param['size']))
      {
         $size = $param['size'];  
      }
      
      $limit = ($start !== false && $size) ? " LIMIT $start, $size" : "";

      $sql = "SELECT t.ticket_id, 
                     t.title, 
                     t.status, 
                     t.create_date, 
                     d.notes, 
                     d.user_id, 
                     u.user_id, 
                     u.first_name, 
                     u.last_name 
             FROM " . TICKET_ASSIGNMENTS_TBL . " AS ta 
             LEFT JOIN " . TICKETS_DETAILS_TBL . " AS d 
             ON ta.user_id = d.user_id  
             LEFT JOIN " . TICKETS_TBL . " as t 
             ON ta.ticket_id = t.ticket_id 
             LEFT JOIN " . USERS_TBL . " AS u 
             ON d.user_id = u.user_id 
             WHERE t.status IN ($status_type_str) 
                   $where 
             GROUP BY t.ticket_id ORDER BY t.ticket_id DESC, d.create_date ASC $limit"; 
      try
      {
         $rows = $database->select($sql);
      }
      catch(Exception $Exception){}
      
      $list = array();
      if($rows)
      {
         foreach($rows as $index => $row)
         {
            $row->status            = $GLOBALS['TICKET_STATUS_TYPE'][$row->status];
            $row->create_date       = date('l jS F Y @ h:i A (T)', 
                                           strtotime($row->create_date));
            
            $param                  = array();
            $param['ticket']        = $row;
            $param['source_list']   = $my_sources;
            
            $list[] = $template->parseTemplate(TICKET_SUMMARY_TEMPLATE, $param);
         }
      }
      
      return $list;
   }
   
   public function getSourceName($dbLink, $sourceId = null)
   {
      if($sourceId == null)
      {
         return 'All';
      }

      $query = " SELECT short_name FROM " . SOURCE_SETTINGS_TBL . 
               " WHERE source_id = " . $sourceId;

      $result = $dbLink->select($query);
      
      if(count($result))
      {
         foreach($result as $row => $value)
         {
            return $value->short_name;
         }
      }
   }
   
   function getTagListByTicketId($database, $ticketId)
   {
        $sql = "SELECT TG.tag, TG.tag_title from " . TAGS_TBL ." as TG LEFT JOIN " . TICKETS_TAG_TBL . " as T ON (TG.tag_id = T.tag_id) where T.ticket_id = $ticketId";
        $rows = $database->select($sql);
        if($rows)
        {
           foreach($rows as $key=>$row)
           {
            
              $tagArr[$key]->tag_slug = $row->tag;
              $tagArr[$key]->tag_label = ucwords(strtolower($row->tag_title));
           }
        }        
        
        return $tagArr;
   }
   
   function getTagLabel($tag)
   {
      $tag = preg_replace("/_/", ' ', $tag);
      return ucwords($tag);
   }
   
   function isResolverUser($sourceId)
   {
      $resolversData = $_SESSION['LOGIN_USER']['resolver_sources'];
      if($resolversData)
      {
         foreach($resolversData as $resolver)
         {
            $userArr[$resolver->source_id] = $resolver->user_id;
         }
      }
       
      if(empty($userArr[$sourceId]))
      {
         return false;
      }
      else
      {
         return true;
      }
   }
   
   function getSourceIdByTicketId($database, $ticketId)
   {
       $sql = "SELECT source_id from " .TICKET_SOURCES_TBL. " where ticket_id = $ticketId";
       $result = $database->select($sql);
       return $result[0]->source_id;
   }
   
   function getPriorityColorSettings($database, $userId = null)
   {
       $sql = "SELECT priority,color from " .TICKET_COLOR_SETTINGS_TBL. " where user_id = $userId";
       $result = $database->select($sql);
       if($result)
       {
         foreach($result as $key => $value)
         {
            $data[$value->priority] = $value->color;
         }
       } 
       return $data;
   }
   
} // end of class
