window.RelicLinkSDK = {
    onReady: function () {
        $(document).on("click", "[href*='/entry/signout']", function(ev) {
            ev.preventDefault();
            var logoutURL = $(this).attr("href");
            console.log('LogoutURL : ' + document.location + logoutURL);
            RelicLinkSDK.logOut().then(function() {
                console.log("Returned from logout");
                //$.get(logoutURL).then(function() {
                    console.log("Should be reloading window.");
                    window.location.href = logoutURL;
                //});
            });
        });

        RelicLinkSDK.getSessionStatus().then(function(status){
            var presentAddress = window.location.pathname;
            console.log("PresentAddress: " + presentAddress);
            console.log("LoggedIn: " + status.loggedIn);
            if (!status.loggedIn && presentAddress.indexOf('/entry/signin') === -1) {
                window.location.href = $("[href$='/entry/signin']").attr('href');
            }
        });
    }
};

