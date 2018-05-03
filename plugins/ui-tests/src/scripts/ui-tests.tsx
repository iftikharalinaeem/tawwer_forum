import React from "react";
import ReactDOM from "react-dom";
import SSOMethods from "@dashboard/Authenticate/Components/SSOMethods";

// SSO Methods
window.console.log(SSOMethods);

const ssoMethodsData1 = [
    {
        authenticatorID: "facebook",
        type: "facebook",
        isUnique: true,
        name: "Facebook",
        ui: {
            url: "/entry/connect/facebook",
            buttonName: "Sign in with Facebook",
            photoUrl: "https://dev.vanilla.localhost/applications/dashboard/design/images/authenticators/facebook.svg",
            backgroundColor: "#4A70BD",
            foregroundColor: "#fff",
        },
    },
    {
        authenticatorID: "googleplus",
        type: "googleplus",
        isUnique: true,
        name: "GooglePlus",
        ui: {
            url: "/entry/connect/googlePlusAuthRedirect",
            buttonName: "Sign in with Google",
            photoUrl: "https://dev.vanilla.localhost/applications/dashboard/design/images/authenticators/google.svg",
            backgroundColor: "#fff",
            foregroundColor: "#000",
        },
    },
    {
        authenticatorID: "twitter",
        type: "twitter",
        isUnique: true,
        name: "Twitter",
        ui: {
            url: "/entry/connect/twitter",
            buttonName: "Sign in with Twitter",
            photoUrl: "https://dev.vanilla.localhost/applications/dashboard/design/images/authenticators/twitter.svg",
            backgroundColor: "#1DA1F2",
            foregroundColor: "#fff",
        },
    },
];

ReactDOM.render(<SSOMethods ssoMethods={ssoMethodsData1} />, document.getElementById("uitest-signinpage"));
