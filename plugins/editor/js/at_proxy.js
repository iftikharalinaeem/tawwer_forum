(function() {
   console.log('load call');


   var editorE = document.body;

   //console.log($(document.body));







      var atCompleteInit = function(editorElement) {

         var emojis = ["smile", "iphone", "girl", "smiley", "heart", "kiss", "copyright", "coffee"];
         var names = ["Jacob", "Isabella", "Ethan", "Emma", "Michael", "Olivia", "Alexander", "Sophia", "William", "Ava", "Joshua", "Emily", "Daniel", "Madison", "Jayden", "Abigail", "Noah", "Chloe", "你好", "你你你"];

         var emojis_list = $.map(emojis, function(value, i) {
           return {'id':i, 'name':value};
         });

         //http://a248.e.akamai.net/assets.github.com/images/icons/emoji/8.png
         $(editorElement)
           .atwho({
             at: "@",
             data: names
           })
           .atwho({
             at: ":",
             tpl: "<li data-value=':${name}:'><img src='http://a248.e.akamai.net/assets.github.com/images/icons/emoji/${name}.png' height='20' width='20'/> ${name} </li>",
             data: emojis_list
           });
      };



      atCompleteInit(editorE);


}());

