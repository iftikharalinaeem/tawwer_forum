(function() {
  (function($) {
    var Wysihtml5SizeMatters;

    Wysihtml5SizeMatters = (function() {
      function Wysihtml5SizeMatters(iframe) {
        this.$iframe = $(iframe);
        this.$body = this.findBody();
        this.addBodyStyles();
        this.setupEvents();
        this.adjustHeight();
      }

      Wysihtml5SizeMatters.prototype.addBodyStyles = function() {
        this.$body.css('overflow', 'hidden');
        return this.$body.css('min-height', 0);
      };

      Wysihtml5SizeMatters.prototype.setupEvents = function() {
        var _this = this;

        return this.$body.on('keyup keydown keypress paste change focus focusin blur hover mouseover mouseenter mouseout select', function() {
          return _this.adjustHeight();
        });
      };

      Wysihtml5SizeMatters.prototype.adjustHeight = function() {
        var height = this.$body.outerHeight() + this.extraBottomSpacing();
        
        if (this.$iframe.css('box-sizing') == 'border-box') {
            height += parseInt(this.$iframe.css('padding-top')) + parseInt(this.$iframe.css('padding-bottom'));
        }
        
        return this.$iframe.css('min-height', height);
      };

      Wysihtml5SizeMatters.prototype.extraBottomSpacing = function() {
        return parseInt(this.$body.css('line-height')) || this.estimateLineHeight();
      };

      Wysihtml5SizeMatters.prototype.estimateLineHeight = function() {
        return parseInt(this.$body.css('font-size')) * 1.14;
      };

      Wysihtml5SizeMatters.prototype.findBody = function() {
        return this.$iframe.contents().find('body');
      };

      return Wysihtml5SizeMatters;

    })();
    return $.fn.wysihtml5_size_matters = function() {
      return this.each(function() {
        var wysihtml5_size_matters;

        wysihtml5_size_matters = $.data(this, 'wysihtml5_size_matters');
        if (!wysihtml5_size_matters) {
          return wysihtml5_size_matters = $.data(this, 'wysihtml5_size_matters', new Wysihtml5SizeMatters(this));
        }
      });
    };
  })($);

}).call(this);