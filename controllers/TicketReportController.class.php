<?php
/**
* Filename   : TicketReportContoller.class.php
* Purpose    : It is used to show ITS ticket report.
*
* @author    : Sheikh Iftekhar <siftekher@gmail.com>
* @project   : Issue tracking system(ITS)
* @version   : 1.0.0
*/

class TicketReport
{
   private $dbLink;
   private $templateObj;
   private $param;
   private $wkArr;
   private $reportType;

   function __construct($params)
   {
      $this->templateObj = new Template();
      $this->dbLink      = $params['db_link'];
      $this->param       = $params['cmdList'];
   }

   function run()
   {
      if(($_SESSION['LOGIN_USER']['userId']) == null)
      {
         header('location: '.SUPER_CONTROLLER_URL_PREFIX.'Login');
      }

      $cmd = $this->param[1];

      switch($cmd)
      {
         case 'recent'      : $screen = $this->getWeeklyRecentReport();     break;
         case 'this_week'   : $screen = $this->getWeeklyRecentReport(true); break;
         case 'this_month'  : $screen = $this->getMonthlyReport();          break;
         case 'last_month'  : $screen = $this->getMonthlyReport(true);      break;
         default            : $screen = $this->getYearlyReport();
      }

      $data            = array();
      $data['topnav']  = 'ticket';
      $data['tagList'] = Utils::getAllTagList($this->dbLink);
      
      $userList = new UserList($this->dbLink);
      $data['sourceUser']  = $userList->getUsersFromMySources();
      $data['source_id']   = $_SESSION['source_id'];
      $data['source_name'] = Utils::getSourceName($this->dbLink, $_SESSION['source_id']);
      echo $this->templateObj->createScreen($screen, $data);
      exit;
   }

   /**
    * function - getYearlyReport - get Yearly Report
    * @param - None
    * @Return - string - html content output
   **/
   
   private function getYearlyReport()
   {
      $data['current_month']        = date('M Y');
      $data['report_header']        = $this->getMonthArray();
      $data['report_type']          = $this->reportType = CURRENT_YEAR;
      $data['resolver_label']       = date('M Y');
      $data['resolver_report_data'] = $this->getMonthlyResolverReport();
      $data['ticket_report_data']   = $this->getYearlyTicketProjectsReport(TICKETS);
      $data['noisy_projects_data']  = $this->getYearlyTicketProjectsReport(PROJECTS);

      return $this->templateObj->parseTemplate(REPORT_TEMPLATE, $data);
   }

   /**
    * function - getMonthlyReport - get current/last month report
    * @param   - $opt - boolean - true/false
    * @Return  - string - html content output
   **/

   function getMonthlyReport($opt=null)
   {
      $this->reportType = "Monthly";
      if(empty($opt))
      {
         $day                          = strtotime(date("m/d/y"));
         $params['start_date']         = date('Y-m-d', $this->getStartDateOfWeek());
         $params['end_date']           = date('Y-m-d', $this->getEndDateOfWeek());
         $data['report_type']          = CURRENT_MONTH;
         $data['resolver_label']       = CURRENT_WEEK;
         $data['resolver_report_data'] = $this->getWeeklyResolverReport($params);
      }
      else
      {
         $day                          = mktime(0, 0, 0, date("m")-1, date("d"), date("Y"));
         $data['report_type']          = LAST_MONTH;
         $data['resolver_label']       = date("M Y",$day);
         $data['resolver_report_data'] = $this->getMonthlyResolverReport();
      }

      $timestamp     = strtotime(date('m',$day).'/01/'.date('Y',$day).' 00:00:00');
      $startWeek     = date("W",$timestamp);
      $startDay      = date("Y-m-d", $timestamp);
      $endDay        = date("Y-m-d",
                       strtotime('-1 second',strtotime('+1 month',$timestamp)));
      $betweenClause = " BETWEEN '$startDay' AND '$endDay' ";

      $data['ticket_report_data']  = $this->getMonthlyTicketProjectsReport(
                                           $betweenClause,$startWeek,TICKETS);
      $data['noisy_projects_data'] = $this->getMonthlyTicketProjectsReport(
                                           $betweenClause,$startWeek,PROJECTS);
      $data['report_header']       = $this->wkArr;

      return $this->templateObj->parseTemplate(REPORT_TEMPLATE, $data);
   }

