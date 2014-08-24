var RELOAD_CURRENT_URL = false;

//'scriptData'     : {'session_id':'{/literal}{$session_id}{literal}'},
//		'script'         : '/its/ext/js/jquery/jquery.uploadify-v2.1.0/uploadify.php',


$(document).ready(function() {
	$("#uploadify").uploadify({
		'uploader'       : '/its/ext/js/jquery/jquery.uploadify-v2.1.0/uploadify.swf',
		'script'         : CONTROLLER_URL+'Ticket/upload',
		'cancelImg'      : '/its/ext/js/jquery/jquery.uploadify-v2.1.0/cancel.png',
		'folder'         : '/its/tmp',
		'queueID'        : 'fileQueue',
		'auto'           : true,
		'multi'          : true,
		'onComplete'     : function(event, queueID, fileObj, response, data){
                           
                         }
	});
});




$(document).ready(function() {
   $("#dialog-tag").dialog({
   	autoOpen: false,
   	height: 600,
   	width: 500,
   	modal: true,
   	buttons: {
   		'Save': function() {
            alert(447);
   		},
   		Cancel: function() {
   
   			$(this).dialog('close');
   		}
   	},
   	close: function() {
   
   	}
   });
});



$(document).ready(function() {
	$("#tag").autocomplete({
		source: CONTROLLER_URL+'Ticket/search_tag/'
	});
});
	
	
	
   
	$(function() {

		$("#dialog").dialog("destroy");
		
		$("#dialog-form").dialog({
			autoOpen: false,
			height: 600,
			width: 500,
			modal: true,
			buttons: {
				'Save': function() {

               $('.dialog-message-box').css('color', 'black');
               $('.dialog-message-box').html("<span class='required-star' style='color:red;'>*</span> fields are required.");

					if(!$('[name=status]').val())
					{
					   $('.dialog-message-box').css('color', 'red');
					   $('.dialog-message-box').html("Status is required");
					   return false;
					}
					
					if(!$('[name=subject]').val())
					{
					   $('.dialog-message-box').css('color', 'red');
					   $('.dialog-message-box').html("Subject is required");
					   return false;
					}
					
					if(!$('[name=detail]').val())
					{
					   $('.dialog-message-box').css('color', 'red');
					   $('.dialog-message-box').html("Detail is required");
					   return false;
					}

               saveDetail();

               $('.dialog-message-box').css('color', 'green');
               $('.dialog-message-box').html("Deatils has been saved successfully.");
               $('[name=status]').val('');
               $('[name=subject]').val('');
               $('[name=detail]').val('');
               CUR_TICKET_ID = 0;
               if(RELOAD_CURRENT_URL)
               {
                  location.href = location.href;
               }
               
				},
				Cancel: function() {
               $('.dialog-message-box').css('color', 'black');
               $('.dialog-message-box').html("<span class='required-star' style='color:red;'>*</span> fields are required.");
					$(this).dialog('close');
				}
			},
			close: function() {
				//allFields.val('').removeClass('ui-state-error');
			}
		});
		
		
		
		$('#add-detail')
			.click(function() {
				$('#dialog-form').dialog('open');
			});

      $('.required-star').css('color', '#ff0000');
      
      

		
		
	});
	
	
	function showDetailDialog(ticket_id, isReloadCurrentURL)
	{
	   CUR_TICKET_ID = ticket_id;
	   RELOAD_CURRENT_URL = isReloadCurrentURL;
	   $('#dialog-form').dialog('open');
	}
	
	function showTagDialog(ticket_id, isReloadCurrentURL)
	{
	   CUR_TICKET_ID = ticket_id;
	   RELOAD_CURRENT_URL = isReloadCurrentURL;
	   $('#dialog-tag').dialog('open');
	}
	
	function saveDetail()
	{
      var _url = CONTROLLER_URL + "Ticket/add_detail";
      var _data = "ticket_id=" + CUR_TICKET_ID + "&status=" + $('[name=status]').val() + "&subject=" + $('[name=subject]').val() + "&notes=" + $('[name=detail]').val();

      $.ajax({
         type: 'post',
         url: _url,
         data: _data,
         dataType: 'json',
         async: false,
         success: function (json)
         {
            //$('.dialog-message-box').html("["+json.message+"]");
            //if(!json.isError)
            //{
            //   $('[name=status]').val('');
            //   $('[name=subject]').val('');
            //   $('[name=detail]').val('');
            //}
         }
      });
	}