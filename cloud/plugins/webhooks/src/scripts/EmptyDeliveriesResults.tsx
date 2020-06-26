/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { t } from "@vanilla/i18n";

export function EmptyDeliveriesResults() {
    return (
        <div className="padded">
            <p>{t("There are no deliveries for this webhook.")}</p>
        </div>
    );
}
