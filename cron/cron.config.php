<?php
/**
* Filename: cron.config.php
* Purpose : Common configuration for cron jobs
*/

define('PRODUCTION_MODE', false);

if(PRODUCTION_MODE)
{
   define('CRON_ROOT',                 '');
   define('DOCUMENT_ROOT',             '');
   
   define('DB_HOST',                   '');
   define('DB_USER',                   'root');
   define('DB_PASS',                   '');
   define('DB_NAME',                   'its');
   define('SITE_URL',                  '');
   define('SMTP_HOST',                 '');
}
else
{
   define('CRON_ROOT',                 '');
   define('DOCUMENT_ROOT',             '');

   define('DB_HOST',                   'localhost');
   define('DB_USER',                   'root');
   define('DB_PASS',                   '');
   define('DB_NAME',                   'its');
   define('SITE_URL',                  '');
   define('SMTP_HOST',                 '');
}
define('CRON_CLASS_DIR',            CRON_ROOT . '/classes');
define('CLASS_DIR',                 DOCUMENT_ROOT . '/its/classes');
$_SERVER['DOCUMENT_ROOT']           = DOCUMENT_ROOT;
define('TEMP_DIR',                  DOCUMENT_ROOT . '/temp');
define('ATTACHMENT_DIR',            '/data/web/its/its_attachments');

set_include_path(get_include_path() . PATH_SEPARATOR . CLASS_DIR);

define('COMMON_TEMPLATE_DIR',         DOCUMENT_ROOT . '/its/views/common');
define('SYSTEM_VIEW_COMMON_DIR',      '/its/views/common');
define('SYSTEM_TEMPLATE_COMPILE_DIR', TEMP_DIR);
define('EXT_DIR',                     DOCUMENT_ROOT . '/ext/');
require_once(EXT_DIR                  . '/smarty/libs/Smarty.class.php');

define('LAST_RUN',                    'last_run');
define('USERS_TBL',                   'users');
define('TAGS_TBL',                    'tags');
define('TICKETS_TAG_TBL',             'ticket_tags');
define('SOURCE_SETTINGS_TBL',         'source_settings');
define('AUTHORIZED_SOURCES_TBL',      'authorized_sources');
define('SOURCE_RESOLVERS_TBL',        'source_resolvers');
define('TICKETS_TBL',                 'tickets');
define('TICKET_AUTH_KEY',             'ticket_auth_keys');
define('TICKETS_DETAILS_TBL',         'ticket_details');
define('TICKET_USER_SETTINGS_TBL',    'ticket_user_settings');
define('TICKET_SOURCES_TBL',          'ticket_sources');
define('TICKET_ASSIGNMENTS_TBL',      'ticket_assignments');
define('TICKET_ATTACHMENTS_TBL',      'ticket_attachments');
define('EMAIL_LOGS',                  'email_logs');
define('TICKET_HISTORY_TBL',          'ticket_history');

define('KNOWN_SOURCE_EMAIL_SUBJECT',  'Reply From ITS');
define('NOTIFICATION_EMAIL_SUBJECT',  'Notification From ITS');
define('EMAIL_REPLY_TEMPLATE',        COMMON_TEMPLATE_DIR . '/reply_template.html');
define('EMAIL_REPLY_LIST_TEMPLATE',   COMMON_TEMPLATE_DIR . '/reply_list_template.html');
define('TICKET_REMINDER_TEMPLATE' ,   COMMON_TEMPLATE_DIR . '/ticket_reminder_template.html');

define('EMAIL_PRIORITY',              3);
define('EMAIL_FROM',                  '');
define('EMAIL_FROM_NAME',             'Administrator');
define('EMAIL_SENDER',                '');
define('EMAIL_REPLAY',                '');
define('CHAR_SET',                    'utf-8');
define('SMTP_FLAG',                   'true');

define('MAILSERVER'    ,              '');
define('SERVERTYPE'    ,              'imap'); // Server type imap/pop3
define('SERVERPORT'    ,              143);    // port no: By Default 143 for imap, 110 for pop3
define('FOLDER_PREFIX' ,              '/imap/novalidate-cert/user=');

define('FLAG_EXCLUDE_ATTACHMENT',     true);
define('MSG_HEADER_DATE',             'date');
define('MSG_HEADER_SUBJECT',          'subject');
define('MSG_HEADER_TO',               'to');
define('MSG_HEADER_REPLY_TO',         'reply_to');
define('MSG_HEADER_FROM',             'from');
define('MSG_HEADER_BCC',              'bcc');   
define('MSG_HEADER_CC',               'cc');
define('MSG_HEADER_CONTENT_TYPE',     'Content-Type:');

define('UNKNOWN_SOURCE_EMAIL_REPLY_TEMPLATE',   COMMON_TEMPLATE_DIR . '/unknown_reply_template.html');
define('UNKNOWN_SOURCE_EMAIL_SUBJECT',          'unauthorized email reply');

define('OPEN_STATUS',               0);
define('IN_PROGRESS_STATUS',        2);
define('COMPLETED_STATUS',          6);
define('OTHER_STATUS',              12);

//column type add in ticket_details, suggetsted by mashuk
define('TICKET_DETAILS_TYPE',       '1');


?>
