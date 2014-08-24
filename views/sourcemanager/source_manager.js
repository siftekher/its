/*
 * Filename   : source_manager.js
 * Purpose    : Do the validation of source manager tool
 *
 * @author    : beroza@evoknow.com
 * @project   : Issue Tracking System
 * @version   : 1.0
 * @copyright : http://www.evoknow.com
 */
   
   var DIR_NAME = '/its/run.php/';
   
   var EMAIL_EXISTS   = 'Email address already exists.'; 
   var REQUIRED_FIELD = 'Required field cannot be left blank.'; 
   var INVALID_EMAIL  = 'Invalid email address.';   
   var EMAIL_PATTERN  = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;   
   
   var PWD_ERR_MSG    = 'Passwords do not match.';
   
   var NAME_ERR_MSG   = 'Invalid name.';
   var NAME_PATTERN   = /^[a-z0-9]$/;
   
   
   function doValidation()
   {   	  
   	  var source_id = $('#source_id').val(); 
   	  var flag      = 0;     	    	     	  
   	  
   	  var fieldList=new Array("name","short_name","pop_email","pop_password",
   	                          "pop_server","min_image_attachment_size",
   	                          "footer_text","reply_from_name","reply_from_address",
   	                          "new_ticket_email_subject","new_ticket_email_template",
   	                          "existing_ticket_email_subject",
   	                          "existing_ticket_email_template",
   	                          "status_reply_email_subject","status_reply_email_template",
   	                          "list_ticket_email_subject","list_ticket_email_subject",
   	                          "list_ticket_email_template","max_response_time");
   	     	  
      for(i = 0 ; i < fieldList.length ; i++)
      {
      	 var fieldId    = '#'+fieldList[i];
         var fieldObj   = $(fieldId);   
         var fieldValue = fieldObj.val();             
         
         if(fieldValue == '')
         {
         	  if(source_id != '' && fieldList[i] == 'pop_password')
         	  {
         	  	 $(fieldId+'_error_msg').html('');        	     	         	  
         	  }
         	  else
         	  {
               $(fieldId+'_error_msg').html(REQUIRED_FIELD);
               flag = 1;  
                
            }
         }      
         else
         {
            if(fieldList[i] == 'pop_email')
            {
               if(!EMAIL_PATTERN.test(fieldValue))
               {         	  
                  $(fieldId+'_error_msg').html(INVALID_EMAIL);
                  fieldObj.val(''); 
                  fieldObj.focus();            
                  return false;	
               }
               else
               {         
                  $(fieldId+'_error_msg').html(''); 
               }                              
            }
            else
            {
               $(fieldId+'_error_msg').html('');             
            }
         }         	        
      }   	     	     	     	     	  
        
      if(flag == 1)
      {          
         return false;	
      }
      
      return true; 
   }
          