var RELOAD_CURRENT_URL = false;
var UPLOADED_FILES_INDEX = 0;
var UPLOADED_FILES = new Array();
var NUMERIC_ONLY   = /[0-9]+/;
var DIR_NAME = '/its/run.php/';


	   
function initUploadifyForDetailDialog()
{
	$("#uploadify").uploadify({
		'uploader'       : '/its/ext/js/jquery/jquery.uploadify-v2.1.0/uploadify.swf',
		'script'         : CONTROLLER_URL+'Ticket/upload/'+LOGIN_USER_ID,
		'cancelImg'      : '/its/ext/js/jquery/jquery.uploadify-v2.1.0/cancel.png',
		'folder'         : '/its/tmp',
		'queueID'        : 'fileQueue',
		'buttonText'     : 'Attach File',
		'auto'           : true,
		'multi'          : true,
		'onComplete'     : function(event, queueID, fileObj, response, data){ 
                           displayAttachments(fileObj, response);
                         }
	});
}



function initAutoCompleteTagDialog()
{
	$("#tag").autocomplete({
		source: CONTROLLER_URL+'Ticket/search_tag/'
	});
}



function initTagDialog()
{
   //$("#dialog-tag").dialog("destroy");
   
   $("#dialog-tag").dialog({
   	autoOpen: false,
   	height: 250,
   	width: 300,
   	modal: true,
   	buttons: {
   		Cancel: function() {
   		   $('.tag-message-box').css('color', 'black');
            $('#tag-message-box').html("");
            $('#tag_msg').html("");
   			$(this).dialog('close');
   		},
   		'Save': function() {
   		   
   		   $('#tag_msg').html("");
   		   $('[name=tag]').val( trim($('[name=tag]').val()) );

   			if(!$('[name=tag]').val())
   			{
   			   $('#tag_msg').html("Tag is required");
   			   return false;
   			}

            addTag();
            
            $('[name=tag]').val('');
   		}
   	},
   	close: function() {
   
   	}
   });
}

function initMergeDialog()
{  
   $("#dialog-merge").dialog({
   	autoOpen: false,
   	height: 250,
   	width: 500,
   	modal: true,
   	buttons: {
   		Cancel: function() {
            $('#merge_msg').html("");
   			$(this).dialog('close');
   		},
   		'Save': function() {
   		   
   		   $('#merge_msg').html("");
   		   $('[name=merge_ticket_id]').val( trim($('[name=merge_ticket_id]').val()) );

   			if(!$('[name=merge_ticket_id]').val())
   			{
   			   $('#merge_msg').html("Ticket Id is required.");
   			   return false;
   			}
   			
   			if(!NUMERIC_ONLY.test($('[name=merge_ticket_id]').val()))
   			{
   			   $('#merge_msg').html("Ticket Id should be numeric.");
   			   return false;
   			}

            mergeTicket();
   		}
   	},
   	close: function() {
   
   	}
   });
}

	
	
function initFormDialog()
{
   //$("#dialog-form").dialog("destroy");
   
   $("#dialog-form").dialog({
   	autoOpen: false,
   	height: 600,
   	width: 800,
   	modal: true,
   	open: function(event,ui) {    
                                //$('.ui-dialog-buttonpane').attr('id','form_button');
                                $('.ui-dialog-buttonpane').addClass('form-dialog');
                            },
     beforeClose: function(event, ui) { 
                                $('.ui-dialog-buttonpane').removeClass('form-dialog');
                           },


   	buttons: {
   		Cancel: function() {
            $('.dialog-message-box').css('color', 'black');
            $('.dialog-message-box').html("<span class='required-star' style='color:red;'>*</span> fields are required.");
            $('#uploaded_file_list').html('');
            clearDetailDialogMessages();
   			$(this).dialog('close');
   		},
   		'Save': function() {
            var is_error = false;
            clearDetailDialogMessages();

            //$('[name=status]').val( trim($('[name=status]').val()) );
            $('[name=subject]').val( trim($('[name=subject]').val()) );
            $('[name=detail]').val( trim($('[name=detail]').val()) );

   			//if(!$('[name=status]').val())
   			//{
   			//   printMessage("status_msg", "Status is required");
   			//   is_error = true;
   			//}
   			
   			if(!$('[name=subject]').val())
   			{
   			   printMessage("subject_msg", "Subject is required");
   			   is_error = true;
   			}
   			
   			if(!$('[name=detail]').val())
   			{
   			   printMessage("detail_msg", "Details is required");
   			   is_error = true;
   			}
   
            if(is_error)
            {
               return false;
            }
            
            addDetail();

            CUR_TICKET_ID = 0;
            UPLOADED_FILES_INDEX = 0;
            UPLOADED_FILES = new Array();
            //$('[name=status]').val('');
            $('[name=subject]').val('');
            $('[name=detail]').val('');
            if(RELOAD_CURRENT_URL)
            {
               location.href = location.href;
            }
            
   		}
   	},
   	close: function() {
   		//allFields.val('').removeClass('ui-state-error');
   	}
   });
   
   $('.required-star').css('color', '#ff0000');
}

