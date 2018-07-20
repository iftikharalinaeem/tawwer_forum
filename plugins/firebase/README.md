# Firebase

Found in `plugins/firebase`, this plugin adds Javascript and CSS files from Firebase to allow users to SSO into a forum using a configured Firebase application. Firebase is a platform  developed by Google as a middleware for authenticating against multiple providers. 

There are two 'modes' that this plugin can be used in: with Firebase UI and without. The mode is saved to the Provider. With Firebase UI javascript and CSS files inject buttons into the page on the forum to allow users to log in. Without, just the Firebase Web SDK is injected to detect if a visitor has a valid session initiated on the authentication provider and logs the visitor into the forum.

See [Firebase UI](https://github.com/firebase/FirebaseUI) and [Firebase Web](https://github.com/firebase/firebaseui-web).

### Firebase SSO

- **Summary:** Allow admins to configure OAuth2 authentication against a Firebase SSO application, allow Firebase SDK to detect if a visitor is logged in through the Firebase application and connect him/her to the forum.
- **Use case:** Single Sign On.
- **Description:** 
	- Extends Vanilla's OAuth2 class.
	- Injects Firebase javascript to detect if a visitor is logged in.
	- Optionally injects the Firebase SDK (Firebase UI) into the head of every page when a user is not logged into the Forum.
	- Firebase UI allows users to connect directly by email/password or by third-party providers. The list of third-party providers are: Google, Facebook, Twitter, and GitHub.
	- When SDK detects if the visitor **is** logged in through **Firebase** but **not** logged into the Forum it posts the user's profile data via AJAX to the OAuth2 entry endpoint.
	- The user is then redirected to /entry/connect/firebase where they either create a user on the Forum or connect to an existing user. 
	- If no display name is sent they create one on this page. 
	- If the user is **not** logged in, the SDK prints log in buttons, or presents an email/password form to the page depending on what is configured.
- **Configs set or added:**
    - The log in buttons are hidden using CSS in the firebase-ui view whenever the Firebase UI constrols are present.
    - `Vanilla.SSO.Debug` is used to log data to the EventLog and to log javascript to the browser console. 
    - `Firebase.AutoDetectFirebaseUser` is a flag to tell Firebase to always be listening for visitors who arrive authenticated by the configured Firebase app. Defaults to true. Can be turned off by other plugins that will initiate their own detection. This avoids conflict in the javascript.
- **Events used:**
	- `afterRenderAsset`: Inject Javascript and CSS from Firebase  and Firebase UI to create an interface for users to log in and detect if visitors are logged in.
	- `afterSignInButton`: Inject an HTML element to recieve the UI elements from Firebase.
- **Setup steps:**
    1. Turn on the plugin.
    2. Add a valid API Key and Auth Domain from a functional Firebase application.
    3. Turn on at least one form of log in either by email/password or one of the third-party providers (e.g. Google, Facebook).
- **QA steps:**
    1. Turn on plugin.
    2. Log in through various providers.
    3. Log in through a provider who does not pass a display name to make sure you are given an interface on the connect page to create a disply name.
    4. Go to a discussion page when you are not logged in and click on the link at the bottom of the discussion to log in. It should take you to a Firebase login page. Check that you are returned to the correct Target URL.