   /**
    * function - getWeeklyRecentReport - get weekly/recent report
    * @param   - $opt - boolean - true/false
    * @Return - string - html content output
   **/
   function getWeeklyRecentReport($opt=null)
   {
      if(!empty($opt))
      {
         # this week
         $data['resolver_label']= CURRENT_WEEK;
         $params['header_type'] = CURRENT_WEEK;
         $params['start_date']  = date('Y-m-d', $this->getStartDateOfWeek());
         $params['end_date']    = date('Y-m-d', $this->getEndDateOfWeek());
         $params['day_arr']     = $this->getDayArray($params['header_type']);
      }
      else
      {
         # recent ticket - depend on "RECENT_TICKET_RANGE".
         $data['resolver_label']= RECENT;
         $params['header_type'] = RECENT;
         $params['start_date']  = date("Y-m-d", mktime(0, 0, 0, date("m"),
                                  date("d")- RECENT_TICKET_RANGE, date("Y")));
         $params['end_date']    = date("Y-m-d", mktime(0, 0, 0, date("m"),
                                  date("d"), date("Y")));
         $params['day_arr']     = $this->getDayArray($params['header_type']);
      }
      
      $data['report_header']        = $params['day_arr'];
      $data['report_type']          = $this->reportType = $params['header_type'];
      $data['resolver_report_data'] = $this->getWeeklyResolverReport($params);
      $data['ticket_report_data']   = $this->getWeeklyTicketsProjectsReport($params,TICKETS);
      $data['noisy_projects_data']  = $this->getWeeklyTicketsProjectsReport($params,PROJECTS);

      return $this->templateObj->parseTemplate(REPORT_TEMPLATE, $data);
   }

   /**
    * function - getMonthlyTicketProjectsReport - get Monthly tickets/projects Report data
    * @param   - $betweenClause - str - date range
    * @param   - $startWeek - str - start week on month base on year
    * @param   - $cType - str - chart type, tickets/projects
    * @Return  - array - array of data
   **/
   function getMonthlyTicketProjectsReport($betweenClause,$startWeek, $cType)
   {
       $weekArray    = array();
       # get raw data from DB.
       $rawData      = ($cType == TICKETS) ?
                        $this->getMonthlyTicketRawData($betweenClause)
                        : $this->getMonthlyNoisyProjectsRawData($betweenClause);
       if($rawData)
       {
          foreach($rawData as $value)
          {
             $arrayKey     = ($cType == TICKETS) ?
                              ucfirst($GLOBALS['TICKET_STATUS_TYPE'][$value->status])
                              : $value->name;
             $weekArray[]  = $value->week_num;
          
             $data[$arrayKey][$value->week_num] = $value->ticket_num;
          
             # year total number of ticket base on key(status of tickets/projects name)
             $total[$arrayKey]         = (isset($total[$arrayKey]) ?
                                          $total[$arrayKey] + $value->ticket_num
                                          : 0 + $value->ticket_num);
          
             # total number of ticket base on week
             $wtotal[$value->week_num] = (isset( $wtotal[$value->week_num]) ?
                                         $wtotal[$value->week_num] + $value->ticket_num
                                         : 0 + $value->ticket_num);
          }
       }

       # get week array (like week1,week2.....)
       $this->wkArr = $this->getWeekArray($startWeek,$weekArray);

       return $this->populateChartArray($data,$total,$wtotal,$this->wkArr);
   }

