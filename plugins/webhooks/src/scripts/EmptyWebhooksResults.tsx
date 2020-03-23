/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@vanilla/i18n";

export function EmptyWebhooksResults() {
    return (
        <div className="padded">
            <p>{t("Add a webhook to get started.")}</p>
        </div>
    );
}
