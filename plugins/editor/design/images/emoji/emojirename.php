<?php

// copied from class.editor.plugin.php
$emojiCanonicalList = array(
   // Smileys
   'relaxed'                      => array('50.png'),  
   'grinning'                     => array('701.png'),  
   'grin'                         => array('702.png'),  
   'joy'                          => array('703.png'),  
   'smiley'                       => array('704.png'),  
   'smile'                        => array('705.png'),  
   'sweat_smile'                  => array('706.png'),  
   'satisfied'                    => array('707.png'),  
   'innocent'                     => array('708.png'),  
   'smiling_imp'                  => array('709.png'),  
   'wink'                         => array('710.png'),  
   'blush'                        => array('711.png'),  
   'yum'                          => array('712.png'),  
   'relieved'                     => array('713.png'),  
   'heart_eyes'                   => array('714.png'),  
   'sunglasses'                   => array('715.png'),  
   'smirk'                        => array('716.png'),  
   'neutral_face'                 => array('717.png'),  
   'expressionless'               => array('718.png'),  
   'unamused'                     => array('719.png'),  
   'sweat'                        => array('720.png'),  
   'pensive'                      => array('721.png'),  
   'confused'                     => array('722.png'),  
   'confounded'                   => array('723.png'),  
   'kissing'                      => array('724.png'),  
   'kissing_heart'                => array('725.png'),  
   'kissing_smiling_eyes'         => array('726.png'),  
   'kissing_closed_eyes'          => array('727.png'),  
   'stuck_out_tongue'             => array('728.png'),  
   'stuck_out_tongue_winking_eye' => array('729.png'),  
   'stuck_out_tongue_closed_eyes' => array('730.png'),  
   'disappointed'                 => array('731.png'),  
   'worried'                      => array('732.png'),  
   'angry'                        => array('733.png'),  
   'rage'                         => array('734.png'),  
   'cry'                          => array('735.png'),  
   'persevere'                    => array('736.png'),  
   'triumph'                      => array('737.png'),  
   'disapponted_relieved'         => array('738.png'),  
   'frowning'                     => array('739.png'),  
   'anguished'                    => array('740.png'),  
   'fearful'                      => array('741.png'),  
   'weary'                        => array('742.png'),  
   'sleepy'                       => array('743.png'),  
   'tired_face'                   => array('744.png'),  
   'grimacing'                    => array('745.png'),  
   'sob'                          => array('746.png'),  
   'open_mouth'                   => array('747.png'),  
   'hushed'                       => array('748.png'),  
   'cold_sweat'                   => array('749.png'),  
   'scream'                       => array('750.png'),  
   'astonished'                   => array('751.png'),  
   'flushed'                      => array('752.png'),  
   'sleeping'                     => array('753.png'),  
   'dizzy_face'                   => array('754.png'),  
   'no_mouth'                     => array('755.png'),  
   'mask'                         => array('756.png'),  

   // Love
   'heart'                        => array('109.png'),  
   'broken_heart'                 => array('506.png'),  
   'kiss'                         => array('497.png'),

   // Hand gestures
   '+1'                           => array('435.png'),  
   '-1'                           => array('436.png'),

   // Custom icons, canonical naming
   'trollface'                    => array('trollface.png')
);


if ($handle = opendir('.')) {
   while (false !== ($filename = readdir($handle))) {

      foreach ($emojiCanonicalList as $emojiCanonicalName => $emojiFileName) {
         if ($emojiFileName[0] == $filename) {
            $newname = str_replace($filename,"$emojiCanonicalName.png",$filename);
            rename($filename, $newname);
         }
      }
   }

   closedir($handle);
}

?>