   /*
    * function - getYearlyTicketProjectsReport - get Yearly Ticket/Project Report data
    * @param   - $cType - str - chart type, tickets/projects
    * @Return - array - array of data.
   */
   private function getYearlyTicketProjectsReport($cType)
   {
      $rawData      = ($cType == TICKETS) ?
                          $this->getYearlyTicketRawData()
                          : $this->getYearlyNoisyProjectsRawData();
      if(count($rawData))
      {
         # raw array from database
         foreach($rawData as $value)
         {
            # get ticket status
            $arrayKey  = ($cType == TICKETS) ?
                          ucfirst($GLOBALS['TICKET_STATUS_TYPE'][$value->status])
                          : $value->name;

            $data[$arrayKey][$value->month] = $value->ticket_num;

            # year total number of ticket base on key (status of tickets/projects name)
            $total[$arrayKey] = (isset($total[$arrayKey]) ?
                                 $total[$arrayKey] + $value->ticket_num
                                 : 0 + $value->ticket_num);

            # total number of ticket base on month
            $mtotal[$value->month] = (isset( $mtotal[$value->month]) ?
                                      $mtotal[$value->month] + $value->ticket_num
                                      : 0 + $value->ticket_num);
         }
         $monthArr = $this->getMonthArray();

         # populate chart data array
         $ticketReportData = $this->populateChartArray($data,$total,$mtotal,$monthArr);
      }
      return $ticketReportData;
   }

   /*
    * function - getWeeklyTicketsProjectsReport - get Weekly Ticket/project Report data
    * @param   - $params - array - array of parameters
    * @param   - $cType - str - chart type, tickets/projects
    * @Return  - array - array of ticket data.
   **/
   private function getWeeklyTicketsProjectsReport($params,$cType)
   {
      $rawData = ($cType == TICKETS) ?
                  $this->getWeeklyRawTicketData($params)
                  : $this->getWeeklyRawNoisyProjectsData($params);

      if(count($rawData))
      {
          # raw array from database
          foreach($rawData as $value)
          {
             # get ticket status/project name base on chart type
             $arrayKey  = ($cType == TICKETS) ?
                            ucfirst($GLOBALS['TICKET_STATUS_TYPE'][$value->status])
                           : $value->name;

             $data[$arrayKey][$value->day] = $value->ticket_num;

             # week total number of ticket base on status
             $total[$arrayKey]      = (isset($total[$arrayKey]) ?
                                       $total[$arrayKey] + $value->ticket_num
                                       : 0 + $value->ticket_num);

             # total number of ticket base on day
             $daytotal[$value->day] = (isset( $daytotal[$value->day]) ?
                                      $daytotal[$value->day] + $value->ticket_num
                                      : 0 + $value->ticket_num);

          }

          # populate chart data array
          $ticketReportData = $this->populateChartArray(
                              $data,$total,$daytotal,$params['day_arr']);
      }

      return $ticketReportData;
   }

   /*
    * function - getMonthlyResolverReport - get monthly resolver Report
    * @param - None
    * @Return - array - array of data.
    */
   private function getMonthlyResolverReport()
   {
      $monthlyTicketData = array();
      $row = $this->getRawMonthlyResolverData();
      if($row)
      {
         foreach($row as $value)
         {
             $name = stripslashes($value->first_name ." ".$value->last_name);
             $temp = array();
             $temp['assigned']   = $value->assigned;
             $temp['completed']  = $value->completed;
             $temp['percantage'] = ($value->completed/$value->assigned)*100;
             $monthlyTicketData[$name] = $temp;
         }
      }

      return $monthlyTicketData;
   }

   /*
    * function - getWeeklyResolverReport - get weekly Ticket Report for resolver
    * @param - $params - array of parameters
    * @Return - array - array of data.
    **/
   private function getWeeklyResolverReport($params)
   {
      $resolverData = array();
      $row = $this->getWeeklyResolverRawData($params);
      if($row)
      {
         foreach($row as $value)
         {
             $name = stripslashes($value->first_name ." ".$value->last_name);
             $temp = array();
             $temp['assigned']    = $value->assigned;
             $temp['completed']   = $value->completed;
             $temp['percantage']  = ($value->completed/$value->assigned)*100;
             $resolverData[$name] = $temp;
         }
      }

      return $resolverData;
   }


   /*
    * function - getMonthArray - get passed month of current year
    * @param - None
    * @Return - array - array of month
    */
   function getMonthArray()
   {
      $currentMonth = date("m");
      for($i=$currentMonth-1;$i>=0; $i--)
      {
         $monthArr[] = date('F', strtotime("-$i month")) ;
      }
      return $monthArr;
   }

