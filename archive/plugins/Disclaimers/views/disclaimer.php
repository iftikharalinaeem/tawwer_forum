<?php if (!defined('APPLICATION')) exit(); ?>
<style>
   .Disclaimer-Links {
      font-size: 200%;
      font-weight: bold;
   }

   .Disclaimer-Leave {
      color: #f00;
   }
}
</style>
<h1><?php echo $this->Data('Title'); ?></h1>

<div class="Disclaimer">
<?php
$TK = $this->Data('TK');
$CategoryID = rawurlencode($this->Data('CategoryID'));

echo '<div class="Disclaimer-Text Info">', vf_wpautop($this->Data('Disclaimer')), '</div>';

echo '<div class="Disclaimer-Links">',
   Anchor(T('Leave'), "/entry/disclaimer/$CategoryID?TK=$TK&Disclaimed=0", 'Disclaimer-Leave'),
   '<span class="Sep"> | </span>',
   Anchor(T('Continue'), "/entry/disclaimer/$CategoryID?TK=$TK&Disclaimed=1&Target=".urlencode($this->Data('Target')), 'Disclaimer-Continue'),
   '</div>';
?>
</div>