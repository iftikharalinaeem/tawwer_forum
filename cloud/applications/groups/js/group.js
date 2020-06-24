/**
 * Initializes the token input for the invite form in a popup.
 *
 * @type {{start: Function}}
 */
var userTokens = {
  start: function() {
    var input = $('.Tokens-User');
    var translate = window.gdn.translate;
    if (input.length) {
      var author = input.val();
      if (author && author.length) {
        author = author.split(",");
        for (i = 0; i < author.length; i++) {
          author[i] = {id: i, name: author[i]};
        }
      } else {
        author = [];
      }

      input.tokenInput(gdn.url('/user/tagsearch'), {
        hintText: translate("TagHint", "Start to type..."),
        tokenValue: 'name',
        searchingText: '', // search text gives flickery ux, don't like
        searchDelay: 300,
        minChars: 1,
        maxLength: 25,
        prePopulate: author,
        animateDropdown: false,
        allowTabOut: true,
        ariaLabel: translate("Invite"),
      });
    }
  }
}

jQuery(function($) {
  userTokens.start();

  $('.js-invite-members').popup({
    afterLoad: userTokens.start
  });
});

