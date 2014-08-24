<?php

/*
 * Filename   : UserManagerController.class.php
 * Purpose    : 
 * @author    : Sheikh Iftekhar <siftekher@gmail.com>
 * @project   : Issue Tracking System
 * @version   : 1.0
 */

class UserManager
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
         case 'add'        : $screen = $this->addNewUser(); break;                  
         case 'edit'       : $screen = $this->editUser(); break; 
         case 'update'     : $screen = $this->updateUser(); break; 
         case 'delete'     : $screen = $this->deleteUser(); break; 
         case 'checkEmail' : $screen = $this->checkEmailAvailablity(); break; 
         case 'list'       : $screen = $this->showUserList(); break;  
         default           : $screen = $this->showAddUserForm();        
      }

      $data            = array();
      $data['topnav']  = 'admin';
      $data['tagList'] = Utils::getAllTagList($this->db);

      $userList = new UserList($this->db);
      $data['sourceUser']  = $userList->getUsersFromMySources();
      $data['source_id']   = $_SESSION['source_id'];
      $data['source_name'] = Utils::getSourceName($this->db, $_SESSION['source_id']);
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
   
   function deleteUser()
   {   	
      $data   = array();          
      $user_id = $this->cmdList[2];             

      $userObj = new User($this->db);
            
      $userObj->setUserId($user_id);      
      
      $status = $userObj->delete();
      
      $this->removeUserInvolvement($user_id,AUTHORIZED_SOURCES_TBL);
      $this->removeUserInvolvement($user_id,SOURCE_RESOLVERS_TBL);
        
      if(!empty($status))
      {      	 
      	 $_SESSION['confirm_msg'] = DELETE_USER_MESSAGE;
      	 header('location: /its/run.php/UserManager/list');
      	 exit;
      }
      else
      {
      	 $_SESSION['confirm_msg'] = DELETE_USER_FAIL_MESSAGE; 
      	 header('location: /its/run.php/UserManager/list');
      	 exit;
      }             
   }

  /**
   * Update a specific user
   *
   * @access public
   * @param  none
   * @return none
   */
   
   function updateUser()
   {   	
      $data    = array();   
      $count   = 0;            
      $user_id = $this->cmdList[2];  
      
      $maxInvolvement     = 0;
      $uniqueRelatioalArr = array();
      $sourceUserArr      = array();  

      foreach($_REQUEST as $key => $value)
      {
         if(preg_match('/source_type/',$key))
         {
         	  $keyArr = explode("source_type",$key);
         	  $relationalItem = $_REQUEST[$key] . $_REQUEST['user_type'.$keyArr[1]];
         	  if(!in_array($relationalItem, $uniqueRelatioalArr))
         	  {
         	  	 $uniqueRelatioalArr[] = $relationalItem;
         	  	 $sourceUserArr['source_type'.$count]  = $_REQUEST[$key];  
         	  	 $sourceUserArr['user_type'.$count]    = $_REQUEST['user_type'.$keyArr[1]];
         	  	 $count++;         	  	          	  	         	  	          	  	         	                 
            }
            $maxInvolvement++;
         }                    
      }            
      
      if($maxInvolvement == 0)
      {      	           	    
         header('location: /its/run.php/UserManager/edit/'.$user_id.'/norelation');
         exit;             	
      }
      
      if($maxInvolvement != count($uniqueRelatioalArr))
      {      	                                                                     	    
         header('location: /its/run.php/UserManager/edit/'.$user_id.'/duplicate');
         exit;
      }      
      
      $userObj = new User($this->db);
      
      // Now set all the values 
      $userObj->setUserId($user_id);
      $userObj->setEmail($_REQUEST['email']);      
      $userObj->setFirstName($_REQUEST['first_name']);
      $userObj->setLastName($_REQUEST['last_name']);
      $userObj->setStatus($_REQUEST['status']);
      
      $status1  = $userObj->update();                
      
      if(!empty($_REQUEST['password']))
      {
         $userObj->setPassword($_REQUEST['password']);
         $userObj->changePassword();
      }                  

      $confirm1 = $this->removeUserInvolvement($user_id,SOURCE_RESOLVERS_TBL);
      $confirm2 = $this->removeUserInvolvement($user_id,AUTHORIZED_SOURCES_TBL);      
          
      if(!empty($status1))
      {      
         for($i = 0 ; $i < $maxInvolvement ; $i++)
         {      	 
            $user_type   = 'user_type' . $i;          
            $source_type = 'source_type' .$i;               	 
            
            // If user is authorized source then insert in authorized source table
            if($sourceUserArr[$user_type] == 4)
            {
               $status2 = $this->addAuthorizedSource($user_id, $source_type,$sourceUserArr);               
            } 
            else
            {
               $status3 = $this->addSourceResolver($user_id,$user_type,$source_type,$sourceUserArr);
            }                            
         }
      }               
      
      $data['msg']       = (!empty($status1)) ? 
                           UPDATE_USER_MESSAGE : UPDATE_USER_FAIL_MESSAGE ;   
                                                                                   
      $data['user_list'] = $this->getAllUsers();
      $data['user_list'] = $this->filterStatus($data['user_list']);
      $data['involvement'] = $maxInvolvement - 1;
     
      if($user_id == $_SESSION['LOGIN_USER']['userId'])
      {
         $this->updateSession();
      }
   
      return $this->template->parseTemplate(USER_LIST_TEMPLATE, $data);     
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
   
   
   function removeUserInvolvement($user_id,$table)
   {      	    	  
      $query['table'] = $table;
      $query['where'] = ' user_id = ' . $user_id; 

      if($this->db->delete($query))
      {
         return true;       
      }      
   }
  
   
  /**
   * Show edit user page from admin panel 
   *
   * @access public
   * @param  none
   * @return none
   */
   
   function editUser($msg = null)
   {   	
      $data  = array();         
      $index = 0 ;
      
      $user_type    = 'user_type';          
      $source_type  = 'source_type'; 
      $sourceList   = array();
      $resolverList = array();  
                 
      $user_id = $this->cmdList[2];             
     
      $query = "SELECT u.user_id, u.first_name,u.last_name,u.email,u.status,
                asrc.source_id as authorized_source, sr.resolver_type 
                as resolver_type, sr.source_id as resolver_source 
                FROM " .  USERS_TBL ." as u LEFT JOIN " . SOURCE_RESOLVERS_TBL . 
                " as sr ON u.user_id = sr.user_id LEFT JOIN " . AUTHORIZED_SOURCES_TBL .
                " as asrc ON u.user_id = asrc.user_id 
                WHERE u.user_id = " .  $user_id;                
      
      $userInfo = $this->db->select($query);                                      
      
      foreach($userInfo as $key => $thisUser)
      {
         if(!empty($thisUser->authorized_source))
         {
            if(!in_array($thisUser->authorized_source,$sourceList))
            {
               $data['dropdown'][$index][$source_type . $index] = $thisUser->authorized_source;
               $data['dropdown'][$index][$user_type . $index]   = 4;
               $sourceList[] = $thisUser->authorized_source;
               $index++;
            }
            
            $resolverTypeItem = $thisUser->resolver_source . $thisUser->resolver_type;
            if(!empty($thisUser->resolver_source) && !in_array($resolverTypeItem,$resolverList))
            {               
               $data['dropdown'][$index][$source_type . $index] = $thisUser->resolver_source;
               $data['dropdown'][$index][$user_type . $index]   = $thisUser->resolver_type;
               $resolverList[] = $thisUser->resolver_source . $thisUser->resolver_type;
               $index++;
            }
         }
         else
         {
            $resolverTypeItem = $thisUser->resolver_source . $thisUser->resolver_type;
            if(!empty($thisUser->resolver_source) && !in_array($resolverTypeItem,$resolverList))
            {               
               $data['dropdown'][$index][$source_type . $index] = $thisUser->resolver_source;
               $data['dropdown'][$index][$user_type . $index]   = $thisUser->resolver_type;
               $resolverList[] = $thisUser->resolver_source . $thisUser->resolver_type;
               $index++;
            }
         }
         
         $data['user_id']    = $thisUser->user_id;         
         $data['first_name'] = $thisUser->first_name;         
         $data['last_name']  = $thisUser->last_name; 
         $data['email']      = $thisUser->email;                  
         $data['status']     = $thisUser->status;         
      }
      
      $data['source_list']     = $this->getSourceList();       
      $data['source_list_str'] = $this->getSourceListStr($data['source_list']); 
      $data['involvement']     = $index -1; 
      
      if($this->cmdList[3] == 'duplicate')
      {
         $data['msg'] = DUPLICATE_RELATION_MESSAGE; 
      }
       
      if($this->cmdList[3] == 'norelation')
      {
         $data['msg'] = NO_RELATION_MESSAGE; 
      }            

      return $this->template->parseTemplate(ADD_USER_TEMPLATE, $data);     
   }
      
  /**
   * Add user from admin panel 
   *
   * @access public
   * @param  none
   * @return none
   */
   
   function addNewUser()
   {   	
      $data  = array(); 
      $count = 0;       
                  
      $maxInvolvement     = 0;   
      $sourceUserArr      = array();  
      $uniqueRelatioalArr = array();                 

      foreach($_REQUEST as $key => $value)
      {
         if(preg_match('/source_type/',$key))
         {
         	  $keyArr = explode("source_type",$key);
         	  $relationalItem = $_REQUEST[$key] . $_REQUEST['user_type'.$keyArr[1]];
         	           	                    
         	  $typedData['dropdown'][$maxInvolvement][$key] = $_REQUEST[$key]; 
         	  
         	  $typedData['dropdown'][$maxInvolvement]['user_type'.$keyArr[1]]   
         	  = $_REQUEST['user_type'.$keyArr[1]]; 
         	  
         	  if(!in_array($relationalItem, $uniqueRelatioalArr))
         	  {
         	  	 $uniqueRelatioalArr[] = $relationalItem;
         	  	 $sourceUserArr['source_type'.$count]  = $_REQUEST[$key];  
         	  	 $sourceUserArr['user_type'.$count]    = $_REQUEST['user_type'.$keyArr[1]]; 
         	  	 $count++;        	                 
            }
            $maxInvolvement++;
         }	
      }
      
      if($maxInvolvement != count($uniqueRelatioalArr))
      {      	                                                                     	             
         $data['first_name'] = $_REQUEST['first_name'];         
         $data['last_name']  = $_REQUEST['last_name']; 
         $data['email']      = $_REQUEST['email'];                  
         $data['status']     = $_REQUEST['status'];          
         $data['dropdown']   = $typedData['dropdown']; 
         
         $data['source_list']     = $this->getSourceList();  
         $data['source_list_str'] = $this->getSourceListStr($data['source_list']); 
         $data['involvement']     = $maxInvolvement -1;
             
         $data['msg']      = DUPLICATE_RELATION_MESSAGE;
                            
         return $this->template->parseTemplate(ADD_USER_TEMPLATE, $data); 
      }
      
      if($maxInvolvement == 0)
      {      	           	    
         $data['first_name'] = $_REQUEST['first_name'];         
         $data['last_name']  = $_REQUEST['last_name']; 
         $data['email']      = $_REQUEST['email'];                  
         $data['status']     = $_REQUEST['status'];          
         $data['dropdown']   = array(array()); 
         
         $data['source_list']     = $this->getSourceList();  
         $data['source_list_str'] = $this->getSourceListStr($data['source_list']);          
             
         $data['msg']             = NO_RELATION_MESSAGE;
                            
         return $this->template->parseTemplate(ADD_USER_TEMPLATE, $data);           	
      }          
                    
      $userObj = new User($this->db);  
      
      $_REQUEST['first_name'] = preg_replace('/[^a-z0-9A-Z]+/i','',$_REQUEST['first_name']);
      $_REQUEST['last_name']  = preg_replace('/[^a-z0-9A-Z]+/i','',$_REQUEST['last_name']);    
      
      // Now set all the values 
      $userObj->setEmail($_REQUEST['email']);
      $userObj->setPassword($_REQUEST['password']);
      $userObj->setFirstName($_REQUEST['first_name']);
      $userObj->setLastName($_REQUEST['last_name']);
      $userObj->setStatus($_REQUEST['status']);

      $user_id = $userObj->insert();      
      
       
      if(!empty($user_id))
      {      
         for($i = 0 ; $i < $maxInvolvement ; $i++)
         {      	 
            $user_type   = 'user_type' . $i;          
            $source_type = 'source_type' .$i;       	 
            
            // If user is authorized source then insert in authorized source table
            if($sourceUserArr[$user_type] == 4)
            {
               $this->addAuthorizedSource($user_id, $source_type,$sourceUserArr);
            } 
            else
            {
               $this->addSourceResolver($user_id,$user_type,$source_type,$sourceUserArr);
            }                            
         }
      }      
      
      $data['msg']      = (!empty($user_id)) ? ADD_USER_MESSAGE : ADD_USER_FAIL_MESSAGE;
      $data['dropdown'] = array(array()); 
      
      $data['source_list']     = $this->getSourceList();  
      $data['source_list_str'] = $this->getSourceListStr($data['source_list']);        
      
      return $this->template->parseTemplate(ADD_USER_TEMPLATE, $data);     
   }
   
   function addSourceResolver($user_id,$user_type,$source_type,$sourceUserArr)
   {      
      $status_date = date('Y-m-d H:i:s'); 
      $source_id   = $sourceUserArr[$source_type]; 
      $user_type   = $sourceUserArr[$user_type];
	    	  
      $query['table'] = SOURCE_RESOLVERS_TBL;
      $query['data']  = array(
                        'user_id' => $user_id,
                        'resolver_type' => $user_type,
                        'status' => 1,
                        'status_date' => $status_date,
                        'source_id' => $source_id
                        );
                              
      if($this->db->insert($query))
      {        
         return true;       
      }
   }
   
   function addAuthorizedSource($user_id,$source_type,$sourceUserArr)
   {      
      $source_id      = $sourceUserArr[$source_type]; 

      $query['table'] = AUTHORIZED_SOURCES_TBL;          	       	 
      $query['data']  = array(
                        'user_id'   => $user_id,
                        'source_id' => $source_id
                        );
                                          
      if($this->db->insert($query))
      {                    
         return true;             
      }
   }

   
  /**
   * Show all available users in the system to admin
   *
   * @access public
   * @param  none
   * @return none
   */
   function showUserList($msg = null)
   {                       
      $data    = array();                                    
      
      $data['user_list'] = $this->getAllUsers();
      
      $data['user_list'] = $this->filterStatus($data['user_list']);      
            
      if(!empty($_SESSION['confirm_msg']))
      {
      	 $data['msg'] = $_SESSION['confirm_msg'];        
      }
      
      unset($_SESSION['confirm_msg']); 
      return $this->template->parseTemplate(USER_LIST_TEMPLATE, $data);
   }
   
   /**
   * Get all users 
   *
   * @access public
   * @param  none
   * @return none
   */
   function getAllUsers()
   {
      $query   =  "SELECT * FROM " . USERS_TBL . " ORDER BY first_name"; 
      return $this->db->select($query);       
   }
   
  /**
   * Display add user form for admin
   *
   * @access public
   * @param  none
   * @return none
   */
   function showAddUserForm()
   {
      $data = array();
            
      $data['source_list']     = $this->getSourceList();       
      $data['source_list_str'] = $this->getSourceListStr($data['source_list']); 
      $data['involvement']     = 0;
      
      $data['dropdown']    = array(array());
      
      return $this->template->parseTemplate(ADD_USER_TEMPLATE,$data);
   }
   
  /**
   * Get source list as comma seperated string
   *
   * @access public
   * @param  an array of source 
   * @return comma seperated string of source
   */   
   function getSourceListStr($srcArr = null)
   {
   	  
      if(!empty($srcArr))
      {
         foreach($srcArr as $key => $thisSouce)
         {
            $str .= $thisSouce->source_id . ":" . $thisSouce->short_name;
            
            if($key < count($srcArr)-1)
            {
               $str .= ',';	
            }
         }      
      } 
      
      return $str;      
   }
   
  /**
   * Get all the available sources
   *
   * @access public
   * @param  none
   * @return an array of std objects 
   */       
   function getSourceList()
   {            
      $query =  "SELECT source_id,short_name FROM " . SOURCE_SETTINGS_TBL ; 
      return $this->db->select($query);    	
   }
   
   
   function checkEmailAvailablity()
   {
      $email = $_REQUEST['email'];

      
      // Now do a query to check wheather this eamil already exists or not.
      $query  = "SELECT email FROM " . USERS_TBL . " WHERE email = '$email'";
      
      $result = $this->db->select($query);        
            
      if(!empty($result))
      {
         $response->success = true;
      }
      else
      {
         $response->success = false;
      }
      
      echo json_encode($response);
      exit;
   } 
   
   function filterStatus($userList = null)
   {   	
      if(!empty($userList))
      {
         foreach($userList as $key => $thisUser)
         {
            if($thisUser->status == 1)
            {
            	$userList[$key]->status = 'Active';
            }
            else
            {
               $userList[$key]->status = 'Inactive';	
            }
         }	
      } 
      
      return $userList;     
   }
   
   function debug($data = null)
   {
      echo '<pre>';
      print_r($data); 
      	
   }
        
   
}
?>
