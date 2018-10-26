/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { getPageRoutes } from "@knowledge/routes/pageRoutes";
import { getModalRoutes } from "@knowledge/routes/modalRoutes";
import { ModalRouter } from "@library/components/modal";

/**
 * Routing component for pages and modals in the /kb directory.
 */
export default class KnowledgeRoutes extends React.Component {
    public render() {
        return <ModalRouter modalRoutes={getModalRoutes()} pageRoutes={getPageRoutes()} />;
    }
}
