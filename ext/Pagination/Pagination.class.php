<?php
/**
* Filename   : Pagination.class.php
* Purpose    : 
* @author    : ripon@evoknow.com
* @project   : Parklane
* @version   : 1.0.0
* @copyright : www.evoknow.com
*/

class Pagination
{
	private $result;
	private $anchors;
	private $total;
	private $param;
	private $action;
	private $recordPerPage;
	private $linksPerPage;
	
   /**
   * Initiate pagination object
   *
   * @access public
   * @param $db - database object - instance of central DB.class.php
   * @param $qry - string - sql query to select records
   * @param $starting - int - starting index to select records
   * @param $recordPerPage - int - number of records for per page
   * $param $linksPerPage - int - number of links to display in the pagination bar
   * @param $action - string - script url
   * @param $param - array - parameters for quesry string
   * @return none
   */
	public function Pagination($db, 
	                           $qry, 
	                           $starting = 0, 
	                           $recordPerPage = 10, 
	                           $linksPerPage = 5, 
	                           $action = null, 
	                           $param = array())
	{
	   $this->param = $param;
	   $this->action = $action;
	   
		$rst = $db->select($qry);
		$numrows	=	count($rst);
		$qry	  .=	" limit $starting, $recordPerPage";
		
      try
      {
         $this->result = $db->select($qry);
      }
      catch(Exception $Exception){}

		$next		=	$starting+$recordPerPage;
		$var		=	((intval($numrows/$recordPerPage))-1)*$recordPerPage;
		$page_showing	=	intval($starting/$recordPerPage)+1;
		$total_page	=	ceil($numrows/$recordPerPage);

		if($numrows % $recordPerPage != 0)
		{
			$last = ((intval($numrows/$recordPerPage)))*$recordPerPage;
		}
		else
		{
			$last = ((intval($numrows/$recordPerPage))-1)*$recordPerPage;
		}
		
		$previous = $starting-$recordPerPage;
		
		$anc = "<ul id='pagination-flickr'>";
		if($previous < 0)
		{
			$anc .= "<li class='previous-off'>First</li>";
			$anc .= "<li class='previous-off'>Previous</li>";
		}
		else
		{
			$anc .= "<li class='next'><a href='".$this->getURL()."/0'>First </a></li>";
			$anc .= "<li class='next'><a href='".$this->getURL()."/$previous'>Previous </a></li>";
		}
		
		//no of pages showing in the left and right side of the current page in the anchors
		$norepeat = $linksPerPage; 
		$j = 1;
		$anch = "";
		for($i=$page_showing; $i>1; $i--)
		{
			$fpreviousPage = $i-1;
			$page = ceil($fpreviousPage*$recordPerPage)-$recordPerPage;
			$anch = "<li><a href='".$this->getURL()."/$page'>$fpreviousPage </a></li>".$anch;
			if($j == $norepeat) break;
			$j++;
		}
		$anc .= $anch;
		$anc .= "<li class='active'>".$page_showing."</li>";
		$j = 1;
		for($i=$page_showing; $i<$total_page; $i++)
		{
			$fnextPage = $i+1;
			$page = ceil($fnextPage*$recordPerPage)-$recordPerPage;
			$anc .= "<li><a href='".$this->getURL()."/$page'>$fnextPage</a></li>";
			if($j==$norepeat) break;
			$j++;
		}
		
		if($next >= $numrows)
		{
			$anc .= "<li class='previous-off'>Next</li>";
			$anc .= "<li class='previous-off'>Last</li>";
		}
		else
		{
			$anc .= "<li class='next'><a href='".$this->getURL()."/$next'>Next</a></li>";
			$anc .= "<li class='next'><a href='".$this->getURL()."/$last'>Last</a></li>";
		}
			$anc .= "</ul>";
		$this->anchors = $anc;
		
		$this->total = "$numrows";
	}
	
	/**
   * get pagination link
   * @access public
   * @param none
   * @return link
   */
	public function getPaginationLinks()
	{
	   return $this->anchors;
	}
   
   /**
   * get number of record
   * @access public
   * @param none
   * @return total record number
   */
	public function getNumberOfRecord()
	{
	   return $this->total;
	}
	
	/**
   * get result
   * @access public
   * @param none
   * @return result
   */
	public function getResult()
	{
	   return $this->result;
	}
	
	/**
   * add key, value to the param array
   * @access public
   * @param none
   * @return none
   */
	public function addParam($key, $value)
	{
	   $this->param[$key] = $value;
	}
	
	/**
   * get the query string from param
   * @access public
   * @param none
   * @return query string
   */
	public function getQueryString()
	{
	   $param = array();
	   foreach($this->param as $key => $value)
	   {
	      $param[] = "$key=$value";   
	   }
	   
	   return implode("&", $param);
	}
	
	/**
   * get URL
   * @access public
   * @param none
   * @return url
   */
	public function getURL()
	{
	   //return $this->action . "?" . $this->getQueryString();
	   return $this->action;
	}
}
?>