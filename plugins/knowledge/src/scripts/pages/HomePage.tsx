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
import { LoadStatus } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import { t } from "@library/utility/appUtils";
import DocumentTitle from "@library/routing/DocumentTitle";
import Loader from "@library/loaders/Loader";
import VanillaHeader from "@library/headers/VanillaHeader";
import Container from "@library/layout/components/Container";
import React from "react";
import { connect } from "react-redux";
import { RouteComponentProps } from "react-router";
import Splash from "@library/splash/Splash";
import { hot } from "react-hot-loader";
import { splashVariables } from "@library/splash/splashStyles";
import get from "lodash/get";

export class HomePage extends React.Component<IProps> {
    public render() {
        const splashVars = splashVariables();
        const title = get(splashVars, "title.text", t("How can we help you?"));
        const { loadStatus, knowledgeBases } = this.props;
        if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(loadStatus)) {
            return <Loader />;
        }

        if (knowledgeBases.length === 1) {
            const { urlCode } = knowledgeBases[0];
            return <KnowledgeBasePage {...this.props} match={{ ...this.props.match, params: { urlCode } }} />;
        }

        return (
            <>
                <Splash title={title} />
                <Container>
                    <DocumentTitle title={t("Home")}>
                        <VanillaHeader />
                    </DocumentTitle>
                    <KnowledgeBaseList />
                </Container>
            </>
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

export default hot(module)(
    connect(
        mapStateToProps,
        mapDispatchToProps,
    )(HomePage),
);
