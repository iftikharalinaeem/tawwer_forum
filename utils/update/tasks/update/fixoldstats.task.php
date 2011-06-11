<?php

class FixOldStatsTask extends Task {

   public function __construct($ClientDir) {
      parent::__construct($ClientDir);
   }
   
   protected function Run() {
      $this->RemoveFromConfig('EnabledPlugins.Statistics', TRUE);
      $this->EnablePlugin('VanillaStats');
      
      // We do this to ensure that vfoptions goes to the end. Dirty hacks ;)
      $this->RemoveFromConfig('EnabledPlugins.vfoptions');
      $this->SaveToConfig('EnabledPlugins.vfoptions', TRUE);
   }

}

