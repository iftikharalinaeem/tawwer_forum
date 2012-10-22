<div class="CatchupBlock Slice" rel="/plugin/statistics/catchup">
   <?php
      echo $this->Form->Open();
      echo $this->Form->Button("Catchup",array(
         'class'  => 'Button SmallButton RunCatchup',
         'type'   => 'button'
      )); 
   ?>
   <div class="CatchupResults"></div>
</div>