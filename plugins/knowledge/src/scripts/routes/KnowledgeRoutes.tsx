/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { getPageRoutes } from "@knowledge/routes/pageRoutes";
import { getModalRoutes } from "@knowledge/routes/modalRoutes";
import { ModalRouter } from "@library/components/modal";
import { IStoreState } from "@knowledge/state/model";
import ErrorPage, { DefaultError } from "@knowledge/routes/ErrorPage";
import { LoadStatus } from "@library/@types/api";
import { connect } from "react-redux";
import KnowledgeBaseActions from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import apiv2 from "@library/apiv2";
import FullPageLoader from "@library/components/FullPageLoader";
import { RouteComponentProps, withRouter } from "react-router";
import RouteActions from "@knowledge/routes/RouteActions";
import { UnregisterCallback } from "history";
import PageLoader from "@library/components/PageLoader";

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

        return (
            <PageLoader status={LoadStatus.SUCCESS}>
                <ModalRouter modalRoutes={getModalRoutes()} pageRoutes={getPageRoutes()} />
            </PageLoader>
        );
    }

    private unlisten: UnregisterCallback;

    public componentDidMount() {
        if (this.props.kbLoadable.status === LoadStatus.PENDING) {
            this.props.requestKnowledgeBases();
        }

        this.props.history.listen(this.onChange);
    }

    private onChange = () => {
        this.props.clearError();
    };

    public componentWillUnmount() {
        this.unlisten && this.unlisten();
    }
}

interface IOwnProps extends RouteComponentProps<never> {}

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
        clearError: () => dispatch(RouteActions.resetAC()),
    };
}

export default withRouter(
    connect(
        mapStateToProps,
        mapDispatchToProps,
    )(KnowledgeRoutes),
);
