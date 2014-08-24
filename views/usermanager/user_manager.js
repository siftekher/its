/*
 * Filename   : user_manager.js
 * Purpose    : Do the validation of usermanager tool
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
   
   
   $(document).ready(function() 
   {
   	  $('.its-post_msg').fadeOut(6000);	    
   });   
   
   
   function doValidation()
   {   	  
   	  var user_id = $('#user_id').val(); 

   	  var emailObj = $('#email');    	  
   	  var email    = emailObj.val();
   	  var flag     = 0;
   	     	  
      if(email == '')
      {
         $('#email_error_msg').html(REQUIRED_FIELD);
         emailObj.focus();
         flag = 1;         
      }
      else
      {
         if(!EMAIL_PATTERN.test(email))
         {         	  
            $('#email_error_msg').html(INVALID_EMAIL);
            emailObj.val(''); 
            emailObj.focus();            
            flag = 1; 
         }
         else
         {        
         	  if(user_id == '')
         	  { 
        	     var full_url = DIR_NAME + "UserManager/checkEmail";                    
               
               $.post(full_url, {"email" : email},
               function(response)
               {            
                  if(response.success)
                  {
                     $('#email_error_msg').html(EMAIL_EXISTS);
                     emailObj.val(''); 
                     emailObj.focus();
                     flag = 1;     
                  }
                  else
                  {
                     $('#email_error_msg').html('')
                  }
               }, "json"); 
            }                    	                       
         }
      }
      
      var passwordObj = $('#password');   
      var password    = $('#password').val();             
      if(password == '')
      {
      	 if(user_id == '')
      	 {
            $('#password_error_msg').html(REQUIRED_FIELD);
            passwordObj.focus();
            flag = 1; 	         
         }
      }
      else
      {
         if(password != $('#re_enter_password').val())
         {
            $('#password_error_msg').html(PWD_ERR_MSG);
            passwordObj.val(''); 
            passwordObj.focus();
            flag = 1; 	
         }
         else
         {
            $('#password_error_msg').html('');
         }
      }  
      
      var firstNameObj = $('#first_name');   
      var firstName    = $('#first_name').val();             
      if(firstName == '')
      {
         $('#first_name_error_msg').html(REQUIRED_FIELD);
         firstNameObj.focus();
         flag = 1; 	 
      }
      else
      {
         $('#first_name_error_msg').html('');
      }
      
      if(flag == 1)
      {
         return false;	
      }          
            
      return true;	
   }  
   
   function validateEmail()
   {
   	  var user_id = $('#user_id').val(); 
   	  
   	  var emailObj = $('#email');    	  
   	  var email    = emailObj.val();
   	     	
      if(email == '')
      {
         $('#email_error_msg').html(REQUIRED_FIELD);
         emailObj.focus();
         return false;	         
      }
      else
      {
         if(!EMAIL_PATTERN.test(email))
         {         	  
            $('#email_error_msg').html(INVALID_EMAIL);
            emailObj.val(''); 
            emailObj.focus();            
            return false;	
         }
         else
         {        
         	  if(user_id == '')
         	  { 
        	     var full_url = DIR_NAME + "UserManager/checkEmail";                    
               
               $.post(full_url, {"email" : email},
               function(response)
               {            
                  if(response.success)
                  {
                     $('#email_error_msg').html(EMAIL_EXISTS);
                     emailObj.val(''); 
                     emailObj.focus();
                     return false;      
                  }
                  else
                  {
                     $('#email_error_msg').html('')
                  }
               }, "json"); 
            }                    	                       
         }
      }      	
   }    
   
   function addElement(source_list)
   {    	     	 
      var individualSrc = source_list.split(",");    
      var optionStr     = '';
      
      optionStr += '<option>Select a source</option>';
      
      for(i = 0 ; i < individualSrc.length ; i++)
      {
          var sourceIdArr = individualSrc[i].split(":"); 
          optionStr = optionStr + '<option value="'+sourceIdArr[0]+'">'
                       +sourceIdArr[1]+'</option>';    	        
      }
   	         	     	 
      var divObj = document.getElementById('myDiv');                
      
      var numi = document.getElementById('theValue');       
      var num = (document.getElementById('theValue').value -1)+ 2;   
      
      
             
      numi.value = num;
   
      var sourceNewdiv = document.createElement('div');
      var divIdNameSource = 'mysource'+num+'Div';       
      sourceNewdiv.setAttribute('id',divIdNameSource);     
      sourceNewdiv.className = 'sourceDiv';    
                           
      sourceNewdiv.innerHTML = '<div class="input_box" style="text-align:left; margin-right:10px;">'+
          '<select name="source_type' + num + '" id="source_type' + num +'">'+ optionStr +                	                    
          '</select></div>';                                   
      
      divObj.appendChild(sourceNewdiv);       
      
      var resolverNewdiv = document.createElement('div');
      var divIdNameResolver = 'myresolver'+num+'Div';
      resolverNewdiv.setAttribute('id',divIdNameResolver); 
      resolverNewdiv.className = 'resolverDiv';    
              
      
      resolverNewdiv.innerHTML = '<div class="input_box" style="text-align:left;">'+
          '<select name="user_type' + num + '" id="user_type' + num +'">'+
          '<option>Select a role</option>'+
          '<option value="1">Staff</option>'+
          '<option value="2">Executive</option>'+
          '<option value="3">Supervisor</option>'+
          '<option value="4">Authorized Source</option>'+
          '</select>&nbsp;<a href="#" onclick="removeElement('+num+')"><img src="/its/views/common/images/remove.png"></a></div>';        
      
      divObj.appendChild(resolverNewdiv);                     
   } 
   
   function removeElement(num)
   {        	     	    	  
      var d = document.getElementById('myDiv'); 
      var sourceDivObj   = document.getElementById('mysource'+num+'Div'); 
      var resolverDivObj = document.getElementById('myresolver'+num+'Div');                         
      d.removeChild(sourceDivObj);     
      d.removeChild(resolverDivObj);
   } 
   
   function removeElementFromEdit(num)
   {
      var d = document.getElementById('add_user'); 
      var containerDivObj   = document.getElementById('dropdown_container'+num);       
      d.removeChild(containerDivObj);           
   }  
   
    function confirmation(user_id)
    {
    	 var DELETE_CONFIRM_MSG = 'Are you sure you want to delete this user?';
       var deleteok = confirm(DELETE_CONFIRM_MSG);
       if (deleteok== true) 
       {
          window.location="/its/run.php/UserManager/delete/"+user_id;          
       }
    }       
   