function initSourceDialog()
{
   //$("#dialog-tag").dialog("destroy");
   
   $("#dialog-source").dialog({
   	autoOpen: false,
      height: 194,
      width: 325,
   	modal: true,
   	buttons: {
   		Cancel: function() {
   			$(this).dialog('close');
   		},

   		'copy': function() {
   		   var sourceId = $('#source').val();
            copySelectedSource(sourceId);
            $(this).dialog('close');
   		}
   	},
   	close: function() {
   
   	}
   });
}


function showDetailDialog(ticket_id, isReloadCurrentURL)
{
   CUR_TICKET_ID = ticket_id;
   RELOAD_CURRENT_URL = isReloadCurrentURL;
   regenerateDialogForm(ticket_id)
   $('#dialog-form').dialog('open');
}

function regenerateDialogForm(ticketId)
{
    var _url  = CONTROLLER_URL + "Ticket/gen_dialog_form";
    var _data = "ticket_id=" + ticketId;         
    $.ajax({
      type: 'post',
      url: _url,
      data: _data,
      async: false,
      success: function (response)
      {
         if(response == 1)
         {
            var stOpt = "<input type='radio' name='clarification_re' value='5'> Clarifications required <br>"
                        + "<input type='radio' name='clarification_re' value='6'> Completed";
                         //+ "<input type='radio' name='clarification_re' value='0' checked> None ";
                        
            $('#clarification_container').html(stOpt);
         }
         else
         {
            $('#clarification_container').html('');          }
         }
   });
}


function showTagDialog(ticket_id, isReloadCurrentURL)
{
   CUR_TICKET_ID = ticket_id;
   RELOAD_CURRENT_URL = isReloadCurrentURL;
   $('#dialog-tag').dialog('open');
}

function showSourceDialog()
{
   //alert('hi');
   //CUR_TICKET_ID = ticket_id;
   //RELOAD_CURRENT_URL = isReloadCurrentURL;
   $('#dialog-source').dialog('open');
}


function showMergeDialog(ticket_id, isReloadCurrentURL)
{
   CUR_TICKET_ID = ticket_id;
   RELOAD_CURRENT_URL = isReloadCurrentURL;
   $('#dialog-merge').dialog('open');
}


function trim(_text)
{
   return _text.replace(/^\s*|\s*$/g,'');
}


function printMessage(_message_id, _message_text)
{
   $('#'+_message_id).html(_message_text);
}

function clearMessage(_message_id)
{
   $('#'+_message_id).html('');
}

function clearDetailDialogMessages()
{
   //clearMessage("status_msg");
   clearMessage("subject_msg");
   clearMessage("detail_msg");
}



function clearTagDialog()
{

}

function mergeTicket()
{
   var _url  = CONTROLLER_URL + "Ticket/merge_ticket"; 
   var _data = "ticket_id=" + CUR_TICKET_ID + "&merge_ticket_id=" + $('[name=merge_ticket_id]').val();
   
   $.ajax({
      type: 'post',
      url: _url,
      data: _data,
      dataType: 'json',
      async: false,
      success: function(json)
      {
          if(json[0].isError == 1)
          {
             $('#merge_msg').html(json[0].message);
          }
          else
          {
             $('#dialog-merge').dialog('close');
             window.location = "/its/run.php/ticket/details/" + CUR_TICKET_ID;
          }
      }
   });   
}

