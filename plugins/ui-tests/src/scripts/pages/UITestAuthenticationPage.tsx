/**
 * Page component with stub data for the Authentication pages.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { formatUrl } from "@library/application";
import SSOMethods from "@dashboard/pages/authenticate/components/SSOMethods";
import { PasswordForm } from "@dashboard/pages/authenticate/components/PasswordForm";
import { RecoverPasswordPage } from "@dashboard/pages/recoverPassword/RecoverPasswordPage";
import {
    ssoMethodsData1,
    passwordFormPlausibleErrors,
    recoverPasswordErrors,
    passwordFormExtremeTest,
} from "../authenticate-data";

/* tslint:disable:jsx-use-translation-function */

const noop = (...params) => undefined as any;
export default function UITestAuthenticationPage() {
    return (
        <div>
            <h2>
                <a href={formatUrl("/uitests")}>{"> Back"}</a>
            </h2>
            <h1>Authentication</h1>

            <p>
                <strong>Attention:</strong> This page is intended to test various potential states for React components
                without needing to create endpoints for them. It's also a good spot check when doing CSS changes that
                affect many components. These components may or may not fully work on <em>this</em> page. The check is
                on the hard coded, initial state of the component. Testing the actual component should be on the real
                page.
            </p>

            <h2>
                SSO Methods{" "}
                <a href={formatUrl("/authenticate/signin")} target="_blank">
                    /authenticate/signin
                </a>
            </h2>
            <div className="authenticateUserCol">
                <SSOMethods ssoMethods={ssoMethodsData1} />
            </div>

            <h2>
                Simple Password Form{" "}
                <a href={formatUrl("/authenticate/password")} target="_blank">
                    /authenticate/password
                </a>
            </h2>
            <div className="authenticateUserCol">
                <h4>Plausible Example</h4>
                <PasswordForm passwordState={passwordFormPlausibleErrors} authenticate={noop} />

                <hr />

                <h4>Extreme example (for testing CSS)</h4>
                <PasswordForm passwordState={passwordFormExtremeTest} authenticate={noop} />
            </div>

            <h2>
                Recover Password{" "}
                <a href={formatUrl("/authenticate/recoverpassword")} target="_blank">
                    /authenticate/recoverpassword
                </a>
            </h2>
            <RecoverPasswordPage
                requestPasswordState={recoverPasswordErrors}
                postRequestPassword={noop}
                onNavigateAway={noop}
            />
        </div>
    );
}
