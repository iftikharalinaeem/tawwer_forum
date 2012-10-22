jQuery(document).ready(function($) {
   var colorPicker = null;
   
   var getRGB = function(c) {
      var parts = c.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);

      if (parts != null)
         return {r: parts[1], g: parts[2], b: parts[3]};

      return $.HexToRGB(c);
   };

   var getNewColor = function(baseCurr, baseOrig, newOrig) {
      baseCurr = getRGB(baseCurr);
      baseOrig = getRGB(baseOrig);
      newOrig = getRGB(newOrig);
      //var isGrey = baseOrig.r == baseOrig.g && baseOrig.g == baseOrig.b;

      // Convert the colors to hsb.
      baseCurr = $.RGBToHSB(baseCurr);
      baseOrig = $.RGBToHSB(baseOrig);
      newOrig = $.RGBToHSB(newOrig);

      var newCurr = {h: 0, s: 0, b: 0};

      // Transform the hue.
      newCurr.h = newOrig.h + (baseCurr.h - baseOrig.h);
      if (newCurr.h < 0)
         newCurr.h = 360 + newCurr.h;
      else if (newCurr.h > 360)
         newCurr.h = newCurr.h - 360;

      // Transform the saturation.
      newCurr.s = newOrig.s + (baseCurr.s - baseOrig.s);
      if (newCurr.s < 0)
         newCurr.s = 0;
      if (newCurr.s > 100)
         newCurr.s = 100;

      // Transform the brightness.
      newCurr.b = newOrig.b;
      newCurr.b = newOrig.b + (baseCurr.b - baseCurr.b);
      if (newCurr.b < 0)
         newCurr.b = 0;
      if (newCurr.b > 100)
         newCurr.b = 100;

      newCurr = $.HSBToRGB(newCurr);
      return newCurr;
   }


   var changeColors = function(baseCurr, baseOrig, group) {
      // Loop through all of the swatches and change them.
      $(".ColorPickerPlugin .ColorGroup" + group + " .Swatch").each(function(index, element) {
         // Get the original color of the swatch.
         var newOrig = $(element).attr("orig");
         var newCurr = getNewColor(baseCurr, baseOrig, newOrig);
         $(element).css("background-color", "#" + $.RGBToHex(newCurr));
      });

   };


   // Move all of the color picker elements to the end of the document.
   $(".ColorPickerPlugin").detach().appendTo('body');

   // Set the height of the stub so the color picker doesn't obscure anything on the page.
   $(".ColorPickerPlugin .Stub").height($(".ColorPickerPlugin .ColorPicker").height());

   // Add hover effects to the swatches.
   $(".ColorPickerPlugin .BigSwatch,.ColorPickerPlugin .Swatch").hover(
      function() {
         $(this).addClass("Hover");
      },
      function() {
         $(this).removeClass("Hover");
      });

   // Add the color picker UI.
   var currentSwatch = null;
   $(".ColorPickerPlugin .BigSwatch").ColorPicker({
      onBeforeShow: function() {
         currentSwatch = this;
         var color = $(this).css("background-color");
         color = getRGB(color);
         $(this).ColorPickerSetColor(color);
      },
      onChange: function(hsb, hex, rgb) {
         // Get the original color.
         var origColor = $(currentSwatch).attr("orig");

         // Set the background of the current swatch.
         var color = "rgb(" + rgb.r + "," + rgb.g + "," + rgb.b + ")";
         $(currentSwatch).css("background-color", color);
         
         // Get the group.
         var group = $(currentSwatch).closest(".ColorGroup").attr("group");
         changeColors(hex, origColor, group);
      }
   });
});