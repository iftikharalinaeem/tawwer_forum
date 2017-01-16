<?php
$onlineUsers = $this->data('OnlineUsers');
$guestCount = $this->data('GuestCount');
$totalCount = $this->data('TotalCount');

$displayStyle = c('WhosOnline.DisplayStyle', 'list');
?>
<div id="WhosOnline" class="Box">
    <h4><?php echo t('Who\'s Online'); ?>
        <span class="Count"><?php echo Gdn_Format::bigNumber($totalCount, 'html') ?></span>
    </h4>
    <?php
    if ($totalCount > 0) {
        if ($displayStyle == 'pictures') {
            if (count($onlineUsers) > 10) {
                $listClass = 'PhotoGrid PhotoGridSmall';
            } else {
                $listClass = 'PhotoGrid';
            }

            echo '<div class="'.$listClass.'">';

            foreach ($onlineUsers as $user) {
                if (!$user['Photo'] && !function_exists('UserPhotoDefaultUrl')) {
                    $user['Photo'] = asset('/applications/dashboard/design/images/usericon.gif', true);
                }

                echo userPhoto($user, [
                    'LinkClass' => (($user['Invisible']) ? 'Invisible' : '')
                ]);
            }

            if ($guestCount) {
                $formattedGuestCount = Gdn_Format::bigNumber($guestCount, 'html');
                $guestsText = plural($guestCount, 'guest', 'guests');
                $plus =  $guestCount != $totalCount ? '+' : '';
                echo <<<EOT
        <span class="GuestCountBox"><span class="GuestCount">{$plus}$formattedGuestCount</span> <span class="GuestLabel">$guestsText</span></span>
EOT;
            }

            echo '</div>';
        } else {
            echo '<ul class="PanelInfo">';

            foreach ($onlineUsers as $user) {
                echo '<li>'.userAnchor($user, ($user['Invisible']) ? 'Invisible' : '').'</li>';
            }

            if ($guestCount) {
                echo '<li><strong>'.sprintf(t('+%s Guests'), Gdn_Format::bigNumber($guestCount, 'html')).'</strong></li>';
            }

            echo '</ul>';
        }
    }
    ?>
</div>