   /*
    * function - getAuthoriseSourceIds - get all source_id of current user from session
    * @param - None
    * @Return -$sourceIdStr - string
    */
    function getAuthoriseSourceIds()
    {
         $sourceIdStr = ''; 
         if($_SESSION['LOGIN_USER']['sources'])
         {
            foreach($_SESSION['LOGIN_USER']['sources'] as $key => $source)
            {
               $sourceIdStr = ($key == 0) ?
                             $sourceIdStr.$source->source_id
                             : $sourceIdStr.','.$source->source_id;
            }
         }
         return $sourceIdStr;
    }

   /*
    * function - getWeekArray - get week array of the month base on month
    *                           (like week1, week2....)
    * @param - $startWeek - start week of the month
    * @param - $weekArray - array of week of the month base on year
    * @Return -$sourceIdStr - string
    */
    function getWeekArray($startWeek,$weekArray)
    {
        $endWeek = max($weekArray);
        for($i = $startWeek; $i <= $endWeek; $i++)
        {
           $weekNum = $i - $startWeek + 1;
           $wk[$i] = "Week" . $weekNum;
        }
        return $wk;
    }


 /*
  * function - populateChartArray - populate the array for chart
  * @param - $data   - array - raw data from db
  * @param - $total  - array - base on key of report data.
  * @param - $wtotal - array - total, base on months/week/day
  * @param - $headArr - array - arry of report header
  * @Return -$chartData - array - array for chart
  */
  function populateChartArray($data,$total,$wtotal,$headArr )
  {
     $numOfHead = count($headArr);
     if($data)
     {
        # add the index for 'total'.
        $data["Total"] = '';
        $chartData     = array();
        foreach($data as $key => $value)
        {
           $ticketArray = array();
           foreach($headArr as $k=>$head)
           {
              $headKey = ($this->reportType == 'Monthly') ? $k :$head;
             
              if($key == "Total")
              {
                 # populate row for total
                 $ticketArray[] = (isset($wtotal[$headKey]) ?
                                    $wtotal[$headKey] : 0) ;
              }
              else
              {
                 $ticketArray[] = (isset($data[$key][$headKey]) ?
                                   $data[$key][$headKey] : 0) ;
              }
           }
        
           $chartData[$key]['data']    = $ticketArray;
           $chartData[$key]['total']   = ($key == "Total")
                                         ? array_sum($wtotal):$total[$key];
           $chartData[$key]['average'] = ROUND($chartData[$key]['total']/$numOfHead,2);
        }
     }
     return $chartData;
  }


   /*
    * function - getStartDateOfWeek - find out the start date of current week
    * @param - None
    * @Return -dateStr
    */
   function getStartDateOfWeek()
   {
      $today   = mktime(0,0,0, date('m'), date('d'),  date('Y'));
      $weekDay = date('N');
      $start   = ($weekDay > 0) ? $today - ($weekDay * 24*3600) + 24*3600
                              : $today + 24*3600;
      return $start;

   }

   /*
    * function - getEndDateOfWeek - find out the end date of current week
    * @param - None
    * @Return -dateStr
    */
   function getEndDateOfWeek()
   {
      $start = $this->getStartDateOfWeek();
      /*
        We are adding 1 day at the start of the week
        so we need 6 more days get end of week
       */
      return $start + (6 * 24 * 3600);

   }

   /*
    * function - getDayArray - get passed day of current week
    * @param - $rType - report type
    * @Return - array - array of day
    */
   function getDayArray($rType= null)
   {
      $dayArr = array();
      if($rType == RECENT)
      {
         # get day array of date
         $startDateStr = mktime(0, 0, 0, date("m"), date("d")- RECENT_TICKET_RANGE, date("Y"));
         $endDateStr   = mktime(0, 0, 0, date("m"), date("d"), date("Y"));

         if ($endDateStr >= $startDateStr)
         {
            array_push($dayArr,date('M d',$startDateStr)); // first entry
            while ($startDateStr < $endDateStr)
            {
               $startDateStr+=86400; // add 24 hours
               array_push($dayArr,date('M d',$startDateStr));
            }
         }

      }

      else
      {
         # get day array of week (like Monday, Tuesday, Wednesday)

         $weekArr = array('Monday', 'Tuesday', 'Wednesday',
                       'Thursday', 'Friday', 'Saturday', 'Sunday');

         for($i =0; $i < date("N"); $i++ )
         {
            array_push($dayArr,$weekArr[$i]);
         }
      }
      return $dayArr;
   }

