<?php

/*
 * Filename   : SourceManagerController.class.php
 * Purpose    : 
 *
 * @author    : Sheikh Iftekhar <siftekher@gmail.com>
 * @project   : Issue Tracking System
 * @version   : 1.0
 */
require_once('UserAuthentication.class.php');
class SourceManager
{
   private $db;
   private $template;
   private $params;
   private $cmdList;

   public function __construct($params)
   {
      $this->db       = $params['db_link'];
      $this->cmdList  = $params['cmdList'];
      $this->template = new Template();            
   }
   
   public function run()
   {   	  
      $userId = $_SESSION['LOGIN_USER']['userId'];

      if($userId == null)
      {
         header('location: '.SUPER_CONTROLLER_URL_PREFIX . 'Login');
         exit;
      }
      $cmd = $this->cmdList[1];

      switch ($cmd)
      {         
         case 'add'        : $screen = $this->addNewSource(); break;                  
         case 'edit'       : $screen = $this->editSource(); break; 
         case 'update'     : $screen = $this->updateSource(); break; 
         case 'delete'     : $screen = $this->deleteSource(); break; 
         case 'list'       : $screen = $this->showSourceList(); break;  
         case 'get_source' : $screen = $this->getSourceFields(); break;
         default           : $screen = $this->showAddSourceForm();        
      }
      
      $data            = array();
      $data['topnav']  = 'admin';
      $data['tagList'] = Utils::getAllTagList($this->db);

      $userList = new UserList($this->db);
      $data['sourceUser'] = $userList->getUsersFromMySources();
      $data['source_id']  = $_SESSION['source_id'];
      $data['source_name'] = Utils::getSourceName($this->db , $_SESSION['source_id']);
      echo $this->template->createScreen($screen, $data);
      exit;      
   }


  /**
   * Delete an existing user
   *
   * @access public
   * @param  none
   * @return none
   */
   
   function deleteSource()
   {   	
      $data   = array();          
      $source_id = $this->cmdList[2];                   
      
      $query['table'] = SOURCE_SETTINGS_TBL;
      $query['where'] = ' source_id = ' . $source_id;
      
      $status = $this->db->delete($query);
      
      $data['msg'] = (!empty($status)) ? DELETE_SOURCE_MESSAGE : DELETE_SOURCE_FAIL_MESSAGE ;                                                     
      
      $data['source_list'] = $this->getAllSources();
      # update session after deleting source.
      $this->updateSession();  
                 
      return $this->template->parseTemplate(SOURCE_LIST_TEMPLATE, $data);     
   }

  /**
   * Update a specific user
   *
   * @access public
   * @param  none
   * @return none
   */
   
   function updateSource()
   {   	
      $data      = array();          
      $source_id = $this->cmdList[2]; 
      $notifyArr = array('auto_notify_supervisor','auto_notify_executive','auto_notify_staff_members');       
            
      foreach($notifyArr as $key => $thisData)
      {
         if(!array_key_exists($thisData,$_REQUEST))
         {
            $_REQUEST[$thisData] = 2; 	
         }	
      }           
                  
      $query['table'] = SOURCE_SETTINGS_TBL;
      $query['data']  = $this->makeFieldValueList($_REQUEST);
      $query['where'] = ' source_id = ' . $source_id;             
      
      $status = $this->db->update($query);
      
      $data['msg'] = (!empty($status)) ? UPDATE_SOURCE_MESSAGE : UPDATE_SOURCE_FAIL_MESSAGE ;    
         
      $this->updateSession();                                              
      
      $data['source_list'] = $this->getAllSources();
      $data['source_list'] = $this->filterStatus($data['source_list']);
      
       
      return $this->template->parseTemplate(SOURCE_LIST_TEMPLATE, $data);     
   }
   
  /**
   * Show edit user page from admin panel 
   *
   * @access public
   * @param  none
   * @return none
   */
   
   function editSource()
   {   	
      $data      = array();          
      $source_id = $this->cmdList[2];             
     
      $query   =  "SELECT * FROM " . SOURCE_SETTINGS_TBL . " WHERE source_id = " . $source_id;       
      
      $sourceInfo = $this->db->select($query);     
      
      foreach($sourceInfo[0] as $key => $thisInfo)
      {
         $data[$key] = $thisInfo; 
      }                                                          
             
      return $this->template->parseTemplate(ADD_SOURCE_TEMPLATE, $data);     
   }
      
  /**
   * Add new source from admin panel 
   *
   * @access public
   * @param  none
   * @return none
   */
   
   function addNewSource()
   {   	
      $data     = array();         

      $query['table']         = SOURCE_SETTINGS_TBL;
      $_REQUEST['name']       = stripslashes(preg_replace('/[^a-z0-9A-Z]+/i','',$_REQUEST['name']));
      $_REQUEST['short_name'] = stripslashes(preg_replace('/[^a-z0-9A-Z]+/i','',$_REQUEST['short_name']));
      
      $query['data']  = $this->makeFieldValueList($_REQUEST);
      
      $source_id = $this->db->insert($query);
      
      # add current user as a authorise source 
      $this->assignAuthSource($source_id);

      # Update session
      $_SESSION['LOGIN_USER']['authorized_sources'] = $this->getAuthoriseSources();

      $data['msg'] = (!empty($source_id)) ? ADD_SOURCE_MESSAGE : ADD_SOURCE_FAIL_MESSAGE;
      
      return $this->template->parseTemplate(ADD_SOURCE_TEMPLATE, $data);     
   }
   
