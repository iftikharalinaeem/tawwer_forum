<?php echo '<?xml version="1.0" encoding="utf-8"?>'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-ca">
<head>
  <?php $this->RenderAsset('Head'); ?>
</head>

<body id="<?php echo $BodyIdentifier; ?>" class="<?php echo $this->CssClass; ?>">

   <div id="Frame">
      <div id="Head">
         <div class="Banner Menu">
            <h1><a class="Title" href="<?php echo Url('/'); ?>"><?php echo Gdn_Theme::Logo(); ?></a></h1>
            <div class="MenuHolder">
               <?php
                  $Session = Gdn::Session();
                  if ($this->Menu) {
                     $this->Menu->AddLink('Dashboard', T('Dashboard'), '/dashboard/settings', array('Garden.Settings.Manage'));
                     // $this->Menu->AddLink('Dashboard', T('Users'), '/user/browse', array('Garden.Users.Add', 'Garden.Users.Edit', 'Garden.Users.Delete'));
                     $this->Menu->AddLink('Activity', T('Activity'), '/activity');
                     $Authenticator = Gdn::Authenticator();
                     if ($Session->IsValid()) {
                        $Name = $Session->User->Name;
                        $CountNotifications = $Session->User->CountNotifications;
                        if (is_numeric($CountNotifications) && $CountNotifications > 0)
                           $Name .= ' <span class="Alert">'.$CountNotifications.'</span>';

                        if (urlencode($Session->User->Name) == $Session->User->Name)
                           $ProfileSlug = $Session->User->Name;
                        else
                           $ProfileSlug = $Session->UserID.'/'.urlencode($Session->User->Name);
                        $this->Menu->AddLink('User', $Name, '/profile/'.$ProfileSlug, array('Garden.SignIn.Allow'), array('class' => 'UserNotifications'));
                        $this->Menu->AddLink('SignOut', T('Sign Out'), Gdn::Authenticator()->SignOutUrl(), FALSE, array('class' => 'SignOut'));
                     } else {
                        $Attribs = array();
                        if (SignInPopup() && strpos(Gdn::Request()->Url(), 'entry') === FALSE)
                           $Attribs['class'] = 'SignInPopup';
                           
                        $this->Menu->AddLink('Entry', T('Sign In'), Gdn::Authenticator()->SignInUrl(), FALSE, array('class' => ''), $Attribs);
                     }
                     echo $this->Menu->ToString();
                  }
               ?>
            </div>
         </div>
      </div>

      <div id="Body">

         <div id="Panel">
            <?php $this->RenderAsset('Panel'); ?>
            <div id="Search"><?php
               $Form = Gdn::Factory('Form');
               $Form->InputPrefix = '';
               echo 
                  $Form->Open(array('action' => Url('/search'), 'method' => 'get')),
                  $Form->TextBox('Search'),
                  $Form->Button('Go', array('Name' => '')),
                  $Form->Close();
            ?></div>
            <a class="VanillaLink" href="<?php echo C('Garden.VanillaUrl'); ?>"><span>Powered by Vanilla</span></a>
         </div>

         <div id="Content">
            <?php $this->RenderAsset('Content'); ?>
         </div>

      </div>
        
      <div id="Foot">
         <?php $this->RenderAsset('Foot'); ?>
      </div>
   </div>
   <?php $this->FireEvent('AfterBody'); ?>
</body>
</html>