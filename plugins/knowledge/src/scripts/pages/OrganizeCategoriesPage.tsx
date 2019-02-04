/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import KnowledgeBaseActions from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import FullKnowledgeModal from "@knowledge/modules/common/FullKnowledgeModal";
import NavigationManager from "@knowledge/navigation/NavigationManager";
import NavigationManagerMenu from "@knowledge/navigation/NavigationManagerMenu";
import ErrorPage, { DefaultError } from "@knowledge/routes/ErrorPage";
import { IStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api";
import apiv2 from "@library/apiv2";
import { t } from "@library/application";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import DocumentTitle from "@library/components/DocumentTitle";
import FullPageLoader from "@library/components/FullPageLoader";
import Heading from "@library/components/Heading";
import React from "react";
import { connect } from "react-redux";
import { match } from "react-router";

class OrganizeCategoriesPage extends React.Component<IProps> {
    private titleID = uniqueIDFromPrefix("organizeCategoriesTitle");

    public render() {
        const { knowledgeBase } = this.props;
        const pageTitle = t("Navigation Manager");

        if ([LoadStatus.LOADING, LoadStatus.PENDING].includes(knowledgeBase.status)) {
            return <FullPageLoader />;
        }

        if (knowledgeBase.status === LoadStatus.ERROR || !knowledgeBase.data) {
            return <ErrorPage defaultError={DefaultError.NOT_FOUND} />;
        }

        return (
            <>
                <FullKnowledgeModal titleID={this.titleID}>
                    <NavigationManagerMenu />
                    <div className="modal-scroll inheritHeight">
                        <div className="container inheritHeight">
                            <div className="navigationManager-container inheritHeight">
                                <DocumentTitle title={pageTitle}>
                                    <Heading
                                        id={this.titleID}
                                        depth={1}
                                        renderAsDepth={2}
                                        className="pageSubTitle navigationManager-header"
                                        title={pageTitle}
                                    />
                                </DocumentTitle>
                                <div className="inheritHeight">
                                    <NavigationManager
                                        knowledgeBase={knowledgeBase.data}
                                        rootNavigationItemID="knowledgeCategory1"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </FullKnowledgeModal>
            </>
        );
    }

    public componentDidMount() {
        if (this.props.knowledgeBase.status === LoadStatus.PENDING) {
            this.props.requestData();
        }
    }
}

interface IOwnProps {
    match: match<{
        id: string;
        page?: number;
    }>;
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;

function mapStateToProps(state: IStoreState, ownProps: IOwnProps) {
    const { knowledgeBasesByID } = state.knowledge.knowledgeBases;
    const kbID = parseInt(ownProps.match.params.id, 10);

    const knowledgeBase = {
        ...knowledgeBasesByID,
        data: knowledgeBasesByID.data ? knowledgeBasesByID.data[kbID] : undefined,
    };

    return {
        knowledgeBase,
    };
}

function mapDispatchToProps(dispatch: any) {
    const kbActions = new KnowledgeBaseActions(dispatch, apiv2);

    return {
        requestData: () => kbActions.getAll(),
    };
}

export default connect(
    mapStateToProps,
    mapDispatchToProps,
)(OrganizeCategoriesPage);
