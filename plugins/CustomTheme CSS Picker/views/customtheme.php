<?php if (!defined('APPLICATION')) exit();

// Only show the "color" tab if using the default theme.
$ShowColor = C('Garden.Theme') == 'defaultsmarty';
$DefaultTab = $ShowColor ? 'color' : 'html';

function WriteRevisions($Sender, $Tab = '') {
   $Data = GetValue('RevisionData', $Sender->Data);
   $LiveRevisionID = GetValue('LiveRevisionID', $Sender->Data);
   if (!$Data || $Data->NumRows() == 0)
      return;
   
   ?>
   <strong>Recent Revisions</strong>
   <div class="InfoBox RecentRevisions">
   <?php
   $LastDay = '';
   foreach ($Data->Result() as $Row) {
      $Day = date('M jS, Y', Gdn_Format::ToTimeStamp($Row->DateInserted));
      if ($Day != $LastDay) {
         echo "<div class=\"NewDay\">$Day</div>";
         $LastDay = $Day;
      }

      echo '<div class="Revision'.($Row->RevisionID == $LiveRevisionID ? ' LiveRevision' : '').'">&rarr;'
         .Anchor(date("g:i:sa", Gdn_Format::ToTimeStamp($Row->DateInserted)), 'settings/customtheme/revision/'.$Tab.'/'.$Row->RevisionID)
         .($Row->RevisionID == $LiveRevisionID ? ' Live Version' : '')
      .'</div>';
   }  
   ?>
      <div class="NewDay"><?php echo Anchor(T('Original Version'), 'settings/customtheme/revision/'.$Tab.'/0'); ?></div>
   </div>
   <?php
}
$CurrentTab = $this->Form->GetFormValue('CurrentTab', GetValue(1, $this->RequestArgs, $DefaultTab));
if (!in_array($CurrentTab, array('html', 'color', 'css')))
   $CurrentTab = $DefaultTab;
   
$this->Form->AddHidden('CurrentTab', $CurrentTab);
echo $this->Form->Open();
?>
<h1>Customize Theme</h1>
<?php
echo $this->Form->Errors();
?>
<div class="Tabs CustomThemeTabs">
   <ul>
<?php if ($ShowColor) { ?>
      <li class="CustomColor<?php echo $CurrentTab == 'color' ? ' Active' : ''; ?>"><?php echo Anchor(T('Edit Colors'), 'settings/customtheme/#'); ?></li>
<?php } ?>
      <li class="CustomHtml<?php echo $CurrentTab == 'html' ? ' Active' : ''; ?>"><?php echo Anchor(T('Edit Html'), 'settings/customtheme/#'); ?></li>
      <li class="CustomCSS<?php echo $CurrentTab == 'css' ? ' Active' : ''; ?>"><?php echo Anchor(T('Edit CSS'), 'settings/customtheme/#'); ?></li>
   </ul>