   /*
    * function - getYearlyNoisyProjectsRawData - fetch data from database for noisy project report
    * @param - None
    * @Return -$row - array
    */
   function getYearlyNoisyProjectsRawData()
   {
      $currentYear = date('Y');
      # if user is a authorise source
      if(count($_SESSION['LOGIN_USER']['authorized_sources']) > 0)
      {
         # get source id for this authorise
         $sourceIdStr = $this->getAuthoriseSourceIds();
         $sourceQStr =  "TS.source_id IN($sourceIdStr) and";
      }
      else
      {
         $sourceQStr = "";
      }

      $query = "SELECT T.status,
                MONTHNAME(T.create_date) as month,
                COUNT(T.status) as ticket_num,S.name
                FROM " .TICKETS_TBL. " AS T
                LEFT JOIN " .TICKET_SOURCES_TBL. " as TS ON (T.ticket_id = TS.ticket_id)
                LEFT JOIN " .SOURCE_SETTINGS_TBL . " as S on (TS.source_id = S.source_id)
                WHERE $sourceQStr year(T.create_date) = $currentYear
                GROUP by month(T.create_date), S.source_id";

      $row = $this->dbLink->select($query);
      return $row;
   }

   /*
    * function - getMonthlyNoisyProjectsRawData - fetch data from database
    *                                       for monthly noisy projects report
    * @param - $betweenClause - str - date range
    * @Return -$row - array
    */
   function getMonthlyNoisyProjectsRawData($betweenClause)
   {
        # if user is a authorise source
        if(count($_SESSION['LOGIN_USER']['authorized_sources']) > 0)
        {
           # get source id for this authorise
           $sourceIdStr = $this->getAuthoriseSourceIds();
           $sourceQStr =  "TS.source_id IN($sourceIdStr) and";
        }
        else
        {
           $sourceQStr = "";
        }

        $query = "SELECT WEEKOFYEAR( T.create_date ) AS week_num,
                  COUNT( T.title ) AS ticket_num, S.name
                  FROM ". TICKETS_TBL ." AS T
                  LEFT JOIN " .TICKET_SOURCES_TBL. " AS TS ON ( T.ticket_id = TS.ticket_id )
                  LEFT JOIN " .SOURCE_SETTINGS_TBL. " AS S ON ( TS.source_id = S.source_id )
                  WHERE $sourceQStr ( T.create_date $betweenClause )
                  GROUP BY WEEKOFYEAR( T.create_date ) , S.source_id";

        $row = $this->dbLink->select($query);
        return $row;
   }

   /*
    * function - getWeeklyRawNoisyProjectsData - fetch data from database
    *                                            for noisy project report
    * @param - $params - array of parameters
    * @Return -$row - array
    */
   function getWeeklyRawNoisyProjectsData($params)
   {
      $startDate   = $params['start_date'];
      $endDate     = $params['end_date'];
      $header_type = $params['header_type'];
      $date_format = ($header_type == RECENT)
                         ? "date_format(T.create_date, '%M %d')"
                         : "DAYNAME(T.create_date)";

      # if user is a authorise source
      if(count($_SESSION['LOGIN_USER']['authorized_sources']) > 0)
      {
         # get source id for this authorise
         $sourceIdStr = $this->getAuthoriseSourceIds();
         $sourceQStr =  "TS.source_id IN($sourceIdStr) and";
      }
      else
      {
         $sourceQStr = "";
      }

      $query =  "SELECT T.status,$date_format as day,
                 COUNT(T.status) as ticket_num,S.name
                 FROM " .TICKETS_TBL. " AS T
                 LEFT JOIN " .TICKET_SOURCES_TBL. " as TS ON (T.ticket_id = TS.ticket_id)
                 LEFT JOIN " .SOURCE_SETTINGS_TBL . " as S on (TS.source_id = S.source_id)
                 WHERE $sourceQStr T.create_date BETWEEN '$startDate' AND '$endDate'
                 GROUP by day(T.create_date), S.source_id";

      $row = $this->dbLink->select($query);
      
      return $row;
           
   }



