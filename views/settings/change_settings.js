RE_EMAIL   = new RegExp(/^[A-Za-z0-9](([_|\.|\-]?[a-zA-Z0-9]+)*)@([A-Za-z0-9]+)(([_|\.|\-]?[a-zA-Z0-9]+)*)\.([A-Za-z]{2,})$/);
RE_INTEGER = new RegExp(/^(?:[1-9][0-9]*|0)$/);

var ERROR_IN_FORM = false;

function validateUserSettingsForm()
{
    ERROR_IN_FORM = false;
    
    clearFormMessages('UserSettings');
    trimFormTextFields('UserSettings');
    validateFields('UserSettings');
        
    if(!ERROR_IN_FORM)
    {
       $('#UserSettings').submit();
    }
      
}

 function trimFormTextFields(_form_id)
 {
    $('#'+_form_id).find(':input').each(function(){
       if(this.type == 'text')
       {
          $('[name='+this.name+']').val( trim($('[name='+this.name+']').val()) );
       }
    });
 }
 
 function trim(_text)
 {
    return _text.replace(/^\s*|\s*$/g,'');
 }

   
   
function validateFields(_form_id)
{
   var email                     = $('[name=email_address]').val(); 
   var new_password              = $('[name=new_password]').val(); 
   var confirm_new_password      = $('[name=confirm_new_password]').val();   
   var issue_per_page            = $('[name=show_issues_per_page]').val();
   var number_of_issues_for_rss  = $('[name=number_of_issues_for_rss]').val();   
   var show_tag_type             = $('input[name=show_tag_type]:checked').val();
   var no_of_shown_tags          = $('[name=no_of_shown_tags]').val();       
   
   
   if(!issue_per_page)
   {
      $('#show_issues_per_page_error').text("Required field");
      ERROR_IN_FORM = true;
   }
   
   if(issue_per_page && issue_per_page == 0)
   {
      $('#show_issues_per_page_error').text("Issue per page should not be 0");
      ERROR_IN_FORM = true;
   }
   
   if(issue_per_page && issue_per_page != 0 && !RE_INTEGER.test(issue_per_page))
   {
      $('#show_issues_per_page_error').text("Integer Only");
      ERROR_IN_FORM = true;
   }
   
   if(show_tag_type == 'top_tags')
   {
       if(!no_of_shown_tags)
       {
          $('#no_of_shown_tags_error').text("Required field");
          ERROR_IN_FORM = true;
       }
       
       if(no_of_shown_tags && no_of_shown_tags == 0)
       {
          $('#no_of_shown_tags_error').text("Number of top tags should not be 0");
          ERROR_IN_FORM = true;
       }
       
       if(no_of_shown_tags && no_of_shown_tags != 0 && !RE_INTEGER.test(no_of_shown_tags))
       {
          $('#no_of_shown_tags_error').text("Integer Only");
          ERROR_IN_FORM = true;
       }
   }

   if(!email)
   {
      $('#email_address_error').text("Required field");
      ERROR_IN_FORM = true;
   }
   
   if(email && !RE_EMAIL.test(email))
   {
      $('#email_address_error').text("Invalid format");
      ERROR_IN_FORM = true;
   }
   
   if(new_password != confirm_new_password)
   {
      $('#confirm_new_password_error').text("Password not matched");
      ERROR_IN_FORM = true;
   }
   
      
   if($('[name=include_rss]').is(':checked'))
   {     
     if(!number_of_issues_for_rss)
     {
        $('#number_of_issues_for_rss_error').text("Required field");
        ERROR_IN_FORM = true;
     }
     
     if(number_of_issues_for_rss && number_of_issues_for_rss == 0)
     {
        $('#number_of_issues_for_rss_error').text("Number of issue in rss feed should not be 0");
        ERROR_IN_FORM = true;
     }
     
     if(number_of_issues_for_rss && number_of_issues_for_rss != 0 && !RE_INTEGER.test(number_of_issues_for_rss))
     {
        $('#number_of_issues_for_rss_error').text("Integer Only");
        ERROR_IN_FORM = true;
     }
     
     
   }      
   
 }
 
function clearFormMessages(_form_id)
{
  	for(i=0; i < document.getElementById(_form_id).elements.length; i++)
  	{
  		var _input = document.getElementById(_form_id).elements[i];
      switch(_input.type)
      {
         case 'text':
         case 'checkbox':
         case 'radio':
         case 'select':
         $('#'+_input.name+'_error').text("");
         break;
         default:
      
         break;
      }
   }
   //});
}

function clearPasswordField()
{   
   $("#new_password").val("");
   $("#confirm_new_password").val("");   
}
