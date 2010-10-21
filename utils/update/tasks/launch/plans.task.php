<?php

class PlansTask extends Task {
   
   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
   }
   
   protected function Run() {
   
      // First check if they are real paying clients
      TaskList::Event("Checking real paid status...", TaskList::NOBREAK);
      $AccountID = isset($this->ClientInfo['AccountID']) ? $this->ClientInfo['AccountID'] : FALSE;
      if ($AccountID == FALSE) {
         TaskList::Event("site not found");
         return;
      }
         
      $RealPaidQuery = "SELECT SubscriptionStatus FROM GDN_Account WHERE AccountID = '%d'";
      $PaidStatusResult = mysql_query(sprintf($RealPaidQuery, $AccountID), $this->Database);
      if (!$PaidStatusResult || !mysql_num_rows($PaidStatusResult)) {
         TaskList::Event("row not found");
         return;
      }
      
      $PaidStatus = array_pop(mysql_fetch_assoc($PaidStatusResult));
      if ($PaidStatus == 'Paid') {
         TaskList::Event("paid");
         return;
      } else {
         TaskList::Event("free - ".$PaidStatus);
         return;
      }
      
      return;
   }

}

