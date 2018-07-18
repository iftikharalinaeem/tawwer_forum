<script src="https://www.gstatic.com/firebasejs/5.0.2/firebase.js"></script>
<?php if ($sender->data('UseFirebaseUI')) : ?>
    <script src="https://cdn.firebase.com/libs/firebaseui/2.5.1/firebaseui.js"></script>
    <link type="text/css" rel="stylesheet" href="https://cdn.firebase.com/libs/firebaseui/2.5.1/firebaseui.css" />
    <style type="text/css">
        .GuestBox .P {
            display:none
        }
    </style>
<?php endif; ?>
<script>
    var useFirebaseUI = "<?php echo $sender->data('UseFirebaseUI') ?>";
    var autoDetectFirebaseUser = "<?php echo $sender->data('autoDetectFirebaseUser') ?>";

    function initFirebaseDetection () {
        var targetUrl = "<?php echo Gdn::request()->get('Target') ?>";
        var debug = "<?php echo $sender->data('DebugJavascript') ?>";

        console.log('debug: '+ debug);
        if (debug) {
            console.debug('useFirebaseUI', useFirebaseUI);
        }
        // Initialize Firebase
        var config = {
            apiKey: "<?php echo $sender->data('APIKey') ?>",
            authDomain: "<?php echo $sender->data('AuthDomain') ?>"
        };
            console.log('There are no apps, initializing the App');
        firebase.initializeApp(config);

        firebase.auth().onAuthStateChanged(function (user) {
            if (user && !gdn.getMeta('SignedIn')) {
                if (debug) {
                    console.debug('User Detected: '+user.displayName);
                    console.debug('User Not Logged in:'+gdn.getMeta('SignedIn'));
                    console.debug('Passed Target '+targetUrl)
                }
                var request = $.ajax({
                    method : "post",
                    url : "/entry/firebase",
                    data : {
                        "displayName": user.displayName,
                        "email": user.email,
                        "emailVerified": user.emailVerified,
                        "phoneNumber": user.phoneNumber,
                        "photoURL": user.photoURL,
                        "uid": user.uid,
                        "accessToken": user.accessToken,
                        "providerData": user.providerData
                    },
                    success: function(result) {
                        if (targetUrl) {
                            var target = targetUrl;
                        } else {
                            var target = encodeURIComponent(window.location);
                        }
                        var redirectUri = '/entry/connect/firebase?target='+target;
                        window.location = redirectUri;
                        if (debug) {
                            console.debug('Entry Connect Redirect: '+ redirectUri)
                        }
                    },
                    error: function(msg) {
                        if (debug) {
                            console.debug('There has been an error calling firebase!');
                            console.debug(msg);
                        }
                    }
                });
            }

            if (!user && useFirebaseUI) {
                // FirebaseUI config.
                var uiConfig = {
                    signInSuccessUrl: window.location,
                    signInOptions: [
                        // Leave the lines as is for the providers you want to offer your users.
                        <?php
                        echo $sender->data('FirebaseAuthProviders');
                        ?>
                    ],
                    <?php
                    if ($sender->data('TermsUrl')) {
                        // Terms of service url.
                        echo 'tosUrl: '.$sender->data('TermsUrl').',
                ';
                    }
                    ?>
                };
                if (debug) {
                    console.debug('No User Detected');
                    console.debug('UIConfig SuccessUrl: '+uiConfig.signInSuccessUrl+', SignInOptions: '+uiConfig.signInOptions);
                }
                // Initialize the FirebaseUI Widget using Firebase.
                var ui = new firebaseui.auth.AuthUI(firebase.auth());
                // The start method will wait until the DOM is loaded.
                ui.start('#firebaseui-auth-container', uiConfig);
            }
        });
    }

    $(function (){
        if (useFirebaseUI || autoDetectFirebaseUser) {
            initFirebaseDetection();
        }
    })
</script>
