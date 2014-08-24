
var Pagination =
{
   ACTION_URL    : '',
   ERROR         : 0,
   TOTALROWS     : 0,
   ROWSPERPAGE   : 10,
   HALFCHUNKSIZE : 1,
   CURRENTPAGE   : 1,
   START         : 0,
   TOP_NAV       : '',
   BOTTOM_NAV    : '',
   
   init : function(options){
      this.ACTION_URL   = options.action_url;
      this.ROWSPERPAGE  = options.page_size;
      this.TOTALROWS    = options.total_rows;
      this.TOP_NAV      = options.top_nav;
      this.BOTTOM_NAV   = options.bottom_nav;

      this.getData({
         'page' : 0
      });
      
      this.createPagingBar();
   },
   
   createPagingBar : function() {
	    var pages = Math.ceil(this.TOTALROWS/this.ROWSPERPAGE);
	    if(pages<2){
	       document.getElementById(this.TOP_NAV).style.display = 'none';
	       document.getElementById(this.BOTTOM_NAV).style.display = 'none';
	       return;
	    }
	    var halfChunk  = this.HALFCHUNKSIZE;
	    var elementIds = Array();
	    var bottom_elementIds = Array();


       var _first = '<span>First</span>';
       if(this.CURRENTPAGE > 1)
       {
         _first = '<span class="normal-page" in="page_1" onclick="Pagination.showPage(1)"><a href="#">First</a></span>';
       }

	    var pager = '<span class="selected-page" id="page_'+this.CURRENTPAGE+'" style="border:1px solid #bbbbbb; padding:0px 3px 0px 3px">'+this.CURRENTPAGE+'</span>';
	    var bottom_pager = '<span class="selected-page" id="bottom_page_'+this.CURRENTPAGE+'" style="border:1px solid #bbbbbb; padding:0px 3px 0px 3px">'+this.CURRENTPAGE+'</span>';
	    
	    elementIds.push("page_"+this.CURRENTPAGE);
	    bottom_elementIds.push("bottom_page_"+this.CURRENTPAGE);

	    for(var pageNum = this.CURRENTPAGE-1; pageNum> 0 && halfChunk > 0; pageNum--, halfChunk--) {
    	    pager = '<span class="normal-page" id="page_'+pageNum+'" style="border:1px solid #bbbbbb; padding:0px 3px 0px 3px"><a href="#">'+pageNum+'</a></span>&nbsp;&nbsp;'+pager;
    	    bottom_pager = '<span class="normal-page" id="bottom_page_'+pageNum+'" style="border:1px solid #bbbbbb; padding:0px 3px 0px 3px"><a href="#">'+pageNum+'</a></span>&nbsp;&nbsp;'+bottom_pager;
    	    
    	    elementIds.push("page_"+pageNum);
    	    bottom_elementIds.push("bottom_page_"+pageNum);
       }
       var halfChunk  = this.HALFCHUNKSIZE;
       var currentPage = parseInt(this.CURRENTPAGE, 10) +1;
       for(var pageNum = currentPage; pageNum<=pages&&halfChunk>0;pageNum++,halfChunk--) {
	       pager += '&nbsp;&nbsp;<span class="normal-page" id="page_'+pageNum+'" style="border:1px solid #bbbbbb; padding:0px 3px 0px 3px"><a href="#">'+pageNum+'</a></span>';
	       bottom_pager += '&nbsp;&nbsp;<span class="normal-page" id="bottom_page_'+pageNum+'" style="border:1px solid #bbbbbb; padding:0px 3px 0px 3px"><a href="#">'+pageNum+'</a></span>';
	       
	       elementIds.push("page_"+pageNum);
	       bottom_elementIds.push("bottom_page_"+pageNum);
       }

       var _last = '<span class="normal-page" in="page_'+pages+'" onclick="Pagination.showPage('+pages+')"><a href="#">Last</a></span>';
       if(this.CURRENTPAGE == pages)
       {
         _last = "Last";
       }

       var bar = "Total Found ("+this.TOTALROWS+")   :  " + _first + "&nbsp;&nbsp;" + pager + "&nbsp;&nbsp;" + _last;
       var bottom_bar = "Total Found ("+this.TOTALROWS+")   :  " + _first + "&nbsp;&nbsp;" + bottom_pager + "&nbsp;&nbsp;" + _last;
       
	    document.getElementById(this.TOP_NAV).innerHTML = bar;
	    document.getElementById(this.BOTTOM_NAV).innerHTML = bottom_bar;

       for(i=0; i<elementIds.length; i++)  {
         $('span#'+elementIds[i]).click(function(){
            Pagination.showPage($(this).text());
         });
       }
       
       for(i=0; i<bottom_elementIds.length; i++)  {
         $('span#'+bottom_elementIds[i]).click(function(){
            Pagination.showPage($(this).text());
         });
       }
   },
   
   showPage : function(pageNumber) {
      if(this.CURRENTPAGE == pageNumber) {
          return;
      }

      this.CURRENTPAGE = pageNumber;
      this.START       = parseInt((pageNumber*this.ROWSPERPAGE - this.ROWSPERPAGE) , 10);

      this.getData({
         'page' : this.START
      });
      
      this.createPagingBar();
   },
   
   
   getData : function(_params){
      var _url = this.ACTION_URL + '/' + _params.page;
      
      var _data = "";

      $.ajax({
         type: 'post',
         url: _url,
         data: _data,
         async: false,
         success: function(response)
         {
            if(response.length)
            {
               $('#pagging_content').html(response);
               itsButtonsSetupJsHover("its-button");
            }
            else
            {
               $('#pagging_content').html(getMessageBox('No Result found'));
            }
         }
      });
   }
   

};



function getMessageBox(_message)
{
   var _message_box_cyan_message_text = $('#message_box_cyan_text').html(_message);
   var _message_box_cyan = $('#message_box_cyan').html();
   
   return _message_box_cyan;
}