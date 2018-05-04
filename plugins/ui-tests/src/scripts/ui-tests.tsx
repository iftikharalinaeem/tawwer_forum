import React from "react";
import ReactDOM from "react-dom";
import SSOMethods from "@dashboard/Authenticate/Components/SSOMethods";
import PasswordForm from "@dashboard/Authenticate/Components/PasswordForm";
import { getMeta } from "@core/application";
import { HashRouter } from "react-router-dom";

const basePath = getMeta("context.basePath", "");

// SSO Methods
const ssoMethodsData1 = [
    {
        authenticatorID: "facebook",
        type: "facebook",
        isUnique: true,
        name: "Facebook",
        ui: {
            url: "#",
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
            url: "#",
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
            url: "#",
            buttonName: "Sign in with Twitter",
            photoUrl: "https://dev.vanilla.localhost/applications/dashboard/design/images/authenticators/twitter.svg",
            backgroundColor: "#1DA1F2",
            foregroundColor: "#fff",
        },
    },
    {
        authenticatorID: "disqus",
        type: "disqus",
        isUnique: true,
        name: "Disqus",
        ui: {
            url: "#",
            buttonName: "Sign in with Disqus",
            photoUrl: "https://dev.vanilla.localhost/applications/dashboard/design/images/authenticators/disqus.svg",
            backgroundColor: "#35A9FF",
            foregroundColor: "#fff",
        },
    },
    {
        authenticatorID: "github",
        type: "github",
        isUnique: true,
        name: "Github",
        ui: {
            url: "#",
            buttonName: "Sign in with Github",
            photoUrl: "https://dev.vanilla.localhost/applications/dashboard/design/images/authenticators/github.svg",
            backgroundColor: "#fff",
            foregroundColor: "#000",
        },
    },
    {
        authenticatorID: "linkedin",
        type: "linkedin",
        isUnique: true,
        name: "LinkedIn",
        ui: {
            url: "#",
            buttonName: "Sign in with LinkedIn",
            photoUrl: "https://dev.vanilla.localhost/applications/dashboard/design/images/authenticators/linkedin.svg",
            backgroundColor: "#0077B5",
            foregroundColor: "#fff",
        },
    },
    {
        authenticatorID: "microsoft",
        type: "microsoft",
        isUnique: true,
        name: "Microsoft",
        ui: {
            url: "#",
            buttonName: "Sign in with Microsoft",
            photoUrl: "https://dev.vanilla.localhost/applications/dashboard/design/images/authenticators/microsoft.svg",
            backgroundColor: "#fff",
            foregroundColor: "#000",
        },
    },
    {
        authenticatorID: "openid",
        type: "openid",
        isUnique: true,
        name: "OpenID",
        ui: {
            url: "#",
            buttonName: "Sign in with OpenID",
            photoUrl: "https://dev.vanilla.localhost/applications/dashboard/design/images/authenticators/openid.svg",
            backgroundColor: "#F8941C",
            foregroundColor: "#fff",
        },
    },
    {
        authenticatorID: "yahoo",
        type: "yahoo",
        isUnique: true,
        name: "Yahoo",
        ui: {
            url: "#",
            buttonName: "Sign in with Yahoo",
            photoUrl: "https://dev.vanilla.localhost/applications/dashboard/design/images/authenticators/yahoo.svg",
            backgroundColor: "#40008F",
            foregroundColor: "#fff",
        },
    },
    {
        authenticatorID: "default",
        type: "default",
        isUnique: true,
        name: "Default Logo",
        ui: {
            url: "#",
            buttonName: "Fallback Styles",
            photoUrl: "https://dev.vanilla.localhost/applications/dashboard/design/images/authenticators/sign_in.svg",
            backgroundColor: "#0291db",
            foregroundColor: "#fff",
        },
    },
];
ReactDOM.render(<SSOMethods ssoMethods={ssoMethodsData1} />, document.getElementById("uitest-ssomethods"));

// Plausible Errors
const passwordFormPasswordErrors = {
    globalError: "Global error message.",
    passwordErrors: [
        {
            field: "password",
            code: "missingField",
            message: "password is required.",
        },
    ],
    usernameErrors: [
        {
            field: "username",
            code: "missingField",
            message: "username is required.",
        },
    ],
};

ReactDOM.render(
    <HashRouter basename={basePath}>
        <PasswordForm {...passwordFormPasswordErrors} />
    </HashRouter>,
    document.getElementById("uitest-password-fields"),
);

// uitest-password-fields-unreasonable
const passwordFormPasswordErrors = {
    globalError:
        "Global error message - This is a long message, with some reallllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllly long words",
    passwordErrors: [
        {
            field: "password",
            code: "missingField",
            message: "password is required.",
        },
        {
            field: "password",
            code: "missingField",
            message:
                "ReallyLongTextWithoutAnySpacesReallyLongTextWithoutAnySpacesReallyLongTextWithoutAnySpacesReallyLongTextWithoutAnySpacesReallyLongTextWithoutAnySpaces",
        },
        {
            field: "password",
            code: "missingField",
            message: "Testing Multiple Errors 1",
        },
        {
            field: "password",
            code: "missingField",
            message:
                "Testing Multiple Errors 2sting Multiple Errors 2sting Multiple Errors 2sting Multiple Errors 2sting Multiple Errors 2Testing Multiple Errors 2sting Multiple Errors 2sting Multiple Errors 2sting Multiple Errors 2sting Multiple Errors 2",
        },
    ],
    usernameErrors: [
        {
            field: "username",
            code: "missingField",
            message: "username is required.",
        },
        {
            field: "username",
            code: "missingField",
            message:
                "ReallyLongTextWithoutAnySpacesReallyLongTextWithoutAnySpacesReallyLongTextWithoutAnySpacesReallyLongTextWithoutAnySpacesReallyLongTextWithoutAnySpaces",
        },
        {
            field: "username",
            code: "missingField",
            message: "Testing Multiple Errors 1",
        },
        {
            field: "username",
            code: "missingField",
            message:
                "Testing Multiple Errors 2sting Multiple Errors 2sting Multiple Errors 2sting Multiple Errors 2sting Multiple Errors 2Testing Multiple Errors 2sting Multiple Errors 2sting Multiple Errors 2sting Multiple Errors 2sting Multiple Errors 2",
        },
    ],
};

ReactDOM.render(
    <HashRouter basename={basePath}>
        <PasswordForm {...passwordFormPasswordErrors} />
    </HashRouter>,
    document.getElementById("uitest-password-fields-unreasonable"),
);
