<?php
/*
 * Filename   : User.class.php
 * Purpose    : 
 *
 * @author    : Sheikh Iftekhar <siftekher@gmail.com>
 * @project   : Issue tracking system
 * @version   : 1.0.0
 * @copyright : 
 */

class User 
{
   private $db;
   
   private $userId;
   private $email;
   private $password;
   private $authKey;
   private $firstName;
   private $lastName;
   private $status;
   
   private $authorized_sources;
   private $resolver_sources;
   private $sources;
   private $user_types;
   private $settings;

  /**
   * Purpose : User object initiator
   *
   * @access : public
   * @param  : none
   * @return : none
   */
   public function __construct($db, $stdObject = null)
   {
      $this->db = $db;
      
      if($stdObject)
      {
         if(is_object($stdObject))
         {
            $this->init($stdObject);
         }
         else
         {
            $sql  = "SELECT * FROM " . USERS_TBL . " WHERE user_id = $stdObject";
            
            try
            {
               $rows = $this->db->select($sql);
            }
            catch(Exception $Exception){}

            if(isset($rows[0]))
            {
               $this->init($rows[0]);
            }
         }
      }
   }

   private function init($stdObject)
   {
      $fields = (array)$stdObject;
      foreach($fields as $key => $value)
      {
         $function_name = "set" . str_replace(
                                    ' ', 
                                    '', 
                                    ucwords(str_replace('_', ' ', $key))
                                  );
         $this->$function_name($value);
      }
      
      $this->preparePersonalizedData();
   }

  /**
   * Purpose : Sets user id
   *
   * @access : public
   * @param  : $userId - int
   * @return : none
   */
   public function setUserId($userId)
   {
      $this->userId = $userId;
   }

  /**
   * Purpose : Sets user email
   *
   * @access : public
   * @param  : $email - string
   * @return : none
   */
   public function setEmail($email)
   {
      $this->email = $email;
   }

  /**
   * Purpose : Sets user password
   *
   * @access : public
   * @param  : $password - string
   * @return : none
   */
   public function setPassword($password)
   {
      $this->password = md5($password);
   }

  /**
   * Purpose : Sets user forst name
   *
   * @access : public
   * @param  : $firstName - string
   * @return : none
   */
   public function setFirstName($firstName)
   {
      $this->firstName = $firstName;
   }

  /**
   * Purpose : Sets user last name
   *
   * @access : public
   * @param  : $lastName - string
   * @return : none
   */
   public function setLastName($lastName)
   {
      $this->lastName = $lastName;
   }

  /**
   * Purpose : Sets user status
   *
   * @access : public
   * @param  : $status - int
   * @return : none
   */
   public function setStatus($status)
   {
      $this->status = $status;
   }

  /**
   * Purpose : Sets user authKey (depricated)
   *           This function will be removed.
   *
   * @access : public
   * @param  : $authKey - string
   * @return : none
   */
   public function setAuthKey($authKey)
   {
      $this->authKey = $authKey;
   }
  /**
   * Purpose : gets user id
   *
   * @access : public
   * @param  : none
   * @return : int
   */
   public function getUserId()
   {
      return $this->userId;
   }

  /**
   * Purpose : gets user email
   *
   * @access : public
   * @param  : none
   * @return : string
   */
   public function getEmail()
   {
      return $this->email;
   }

  /**
   * Purpose : gets user password
   *
   * @access : public
   * @param  : none
   * @return : string - md5 formated
   */
   public function getPassword()
   {
      return $this->password;
   }

  /**
   * Purpose : gets user auth-key
   *
   * @access : public
   * @param  : none
   * @return : string
   */
   public function getAuthKey()
   {
      return $this->authKey;
   }

  /**
   * Purpose : gets user first name
   *
   * @access : public
   * @param  : none
   * @return : string
   */
   public function getFirstName()
   {
      return $this->firstName;
   }

  /**
   * Purpose : gets user last name
   *
   * @access : public
   * @param  : none
   * @return : string
   */
   public function getLastName()
   {
      return $this->lastName;
   }

  /**
   * Purpose : gets user status
   *
   * @access : public
   * @param  : none
   * @return : int
   */
   public function getStatus()
   {
      return $this->status;
   }

  /**
   * Purpose : creates auth key
   *
   * @access : public
   * @param  : none
   * @return : string
   */
   private function createAuthKey()
   {
      $authKey = md5(
                  $this->getEmail() . 
                  $this->getUserId() . 
                  $this->getPassword()
                  );
      return $authKey;
   }

  /**
   * Purpose : checks if user source or solver user
   *
   * @access : public
   * @param  : none
   * @return : string
   */
   public function whoIs()
   {
      if($this->getAuthorizedSources() && 
         $this->getResolverSources())
      {
         return "Source and Resolver";
      }
      
      if($this->getAuthorizedSources() && 
         !$this->getResolverSources())
      {
         return "Source";
      }

      if($this->getResolverSources() && 
         !$this->getAuthorizedSources())
      {
         return "Resolver"; 
      }
      
      return false;
   }

  /**
   * Purpose : gets user type
   *
   * @access : public
   * @param  : none
   * @return : string
   */
   public function getUserTypes()
   {
      return $this->user_types;
   }

  /**
   * Purpose : gets user sources
   *
   * @access : public
   * @param  : none
   * @return : array - of stdObject
   */
   public function getSources()
   {
      return $this->sources;
   }

