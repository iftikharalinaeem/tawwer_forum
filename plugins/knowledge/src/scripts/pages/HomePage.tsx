/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import KnowledgeBaseActions from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import KnowledgeBaseList from "@knowledge/knowledge-bases/KnowledgeBaseList";
import KnowledgeBaseModel from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import KnowledgeBasePage from "@knowledge/pages/KnowledgeBasePage";
import { IStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api";
import apiv2 from "@library/apiv2";
import { t } from "@library/application";
import DocumentTitle from "@library/components/DocumentTitle";
import FullPageLoader from "@library/components/FullPageLoader";
import VanillaHeader from "@library/components/headers/VanillaHeader";
import Container from "@library/components/layouts/components/Container";
import React from "react";
import { connect } from "react-redux";
import { RouteComponentProps } from "react-router";
import Splash from "library/src/scripts/components/splash/Splash";

export class HomePage extends React.Component<IProps> {
    public render() {
        const { loadStatus, knowledgeBases } = this.props;
        if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(loadStatus)) {
            return <FullPageLoader />;
        }

        if (knowledgeBases.length === 1) {
            const { urlCode } = knowledgeBases[0];
            return <KnowledgeBasePage {...this.props} match={{ ...this.props.match, params: { urlCode } }} />;
        }

        return (
            <Container>
                <DocumentTitle title={t("Home")}>
                    <VanillaHeader />
                </DocumentTitle>
                <Splash title={t("How can we help you")} styles={{}} />
                <KnowledgeBaseList />
            </Container>
        );
    }
}

interface IOwnProps extends RouteComponentProps<any> {
    className?: string;
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;
function mapStateToProps(state: IStoreState) {
    return {
        knowledgeBases: KnowledgeBaseModel.selectKnowledgeBases(state),
        loadStatus: state.knowledge.knowledgeBases.knowledgeBasesByID.status,
    };
}

function mapDispatchToProps(dispatch: any) {
    const kbActions = new KnowledgeBaseActions(dispatch, apiv2);
    return {
        requestKnowledgeBases: kbActions.getAll,
    };
}

export default connect(
    mapStateToProps,
    mapDispatchToProps,
)(HomePage);
