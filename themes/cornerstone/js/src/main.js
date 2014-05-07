/*!
 * Cornerstone
 *
 * @author    Kasper Kronborg Isager <kasper@vanillaforums.com>
 * @copyright 2014 (c) Vanilla Forums Inc.
 * @license   GPLv3
 */

;(function ($, window, document, undefined) {

  $(document).on('ready ajaxSuccess', function () {
    // Initialize iCheck
    $('input').iCheck();
  });

})(jQuery, window, document);
