/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { Router } from "@vanilla/library/src/scripts/Router";
import { allWebhookRoutes } from "@webhooks/WebhookPageRoutes";
import { addComponent } from "@vanilla/library/src/scripts/utility/componentRegistry";

Router.addRoutes(allWebhookRoutes);

addComponent("webhookApp", () => {
    return <Router sectionRoot="/webhook-settings" />;
});
