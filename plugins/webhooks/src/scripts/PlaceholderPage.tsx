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
import { useWebhookActions } from "@webhooks/WebhookActions";
import { WebhookReducer, IWebhookState } from "@webhooks/WebhookReducer";
import { registerReducer } from "@library/redux/reducerRegistry";
import { useSelector } from "react-redux";

registerReducer("webhooks", WebhookReducer);

// export function useRoles() {
//     const { getAll } = useWebhookActions();
//     const webhooks = useSelector((state: IWebhookStoreState) => state.webhooks.data);
//
//     useEffect(() => {
//         if (data.status === LoadStatus.PENDING) {
//             void getAll();
//         }
//     }, [getAll, data]);
//
//     return data;
// }

export default function PlaceHolderPage() {
    const params = useParams<{
        // Types of the params from your route match.
        // All parameters come from query so they will be strings.
        // Be sure to convert numbers/booleans/etc.
    }>();

    const webhooks = useWebhookActions();
    const webhookstest1 = webhooks.getAll();
    const webhookstest = useSelector((state: IWebhookState) => state.webhooks);
    console.log("webhookstest1");
    console.log(webhookstest1);
    console.log("webhookstest");
    console.log(webhookstest);

    const selector = useSelector((state: IWebhookState) => state.webhooks);

    const toggleButtonRef = React.createRef<HTMLButtonElement>();

    if (!webhookstest1) {
        return <Loader />;
    }

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
            {JSON.stringify(webhookstest.data)}
        </BrowserRouter>
    );
}