   /*
    * function - getMonthlyTicketRawData - fetch data from database
    *                                      for monthly ticket report
    * @param - $betweenClause - str - date range
    * @Return -$row - array
    */
    function getMonthlyTicketRawData($betweenClause)
    {
       if(count($_SESSION['LOGIN_USER']['authorized_sources']) > 0)
       {
          # if user is a authorise source

          # get source id for this authorise
          $sourceIdStr = $this->getAuthoriseSourceIds();
          $query = "SELECT T.status, WEEKOFYEAR( T.create_date ) AS week_num,
                   COUNT( T.status ) AS ticket_num
                   FROM ". TICKETS_TBL ." AS T
                   LEFT JOIN " .TICKET_SOURCES_TBL. " AS TS ON ( T.ticket_id = TS.ticket_id )
                   WHERE TS.source_id IN ($sourceIdStr)
                   AND (T.create_date $betweenClause )
                   GROUP BY WEEKOFYEAR( T.create_date ) , T.status
                   ORDER BY status";
       }
       else
       {
          # if user is a resolver
           $query = "SELECT status,WEEKOFYEAR(create_date) AS week_num,
                     COUNT(status) AS ticket_num
                     FROM ". TICKETS_TBL ."
                     WHERE create_date $betweenClause
                     GROUP BY WEEKOFYEAR(create_date), status
                     ORDER BY status";
       }

       $row = $this->dbLink->select($query);
       return $row;
    }

   /*
    * function - getYearlyTicketRawData - fetch data from database for yearly ticket report
    * @param - None
    * @Return -$row - array
    */
    function getYearlyTicketRawData()
    {
       $currentYear = date('Y');
        # get source id for this authorise
       if(count($_SESSION['LOGIN_USER']['authorized_sources']) > 0)
       {
          $sourceIdStr = $this->getAuthoriseSourceIds();

          $query = "SELECT  T.status, MONTHNAME(T.create_date) as month,
                    COUNT(T.status) as ticket_num
                    FROM " .TICKETS_TBL. " as T
                    LEFT JOIN " .TICKET_SOURCES_TBL. " as TS ON (T.ticket_id = TS.ticket_id)
                    WHERE TS.source_id IN ($sourceIdStr) and year(T.create_date) = $currentYear
                    GROUP by month(T.create_date), T.status ORDER by T.status";
       }
       else
       {
          # if user is a resolver, take all source's tickets
          $query = "SELECT status,
                    MONTHNAME(create_date) as month,
                    COUNT(status) as ticket_num
                    FROM ".TICKETS_TBL. " WHERE year(create_date) = $currentYear
                    GROUP by month(create_date), status ORDER by status";
       }
       $row = $this->dbLink->select($query);
       return $row;
    }

     /*
    * function - getWeeklyRawTicketData - fetch data from database for weekly ticket report
    * @param - None
    * @Return -$row - array
    **/
   function getWeeklyRawTicketData($params)
   {
      $startDate   = $params['start_date'];
      $endDate     = $params['end_date'];
      $header_type = $params['header_type'];
      $date_format = ($header_type == RECENT)
                         ? "date_format(T.create_date, '%M %d')"
                         : "DAYNAME(T.create_date)";

      if(count($_SESSION['LOGIN_USER']['authorized_sources']) > 0)
      {
         # get source id for this authorise
         $sourceIdStr = $this->getAuthoriseSourceIds();
         $query = "SELECT  T.status, $date_format as day,
                   COUNT(T.status) as ticket_num
                   FROM " .TICKETS_TBL. " as T
                   LEFT JOIN " .TICKET_SOURCES_TBL. " as TS ON (T.ticket_id = TS.ticket_id)
                   WHERE TS.source_id IN ($sourceIdStr)
                   and (T.create_date BETWEEN '$startDate' AND '$endDate')
                   GROUP by day(T.create_date), T.status ORDER by T.status";

      }
      else
      {
         # if user is a resolver, take all source's tickets
         $query = "SELECT T.status,
                   $date_format as day,
                   COUNT(T.status) as ticket_num
                   FROM ".TICKETS_TBL. " AS T 
                   WHERE create_date BETWEEN '$startDate' AND '$endDate'
                   GROUP by day(create_date), status ORDER by status";
      }

      $row = $this->dbLink->select($query);
      return $row;
   }

