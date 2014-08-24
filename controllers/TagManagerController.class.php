<?php

/*
 * Filename   : TagManagerController.class.php
 * Purpose    : 
 *
 * @author    : Sheikh Iftekhar <siftekher@gmail.com>
 * @project   : Issue Tracking System
 * @version   : 1.0
 * @copyright : 
 */


class TagManager
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
         case 'add'       : $screen = $this->addTag(); break; 
         case 'edit'      : $screen = $this->editTag(); break; 
         case 'update'    : $screen = $this->updateTag(); break; 
         case 'delete'    : $screen = $this->deleteTag(); break; 
         case 'list'      : $screen = $this->showTagList(); break;  
         default          : $screen = $this->showAddTagForm();        
      }

      $data            = array();
      $data['topnav']  = 'admin';
      $data['tagList'] = Utils::getAllTagList($this->db);
      
      $userList = new UserList($this->db);
      $data['sourceUser']  = $userList->getUsersFromMySources();
      $data['source_id']   = $_SESSION['source_id'];
      $data['source_name'] = Utils::getSourceName($this->db , $_SESSION['source_id']);
      echo $this->template->createScreen($screen, $data);
      exit; 
   }


  /**
   * Delete an existing tag from admin panel 
   *
   * @access public
   * @param  none
   * @return none
   */
   
   function deleteTag()
   {   	
      $data   = array();          
      $tag_id = $this->cmdList[2];             
      
      $params['db_link'] = $this->db;            

      $tagObj = new TicketTag($params);                        
      $status = $tagObj->removeTag($tag_id);      
      
      if(!empty($status))
      {      	 
      	 $_SESSION['confirm_msg'] = DELETE_TAG_MESSAGE;
      	 header('location: /its/run.php/TagManager/list');
      	 exit;
      }
      else
      {
      	 $_SESSION['confirm_msg'] = DELETE_TAG_FAIL_MESSAGE; 
      	 header('location: /its/run.php/TagManager/list');
      	 exit;
      } 
   }

  /**
   * Update tag from admin panel 
   *
   * @access public
   * @param  none
   * @return none
   */
   
   function updateTag()
   {
      $data   = array();          
      $tag_id = $this->cmdList[2];    
      $tagTitle    = stripslashes(trim($_REQUEST['tag']));
            
      $tag = stripslashes(preg_replace('/[^a-z0-9A-Z\'\"\-]+/i','',$tagTitle));         
      
      $params['db_link'] = $this->db;
                        
      if(empty($tag))
      {
      	 $_SESSION['confirm_msg'] = ENTER_TAG_MESSAGE;
      	 header('location: /its/run.php/TagManager');
      	 exit; 
      }
            
      $tagObj = new TicketTag($params);
      $status = $tagObj->updateTag($tag_id, $tag, $tagTitle);
      
      $_SESSION['confirm_msg'] = (!empty($status)) ? UPDATE_TAG_MESSAGE : UPDATE_TAG_FAIL_MESSAGE;
            
      header('location: /its/run.php/TagManager/list');
      exit;            
   }
   
  /**
   * Show edit tag page from admin panel 
   *
   * @access public
   * @param  none
   * @return none
   */
   
   function editTag()
   {   	
      $data   = array();          
      $tag_id = $this->cmdList[2];             
     
      $query    =  "SELECT tag FROM " . TAGS_TBL . " WHERE tag_id = " . $tag_id;       
      
      $tag_name = $this->db->select($query);                                                   
      $data['tag_name'] = $tag_name[0]->tag;
      $data['tag_id']   = $tag_id; 
       
      return $this->template->parseTemplate(ADD_TAG_TEMPLATE, $data);     
   }
      
  /**
   * Add tag from admin panel 
   *
   * @param  none
   * @return none
   */
   
   function addTag()
   {   	
      $data = array();          
      $tagTitle  = stripslashes(trim($_REQUEST['tag']));
      
      $tag = stripslashes(preg_replace('/[^a-z0-9A-Z\'\"\-]+/i','',$tagTitle));      

      $params['db_link'] = $this->db;
        
      if(empty($tag))
      {                  
      	 $_SESSION['confirm_msg'] = ENTER_TAG_MESSAGE;
      	 header('location: /its/run.php/TagManager');
      	 exit;           	          
      }               
      
      $tagObj = new TicketTag($params);      
      $tag_id = $tagObj->addTag($tag, $tagTitle);
      
      $_SESSION['confirm_msg'] = (!empty($tag_id)) ? ADD_TAG_MESSAGE : ADD_TAG_FAIL_MESSAGE ;
            
      header('location: /its/run.php/TagManager');
      exit;       
   }
   
  /**
   * Show all available tags in the system to admin
   *
   * @access public
   * @param  none
   * @return none
   */
   function showTagList($msg = null)
   {                       
      $data    = array();                                    
      
      $data['tag_list'] = $this->getAllTags();
      
      
      if(!empty($_SESSION['confirm_msg']))
      {
      	 $data['msg'] = $_SESSION['confirm_msg'];        
      }
      
      unset($_SESSION['confirm_msg']); 
      return $this->template->parseTemplate(TAG_LIST_TEMPLATE, $data);
   }
   
   /**
   * Get all tags 
   *
   * @access public
   * @param  none
   * @return none
   */
   function getAllTags()
   {
      $query   =  "SELECT * FROM " . TAGS_TBL . " ORDER BY tag" ; 
      return $this->db->select($query);       
   }
   
  /**
   * Display add tag form for admin
   *
   * @access public
   * @param  none
   * @return none
   */
   function showAddTagForm($msg = null)
   {
      $data = array();
      
      if(!empty($_SESSION['confirm_msg']))
      {
      	 $data['msg'] = $_SESSION['confirm_msg'];        
      }
      unset($_SESSION['confirm_msg']); 
      return $this->template->parseTemplate(ADD_TAG_TEMPLATE,$data);
   }
}
?>
