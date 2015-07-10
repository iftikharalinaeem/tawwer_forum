/**
 * Created by patrick on 15-07-03.
 */

$(document).ready(function() {

  var intercomIOMeta = gdn.getMeta('intercomIO');

  window.intercomSettings = {
    name: intercomIOMeta['name'],
    email: intercomIOMeta['email'],
    user_id: intercomIOMeta['siteID'],
    created_at: intercomIOMeta['starttime'],
    app_id: intercomIOMeta['app_id']
  };

  var trackingPages = intercomIOMeta['trackingPages'].split(",");
  //console.log(trackingPages);

  $("body").on('click.link', '#Panel a', function(e) {

    var linkLabel = $(this).text();
    if($.inArray(linkLabel, trackingPages) === -1) {
      return;
    }

    var metadata = {
      controller : intercomIOMeta['events'][0][0],
      event : intercomIOMeta['events'][1][0],
      url : window.location.pathname
    };
    console.log(metadata);
    var eventName = linkLabel + ' (Nav)';
    Intercom('trackEvent', eventName, metadata);

  });


  $("body").on('click.link', '#Content a, #Content input[type="submit"], #Content input[type="file"]', function(e) {
    var controller = intercomIOMeta['events'][0][0];
    var event = intercomIOMeta['events'][1][0];
    console.log(controller + " and " + event);
    var linkLabel = $(this).text();

    if(!linkLabel) {
      linkLabel = $(this).attr("name");
    }

    if(!linkLabel) {
      linkLabel = "Click";
    }

    var linkName = $("#Panel a[href$='" + window.location.pathname + "']").text();

    if($.inArray(linkName, trackingPages) === -1) {
      return;
    }

    var metadata = {
      controller : intercomIOMeta['events'][0][0],
      event : intercomIOMeta['events'][1][0],
      url : window.location.pathname
    }

    var eventName = linkName + " " + linkLabel;
    console.log('Tracking In Page Event: ' + eventName);
    Intercom('trackEvent', eventName, metadata);

  });

  (function(){var w=window;var ic=w.Intercom;if(typeof ic==="function"){ic('reattach_activator');ic('update',intercomSettings);}else{var d=document;var i=function(){i.c(arguments)};i.q=[];i.c=function(args){i.q.push(args)};w.Intercom=i;function l(){var s=d.createElement('script');s.type='text/javascript';s.async=true;s.src='https://widget.intercom.io/widget/kbok1iui';var x=d.getElementsByTagName('script')[0];x.parentNode.insertBefore(s,x);}if(w.attachEvent){w.attachEvent('onload',l);}else{w.addEventListener('load',l,false);}}})();

});