</div>
<?php if ($ShowColor) { ?>
<style type="text/css">
/* Colors to Manipulate */
.Banner { background: #<?php echo $this->Form->GetValue('ColorBanner'); ?>; }
.Banner a { color: #<?php echo $this->Form->GetValue('ColorBannerLinks'); ?>; }
.Body { background: #<?php echo $this->Form->GetValue('ColorBackground'); ?>; }
.Heading { 
   background: #<?php echo $this->Form->GetValue('ColorTabs'); ?>;
   border-bottom: 1px solid #ABDAFB;
}
.Heading a {
   background: #BBE2F7;
   color: #1E79A7;
   border: 1px solid #ABDAFB;
}
.Heading a:hover { background: #f3fcff; }
.Heading a.Current {
   background: #fff;
   color: #474747;
}
.Row { border-bottom: 1px solid #BEC8CC; }
.RowMeta { color: #<?php echo $this->Form->GetValue('ColorBodyText'); ?>; }
.Row a { color: #<?php echo $this->Form->GetValue('ColorBodyLinks'); ?>; }
.Row a:hover { color: #ff0084; }

/* Layout & Other Stuff */
.Preview {
   border: 1px solid #000;
   overflow: hidden;
}
.Banner { padding: 6px 12px; }
.Banner a {
   font-size: 11px;
   font-weight: bold;
}
.Banner a:hover { text-decoration: underline; }
.Body { padding: 12px 0 12px 12px; }
.Heading { padding: 5px 8px; }
.Heading a {
   font-weight: bold;
   border-radius: 3px;
   -moz-border-radius: 3px;
   -webkit-border-radius: 3px;
   line-height: 2.6;
   margin: 0;
   padding: 5px 10px;
}
.Row { padding: 4px 10px; }
.RowMeta { font-size: 11px; }
.RowMeta .Item {
   margin-right: 12px;
   display: inline-block;
}
.Title {
   display: block;
   font-size: 14px;
   font-weight: bold;
}
table.ColorTable {
   border: none;
   width: auto;
}
table.ColorTable td {
   padding: 0 6px 6px 0;
}
table.ColorTable label {
   font-weight: normal;
   display: block;
}
table.ColorTable input.InputBox {
   border-radius: 1px;
   -moz-border-radius: 1px;
   -webkit-border-radius: 1px;
   padding: 3px;
   width: 100px;
}
.Column {
   display: inline-block;
   width: 380px;
   padding: 0 20px 20px 0;
   vertical-align: top;
}
</style>
<script type="text/javascript" language="javascript">
jQuery(document).ready(function($) {

   // Returns the decimal equivalent of the hexadecimal number  
   hexdec = function(hex) {
      return parseInt(hex, 16);
   }

   // Convert hex values to rgb
   hex2rgb = function(hex) {
      return {
         r:hexdec(hex.substr(0,2)),
         g:hexdec(hex.substr(2,2)),
         b:hexdec(hex.substr(4,2))
      };
   }
   
   // Convert rgb values to hex
   rgb2hex = function(rgb) {
      hex = function(x) {
         return ("0" + parseInt(x).toString(16)).slice(-2);
      }
      return hex(rgb.r) + hex(rgb.g) + hex(rgb.b);
   }
   
   rgb2hsb = function (rgb) {
      var hsb = {
         h: 0,
         s: 0,
         b: 0
      };
      var min = Math.min(rgb.r, rgb.g, rgb.b);
      var max = Math.max(rgb.r, rgb.g, rgb.b);
      var delta = max - min;
      hsb.b = max;
      if (max != 0) {
         
      }
      hsb.s = max != 0 ? 255 * delta / max : 0;
      if (hsb.s != 0) {
         if (rgb.r == max) {
            hsb.h = (rgb.g - rgb.b) / delta;
         } else if (rgb.g == max) {
            hsb.h = 2 + (rgb.b - rgb.r) / delta;
         } else {
            hsb.h = 4 + (rgb.r - rgb.g) / delta;
         }
      } else {
         hsb.h = -1;
      }
      hsb.h *= 60;
      if (hsb.h < 0) {
         hsb.h += 360;
      }
      hsb.s *= 100/255;
      hsb.b *= 100/255;
      return hsb;
   };
   
   writergb = function(rgb) {
      $('.report').append('r:'+rgb.r+'g:'+rgb.g+'b:'+rgb.b+'; ');
   }
   
   // Take a hex value and translate based on the differences between a baseline hex & the converted value.
   var getNewColor = function(baseCurrHex, baseOrigHex, newOrigHex) {
      baseCurr = hex2rgb(baseCurrHex);
      baseOrig = hex2rgb(baseOrigHex);
      newOrig = hex2rgb(newOrigHex);
      //var isGrey = baseOrig.r == baseOrig.g && baseOrig.g == baseOrig.b;

      // Convert the colors to hsb.
      baseCurr = rgb2hsb(baseCurr);
      baseOrig = rgb2hsb(baseOrig);
      newOrig = rgb2hsb(newOrig);

      var newCurr = {h: 0, s: 0, b: 0};

      // Transform the hue.
      newCurr.h = newOrig.h + (baseCurr.h - baseOrig.h);
      if (newCurr.h < 0)
         newCurr.h = 360 + newCurr.h;
      else if (newCurr.h > 360)
         newCurr.h = newCurr.h - 360;

      // Transform the saturation.
      newCurr.s = newOrig.s + (baseCurr.s - baseOrig.s);
      if (newCurr.s < 0)
         newCurr.s = 0;
      if (newCurr.s > 100)
         newCurr.s = 100;

      // Transform the brightness.
      newCurr.b = newOrig.b;
      newCurr.b = newOrig.b + (baseCurr.b - baseCurr.b);
      if (newCurr.b < 0)
         newCurr.b = 0;
      if (newCurr.b > 100)
         newCurr.b = 100;

      newCurr = hsb2rgb(newCurr);
      return rgb2hex(newCurr);
   }
   
   hsb2rgb = function (hsb) {
      var rgb = {};
      var h = Math.round(hsb.h);
      var s = Math.round(hsb.s*255/100);
      var v = Math.round(hsb.b*255/100);
      if(s == 0) {
         rgb.r = rgb.g = rgb.b = v;
      } else {
         var t1 = v;
         var t2 = (255-s)*v/255;
         var t3 = (t1-t2)*(h%60)/60;
         if(h==360) h = 0;
         if(h<60) {rgb.r=t1;	rgb.b=t2; rgb.g=t2+t3}
         else if(h<120) {rgb.g=t1; rgb.b=t2;	rgb.r=t1-t3}
         else if(h<180) {rgb.g=t1; rgb.r=t2;	rgb.b=t2+t3}
         else if(h<240) {rgb.b=t1; rgb.r=t2;	rgb.g=t1-t3}
         else if(h<300) {rgb.b=t1; rgb.g=t2;	rgb.r=t2+t3}
         else if(h<360) {rgb.r=t1; rgb.g=t2;	rgb.b=t1-t3}
         else {rgb.r=0; rgb.g=0;	rgb.b=0}
      }
      return {r:Math.round(rgb.r), g:Math.round(rgb.g), b:Math.round(rgb.b)};
   }

   setColor = function(hsb, hex, rgb, el) {
      $(el).val(hex);
      switch($(el).attr('id').replace('Form_', '')) {
         case 'ColorBanner':
            $('.Banner').css('backgroundColor', '#' + hex);
            break;
         case 'ColorBackground':
            $('.Body').css('backgroundColor', '#' + hex);
            break;
         case 'ColorTabs':
            var tabBackground = 'CFECFF',
               tabBorder = 'ABDAFB',
               buttonBackground = 'BBE2F7',
               buttonBackgroundHover = 'f3fcff',
               buttonBorder = 'abdafb',
               buttonText = '1e79a7',
               buttonActiveBackground = 'ffffff',
               buttonActiveText = '474747';
            
            // Calculate the difference between the returned rgb & the baseline rgb
            var baseline = hex2rgb(tabBackground);
            var diff = { r:baseline.r - rgb.r, g:baseline.g - rgb.g, b:baseline.b - rgb.b };
            // $('.report').append(diff.r + ', ' + diff.g + ', ' + diff.b + '; ');
            //break;
            
            // Apply all the colors
            $('.Heading').css('backgroundColor', '#' + hex);
            $('.Heading').css('borderColor', '#' + getNewColor(tabBorder, tabBackground, hex));
            $('.Heading a').css('backgroundColor', '#' + getNewColor(buttonBackground, tabBackground, hex));
            $('.Heading a').css('borderColor', '#' + getNewColor(buttonBorder, tabBackground, hex));
            $('.Heading a').css('color', '#' + getNewColor(buttonText, tabBackground, hex));
            $('.Heading a:hover').css('backgroundColor', '#' + getNewColor(buttonBackgroundHover, tabBackground, hex));
            $('.Heading a.Current').css('backgroundColor', '#' + buttonActiveBackground);
            // $('.Heading a.Current').css('color', '#' + getNewColor(buttonActiveText, tabBackground, hex));
            break;
         case 'ColorBannerLinks':
            $('.Banner a').css('color', '#' + hex);
            break;
         case 'ColorBodyLinks':
            $('.Row a').css('color', '#' + hex);
            break;
         case 'ColorBodyText':
            $('.RowMeta').css('color', '#' + hex);
            break;
      }
   };
   
   $('.ColorTable .InputBox').ColorPicker({
      onSubmit: function(hsb, hex, rgb, el) {
         $(el).val(hex);
         $(el).ColorPickerHide();
      },
      onChange: setColor,
      onBeforeShow: function () {
         $(this).ColorPickerSetColor(this.value);
      }
   }).bind('keyup', function(){ $(this).ColorPickerSetColor(this.value); });
   
});
</script>
<div class="Container CustomColorContainer<?php echo $CurrentTab == 'color' ? '': ' Hidden'; ?>">
   <ul>
      <li>
         <div class="CustomThemeForm">
            <div class="Info">Note: If you make changes to the html, this color picker may stop working.</div>
            <div class="Column">
               <strong>Preview Changes</strong>
               <div class="Preview">
                  <div class="Banner"><a href="#">Discussions</a></div>
                  <div class="Body">
                     <div class="Heading">
                        <a class="Current" href="#">Current Tab</a>
                        <a href="#">Other Tab</a>
                     </div>
                     <div class="Row">
                        <a class="Title" href="#">Discussion Topic</a>
                        <div class="RowMeta">
                           <div class="Item">6 comments</div>
                           <div class="Item">Most recent by <a href="#">Username</a></div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
            <div class="Column">
               <strong>Change Colors</strong>
               <table class="ColorTable">
                  <tr>
                     <td>
                        <?php
                        echo $this->Form->Label('Banner', 'ColorBanner');
                        echo $this->Form->TextBox('ColorBanner');
                        ?>
                     </td>
                     <td>
                        <?php
                        echo $this->Form->Label('Body', 'ColorBackground');
                        echo $this->Form->TextBox('ColorBackground');
                        ?>
                     </td>
                     <td>
                        <?php
                        echo $this->Form->Label('Tabs', 'ColorTabs');
                        echo $this->Form->TextBox('ColorTabs');
                        ?>
                     </td>
                  </tr>
               </table>
               <table class="ColorTable">
                  <tr>
                     <td>
                        <?php
                        echo $this->Form->Label('Banner Links', 'ColorBannerLinks');
                        echo $this->Form->TextBox('ColorBannerLinks');
                        ?>
                     </td>
                     <td>
                        <?php
                        echo $this->Form->Label('Body Links', 'ColorBodyLinks');
                        echo $this->Form->TextBox('ColorBodyLinks');
                        ?>
                     </td>
                     <td>
                        <?php
                        echo $this->Form->Label('Body Text', 'ColorBodyText');
                        echo $this->Form->TextBox('ColorBodyText');
                        ?>
                     </td>
                  </tr>
               </table>
            </div>
            <div class="report"></div>
         </div>
         <div class="CustomThemeOptions">
            <strong>Revision Options</strong>
            <div class="InfoBox RevisionOptions">
               <div class="Buttons">
               <?php
               echo $this->Form->Button('Preview', array('class' => 'TextButton'));
               if (C('Plugins.CustomTheme.Enabled'))
                  echo $this->Form->Button('Apply', array('class' => 'Button Apply'));
               else
                  echo Anchor('Upgrade to Apply', 'settings/customthemeupgrade/', 'Button Apply');
   
               ?>
               </div>
            </div>
            <?php WriteRevisions($this, 'color'); ?>
         </div>
      </li>
   </ul>
</div>
<?php } ?>
<div class="Container CustomCSSContainer<?php echo $CurrentTab == 'css' ? '': ' Hidden'; ?>">
   <ul>
      <li>
         <div class="CustomThemeForm">
            <?php
            echo $this->Form->TextBox('CustomCSS', array('MultiLine' => TRUE, 'class' => 'TextBox CustomThemeBox Autogrow'));
            ?>
         </div>
         <div class="CustomThemeOptions">
            <strong>Revision Options</strong>
            <div class="InfoBox RevisionOptions">
               <ul>
                  <li>
                     <strong>How to include your Custom CSS:</strong>
                     <?php
                     $Default = C('Plugins.CustomCSS.IncludeThemeCSS', 'Yes');
                     echo $this->Form->Radio('IncludeThemeCSS', 'Add my css after the theme css.', array('value' => 'Yes', 'default' => $Default));
                     echo $this->Form->Radio('IncludeThemeCSS', "ONLY use my CSS (not recommended).", array('value' => 'No', 'default' => $Default));
                     ?>
                  </li>
               </ul>
               <div class="Buttons">
               <?php
               echo $this->Form->Button('Preview', array('class' => 'TextButton'));
               if (C('Plugins.CustomTheme.Enabled'))
                  echo $this->Form->Button('Apply', array('class' => 'Button Apply'));
               else
                  echo Anchor('Upgrade to Apply', 'settings/customthemeupgrade/', 'Button Apply');
   
               ?>
               </div>
            </div>
            <?php WriteRevisions($this, 'css'); ?>
            <strong>Help</strong>
            <div class="InfoBox">
               <div>If you are new to CSS, here are some links you should check out:</div>
               <?php
               echo '&rarr; '.Anchor('Our Custom CSS Documentation', 'http://vanillaforums.com/help/customcss', '', array('target' => '_blank'));
               echo '<br />&rarr; '.Anchor("W3C School's CSS Tutorial", 'http://www.w3schools.com/Css', '', array('target' => '_blank'));
               echo '<br />&rarr; '.Anchor("Html Dog's CSS Beginner Tutorial", 'http://htmldog.com/guides/cssbeginner', '', array('target' => '_blank'));
               ?>            
            </div>
         </div>
      </li>
   </ul>
</div>
<div class="Container CustomHtmlContainer<?php echo $CurrentTab == 'html' ? '' : ' Hidden'; ?>">
   <ul>
      <li>
         <div class="CustomThemeForm">
            <?php
            echo $this->Form->TextBox('CustomHtml', array('MultiLine' => TRUE, 'class' => 'TextBox CustomThemeBox Autogrow'));
            ?>
         </div>
         <div class="CustomThemeOptions">
            <strong>Revision Options</strong>
            <div class="InfoBox RevisionOptions">
               <div class="Buttons">
               <?php
               echo $this->Form->Button('Preview', array('class' => 'TextButton'));
               if (C('Plugins.CustomTheme.Enabled'))
                  echo $this->Form->Button('Apply', array('class' => 'Button Apply'));
               else
                  echo Anchor('Upgrade to Apply', 'settings/customthemeupgrade/', 'Button Apply');
   
               ?>
               </div>
            </div>
            <?php WriteRevisions($this, 'html'); ?>
            <strong>Help</strong>
            <div class="InfoBox">
               <div>If you are new to HTML, here are some links you should check out:</div>
               <?php
               echo '&rarr; '.Anchor('Our Custom HTML Documentation', 'http://vanillaforums.com/blog/help-tutorials/how-to-use-custom-theme-part-1-edit-html/', '', array('target' => '_blank'));
               ?>            
            </div>
         </div>
      </li>
   </ul>
</div>
<?php
echo $this->Form->Close();

