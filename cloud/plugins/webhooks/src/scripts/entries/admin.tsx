/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { Router } from "@vanilla/library/src/scripts/Router";
import { allWebhookRoutes } from "@webhooks/WebhookPageRoutes";
import { addComponent } from "@vanilla/library/src/scripts/utility/componentRegistry";
import { WebhookReducer } from "@webhooks/WebhookReducer";
import { DeliveryReducer } from "@webhooks/DeliveryReducer";
import { registerReducer } from "@library/redux/reducerRegistry";

Router.addRoutes(allWebhookRoutes);

registerReducer("webhooks", WebhookReducer);
registerReducer("deliveries", DeliveryReducer);

addComponent("webhookApp", () => {
    return <Router sectionRoots={["/webhook-settings"]} />;
});