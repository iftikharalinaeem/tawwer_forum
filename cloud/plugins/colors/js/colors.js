jQuery(document).ready(function($) {

   $("#Form_Plugins-dot-colors-dot-header").spectrum({
    color: "#025d98",
    flat: false,
    showInput: true,
    showInitial: true,
    showAlpha: true,
    disabled: false,
    localStorageKey: 'spectrum',
    showPalette: false,
    showPaletteOnly: false,
    showSelectionPalette: true,
    clickoutFiresChange: true,
    cancelText: 'Cancel',
    chooseText: 'Pick',
    //className: '',
    preferredFormat: "hex",
    //maxSelectionSize: int
    palette: [],
    //selectionPalette: [],
    
   change: function(color) {
      var color = color.toHexString();
      
      $('#Head').css({
         'background': color
      });
   }

    
   });

   $("#Form_Plugins-dot-colors-dot-body").spectrum({
    color: "#fff",
    flat: false,
    showInput: true,
    showInitial: true,
    showAlpha: true,
    disabled: false,
    localStorageKey: 'spectrum',
    showPalette: false,
    showPaletteOnly: false,
    showSelectionPalette: true,
    clickoutFiresChange: true,
    cancelText: 'Cancel',
    chooseText: 'Pick',
    //className: '',
    preferredFormat: "hex",
    //maxSelectionSize: int
    palette: [],
    //selectionPalette: [string]
    
   change: function(color) {
      var color = color.toHexString();
      
      $('#Body').css({
         'background': color
      });
   },
           
   move: function(color) {
      var color = color.toHexString();
      
      $('#Body').css({
         'background': color
      });   
   }

    
   });
   
   $("#Form_Plugins-dot-colors-dot-panel").spectrum({
    color: "#dbf3fc",
    flat: false,
    showInput: true,
    showInitial: true,
    showAlpha: true,
    disabled: false,
    localStorageKey: 'spectrum',
    showPalette: false,
    showPaletteOnly: false,
    showSelectionPalette: true,
    clickoutFiresChange: true,
    cancelText: 'Cancel',
    chooseText: 'Pick',
    //className: '',
    preferredFormat: "hex",
    //maxSelectionSize: int
    palette: [],
    //selectionPalette: [string]
    
   change: function(color) {
      var color = color.toHexString();
      
      $('#Panel').css({
         'background': color
      });
   },
           
   move: function(color) {
      var color = color.toHexString();
      
      $('#Panel').css({
         'background': color
      });   
   }

    
   });
   
   
   $("#Form_Plugins-dot-colors-dot-footer").spectrum({
    color: "#013975",
    flat: false,
    showInput: true,
    showInitial: true,
    showAlpha: true,
    disabled: false,
    localStorageKey: 'spectrum',
    showPalette: false,
    showPaletteOnly: false,
    showSelectionPalette: true,
    clickoutFiresChange: true,
    cancelText: 'Cancel',
    chooseText: 'Pick',
    //className: '',
    preferredFormat: "hex",
    //maxSelectionSize: int
    palette: [],
    //selectionPalette: [string]
    
   change: function(color) {
      var color = color.toHexString();
      
      $('#Foot').css({
         'background': color
      });
   }

    
   });
   
});



/**
 * Todd, I haven't tested this, but this should be a working example of how 
 * to invoke the colorPicker on each element found in the page, using it as 
 * a jQuery plugin. It would be better to just use the spectrum library itself, 
 * as you would probably want to define default colors, instead of applying 
 * the same defaults to every element.
 */

/*
(function($) {
   $.fn.colorPicker = function(options) {
      
      var settings = $.extend({
         color: "#ffffff",
         flat: false,
         showInput: true,
         showInitial: true,
         showAlpha: true,
         disabled: false,
         localStorageKey: 'spectrum',
         showPalette: false,
         showPaletteOnly: false,
         showSelectionPalette: true,
         clickoutFiresChange: true,
         cancelText: 'Cancel',
         chooseText: 'Pick',
         //className: '',
         preferredFormat: "hex",
         //maxSelectionSize: int
         palette: [],
         //selectionPalette: [],
         change: function(){}
      }, options);
      
      
      return $(this).each(function(i, el) {     
         $(el).spectrum(settings);
      });
   };
}(jQuery));


jQuery(document).ready(function($) {
   $('.color').colorPicker();
});

*/