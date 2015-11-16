<?php if (!defined('APPLICATION')) exit(); ?>
<form action="<?php echo Url('/search'); ?>" id="cse-search-box">
<div class="SearchResultsBox">
<input type="hidden" name="cx" value="partner-pub-7133054861616181:2yk2yg-lkla" />
<input type="hidden" name="cof" value="FORID:11" />
<input type="hidden" name="ie" value="ISO-8859-1" />
<input type="text" class="InputBox" name="q" value="<?php echo htmlspecialchars(Gdn::Request()->Get('q')); ?>" size="31" id="cse_text" />
<input type="submit" name="sa" value="Search" class="Button" />
</div>
</form>
<div id="cse-search-results"></div>
<script type="text/javascript">
var googleSearchIframeName = "cse-search-results";
var googleSearchFormName = "cse-search-box";
var googleSearchFrameWidth = 670;
var googleSearchDomain = "www.google.com";
var googleSearchPath = "/cse";
</script>
<script type="text/javascript" src="http://www.google.com/afsonline/show_afs_search.js"></script>