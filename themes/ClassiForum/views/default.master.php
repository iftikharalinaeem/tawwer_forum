<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
   <?php $this->RenderAsset('Head'); ?>
   
  <style type="text/css">




/*--Main Container--*/
.main_view {
	float: left;
	position: relative;
	border:1px solid #00BAFF;
	margin:20px 0 50px 0;


}
/*--Window/Masking Styles--*/
.window {
	height:250px;	width: 650px;
	overflow: hidden; /*--Hides anything outside of the set width/height--*/
	position: relative;
}
.image_reel {
	position: absolute;
	top: 0; left: 0;
}
.image_reel img {float: left;}

/*--Paging Styles--*/
.paging {
	
	background:#00BAFF;
	position:absolute;

	top:427px;
	width:652px;
	height:30px;
	z-index:100;
	display: none; /*--Hidden by default, will be later shown with jQuery--*/
}
.paging a {
	padding: 10px;
	text-decoration: none;
	color: #fff;
}
.paging a.active {
	background: #005292; 
	
}
.paging a:hover {}
</style>
   
</head>
<body id="<?php echo $BodyIdentifier; ?>" class="<?php echo $this->CssClass; ?>">
   <div id="Frame">
      <div id="Head">
<a class="top-banner" href="#"> <?php echo Img('/themes/ClassiForum/design/top_banner.jpg'); ?></a>

         <div class="Menu">
<a class="logo" href="<?php echo Url('/'); ?>"> <?php Gdn_Theme::Logo();//echo Img('/themes/ClassiForum/design/logo.png'); ?></a>
            <?php
				
			      $Session = Gdn::Session();
					if ($this->Menu) {
						$this->Menu->AddLink('Dashboard', T('Dashboard'), '/dashboard/settings', array('Garden.Settings.Manage'));
						$this->Menu->AddLink('Dashboard', T('Users'), '/user/browse', array('Garden.Users.Add', 'Garden.Users.Edit', 'Garden.Users.Delete'));
						$this->Menu->AddLink('Activity', T('Activity'), '/activity');
			         $Authenticator = Gdn::Authenticator();
						if ($Session->IsValid()) {
							$Name = $Session->User->Name;
							$CountNotifications = $Session->User->CountNotifications;
							if (is_numeric($CountNotifications) && $CountNotifications > 0)
								$Name .= '<span>'.$CountNotifications.'</span>';
								
							$this->Menu->AddLink('User', $Name, '/profile/{UserID}/{Username}', array('Garden.SignIn.Allow'), array('class' => 'UserNotifications'));
							$this->Menu->AddLink('SignOut', T('Sign Out'), $Authenticator->SignOutUrl(), FALSE, array('class' => 'NonTab SignOut'));
						} else {
							$Attribs = array();
							if (Gdn::Config('Garden.SignIn.Popup'))
								$Attribs['class'] = 'SignInPopup';
								
							$this->Menu->AddLink('Entry', T('Sign In'), $Authenticator->SignInUrl($this->SelfUrl), FALSE, array('class' => 'NonTab'), $Attribs);
						}
						echo $this->Menu->ToString();
					}
				?>
            
         </div>
      </div>
      <div id="Body">
      
         <div id="Content">
		 
		 <div class="main_view">
            <div class="window">	
                <div class="image_reel">
                   <?php echo Img('/themes/ClassiForum/design/feature1.jpg'); ?>
                   <?php echo Img('/themes/ClassiForum/design/feature2.jpg'); ?>
                   <?php echo Img('/themes/ClassiForum/design/feature3.jpg'); ?>
                </div>
            </div>
           
        </div>
        
         <div class="paging">
                <a class="" href="#" rel="1">Feature 1</a>
                <a class="" href="#" rel="2">Feature 2</a>
                <a class="" href="#" rel="3">Feature 3</a>
            </div>
        
   




<script type="text/javascript">

$(document).ready(function() {

	//Set Default State of each portfolio piece
	$(".paging").show();
	$(".paging a:first").addClass("active");
		
	//Get size of images, how many there are, then determin the size of the image reel.
	var imageWidth = $(".window").width();
	var imageSum = $(".image_reel img").size();
	var imageReelWidth = imageWidth * imageSum;
	
	//Adjust the image reel to its new size
	$(".image_reel").css({'width' : imageReelWidth});
	
	//Paging + Slider Function
	rotate = function(){	
		var triggerID = $active.attr("rel") - 1; //Get number of times to slide
		var image_reelPosition = triggerID * imageWidth; //Determines the distance the image reel needs to slide

		$(".paging a").removeClass('active'); //Remove all active class
		$active.addClass('active'); //Add active class (the $active is declared in the rotateSwitch function)
		
		//Slider Animation
		$(".image_reel").animate({ 
			left: -image_reelPosition
		}, 500 );
		
	}; 
	
	//Rotation + Timing Event
	rotateSwitch = function(){		
		play = setInterval(function(){ //Set timer - this will repeat itself every 3 seconds
			$active = $('.paging a.active').next();
			if ( $active.length === 0) { //If paging reaches the end...
				$active = $('.paging a:first'); //go back to first
			}
			rotate(); //Trigger the paging and slider function
		}, 5000); //Timer speed in milliseconds (3 seconds)
	};
	
	rotateSwitch(); //Run function on launch
	
	//On Hover
	$(".image_reel a").hover(function() {
		clearInterval(play); //Stop the rotation
	}, function() {
		rotateSwitch(); //Resume rotation
	});	
	
	//On Click
	$(".paging a").click(function() {	
		$active = $(this); //Activate the clicked paging
		//Reset Timer
		clearInterval(play); //Stop the rotation
		rotateSwitch(); // Resume rotation
		rotate(); //Trigger rotation immediately
		return false; //Prevent browser jump to link anchor
	});	
	
});
</script>
		 
		 
		  <div id="Content">
         <?php $this->RenderAsset('Content'); ?>
         </div>
         
         </div>
        
         <div id="Panel">
		 
         
		 
		 <?php $this->RenderAsset('Panel'); ?>
         
          <div class="Box">
         <h4 class="twitter">Search the Classified Ads</h4>
         
         <div id="Search"><?php
					$Form = Gdn::Factory('Form');
					$Form->InputPrefix = '';
					echo 
						$Form->Open(array('action' => Url('/search'), 'method' => 'get')),
						$Form->TextBox('Search'),
						$Form->Button('Go', array('Name' => '')),
						$Form->Close();
				?></div>
         </div>
         
         
         <a class="small-banner" href="#"> <?php echo Img('/themes/ClassiForum/design/small_banner.jpg'); ?></a>

                  <a class="small-banner" href="#"> <?php echo Img('/themes/ClassiForum/design/small_banner.jpg'); ?></a>

         <a class="small-banner" href="#"> <?php echo Img('/themes/ClassiForum/design/small_banner.jpg'); ?></a>

         <a class="small-banner" href="#"> <?php echo Img('/themes/ClassiForum/design/small_banner.jpg'); ?></a>

         
         </div>
         
      </div>
      <div id="Foot">
			<div><div class="vanilla-ico"><?php echo Img('/themes/minalla/design/vanilla_ico.png'); ?></div><?php
				printf(T('Powered by %s'), '<a href="http://vanillaforums.org"><span>Vanilla</span></a>');
			?></div>
			<?php $this->RenderAsset('Foot'); ?>
		</div>
   </div>
	<?php $this->FireEvent('AfterBody'); ?>
</body>
</html>
