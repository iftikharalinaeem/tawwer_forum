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
import ErrorPage, { DefaultError } from "@knowledge/routes/ErrorPage";
import { LoadStatus } from "@library/@types/api";
import { connect } from "react-redux";
import KnowledgeBaseActions from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import apiv2 from "@library/apiv2";
import FullPageLoader from "@library/components/FullPageLoader";

/**
 * Routing component for pages and modals in the /kb directory.
 */
export class KnowledgeRoutes extends React.Component<IProps> {
    public render() {
        const { kbLoadable, routeState } = this.props;
        if (routeState.error) {
            return <ErrorPage error={routeState.error} />;
        }

        if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(kbLoadable.status)) {
            return <FullPageLoader />;
        }

        if (
            kbLoadable.status === LoadStatus.SUCCESS &&
            kbLoadable.data &&
            Object.values(kbLoadable.data).length === 0
        ) {
            return <ErrorPage defaultError={DefaultError.NO_KNOWLEDGE_BASE} />;
        }

        return <ModalRouter modalRoutes={getModalRoutes()} pageRoutes={getPageRoutes()} />;
    }

    public componentDidMount() {
        if (this.props.kbLoadable.status === LoadStatus.PENDING) {
            this.props.requestKnowledgeBases();
        }
    }
}

interface IOwnProps {}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(state: IStoreState, ownProps: IOwnProps) {
    return {
        kbLoadable: state.knowledge.knowledgeBases.knowledgeBasesByID,
        routeState: state.knowledge.route,
    };
}

function mapDispatchToProps(dispatch: any) {
    const kbActions = new KnowledgeBaseActions(dispatch, apiv2);
    return {
        requestKnowledgeBases: kbActions.getAll,
    };
}

export default connect(mapStateToProps)(KnowledgeRoutes);