   /*
    * function - getRawMonthlyResolverData - fetch data from database
    *                                        for resolver monthly report
    * @param - None
    * @Return -$row - array
    */
    function getRawMonthlyResolverData()
    {
       $month = ($this->reportType == CURRENT_YEAR)? date('m')
                     : date('m',mktime (0,0,0,date("m")-1,date("d"), date("Y")));
       # if user is a authorise source
       if(count($_SESSION['LOGIN_USER']['authorized_sources']) > 0)
       {
          # get source id for this authorise
          $sourceIdStr = $this->getAuthoriseSourceIds();

          $query = "SELECT U.first_name, U.last_name, TA.user_id,
                    count(TA.assigned_date) as assigned,
                    count(TA.completion_date) as completed
                    FROM " .TICKET_ASSIGNMENTS_TBL. " AS TA
                    LEFT JOIN " .USERS_TBL. " as U on (TA.user_id = U.user_id)
                    LEFT JOIN " .TICKET_SOURCES_TBL . " as TS ON (TA.ticket_id = TS.ticket_id)
                    WHERE TS.source_id IN($sourceIdStr) and month(assigned_date) = $month
                    GROUP by TA.user_id";
       }
       else
       {
           # if user is a resolver
           $query = "SELECT U.first_name, U.last_name, TA.user_id,
                     count(TA.assigned_date) as assigned,
                     count(TA.completion_date) as completed
                     FROM " .TICKET_ASSIGNMENTS_TBL. " AS TA
                     LEFT JOIN users as U on (TA.user_id = U.user_id)
                     WHERE month(assigned_date) = $month
                     GROUP by TA.user_id";
       }

       $row = $this->dbLink->select($query);
       return $row;
    }

   /*
    * function - getWeeklyResolverRawData - fetch data from database for resolver weekly report
    * @param - $params - array of parameters
    * @Return -$row - array
    */
   function getWeeklyResolverRawData($params)
   {
      $startDate   = $params['start_date'];
      $endDate     = $params['end_date'];
      $header_type = $params['header_type'];

      # if user is a authorise source
      if(count($_SESSION['LOGIN_USER']['authorized_sources']) > 0)
      {
         # get source id for this authorise
         $sourceIdStr = $this->getAuthoriseSourceIds();
         $query = "SELECT U.first_name, U.last_name, TA.user_id,
                   count(TA.assigned_date) as assigned,
                   count(TA.completion_date) as completed
                   FROM " .TICKET_ASSIGNMENTS_TBL. " AS TA
                   LEFT JOIN " .USERS_TBL. " as U on (TA.user_id = U.user_id)
                   LEFT JOIN " .TICKET_SOURCES_TBL . " as TS ON (TA.ticket_id = TS.ticket_id)
                   WHERE TS.source_id IN($sourceIdStr)
                   and assigned_date BETWEEN '$startDate' AND '$endDate'
                   GROUP by TA.user_id";
      }
      else
      {
         # if user is a resolver
          $query = "SELECT U.first_name, U.last_name, TA.user_id,
                    count(TA.assigned_date) as assigned,
                    count(TA.completion_date) as completed
                    FROM " .TICKET_ASSIGNMENTS_TBL. " AS TA
                    LEFT JOIN users as U on (TA.user_id = U.user_id)
                    WHERE assigned_date BETWEEN '$startDate' AND '$endDate'
                    GROUP by TA.user_id";
      }

      $row = $this->dbLink->select($query);
      return $row;
   }
}

?>