/**
 * Initializes the token input for the invite form in a popup.
 *
 * @type {{start: Function}}
 */
var userTokens = {
  start: function() {
    var form = $('.Tokens-User');
    if (form.length) {
      var author = form.val();
      if (author && author.length) {
        author = author.split(",");
        for (i = 0; i < author.length; i++) {
          author[i] = {id: i, name: author[i]};
        }
      } else {
        author = [];
      }

      form.tokenInput(gdn.url('/user/tagsearch'), {
        hintText: gdn.definition("TagHint", "Start to type..."),
        tokenValue: 'name',
        searchingText: '', // search text gives flickery ux, don't like
        searchDelay: 300,
        minChars: 1,
        maxLength: 25,
        prePopulate: author,
        animateDropdown: false
      });
    }
  }
}

jQuery(document).ready(function($) {
  userTokens.start();

  $('a.InviteMembersLink').popup({
    afterLoad: userTokens.start
  });
});

