<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
  {asset name='Head'}
</head>

<body id="{$BodyID}" class="{$BodyClass}">

<div id="Frame">
 <div id="Head">
   <div class="Banner Menu">
      <h1><a class="Title" href="{link path="/"}"><span>{logo}</span></a></h1>
      
      <div id="Search"></div>
    </div>
  </div>

  <div id="Body">
  
    <div class="Wrapper">
  
        <div id="Content">
            {asset name="Content"}
        </div>
        
        <div id="Panel">
            {searchbox}
            
            
            <div  class="Box">
            <h4>My Profile</h4>
              <ul>
               {inbox_link}
               {profile_link}
               {activity_link}
               {signinout_link}
              </ul>
            </div>
            
            {asset name="Panel"}
        </div>
    
    </div>
    
  </div>
  
  
  
  <div id="Foot">
      <div>
      	<span style="float: right;">
        {profile_link wrap=""}<span style="padding: 0pt 10px; font-size:10px;">&bull;</span>{signinout_link wrap=""}
        </span>
      
      <a href="{vanillaurl}"><span>Powered by Vanilla</span></a></div>
    
    {asset name="Foot"}
 </div>
</div>

</body>
</html>