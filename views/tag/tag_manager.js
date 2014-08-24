/*
 * Filename   : tag_manager.js
 * Purpose    : Do the validation of tagmanager tool
 *
 * @author    : beroza@evoknow.com
 * @project   : Issue Tracking System
 * @version   : 1.0
 * @copyright : http://www.evoknow.com
 */
   
   var DIR_NAME = '/its/run.php/';
   
  
   var REQUIRED_FIELD = 'Required field cannot be left blank.'; 
   var INVALID_TAG    = 'Please enter a valid tag.';   
  
   var TAG_PATTERN   = /[^a-z0-9A-Z\'\"\-]+/;
   
   
   $(document).ready(function() 
   {
      $('.its-post_msg').fadeOut(6000);      
      
      $('#tag').blur(function() 
      {
         if(doValidation())
         {
            return true; 	
         }
      });
      
       $("table").tablesorter({headers:{2:{sorter: false}},widgets: ['zebra']});
      
   });
   


   function doValidation()
   {   	  
   	  var tagObj = $('#tag');
      var tag    = tagObj.val(); 

      if(tag == '')
      {
         $('#tag_error_msg').html(REQUIRED_FIELD);
         tagObj.focus();
         return false;
      }      
      else
      {         	           	  
         if(TAG_PATTERN.test(tag))
         {         	  
            $('#tag_error_msg').html(INVALID_TAG);            
            tagObj.focus();            
            return false;	
         }
         else
         {           	       
      	    $('#tag_error_msg').html('');
      	    
         }                          	  
      }
      return true;     	
   }        
    
    function confirmation(tag_id)
    {
    	 var DELETE_CONFIRM_MSG = 'Are you sure you want to delete this tag?';
       var deleteok = confirm(DELETE_CONFIRM_MSG);
       if (deleteok== true) 
       {          
          window.location="/its/run.php/TagManager/delete/"+tag_id;                
       }
    }