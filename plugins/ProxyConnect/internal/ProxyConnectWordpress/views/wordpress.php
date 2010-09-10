<div class="IntegrationManagerConfigure Slice" rel="dashboard/settings/proxyconnect/integration/proxyconnectwordpress">
   <div class="SliceConfig"><?php echo $this->SliceConfig; ?></div>
   <div class="ProxyConnectWordpress">
   <?php
      echo $this->Form->Open();
      echo $this->Form->Errors();
   
      $IntegrationState = $this->Data('IntegrationState');
   
      $SaveButton = FALSE;
      echo "<div class=\"Task {$IntegrationState}\">\n";
      switch ($IntegrationState) {
         case 'Address':
            echo Wrap(T("<span>Step 1</span>: Enter the address of your Wordpress Blog and we'll take it from there."), 'p',array('class' => 'WordpressStep'));
            echo $this->Form->TextBox('WordpressUrl');
            
            $SaveButton = TRUE;
         break;
      }
      
      echo "</div>\n";
      
      if ($SaveButton) {
         echo $this->Form->Close('Configure', '', array(
            'class' => 'SliceSubmit Button'
         ));
      } else {
         echo $this->Form->Close();
      }
   ?>
   </div>
</div>