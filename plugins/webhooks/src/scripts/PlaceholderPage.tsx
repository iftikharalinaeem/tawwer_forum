/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState } from "react";
import { useParams } from "react-router";
import { BrowserRouter } from "react-router-dom";
import { t } from "@vanilla/i18n";
import { DashboardHeaderBlock } from "@dashboard/components/DashboardHeaderBlock";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import Loader from "@library/loaders/Loader";

export default function PlaceHolderPage() {
    const params = useParams<{
        // Types of the params from your route match.
        // All parameters come from query so they will be strings.
        // Be sure to convert numbers/booleans/etc.
    }>();

    const data = state.webhooks;

    const toggleButtonRef = React.createRef<HTMLButtonElement>();

    // if (!webhooks.data) {
    //     return <Loader />;
    // }

    return (
        <BrowserRouter>
            <DashboardHeaderBlock
                title={t("Webhooks")}
                actionButtons={
                    <Button
                        buttonRef={toggleButtonRef}
                        baseClass={ButtonTypes.DASHBOARD_PRIMARY}
                        onClick={() => console.log("Button click!")}
                    >
                        {t("Add Webhook")}
                    </Button>
                }
            />
            {JSON.stringify(params)}
            {JSON.stringify(data)}
        </BrowserRouter>
    );
}
