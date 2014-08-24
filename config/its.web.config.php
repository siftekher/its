<?php

  define('PRODUCTION_MODE', false);
  
  $dbInfo = array();
  if(PRODUCTION_MODE)
  {
     $dbInfo['db']   = 'its';
     $dbInfo['user'] = 'root';
     $dbInfo['pass'] = '';
     $dbInfo['host'] = '';  
     
     define('SITE_URL',    '');
     define('SMTP_HOST',   '');
     
  }
  else
  {
     $dbInfo['db']   = 'its';
     $dbInfo['user'] = 'root';
     $dbInfo['pass'] = '';
     $dbInfo['host'] = 'localhost';
     
     define('SITE_URL',    'localhost/its/');
     define('SMTP_HOST',   '');
  }


define('VIEWS_DIR',          DOCUMENT_ROOT . '/its/views/');
define('COMMON_DIR',         VIEWS_DIR  . 'common/');
define('HEADER_TEMPLATE',    COMMON_DIR . 'header.html');
define('FOOTER_TEMPLATE',    COMMON_DIR . 'footer.html');
define('LEFT_MENU_TEMPLATE', COMMON_DIR . 'leftnav.html');
define('TOP_MENU_TEMPLATE',  COMMON_DIR . 'topnav.html');
define('SCREEN_TEMPLATE',    COMMON_DIR . 'home.html');
define('HOME_TEMPLATE',      COMMON_DIR . 'home.html');
define('UPDATE_TEMPLATE',    COMMON_DIR . 'reply_update_template.html');

# Templates for ticket report tool
define('REPORT_TEMPLATE',  VIEWS_DIR . 'ticket_reports/report.html');

# Templates for new ticket editor
define('NEW_TICKET_EDITOR', VIEWS_DIR.'new_ticket/new_ticket_editor.html');

# Templates for tag tool
define('ADD_TAG_TEMPLATE', VIEWS_DIR.'tag/add_tag_form.html');
define('TAG_LIST_TEMPLATE', VIEWS_DIR.'tag/tag_list.html');

# Templates for usermanager tool
define('ADD_USER_TEMPLATE', VIEWS_DIR.'usermanager/add_user_form.html');
define('USER_LIST_TEMPLATE', VIEWS_DIR.'usermanager/user_list.html');

# Templates for sourcemanager tool
define('ADD_SOURCE_TEMPLATE', VIEWS_DIR.'sourcemanager/add_source_form.html');
define('SOURCE_LIST_TEMPLATE', VIEWS_DIR.'sourcemanager/source_list.html');

# Templates for Ticket tool
define('TICKET_SUMMARY_TEMPLATE', DOCUMENT_ROOT.'/its/views/ticket/ticket.html');
define('TICKET_LIST_TEMPLATE', DOCUMENT_ROOT.'/its/views/ticket/ticket_list.html');
define('TICKET_DETAIL_TEMPLATE', DOCUMENT_ROOT.'/its/views/ticket/ticket_detail.html');
define('TICKET_DETAIL_DIALOG_TEMPLATE', DOCUMENT_ROOT.'/its/views/ticket/add_detail.dialog.html');
define('TICKET_TAG_DIALOG_TEMPLATE', DOCUMENT_ROOT.'/its/views/ticket/add_tag.dialog.html');
define('MY_TICKET_LIST_TEMPLATE', DOCUMENT_ROOT.'/its/views/mytickets/my_ticket_list.html');
define('MY_ASSIGNMENT_LIST_TEMPLATE', DOCUMENT_ROOT.'/its/views/mytickets/my_assignment_list.html');
define('MY_PROJECT_TICKET_LIST_TEMPLATE', DOCUMENT_ROOT.'/its/views/mytickets/my_project_ticket_list.html');
define('EMPTY_TICKET_TEMPLATE', DOCUMENT_ROOT.'/its/views/mytickets/empty_ticket.html');
define('PRINT_TICKET_DETAIL_TEMPLATE', DOCUMENT_ROOT.'/its/views/ticket/print_ticket_details.html');
define('TICKET_MERGE_DIALOG_TEMPLATE', DOCUMENT_ROOT.'/its/views/ticket/add_merge.dialog.html');
define('ACTIVITY_LOG_DETAIL_TEMPLATE', DOCUMENT_ROOT.'/its/views/ticket/activity_log_report.html');

# Templates for Login tool
define('LOGIN_TEMPLATE', DOCUMENT_ROOT.'/its/views/login/login.html');

# Templates for change settings tool
define('TICKET_USER_SETTINGS_TEMPLATE', DOCUMENT_ROOT.'/its/views/settings/change_settings.html');

# Templates for Rss tool
define('TICKET_RSS_TEMPLATE', DOCUMENT_ROOT.'/its/views/rss/rss.xml');
 
# Messages for Login tool
define('LOGIN_MSG', 'Email or password is incorrect');

# Messages for change settings tool
define('TICKET_USER_SETTINGS_SAVE_MSG', "Ticket user settings has been saved successfully");

# Messages for add new ticket tool
define('TICKET_SAVE_MSG', "Ticket has been added successfully.");
define('TICKET_SAVE_FAIL_MESSAGE', "Ticket is not added.");


# Messages for tag tool
define('ADD_TAG_MESSAGE', 'You have added one tag.');
define('ADD_TAG_FAIL_MESSAGE', 'Your tag is not saved.');
define('UPDATE_TAG_MESSAGE', 'You have updated the tag.');
define('UPDATE_TAG_FAIL_MESSAGE', 'Your update is not ok.');
define('DELETE_TAG_MESSAGE', 'You have deleted the tag.');
define('DELETE_TAG_FAIL_MESSAGE', 'Your tag deletion is not ok.');
define('ENTER_TAG_MESSAGE', 'Please enter a tag.');

