<?php if (!defined('APPLICATION')) exit();
/*!
 * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

if (!function_exists('formatBody')):
    /**
     * Override this function to return the Body as is since it has already been formatted
     *
     * Event argument for $object will be 'Comment' or 'Discussion'.
     *
     * @since 2.1
     * @param DataSet $object Comment or discussion.
     * @return string Parsed body.
     */
    function formatBody($object) {
        Gdn::controller()->fireEvent('BeforeCommentBody');
        $object->FormatBody = $object->Body;
        Gdn::controller()->fireEvent('AfterCommentFormat');

        return $object->FormatBody;
    }
endif;

require_once PATH_APPLICATIONS.'/vanilla/views/discussion/helper_functions.php';
