/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { getPageRoutes } from "@knowledge/routes/pageRoutes";
import { getModalRoutes } from "@knowledge/routes/modalRoutes";
import { ModalRouter } from "@library/components/modal";
import { IRouteState } from "@knowledge/routes/RouteReducer";
import { IStoreState } from "@knowledge/state/model";
import ErrorPage from "@knowledge/routes/ErrorPage";
import { LoadStatus } from "@library/@types/api";
import { connect } from "react-redux";

/**
 * Routing component for pages and modals in the /kb directory.
 */
export class KnowledgeRoutes extends React.Component<IRouteState> {
    public render() {
        if (this.props.error) {
            return <ErrorPage loadable={{ status: LoadStatus.ERROR, error: this.props.error }} />;
        }

        return <ModalRouter modalRoutes={getModalRoutes()} pageRoutes={getPageRoutes()} />;
    }
}

function mapStateToProps(state: IStoreState): IRouteState {
    return state.knowledge.route;
}

export default connect(mapStateToProps)(KnowledgeRoutes);
