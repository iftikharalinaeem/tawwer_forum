<?php if (!defined('APPLICATION')) return;

function PercentBox($Percent, $TitleFormat, $PercentApproved, $ApprovedTitleFormat) {
   $Formatted = number_format($Percent * 100, 0).'%';
   $Title = sprintf(T($TitleFormat), $Formatted);
   
   $ApprovedFormatted = number_format($PercentApproved * 100, 0).'%';
   $Title .= ', '.sprintf(T($ApprovedTitleFormat), $ApprovedFormatted);
   
   
   
   $Result = '<span class="PercentBox" title="'.$Title.'"><span class="NotComplete">';
   
   $Result .= '<span class="PercentTranslated" style="width: '.$Formatted.'"> </span>';
   $Result .= '<span class="PercentApproved" style="width: '.$ApprovedFormatted.'"> </span>';
   
   $Result .= '</span></span>';
   
   return $Result;
}

function Hello() {
   $Hellos = array(
       'Goeie dag!',
       "C'kemi, Tungjatjeta!",
       "Hallo, Güete Tag!",
       "Ola!",
       "Werte!",
       "Hola, Bonos díes!",
       "Дзень добры!",
       "আসসালামু আলাইকুম!",
       "Здравей!",
       "Hola, Bon dia!",
       "Håfa ådai!",
       "你好!",
       "汝好!",
       "Dydh da, Hou, You, Ha, Hou sos!",
       "Hej, Høj, Góðdag!",
       "Hej!",
       "Hallo!",
       "ታዲያስ!",
       "Saluton!",
       "Tere, Tervist!",
       "Bula!",
       "Moin!",
       "Gouden Dai!",
       "A goeie, Hoi, Goeie, Goedei!",
       "გამარჯობა!",
       "Γειά σου!",
       "Aloha!",
       "Jó napot kívánok!",
       "今日は!",
       "Witôjze!",
       "nuqneH!",
       "안녕하세요!",
       "Goddag!",
       "Wes hāl!",
       "Cześć!",
       "ਸਤਿ ਸ੍ਰੀ ਅਕਾਲ।!",
       "Здравствуйте!",
       "Halò, Ciamar a tha thu!",
       "¡Hola!",
       "Merhaba, Selam, İyi günler!",
       "Ciao!"
       );
   return $Hellos[array_rand($Hellos)];
}