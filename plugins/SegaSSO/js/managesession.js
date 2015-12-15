window.RelicLinkSDK = {
    onReady: function () {
        var gdn_session = gdn.getMeta('userLoggedIn');
        var gdn_login_url = gdn.getMeta('loginURL');
        var gdn_logout_url = gdn.getMeta('logoutURL');

        console.log('gdn meta logged in: ' + gdn_session + ', login URL: ' + gdn_login_url + ', logout url: ' + gdn_logout_url);

        RelicLinkSDK.on('logout', function() {
            if (gdn_session && !status.loggedIn) {
                // perform local logout
                window.location.href = gdn_logout_url;
            } else if (status.loggedIn) {
                // perform automatic login
                window.location.href = gdn_login_url;
            }
        });


        RelicLinkSDK.on('logout', function() {
            if (gdn_session) {
                // automatic logout
                window.location.href = gdn_logout_url;
            }
        });

        RelicLinkSDK.on('login', function() {
            if (!gdn_session) {
                //automatic login
                window.location.href = gdn_login_url;
            }
        });

        $(document).on("click", "a[href='" + gdn_logout_url + "']", function(ev) {
            ev.preventDefault();
            RelicLinkSDK.logOut().then(function() {
                window.location.href = gdn_logout_url;
            });
        });
    }
};