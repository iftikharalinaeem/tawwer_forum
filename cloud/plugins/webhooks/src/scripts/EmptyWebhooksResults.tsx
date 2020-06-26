/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
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