function addDetail()
{
   var _url = CONTROLLER_URL + "Ticket/add_detail";
   var clarificationRE = $('input:radio[name=clarification_re]:checked').val();
   
   if(!clarificationRE)
   {
       clarificationRE = 0;
   }
   
   var _data = "ticket_id=" + CUR_TICKET_ID + "&subject=" + $('[name=subject]').val() + "&notes=" + $('[name=detail]').val() + "&changed_status=" +  clarificationRE;

   if(UPLOADED_FILES.length)
   {
      _data = _data + "&files=" + UPLOADED_FILES.join("::");
   }

            
   $.ajax({
      type: 'post',
      url: _url,
      data: _data,
      dataType: 'json',
      async: false,
      success: function (json)
      {
          $('.dialog-message-box').css('color', 'green');
          $('.dialog-message-box').html(json[0].message);
          if(json[0].isError)
          {
             $('.dialog-message-box').css('color', 'red');
          }
          else
          {
             $('#dialog-form').dialog('close');
             window.location = "/its/run.php/ticket/details/" + CUR_TICKET_ID;
          }
      }
   });
}

function addTag()
{
   var _url = CONTROLLER_URL + "Ticket/add_tag";
   var _data = "ticket_id=" + CUR_TICKET_ID + "&tag=" + $('[name=tag]').val();
   
   $.ajax({
      type: 'post',
      url: _url,
      data: _data,
      dataType: 'json',
      async: false,
      success: function(json)
      {
         $('.tag-message-box').css('color', 'green');
         $('.tag-message-box').html(json[0].message);
         if(json[0].isError)
         {
            $('.tag-message-box').css('color', 'red');
         }
         else
         {
            // added on 10302010
            $('#dialog-tag').dialog('close');
            window.location.reload(); 
         }
      }
   });
}

function assignTicketToSelf(_ticket_id)
{
   var _url = CONTROLLER_URL + "Ticket/assign_self";
   var _data = "ticket_id=" + _ticket_id;
   
   $.ajax({
      type: 'post',
      url: _url,
      data: _data,
      dataType: 'json',
      async: false,
      success: function(json)
      {
         $('.message-box').css('display', '');
         $('.msg').css('color', 'green');
         $('.msg').html(json[0].message);
         if(json[0].isError)
         {
            $('.msg').css('color', 'red');
         }
         $('.message-box').fadeOut(6000);
      }
   });
}

function markExecutive(_ticket_id)
{
   var _url = CONTROLLER_URL + "Ticket/mark_executive";
   var _data = "ticket_id=" + _ticket_id;
   
   $.ajax({
      type: 'post',
      url: _url,
      data: _data,
      dataType: 'json',
      async: false,
      success: function(json)
      {
         if(json[0].marked == 1)
         {
            $('#executive_link').html("Mark As Non Executive Complaint");
         }
         else
         {
            $('#executive_link').html("Mark As Executive Complaint");   
         }

         $('.message-box').css('display', '');
         $('.msg').css('color', 'green');
         $('.msg').html(json[0].message);
         if(json[0].isError)
         {
            $('.msg').css('color', 'red');
         }
         $('.message-box').fadeOut(6000);
      }
   });
}

function deleteTicket(_ticket_id)
{
   if(!confirm("Are you sure you want to delete ticket"))
   {
      return false;
   }
   
   
   var _url = CONTROLLER_URL + "Ticket/delete_ticket";
   var _data = "ticket_id=" + _ticket_id;
   
   $.ajax({
      type: 'post',
      url: _url,
      data: _data,
      dataType: 'json',
      async: false,
      success: function(json)
      {
         if(!json[0].isError)
         {
            location.href = CONTROLLER_URL+'Ticket'
            return false;
         }
         
         $('#bottom-add-detail-button').css('display', 'none');
         $('#bottom-add-tag-button').css('display', 'none');
         $('#delete_link').html('DELETED TICKET');
         $('#close_link').html('');
         $('.message-box').css('display', '');
         $('.msg').css('color', 'green');
         $('.msg').html(json[0].message);
         if(json[0].isError)
         {
            $('.msg').css('color', 'red');
         }
         $('.message-box').fadeOut(6000);
      }
   });
}

