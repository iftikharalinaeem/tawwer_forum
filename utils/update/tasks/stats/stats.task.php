<?php
class StatsTask extends Task {

   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
   }

   protected function Run() {
      TaskList::Event("Updating site stats...", TaskList::NOBREAK);
      $SiteID = isset($this->ClientInfo['SiteID']) ? $this->ClientInfo['SiteID'] : FALSE;
      if ($SiteID == FALSE) {
         TaskList::Event("site not found");
         return;
      }
      // Switch to the client's database.
      mysql_select_db($this->ClientInfo['DatabaseName'], $this->Database);

      // Grab the stats from the site.
      $SQL = "select
                 cast(date_format(c.DateInserted, '%Y-%c-1') as datetime) as Date,
                 count(CommentID) as CountComments
               from GDN_Comment c
               group by Date;";

      $StatsResult = mysql_query($SQL, $this->Database);
      $Stats = array();
      while ($Row = mysql_fetch_array($StatsResult)) {
         $Stats[] = "({$SiteID},'{$Row['Date']}',{$Row['CountComments']})";
      }
      mysql_free_result($StatsResult);

      // Select vfcom again.
      mysql_select_db($this->TaskList->DBMAIN, $this->Database);

      // Construct the insert query for the stats.
      $InsertQuery = "insert ignore GDN_SiteStat (SiteID, Date, CountComments) values ".
         implode(",", $Stats);

      $InsertResult = mysql_query($InsertQuery, $this->Database);

      return;
   }

}