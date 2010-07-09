var QuotesPlugin = {
   
   Quote: function(QuotedElement) {
      QuotesPlugin.GetQuoteData(QuotedElement);
   },
   
   QuoteResponse: function(Data, Status, XHR) {
      
      Data = jQuery.parseJSON(Data);
      if (Data.Quote.status == 'failed' || !Data) {
         if (Data && Data.Quote.selector)
            QuotesPlugin.RemoveSpinner(Data.Quote.selector);
         return;
      }
      
      switch (Data.Quote.format) {
         case 'Html':   // HTML
            var Append = '<blockquote rel="'+Data.Quote.authorname+'">'+Data.Quote.body+'</blockquote>'+"\n";
            break;
            
         case 'BBCode':
            var Append = '[quote="'+Data.Quote.authorname+'"]'+Data.Quote.body+'[/quote]'+"\n";
            break;
         
         case 'Display':
         case 'Text':   // Plain
            var Append = ' > '+Data.Quote.authorname+" said:\n";
            Append = Append+' > '+Data.Quote.body+"\n";
            break;
            
         default:
            var Append = '';
            return;
      
      }
      
      $('textarea#Form_Body').val($('textarea#Form_Body').val() + Append);
   },
   
   AddSpinner: function(QuotedElement) {
      
   },
   
   RemoveSpinner: function(QuotedElement) {
      
   },
   
   GetQuoteData: function(QuotedElement) {
      if (!$('#'+QuotedElement)) return;
      QuotesPlugin.AddSpinner(QuotedElement);
      var QuotebackURL = gdn.definition('WebRoot')+'plugin/quotes/getquote/'+QuotedElement;
      jQuery.ajax({
         url: QuotebackURL,
         type: 'GET',
         success: QuotesPlugin.QuoteResponse
      });
   }

}