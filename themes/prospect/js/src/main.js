/*!
 * Prospect - A minimal Vanilla theme focused on customer support communities
 *
 * @author    Kasper Kronborg Isager <kasper@vanillaforums.com>
 * @copyright 2014 (c) Vanilla Forums Inc.
 * @license   GPLv3
 */

;(function ($, window, document, undefined) {

  // $(document).on('ifChanged', '.AdminCheck :checkbox', function (e) {
  //   $(e.currentTarget).trigger('click');
  // });

  $(function () {

    // Initialize iCheck
    $('input').icheck();

    $('[data-geopattern]').each(function () {
      var $this = $(this)
        , pattern = GeoPattern.generate($this.data('geopattern'));

      $this.css('background-image', pattern.toDataUrl());
      $this.noisy({
        intensity  : 0.5
      , opacity    : 0.05
      })
    });

  });

})(jQuery, window, document);
