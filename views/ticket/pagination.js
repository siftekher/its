
$(document).ready(function() {
	Pagination.init({
	   'action_url'  : CONTROLLER_URL + PAGGER_ACTION_URL,
	   'total_rows'  : TOTAL_SQL_RECORD,
	   'page_size'   : SQL_PAZE_SIZE,
	   'top_nav'     : 'top_pagging_bar',
	   'bottom_nav'  : 'bottom_pagging_bar'
	});
});