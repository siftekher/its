var UPLOADED_FILES_INDEX = 0;
var UPLOADED_FILES = new Array();

function checkNSubmit()
{
   var is_error = false;
  
   clearNewTicketEditorMessages();
           
   $('[name=title]').val( trim($('[name=title]').val()) );
   $('[name=notes]').val( trim($('[name=notes]').val()) );
     
    
   if(!$('[name=title]').val())
   {
      printMessage("title_msg", "Title required");
      is_error = true;
   }
    
   if(!$('[name=source]').val())
   {
      printMessage("source_msg", "Source required");
      is_error = true;
   }
   
   if(!$('[name=notes]').val())
   {
      printMessage("notes_msg", "Details required");
      is_error = true;
   }
   
   if(!$('[name=priority]').val())
   {
      printMessage("priority_msg", "Priority required");
      is_error = true;
   }
   
   if(is_error)
   {     
      return false;
   }
   else
   {
     $('#uploaded_files').val(UPLOADED_FILES.join("::"));
     document.new_ticket_frm.submit(); 
   }   
}

function printMessage(_message_id, _message_text)
{
   $('#'+_message_id).html(_message_text);
}

function clearMessage(_message_id)
{
   $('#'+_message_id).html('');
}

function trim(_text)
{
   return _text.replace(/^\s*|\s*$/g,'');
}

function clearNewTicketEditorMessages()
{
   clearMessage("title_msg");
   clearMessage("source_msg");
   clearMessage("notes_msg");
   clearMessage("priority_msg");
}

$(document).ready(function() {
	$("#uploadify").uploadify({
		'uploader'       : '/its/ext/js/jquery/jquery.uploadify-v2.1.0/uploadify.swf',
		'script'         : CONTROLLER_URL+'NewTicket/upload/'+LOGIN_USER_ID,
		'cancelImg'      : '/its/ext/js/jquery/jquery.uploadify-v2.1.0/cancel.png',
		'folder'         : '/its/tmp',
		'queueID'        : 'fileQueue',
		'buttonText'     : 'Attach File',
		'wmode'          : 'transparent',
		'auto'           : true,
		'multi'          : true,
		'onComplete'     : function(event, queueID, fileObj, response, data){
                           displayAttachments(fileObj, response);
                         }
	});
});

function displayAttachments(fileObj, response)
{
   UPLOADED_FILES[UPLOADED_FILES_INDEX] = response;
   UPLOADED_FILES_INDEX++;
   
   var item = "";
   item += "<div id='"+response+"' style='border:0px solid #cccddd; width:70%; border-bottom:0px solid #cccddd; padding-top:7px;'>";
      item += "<div style='float:left;'>";
         item += "<img src='/its/views/common/images/attach.png' border='0' />";
      item += "</div>";
      item += "<div style='float:left;'>"+fileObj.name+"</div>";
      item += "<div style='float:left;'>";
      item += "<img src='/its/views/common/images/cross.png' border='0' style='cursor:pointer;' onclick=\"removeAttachment('"+response+"')\" />";
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
   
   var _url = CONTROLLER_URL + "NewTicket/rm_upload";
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
