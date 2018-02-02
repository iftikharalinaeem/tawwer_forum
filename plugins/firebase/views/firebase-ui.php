<script src="https://www.gstatic.com/firebasejs/4.9.0/firebase.js"></script>
<script>
    // Initialize Firebase
    var config = {
        apiKey: "AIzaSyABfLkOCaR15I9RHQ7dPvLXrV-xbsz7jtA",
        authDomain: "sincere-hearth-93716.firebaseapp.com"
    };
    firebase.initializeApp(config);
</script>
<script src="https://cdn.firebase.com/libs/firebaseui/2.5.1/firebaseui.js"></script>
<link type="text/css" rel="stylesheet" href="https://cdn.firebase.com/libs/firebaseui/2.5.1/firebaseui.css" />
<script type="text/javascript">
    // FirebaseUI config.
    var uiConfig = {
        signInSuccessUrl: 'https://firebasetest.com/vanilla/firebaseconnect',
        signInOptions: [
            // Leave the lines as is for the providers you want to offer your users.
            <?php
                // TODO, Make these default to FALSE
            if (c('firebase.GoogleAuthProvider', true)) {
                echo 'firebase.auth.GoogleAuthProvider.PROVIDER_ID,
                ';
            }
            if (c('firebase.FacebookAuthProvider', true)) {
                echo 'firebase.auth.FacebookAuthProvider.PROVIDER_ID,
                ';
            }
            if (c('firebase.EmailAuthProvider', true)) {
                echo 'firebase.auth.EmailAuthProvider.PROVIDER_ID,
                ';
            }
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
</script>
