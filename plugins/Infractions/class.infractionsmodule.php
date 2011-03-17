<?php if (!defined('APPLICATION')) exit();

class InfractionsModule extends Gdn_Module {
   
   public function AssetTarget() {
      return 'Panel';
   }

   public function ToString() {
      $Session = Gdn::Session();
      if (!$Session->IsValid())
         return '';
      
      $InfractionCache = InfractionsPlugin::GetInfractionCache($Session->UserID);
      $Points = GetValue('Points', $InfractionCache, 0);
      // if ($Points == 0)
      //   return '';
      
      $Count = GetValue('Count', $InfractionCache, 0);
      $Jailed = GetValue('Jailed', $InfractionCache);
      $Banned = GetValue('Banned', $InfractionCache);
      $String = Anchor('<span>×</span>', '/profile/dismissinfractionsmessage', array('class' => 'Dismiss'));
      $String .= Wrap(Anchor(
         sprintf(
            'You have %1$s and %2$s',
            Plural($Count, '%d infraction', '%d infractions'),
            Plural($Points, '%d active infraction point', '%d active infraction points')
         ), 'profile/'.$Session->User->UserID.'/'.Gdn_Format::Url($Session->User->Name).'/infractions/'), 'div');
      
      if ($Banned)
         $String .= Wrap("Your account has been Banned.", 'strong');
      else if ($Jailed)
         $String .= Wrap("Your account has been Jailed.", 'strong');

      $String .= Wrap(Anchor('Find out how infractions work', 'discussion/infractions', array('class' => 'Popup')).'.', 'div');
      return Wrap(Wrap($String, 'div', array('class' => 'Message')), 'div', array('class' => 'Messages'));
   }
}
