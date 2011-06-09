<?php

/**
 * 
 * Run per-forum SiteStats
 * 
 * This should be run regularly to gather hosting statistics.
 * 
 */

class StatsTask extends Task {

   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
   }

   protected function Run() {
      TaskList::Event("Updating site stats...", TaskList::NOBREAK);

      // Grab the stats from the site.
      $SQL = "select
                 cast(date_format(c.DateInserted, '%Y-%c-1') as datetime) as Date,
                 count(CommentID) as CountComments
               from GDN_Comment c
               group by Date;";

      $StatsResult = mysql_query($SQL, $this->Database());
      $Stats = array();
      while ($Row = mysql_fetch_array($StatsResult)) {
         $Stats[] = "({$SiteID},'{$Row['Date']}',{$Row['CountComments']})";
      }
      mysql_free_result($StatsResult);

      // Construct the insert query for the stats.
      $InsertQuery = "insert ignore GDN_SiteStat (SiteID, Date, CountComments) values ".
         implode(",", $Stats);

      $InsertResult = mysql_query($InsertQuery, $this->TaskList->RootDatabase());

      return;
   }

}