  /**
   * Purpose : gets user authorised sources
   *
   * @access : public
   * @param  : none
   * @return : array - of stdObject
   */
   public function getAuthorizedSources()
   {
      return $this->authorized_sources;
   }

  /**
   * Purpose : gets user resolver sources
   *
   * @access : public
   * @param  : none
   * @return : array - of stdObject
   */
   public function getResolverSources()
   {
      return $this->resolver_sources;
   }

  /**
   * Purpose : ads user data into database
   *
   * @access : public
   * @param  : none
   * @return : void
   */
   public function insert()
   {
      $data             = array();
      $data['table']    = USERS_TBL;
      $data['data']     = array(
         'email'        => $this->getEmail(),
         'password'     => $this->getPassword(),
         'first_name'   => $this->getFirstName(),
         'last_name'    => $this->getLastName(),
         'status'       => $this->getStatus()
      );
      
      try
      {
         $id = $this->db->insert($data);
      }
      catch(Exception $Exception){}
      
      if($id)
      {
         $this->setUserId($id);
         $this->setAuthKey($this->createAuthKey());

         $data          = array();
         $data['table'] = USERS_TBL;
         $data['data']  = array(
            'auth_key'  => $this->getAuthKey()
         );
         $data['where'] = "user_id = " . $this->getUserId();
         
         try
         {
            $this->db->update($data);
         }
         catch(Exception $Exception){}
      }
      
      return $id;      
   }

  /**
   * Purpose : update user data into database
   *
   * @access : public
   * @param  : none
   * @return : void
   */
   public function update()
   {
      $this->setAuthKey($this->createAuthKey());
      
      $data             = array();
      $data['table']    = USERS_TBL;
      $data['data']     = array(
         'email'        => $this->getEmail(),
         'first_name'   => $this->getFirstName(),
         'last_name'    => $this->getLastName(),
         'status'       => $this->getStatus(),
         'auth_key'     => $this->getAuthKey()
      );
      $data['where']    = "user_id = " . $this->getUserId();
      
      try
      {
         $this->db->update($data);
      }
      catch(Exception $Exception){}
      
      return true;
   }

  /**
   * Purpose : deletes user from database
   *
   * @access : public
   * @param  : none
   * @return : void
   */
   public function delete()
   {
      $data          = array();
      $data['table'] = USERS_TBL;
      $data['where'] = "user_id = " . $this->getUserId();
      
      try
      {
         $this->db->delete($data);
      }
      catch(Exception $Exception){}
      
      return true;
   }

  /**
   * Purpose : update user password
   *
   * @access : public
   * @param  : none
   * @return : boolean - true or false
   */
   public function changePassword()
   {
      $this->setAuthKey($this->createAuthKey());
      
      $data             = array();
      $data['table']    = USERS_TBL;
      $data['data']     = array(
         'email'        => $this->getEmail(),
         'password'    => $this->getPassword(),
         'auth_key'     => $this->getAuthKey()
      );
      $data['where']    = "user_id = " . $this->getUserId();
      
      try
      {
         $this->db->update($data);
      }
      catch(Exception $Exception){}
      
      return true;
   }

  /**
   * Purpose : collects user source informations
   *
   * @access : public
   * @param  : none
   * @return : void
   */
   private function preparePersonalizedData()
   {
      $list = array();
      
      $sql  = "SELECT u.user_id, u.source_id, s.name, s.short_name 
            FROM " . AUTHORIZED_SOURCES_TBL . " as u, " . SOURCE_SETTINGS_TBL . " as s 
            WHERE u.source_id = s.source_id AND u.`user_id` = " . $this->getUserId();
      
      try
      {
         $authorized_sources = $this->db->select($sql);
      }
      catch(Exception $Exception){}
      
      $authorized_sources = $authorized_sources ? $authorized_sources : array();
      $this->authorized_sources = $authorized_sources;

      $sql  = "SELECT u.user_id, u.source_id, s.name, s.short_name 
            FROM " . SOURCE_RESOLVERS_TBL . " as u, " . SOURCE_SETTINGS_TBL . " as s 
            WHERE u.source_id = s.source_id AND u.`user_id` = " . $this->getUserId();

      try
      {
         $resolver_sources = $this->db->select($sql);
      }
      catch(Exception $Exception){}
      
      $resolver_sources = $resolver_sources ? $resolver_sources : array();
      $this->resolver_sources = $resolver_sources;
      
      $this->sources = array_merge($authorized_sources, $resolver_sources);
      
      $sql  = "SELECT source_id, resolver_type 
               FROM " . SOURCE_RESOLVERS_TBL . " 
               WHERE `user_id` = " . $this->getUserId();
      
      try
      {
         $resolver_types = $this->db->select($sql);
      }
      catch(Exception $Exception){}
      
      if($resolver_types)
      {
         foreach($resolver_types as $index => $row)
         {
            $this->user_types[$row->source_id] = $row->resolver_type;
         }
      }
      
      $sql  = "SELECT * FROM " . TICKET_USER_SETTINGS_TBL . " 
               WHERE user_id = " . $this->getUserId();
      try
      {
         $settings = $this->db->select($sql);
      }
      catch(Exception $Exception){}
      
      $this->settings = isset($settings[0]) ? (array)$settings[0] : array();
   }

  /**
   * Purpose : gets user data from user object
   *
   * @access : public
   * @param  : none
   * @return : array
   */
   public function getData()
   {
      $vars = get_object_vars($this);
      unset($vars['db']);
      return $vars;
   }
   
}