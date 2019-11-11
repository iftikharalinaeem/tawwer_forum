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
    // Inject Firebase generated buttons into the page.
    var useFirebaseUI = "<?php echo $sender->data('UseFirebaseUI') ?>";
    // Initiate Firebase JS to detect if a user is logged in on a Firebase App,
    // not necessarily using Firebase generated buttons to connect.
    var autoDetectFirebaseUser = "<?php echo $sender->data('AutoDetectFirebaseUser') ?>";

    function initFirebaseDetection() {
        var targetUrl = "<?php echo Gdn::request()->get('Target') ?>";
        var debug = "<?php echo $sender->data('DebugJavascript') ?>";
        var stashID = '';

        if (debug) {
            console.debug('useFirebaseUI', useFirebaseUI);
        }
        // Initialize Firebase
        var config = {
            apiKey: "<?php echo $sender->data('APIKey') ?>",
            authDomain: "<?php echo $sender->data('AuthDomain') ?>"
        };

        var fireBaseApp = firebase.initializeApp(config);

        fireBaseApp.auth().onAuthStateChanged(function (user) {
            if (debug) {
                console.debug('user', user);
            }

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
                        // Get the Profile is stashed, get the stashID and pass it on to the connect script.
                        var stashID = result.stashID;
                        if (!stashID && debug) {
                            console.debug('StashID was expected but not found.');
                        }

                        if (targetUrl) {
                            var target = targetUrl;
                        } else {
                            var target = window.location.toString();
                        }

                        if (target.indexOf('?') != -1) {
                            target += '&bc='+new Date().getTime();
                        } else {
                            target += '?bc='+new Date().getTime();
                        }

                        var redirectUri = '/entry/connect/firebase?target='+encodeURIComponent(target)+'&stashID='+stashID;

                        if (debug) {
                            console.debug('Entry Connect Redirect: '+ redirectUri)
                        }
                        if (stashID) {
                            $('#firebaseui-auth-container').html('<?php echo $sender->data('RedirectMessage'); ?>');
                            window.location = redirectUri;
                            return;
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

            if ((!user || !stashID) && useFirebaseUI) {
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
        var debug = "<?php echo $sender->data('DebugJavascript') ?>";

        if (autoDetectFirebaseUser || useFirebaseUI) {
            initFirebaseDetection();
        }

        $('.link-signout').on('click', function() {
            var logoutURL = '';
            if ($(this).attr('href')) {
                logoutURL = $(this).attr('href');
            } else {
                logoutURL = $(this).children('a').attr('href');
            }

            // Initialize Firebase
            var config = {
                apiKey: "<?php echo $sender->data('APIKey') ?>",
                authDomain: "<?php echo $sender->data('AuthDomain') ?>"
            };
            var fireBaseApp = firebase.initializeApp(config);
            fireBaseApp.auth().signOut().then(function() {
                if (debug) {
                    console.debug('Signing out redirecting to '+logoutURL);
                }
                window.location = logoutURL;
            }).catch(function(error) {
                // An error happened.
                if (debug) {
                    console.debug('Signing out failed', error);
                }
            });
        });
    })
</script>