   function updateSession()
   {
        # Update session                                                              
      $_SESSION['LOGIN_USER']['authorized_sources'] = $this->getAuthoriseSources();
      $_SESSION['LOGIN_USER']['resolver_sources']   = $this->getResolverSources(); 

   }   
   
   function getAuthoriseSources()
   {
        $sql  = "SELECT u.user_id, u.source_id, s.name, s.short_name 
            FROM " . AUTHORIZED_SOURCES_TBL . " as u, " . SOURCE_SETTINGS_TBL . " as s 
            WHERE u.source_id = s.source_id AND u.`user_id` = " 
            . $_SESSION['LOGIN_USER']['userId'];
      
         try
         {
            $authorized_sources = $this->db->select($sql);
         }
         catch(Exception $Exception){}
      
         $authorized_sources = $authorized_sources ? $authorized_sources : array();
         return $authorized_sources;
   }
   
   function getResolverSources()
   {
        $sql  = "SELECT u.user_id, u.source_id, s.name, s.short_name 
            FROM " . SOURCE_RESOLVERS_TBL . " as u, " . SOURCE_SETTINGS_TBL . " as s 
            WHERE u.source_id = s.source_id AND u.`user_id` = "  
            . $_SESSION['LOGIN_USER']['userId'];

         try
         {
            $resolver_sources = $this->db->select($sql);
         }
         catch(Exception $Exception){}
         
         $resolver_sources = $resolver_sources ? $resolver_sources : array();
         return $resolver_sources;
   }
   
   function assignAuthSource($source_id)
   {
       $data['user_id']   = $_SESSION['LOGIN_USER']['userId'];
       $data['source_id'] = $source_id;
       
       $info['table']     = AUTHORIZED_SOURCES_TBL;
       $info['data']      = $data;
       
       $this->db->insert($info);
   }
   
   function makeFieldValueList($fieldValueArr = null)
   {  
      $fieldValueList = array();
      
      $fieldList = array('name','short_name','pop_email','pop_password','pop_server',
                         'min_image_attachment_size','footer_text','reply_from_name',
                         'reply_from_address','new_ticket_email_subject',
                         'new_ticket_email_template','existing_ticket_email_subject',
                         'existing_ticket_email_template','status_reply_email_subject',
                         'status_reply_email_template','list_ticket_email_subject',
                         'list_ticket_email_template','max_response_time',
                         'enable_rss_feed','status','auto_assign_staff',
                         'auto_notify_supervisor','auto_notify_executive','auto_notify_staff_members','foo');   
                     
      foreach($fieldList as $thisField)
      {
         if($fieldValueArr[$thisField] != '')
         {
         	  $fieldValueList[$thisField] = $fieldValueArr[$thisField];            
         }
      }
      
      return $fieldValueList;
   }
   
  /**
   * Show all available users in the system to admin
   *
   * @access public
   * @param  none
   * @return none
   */
   function showSourceList($msg = null)
   {                       
      $data    = array();                                    
      
      $data['source_list'] = $this->getAllSources();
      $data['msg']         = $msg;

      $data['source_list'] = $this->filterStatus($data['source_list']);        

      return $this->template->parseTemplate(SOURCE_LIST_TEMPLATE, $data);
   }
   
   function filterStatus($sourceList = null)
   {   	
      if(!empty($sourceList))
      {
         foreach($sourceList as $key => $thisSource)
         {
            if($thisSource->status == 1)
            {
            	$sourceList[$key]->status = 'Active';
            }
            else
            {
               $sourceList[$key]->status = 'Inactive';	
            }
         }	
      } 
      
      return $sourceList;     
   }
   
   /**
   * Get all users 
   *
   * @access public
   * @param  none
   * @return none
   */
   function getAllSources()
   {
      $query   =  "SELECT * FROM " . SOURCE_SETTINGS_TBL . " ORDER BY source_id DESC"; 
      return $this->db->select($query);       
   }
   
  /**
   * Display add user form for admin
   *
   * @access public
   * @param  none
   * @return none
   */
   function showAddSourceForm()
   {
      $data = array();
      $data['source_list'] = $this->getAllSources();
      
      return $this->template->parseTemplate(ADD_SOURCE_TEMPLATE,$data);
   }
   
  /**
   * Show edit user page from admin panel 
   *
   * @access public
   * @param  none
   * @return none
   */
   
   function getSourceFields()
   {   	
      $data      = array();          
      $source_id = $_REQUEST['source_id'];
     
      $query   =  "SELECT * FROM " . SOURCE_SETTINGS_TBL . " WHERE source_id = " . $source_id;
      
      $sourceInfo = $this->db->select($query);     
      
      foreach($sourceInfo[0] as $key => $thisInfo)
      {
         $data[$key] = $thisInfo;
      }                                                          
             
      //return $this->template->parseTemplate(ADD_SOURCE_TEMPLATE, $data);
      
      header("content-type:application/json");
      echo json_encode($data);   
      exit;
   }
   
   
}
?>