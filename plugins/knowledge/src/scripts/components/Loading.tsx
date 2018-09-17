/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { t } from "@library/application";

export default function Loading({ error, pastDelay, retry }) {
    if (error) {
        return (
            <div>
                {t("Error! ")}
                <button onClick={retry}>{t("Retry")}</button>
            </div>
        );
    } else if (pastDelay) {
        return <div>{t("Loading...")}</div>;
    } else {
        return null;
    }
}