# Messages for usermanager tool
define('ADD_USER_MESSAGE', 'You have added one user.');
define('ADD_USER_FAIL_MESSAGE', 'User is not created.');
define('UPDATE_USER_MESSAGE', 'You have updated user information.');
define('UPDATE_USER_FAIL_MESSAGE', 'Your update is not ok.');
define('DELETE_USER_MESSAGE', 'You have deleted the user.');
define('DELETE_USER_FAIL_MESSAGE', 'Your user deletion is not ok.');
define('DUPLICATE_RELATION_MESSAGE', 'You have duplication in user and source relation.');
define('NO_RELATION_MESSAGE', 'Please keep at least one relation between user and source.');


#Message for ticket
define('MERGE_ERROR', 'Please enter a valid ticket number of your source project.');


# Messages for usermanager tool
define('ADD_SOURCE_MESSAGE', 'You have added one source.');
define('ADD_SOURCE_FAIL_MESSAGE', 'Source is not created.');
define('UPDATE_SOURCE_MESSAGE', 'You have updated source information.');
define('UPDATE_SOURCE_FAIL_MESSAGE', 'Your update is not ok.');
define('DELETE_SOURCE_MESSAGE', 'You have deleted the source.');
define('DELETE_SOURCE_FAIL_MESSAGE', 'Your source deletion is not ok.');

# Messages for usermanager tool
define('EMPTY_TICKET_MESSAGE', 'You did not create any ticket yet.');
define('SOURCE_EMPTY_TICKET_MESSAGE', 'Currently no tickets available for this source.');
define('ASSIGNED_EMPTY_TICKET_MESSAGE', 'Currently you are not assigned to any ticket.');

# Database tables
define('USERS_TBL',                    'users');
define('TAGS_TBL',                     'tags');
define('TICKETS_TAG_TBL',              'ticket_tags');
define('SOURCE_SETTINGS_TBL',          'source_settings');
define('AUTHORIZED_SOURCES_TBL',       'authorized_sources');
define('SOURCE_RESOLVERS_TBL',         'source_resolvers');
define('TICKETS_TBL',                  'tickets');
define('TICKET_AUTH_KEYS_TBL',         'ticket_auth_keys');
define('TICKETS_DETAILS_TBL',          'ticket_details');
define('TICKET_USER_SETTINGS_TBL',     'ticket_user_settings');
define('TICKET_SOURCES_TBL',           'ticket_sources');
define('TICKET_ASSIGNMENTS_TBL',       'ticket_assignments');
define('TICKET_ATTACHMENTS_TBL',       'ticket_attachments');
define('TICKET_HISTORY_TBL',           'ticket_history');
define('TICKET_COLOR_SETTINGS_TBL',    'ticket_color_settings');
define('TICKET_RATING_TBL',            'ticket_rating');
define('TICKET_RATING_SUMMARY_TBL',    'ticket_rating_summary');
define('ACTIVITY_LOG_TBL',             'activity_log');


define('TICKET_AUTH_KEY', 'ticket_auth_keys');

define('EMAIL_PRIORITY',              3);
define('EMAIL_FROM',                  '');
define('EMAIL_FROM_NAME',             '');
define('EMAIL_SENDER',                '');
define('EMAIL_REPLAY',                '');
define('CHAR_SET',                    'utf-8');
define('SMTP_FLAG',                   'true');


define('MOST_TAGS_LIMIT',              '10');

# General constants
define('USER_STATUS_ACTIVE',           1);
define('ATTACHMENT_DIR',               dirname(DOCUMENT_ROOT) . 
                                       '/its_attachments');
# Report Type
define('CURRENT_MONTH',                'This Month');    
define('LAST_MONTH',                   'Last Month');
define('CURRENT_YEAR',                 'This Year');   
define('CURRENT_WEEK',                 'This Week'); 
define('RECENT',                       'Recent Tickets');

# Chart Type
define('TICKETS',   'tickets');    
define('PROJECTS',  'projects');
 

$GLOBALS['TICKET_STATUS_TYPE'] = array(
   0  => 'opened',
   1  => 'assigned',
   2  => 'in progress',
   4  => 'cannot duplicate',
   5  => 'clarification required',
   6  => 'completed',
   7  => 'closed',
   8  => 'reopened',
   9  => 'deleted',
   10 => 'archived'
);

$GLOBALS['SOURCE_RESOLVERS_TYPE'] = array(
   1  => 'staff',
   2  => 'supervisor',
   3  => 'executive'
);

$GLOBALS['EXECUTIVE_COMPLAINT_TYPE'] = array(
   0  => 'no',
   1  => 'yes'
);

$GLOBALS['TICKET_STATUS_CHANGE_METHOD'] = array(
   0  => 'email',
   1  => 'web'
);

$GLOBALS['TICKET_COLOR_SETTINGS'] = array(
    'critical' => 'red',   
    'high'     => 'orange',
    'normal'   => 'blue',  
    'low'      => 'gray'  
);

define('RECENT_TICKET_RANGE', '7');
define('SQL_PAGE_SIZE', 10);
define('MAX_EXCERPT_LENGTH', 100);
define('MESSAGE_BOX_CYAN', DOCUMENT_ROOT.'/its/views/common/message_box.html');

define('TICKET_ASSIGNMENT',               1);
define('TICKET_STATUS_CHANGE',            2);
define('TICKET_STATUS_DELETE',            3);
define('TICKET_STATUS_CLOSE',             4);
define('TICKET_STATUS_CLARIFICATION',     5);
define('TICKET_STATUS_COMPLETED',         6);
define('USER_LOGGING',                    7);
define('USER_LOGOUT',                     8);
?>