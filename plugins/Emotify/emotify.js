/**
 * This is a modified version of Ben Alman's dual GPL/MIT licensed "Javascript
 * Emotify" jQuery plugin.
 */

// About: License
// Copyright (c) 2009 "Cowboy" Ben Alman,
// Dual licensed under the MIT and GPL licenses.
// http://benalman.com/about/license/

window.emotify = (function(){
  var emotify,
    EMOTICON_RE,
    emoticons = {},
    lookup = [];
    
  emotify = function(txt, callback) {
    callback = callback || function(cssSuffix, smiley) {
      return '<span class="Emoticon Emoticon' + cssSuffix + '"><span>' + smiley + '</span></span>';
    };
    
    return txt.replace(EMOTICON_RE, function(a, b, text) {
      var i = 0,
        smiley = text,
        e = emoticons[text];
      
      // If smiley matches on manual regexp, reverse-lookup the smiley.
      if (!e) {
        while (i < lookup.length && !lookup[i].regexp.test(text)) { i++ };
        smiley = lookup[i].name;
        e = emoticons[smiley];
      }
      
      // If the smiley was found, return HTML, otherwise the original search string
      return e ? (b + callback(e[0], smiley)) : a;
    });
  };
  
  emotify.emoticons = function() {
    var args = Array.prototype.slice.call( arguments ),
      replace_all = typeof args[0] === 'boolean' ? args.shift() : false,
      smilies = args[0],
      e,
      arr = [],
      alts,
      i,
      regexp_str;
    
    if (smilies) {
      if (replace_all) {
        emoticons = {};
        lookup = [];
      }

      for (e in smilies) {
        emoticons[e] = smilies[e];
        emoticons[e][0] = emoticons[e][0];
      }
      
      // Generate the smiley-match regexp.
      for (e in emoticons) {
        if (emoticons[e].length > 1) {
          // Generate regexp from smiley and alternates.
          alts = emoticons[e].slice(1).concat(e);
          i = alts.length
          while (i--) {
            alts[i] = alts[i].replace(/(\W)/g, '\\$1');
          }
          
          regexp_str = alts.join('|');
          
          // Manual regexp, map regexp back to smiley so we can reverse-match.
          lookup.push({ name: e, regexp: new RegExp( '^' + regexp_str + '$' ) });
        } else {
          // Generate regexp from smiley.
          regexp_str = e.replace(/(\W)/g, '\\$1');
        }
        
        arr.push(regexp_str);
      }
      
      EMOTICON_RE = new RegExp('(^|\\s)(' + arr.join('|') + ')(?=(?:$|\\s))', 'g');
    }
    
    return emoticons;
  };
  
  return emotify;
  
})();

$(function(){
  emotify.emoticons({
/*  smiley, css_suffix, alternate_smileys */
    ":)":    ["1", ":-)"],
    ":(":    ["2", ":-("],
    ";)":    ["3", ";-)"],
    ":D":    ["4", ":-D"],
    ";;)":   ["5"],
    ">:D<":  ["6"],
    ":-/":   ["7", ":/"],
    ":x":    ["8", ":X"],
    ":\">":  ["9"],
    ":P":    ["10", ":p", ":-p", ":-P"],
    ":-*":   ["11", ":*"],
    "=((":   ["12"],
    ":-O":   ["13", ":O"],
    "X(":    ["14"],
    ":>":    ["15"],
    "B-)":   ["16"],
    ":-S":   ["17"],
    "#:-S":  ["18", "#:-s"],
    ">:)":   ["19", ">:-)"],
    ":((":   ["20", ":-((", ":'(", ":'-("],
    ":))":   ["21", ":-))"],
    ":|":    ["22", ":-|"],
    "/:)":   ["23", "/:-)"],
    "=))":   ["24"],
    "O:-)":  ["25", "O:)"],
    ":-B":   ["26"],
    "=;":    ["27"],
    "I-)":   ["28"],
    "8-|":   ["29"],
    "L-)":   ["30"],
    ":-&":   ["31"],
    ":-$":   ["32"],
    "[-(":   ["33"],
    ":O)":   ["34"],
    "8-}":   ["35"],
    "<:-P":  ["36"],
    "(:|":   ["37"],
    "=P~":   ["38"],
    ":-?":   ["39"],
    "#-o":   ["40", "#-O"],
    "=D>":   ["41"],
    ":-SS":  ["42", ":-ss"],
    "@-)":   ["43"],
    ":^o":   ["44"],
    ":-w":   ["45", ":-W"],
    ":-<":   ["46"],
    ">:P":   ["47", ">:p"],
    "<):)":  ["48"],
    ":@)":   ["49"],
    "3:-O":  ["50", "3:-o"],
    ":(|)":  ["51"],
    "~:>":   ["52"],
    "@};-":  ["53"],
    "%%-":   ["54"],
    "**==":  ["55"],
    "(~~)":  ["56"],
    "~O)":   ["57"],
    "*-:)":  ["58"],
    "8-X":   ["59"],
    "=:)":   ["60"],
    ">-)":   ["61"],
    ":-L":   ["62", ":L"],
    "[-O<":  ["63"],
    "$-)":   ["64"],
    ":-\"":  ["65"],
    "b-(":   ["66"],
    ":)>-":  ["67"],
    "[-X":   ["68"],
    "\\:D/": ["69"],
    ">:/":   ["70"],
    ";))":   ["71"],
    "o->":   ["72"],
    "o=>":   ["73"],
    "o-+":   ["74"],
    "(%)":   ["75"],
    ":-@":   ["76"],
    "^:)^":  ["77"],
    ":-j":   ["78"],
    "(*)":   ["79"],
    ":)]":   ["100"],
    ":-c":   ["101"],
    "~X(":   ["102"],
    ":-h":   ["103"],
    ":-t":   ["104"],
    "8->":   ["105"],
    ":-??":  ["106"],
    "%-(":   ["107"],
    ":o3":   ["108"],
    "X_X":   ["109"],
    ":!!":   ["110"],
    "\\m/":  ["111"],
    ":-q":   ["112"],
    ":-bd":  ["113"],
    "^#(^":  ["114"],
    ":bz":   ["115"],
    ":ar!":  ["pirate"],
    "[..]":  ["transformer"]
  });
  
  $('div.Comment div.Message').livequery(function() {
    var html = $(this).html();
    $(this).html(emotify(html));
  });
});
