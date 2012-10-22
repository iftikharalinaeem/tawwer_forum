<style>
   #Foot div.GitBranch {
      background-color: #E3F4FF;
      color:            #1E79A7;
      padding:          0px;
      overflow:         hidden;
      height:           100%;
   }
   
   #Foot div.GitBranch span {
      height:           18px;
      line-height:      18px;
      display:          block;
      float:            left;
      padding:          5px 10px;
   }
   #Foot div.GitBranch span.GitBranchTitle {
      background-color: #1E79A7;
      color:            #CFECFF;
   }
</style>
<div class="GitBranch">
   <span class="GitBranchTitle">GIT</span>
   <span><?php echo "<strong>{$this->GitPlugin_Branch}</strong> ".Gdn::Session()->TransientKey()." :: {$this->GitPlugin_RevHash}"; ?></span>
</div>