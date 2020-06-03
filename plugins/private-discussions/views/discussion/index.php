<?php if (!defined('APPLICATION')) exit();
/*!
 * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

$Session = Gdn::session();
include $this->fetchViewLocation('helper_functions', 'discussion');

// Wrap the discussion related content in a div.
echo '<div class="MessageList Discussion">';

// Write the page title.
echo '<!-- Page Title -->
<div id="Item_0" class="PageTitle">';

echo '<div class="Options">';

$this->fireEvent('BeforeDiscussionOptions');
writeBookmarkLink();
echo getDiscussionOptionsDropdown();
writeAdminCheck();

echo '</div>';

echo '<h1>'.$this->data('Discussion.Name').'</h1>';

echo "</div>\n\n";

$this->fireEvent('AfterDiscussionTitle');
$this->fireEvent('AfterPageTitle');

// Write the initial discussion.
if ($this->data('Page') == 1) {
    include $this->fetchViewLocation('discussion', 'discussion');
    echo '</div>'; // close discussion wrap

    $this->fireEvent('AfterDiscussion');
} else {
    echo '</div>'; // close discussion wrap
}

$guestModule = new GuestModule('', 'plugins/private-discussions');
$guestModule->MessageCode = '';
$guestModule->MessageDefault = '';
echo $guestModule;
