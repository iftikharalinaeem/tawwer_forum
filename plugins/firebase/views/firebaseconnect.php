<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Connecting with Firebase</title>

    <script src="https://www.gstatic.com/firebasejs/4.9.0/firebase.js"></script>
    <script>
        // Initialize Firebase
        var config = {
            apiKey: "<?php c('firebase.ApiKey', 'AIzaSyABfLkOCaR15I9RHQ7dPvLXrV-xbsz7jtA') ?>",
            authDomain: "<?php c('firebase.authDomain', 'sincere-hearth-93716.firebaseapp.com') ?>"
        };
        firebase.initializeApp(config);
    </script>
</head>
<body>
<script>
    firebase.auth().onAuthStateChanged(function (user) {
        if (user) {
            console.log(user);
            document.body.innerHTML = '<h1>Welcome to our website '+ user.displayName+'!</h1>';
            // TODO Make an ajax call to or post to an entry connect script to be made.
        } else {
            document.body.innerHTML = '<h1>You don\'t exist!</h1>';
        }
        
    });
</script>
</body>
</html>