function closeTicket(_ticket_id)
{
   if(!confirm("Are you sure you want to close ticket"))
   {
      return false;
   }
   
   var _url = CONTROLLER_URL + "Ticket/close_ticket";
   var _data = "ticket_id=" + _ticket_id;
   
   $.ajax({
      type: 'post',
      url: _url,
      data: _data,
      dataType: 'json',
      async: false,
      success: function(json)
      {
         if(!json[0].isError)
         {
            location.href = CONTROLLER_URL+'Ticket'
            return false;
         }
         
         $('#bottom-add-detail-button').css('display', 'none');
         $('#bottom-add-tag-button').css('display', 'none');
         $('#close_link').html('CLOSED TICKET');
         $('.message-box').css('display', '');
         $('.msg').css('color', 'green');
         $('.msg').html(json[0].message);
         if(json[0].isError)
         {
            $('.msg').css('color', 'red');
         }
         $('.message-box').fadeOut(6000);
      }
   });
}

function displayAttachments(fileObj, response)
{
   UPLOADED_FILES[UPLOADED_FILES_INDEX] = response;
   UPLOADED_FILES_INDEX++;
   

   var item = "";
   item += "<div id='"+response+"' style='border:0px solid #cccddd; width:70%; border-bottom:0px solid #cccddd; padding-top:7px;'>";
      item += "<div style='float:left;'>";
         //item += "<img src='/its/views/common/images/attach.png' border='0' />";
      item += "</div>";
      item += "<div style='float:left;'><img src='/its/views/common/images/attach.png' border='0' />"+fileObj.name+"</div>";
      item += "<div style='float:left;'>";
         item += "&nbsp;<img src='/its/views/common/images/cross.png' border='0' style='cursor:pointer;' onclick=\"removeAttachment('"+response+"')\" />";
      item += "</div>";
      item += "<div style='clear:both;'></div>";
   item += "</div>";
   
   var html = $('#uploaded_file_list').html() + item;
   
   $('#uploaded_file_list').html(html);
}


function removeAttachment(_file)
{
   if(!_file)
   {
      return false;   
   }
   
   _list = new Array();
   _index = 0;
   for(i=0; i < UPLOADED_FILES.length; i++)
   {
      if(UPLOADED_FILES[i] != _file)
      {
         _list[_index] = UPLOADED_FILES[i];
         _index++;
      }
   }
   
   UPLOADED_FILES = _list
   UPLOADED_FILES_INDEX = _index;
   
   var _container = document.getElementById('uploaded_file_list');
   var _item = document.getElementById(_file);
   _container.removeChild(_item);
   
   var _url = CONTROLLER_URL + "Ticket/rm_upload";
   var _data = "file=" + _file;
   
   $.ajax({
      type: 'post',
      url: _url,
      data: _data,
      dataType: 'json',
      async: false,
      success: function(json)
      {
         
      }
   });
}

function copySelectedSource(sourceId)
{
   var _url = DIR_NAME + "SourceManager/get_source/";
   var _data = "source_id=" + sourceId;
   
   $.ajax({
      type: 'post',
      url: _url,
      data: _data,
      dataType: 'json',
      async: false,
      success: function(json)
      {
         
         $('#name').val(json.name);
         $('#short_name').val(json.short_name);
         $('#pop_email').val(json.pop_email);
         //$('#pop_password').val(json.pop_password);
         $('#pop_server').val(json.pop_server);
         $('#min_image_attachment_size').val(json.min_image_attachment_size);
         $('#footer_text').val(json.footer_text);
         $('#reply_from_name').val(json.reply_from_name);
         $('#reply_from_address').val(json.reply_from_address);
         $('#new_ticket_email_subject').val(json.new_ticket_email_subject);
         $('#new_ticket_email_template').val(json.new_ticket_email_template);
         $('#existing_ticket_email_subject').val(json.existing_ticket_email_subject);
         $('#existing_ticket_email_template').val(json.existing_ticket_email_template);
         $('#status_reply_email_subject').val(json.status_reply_email_subject);
         $('#status_reply_email_template').val(json.status_reply_email_template);
         $('#list_ticket_email_subject').val(json.list_ticket_email_subject);
         $('#list_ticket_email_template').val(json.list_ticket_email_template);
         $('#max_response_time').val(json.max_response_time);
         $('#status').val(parseInt(json.status)+1);
      }
   });
}


function gotoHomePage()
{
   location.href = DIR_NAME + "SourceManager/list";
}

