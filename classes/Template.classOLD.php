<?php
/*
 * Filename   : Template.class.php
 * Purpose    : Pursing html Template
 *
 * @author    : Sheikh Iftekhar <siftekher@gmail.com>
 * @project   : Issue tracking system
 * @version   : 1.0.0
 */

class Template 
{
   static private $instance = NULL; // Static instance of smarty class
   private $smarty;    // smarty object
   private $template;  // template file
   private $screen;    // screen template file

  /**
   * Purpose : GUI initiator
   *
   * @access : public
   * @param  : array $params - settings for template parser
   * @return : none
   */
   public function __construct($params = null)
   {
      $this->smarty = self::getInstance();
      $this->smarty->template_dir   = TEMPLATE_DIR;
      $this->smarty->compile_dir    = TEMP_DIR;
      $this->smarty->cache_dir      = TEMP_DIR;
      $this->smarty->cache          = false;

      if (!empty($params['alt_tag']))
      {
         $this->smarty->left_delimiter  = $params['alt_tag']['left'];
         $this->smarty->right_delimiter = $params['alt_tag']['right'];
      }

      $this->smarty->register_modifier("addslashes", "stripslashes");
      $this->smarty->security_settings = array('ALLOW_CONSTANTS' => true);
      $this->smarty->assign('app_name', APP_NAME);

      if (!empty($params['template']))
      {
         $this->template = $params['template'];
      }

      if (!empty($params['screen']))
      {
         $this->screen = $params['screen'];
      }
      else
      {
         $this->screen = SCREEN_TEMPLATE;
      }
      
   }

  /**
   * Purpose : Returns single instance of Smarty Object ensuring Singleton
   *           property of the class
   * 
   * @param  : none
   * @return : object -- Instace of Smarty class
   */
   public static function getInstance()
   {
      // if the instance doesnot exist
      if (self::$instance == NULL) 
      {
         // create a new instance
         self::$instance = new Smarty();
      }
      
      // then return the instance
      return self::$instance;
   }

  /**
   * Purpose : Sets layout page
   *
   * @access : public
   * @param  : $screen - string - layout file path
   * @return : none
   */
   public function setScreen($screen = null)
   {
      $this->screen = $screen;
   }
   
  /**
   * Purpose : gets layout page
   *
   * @access : public
   * @param  : none
   * @return : string - layout file path
   */
   public function getScreen()
   {
      return $this->screen;
   }

  /**
   * Purpose : Generates the entire page including header, footer and content
   *
   * @access : public
   * @param  : $content - string - content for the layout
   * @param  : $data - array - data to be parsed for header and footer
   * @return : string - html output as page
   */
   public function createScreen($contents = null, $data = null)
   {
      $data['contents']  = $contents;
      $data['userData']  = $_SESSION['LOGIN_USER'];
      $data['header']    = $this->parseTemplate(HEADER_TEMPLATE, $data);
      $data['topmenu']   = $this->parseTemplate(TOP_MENU_TEMPLATE, $data);
      $data['leftmenu']  = $this->parseTemplate(LEFT_MENU_TEMPLATE, $data);
      $data['firstName'] = $_SESSION['LOGIN_USER']['firstName'];
      $data['footer']    = $this->parseTemplate(FOOTER_TEMPLATE, $data);

      return $this->parseTemplate(SCREEN_TEMPLATE, $data);
   }

  /**
   * Purpose : Function to parse and generate template
   *
   * @access : public
   * @param  : $template - template file to parse
   * @param  : $data - data to parse
   * @return : string - html output as parsed template
   */
   public function parseTemplate($template = null, $data = null)
   {
      if (count($data) > 0)
      {
         foreach ($data as $key => $value)
         {
            $this->smarty->assign($key ,$value);
         }
      }

      // Add system time and date
      $this->smarty->assign('SYSTEM_DATE', date('m/d/Y'));
      $this->smarty->assign('SYSTEM_TIME', date('h:i:s A T'));
      $this->smarty->assign('SYSTEM_YEAR', date('Y'));

      // Now return parsed template
      if (!empty($template))
      {
         $this->template = $template;
      }

      return $this->smarty->fetch($this->template);
   }

  /**
   * Purpose : Function to bind user data
   *
   * @access : public
   * @param  : $tplvar - string - template variable
   * @param  : $value - string - value to bind
   * @return : none
   */
   final public function assignData($tplvar, $value = null)
   {
      $this->smarty->assign($tplvar, $value);
   }

  /**
   * Purpose : Function to unbind data
   *
   * @access : public
   * @param  $tplvar - string - template variables to unbind
   * @return none
   */
   final public function clearAssign($tplvar)
   {
      $this->smarty->clear_assign($tplvar);
   }

  /**
   * Purpose : Function to unbind all assigned data
   *
   * @access : public
   * @param  : none
   * @return : none
   */
   final public function clearAllAssign()
   {
      $this->smarty->clear_all_assign();
   }

  /**
   * Purpose : Function to Enable Template Engine
   *           Caching Mechanism
   *
   * @access : public
   * @param  : none
   * @return : none
   */
   final public function enableCache()
   {
      $this->smarty->cache = true;
   }

  /**
   * Purpose : Function to disable Template Engine
   *           Caching Mechanism
   *
   * @access : public
   * @param  : none
   * @return : none
   */
   final public function disableCache()
   {
      $this->smarty->cache = false;
   }

  /**
   * Purpose : Function to Clear all Accumulated Cache
   *
   * @access : public
   * @param  : none
   * @return : none
   */
   final public function clearCache()
   {
      $this->smarty->clear_all_cache();
   }
   
  /**
   * Purpose : Function to register modifier
   *
   * @access : public
   * @param  : $modifier - array, string - name of template modifier
   * @param  : $modifierImpl - string - name of PHP function to register
   * @return : none
   */
   final public function registerModifier($modifier, $modifierImpl = null)
   {
      if(is_array($modifier))
      {
         foreach($modifier as $key => $val)
         {
            if($key != '')
            {
               $this->smarty->register_modifier($key, $val);
            }
         }
      }
      else
      {
         if($modifier != '')
         {
            $this->smarty->register_modifier($modifier, $modifierImpl);
         }
      }
   }


  /**
   * Purpose : Function to unregister modifier
   *
   * @access : public
   * @param  : $modifier - array, string - name of template modifier
   * @return : none
   */
   final public function unregisterModifier($modifier)
   {
      if (is_array($modifier))
      {
         foreach($modifier as $key)
         {
            if($key != '')
            {
               $this->smarty->unregister_modifier($key);
            }
         }
      }
      else
      {
         if($modifier != '')
         {
            $this->smarty->unregister_modifier($modifier);
         }
      }
   }
   
} // End of Template class

?>