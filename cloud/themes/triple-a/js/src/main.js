/*!
 * Triple-A - A gaming theme for Vanilla Forums. Build with Bootstrap.
 *
 * @author    Kasper Kronborg Isager <kasper@funktionel.co>
 * @copyright 2014 (c) Kasper Kronborg Isager
 * @license   GPLv2
 */

;(function ($, window, document, undefined) {

  $(function () {
    $('.BoxPromoted').swiper({
      mode: 'horizontal'
    , loop: true
    , autoplay: gdn.getMeta('swiperAutoplay', 5000)

      // Navigation
    , keyboardControl: true

      // Classes
    , wrapperClass: 'DataList'
    , slideClass: 'PromotedGroup'

      // Pagination
    , pagination: '.swiper-pagination'
    , paginationClickable: true
    });
  });

})(window.jQuery, window, document);
