var currentHeight = null,
    containerPostMessage = null;

if ("postMessage" in parent) {
    containerPostMessage = function(message, target) {
        return parent.postMessage(message, target);
    }
} else {
    var messages = [];
    containerPostMessage = function(message, target) {
        messages.push(message);
    }
    
    function messageUrl(message) {
        var id = Math.floor(Math.random() * 10000);
        return "http://vanillaforums.com" + "#poll:" + id + ":" + message;
    }
    
    function setMessage() {
        if (messages.length == 0)
            return;

        var message = messages.splice(0, 1)[0];
        document.getElementById('messageFrame').src = messageUrl(message);
    }
    
    $(function() {
        var body = document.getElementsByTagName("body")[0],
            messageIframe = document.createElement("iframe");
        messageIframe.id = "messageFrame";
        messageIframe.name = "messageFrame";
        messageIframe.src = msgFrameURL('');
        messageIframe.style.display = "none";
        body.appendChild(messageIframe);

        setMessage();
        setInterval(setMessage, 300);
    });
}

function setHeight() {
    var newHeight = document.body.offsetHeight;
    if (newHeight != currentHeight) {
        currentHeight = newHeight;
        containerPostMessage('height:'+currentHeight, '*');
    }
}

$(function() {
    setHeight();
    setInterval(setHeight, 300);
    
    // Define the webroot & path without domain
    var webroot = gdn.definition('WebRoot'),
        pathroot = gdn.definition('UrlFormat').replace('/{Path}', '').replace('{Path}', '');
    
    // hijack all anchors to see if they should go to "top" or be within the embed (ie. are they in Vanilla or not?)
    $('a').livequery(function() {
        var href = $(this).attr('href'),
            isHttp = href.substr(0, 7) == 'http://';
            
        if (isHttp && href.substr(0, webroot.length) != webroot) {
            $(this).attr('target', '_blank');
        } else {
            // Strip the path from the root folder of the app
            var path = isHttp ? href.substr(webroot.length) : href.substr(pathroot.length);
            var hashIndex = path.indexOf('#');
            var hash = '';
            if (hashIndex > -1) {
                hash = path.substr(hashIndex);
                path = path.substr(0, hashIndex);
            }        
            var concat = path.indexOf('?') > -1 ? '&' : '?';
            $(this).attr('href', pathroot + path + concat + 'DeliveryType=EMBED' + hash);
        }
    });
    $('a').live('click', function() {
        var path = $(this).attr('href');
        var isHttp = path.substr(0, 7) == 'http://';
        if (!isHttp) {
            path = path.substr(pathroot.length).replace('?DeliveryType=EMBED', '').replace('&DeliveryType=EMBED', '');
            containerPostMessage('location:' + path, '*');
            parent.window.frames[0].location.replace($(this).attr('href'));
            return false;
        }
        return true;
    });
});
$(window).unload(function() { containerPostMessage('unload', '*'); });