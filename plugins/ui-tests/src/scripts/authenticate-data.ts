import { IPasswordState, IAuthenticatorState, IRequestPasswordState } from "@dashboard/@types/state";
import { LoadStatus, IUserAuthenticator } from "@dashboard/@types/api";

/**
 * Authenticate components stub data.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

// SSO Methods
export const ssoMethodsData1: IUserAuthenticator[] = [
    {
        authenticatorID: "facebook",
        type: "facebook",
        isUnique: true,
        name: "Facebook",
        ui: {
            url: "#",
            buttonName: "Sign in with Facebook",
            photoUrl: "/applications/dashboard/design/images/authenticators/facebook.svg",
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
            photoUrl: "/applications/dashboard/design/images/authenticators/google.svg",
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
            photoUrl: "/applications/dashboard/design/images/authenticators/twitter.svg",
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
            photoUrl: "/applications/dashboard/design/images/authenticators/disqus.svg",
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
            photoUrl: "/applications/dashboard/design/images/authenticators/github.svg",
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
            photoUrl: "/applications/dashboard/design/images/authenticators/linkedin.svg",
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
            photoUrl: "/applications/dashboard/design/images/authenticators/microsoft.svg",
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
            photoUrl: "/applications/dashboard/design/images/authenticators/openid.svg",
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
            photoUrl: "/applications/dashboard/design/images/authenticators/yahoo.svg",
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
            photoUrl: "/applications/dashboard/design/images/authenticators/sign_in.svg",
            backgroundColor: "#0291db",
            foregroundColor: "#fff",
        },
    },
];

// Plausible Errors
export const passwordFormPlausibleErrors: IPasswordState = {
    status: LoadStatus.ERROR,
    error: {
        message: "Global error message.",
        status: 404,
        errors: {
            password: [
                {
                    field: "password",
                    code: "missingField",
                    message: "password is required.",
                    status: 403,
                },
            ],
            username: [
                {
                    field: "username",
                    code: "missingField",
                    message: "username is required.",
                    status: 403,
                },
            ],
        },
    },
};

// Extreme Example for styling
export const passwordFormExtremeTest: IPasswordState = {
    status: LoadStatus.ERROR,
    error: {
        message:
            "Global error message - This is a long message, with some reallllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllllly long words",
        status: 404,
        errors: {
            password: [
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
            username: [
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
        },
    },
};

// Recover Password Tests
export const recoverPasswordErrors: IRequestPasswordState = {
    status: LoadStatus.ERROR,
    error: {
        message: "Global error message",
        status: 403,
        errors: {
            unknown: [],
        },
    },
};
