<script src="https://www.gstatic.com/firebasejs/4.9.0/firebase.js"></script>
<script src="https://cdn.firebase.com/libs/firebaseui/2.5.1/firebaseui.js"></script>
<link type="text/css" rel="stylesheet" href="https://cdn.firebase.com/libs/firebaseui/2.5.1/firebaseui.css" />
<style type="text/css">
    .GuestBox .P {
        display:none
    }
</style>

<script>
    var targetUrl = '<?php echo Gdn::request()->get('Target') ?>';
    // Initialize Firebase
    var config = {
        apiKey: "<?php echo $sender->data('APIKey') ?>",
        authDomain: "<?php echo $sender->data('AuthDomain') ?>"
    };
    firebase.initializeApp(config);

    firebase.auth().onAuthStateChanged(function (user) {
        if (user && !gdn.getMeta('SignedIn')) {
            console.log('User Detected: '+user.displayName);
            console.log('User Not Logged in:'+gdn.getMeta('SignedIn'));
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
                    console.log('passed target '+targetUrl)
                    if (targetUrl) {
                        var target = targetUrl;
                    } else {
                        var target = encodeURIComponent(window.location);
                    }
                    console.log('call back: '+ '/entry/connect/firebase?target='+target)
                    window.location = '/entry/connect/firebase?target='+target;
                },
                error: function(msg) {
                    console.log('There has been an error calling firebase!');
                    console.log(msg);
                }
            });
        }
        if (!user) {
            // Use the Firebase
            console.log('Has No User');
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

            // Initialize the FirebaseUI Widget using Firebase.
            var ui = new firebaseui.auth.AuthUI(firebase.auth());
            // The start method will wait until the DOM is loaded.
            ui.start('#firebaseui-auth-container', uiConfig);
        }
    });

</script>
