<script src="https://www.gstatic.com/firebasejs/4.9.0/firebase.js"></script>
<script src="https://cdn.firebase.com/libs/firebaseui/2.5.1/firebaseui.js"></script>
<link type="text/css" rel="stylesheet" href="https://cdn.firebase.com/libs/firebaseui/2.5.1/firebaseui.css" />

<script>
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
                    console.log('Has created a profile in stash. Redirecting to '+'/entry/connect/firebase?target='+encodeURIComponent(window.location))
                    window.location = "/entry/connect/firebase?target="+encodeURIComponent(window.location);
                },
                error: function(msg) {
                    console.log('There has been an error calling firebase!');
                    console.log(msg);
                }
            });
        }
        if(!user) {
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
                if (c('firebase.tosUrl')) {
                    // Terms of service url.
                    echo 'tosUrl: '.c('firebase.tosUrl').',
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

    $(function() {
        $('.firebase-connect-link').on('click', function() {
            var authType = $(this).attr('data-authtype');
            switch (authType) {
                case 'googleauthprovider':
                    provider = new firebase.auth.GoogleAuthProvider()
                    break;
                case 'facebookauthprovider':
                    provider = new firebase.auth.FacebookAuthProvider()
                    break;
                case 'githubauthprovider':
                    provider = new firebase.auth.GithubAuthProvider()
                    break;
                case 'twitterauthprovider':
                    provider = new firebase.auth.TwitterAuthProvider()
                    break;
                case 'emailauthprovider':
                    provider = new firebase.auth.sendSignInLinkToEmail()
                    break;
            }

            firebase.auth().signInWithRedirect(provider);
        });
    });
</script>
