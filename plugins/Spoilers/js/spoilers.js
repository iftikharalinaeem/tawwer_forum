var SpoilersPlugin = {
   FindAndReplace: function() {
      $('div.UserSpoiler').each(function(i, el) {
         var SpoilerTitle = $(el).find('div.SpoilerTitle');
         var SpoilerButton = document.createElement('input');
         SpoilerButton.type = 'button';
         SpoilerButton.value = 'show';
         $(SpoilerButton).click(jQuery.proxy(function(event){
            console.log(this);
            $(this).find('div.SpoilerText').css('display','block');
         },el));
         SpoilerTitle.append(SpoilerButton);
      });
   }
};

jQuery(document).ready(function(){
   SpoilersPlugin.FindAndReplace();
});