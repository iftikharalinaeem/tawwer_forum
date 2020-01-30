/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import KnowledgeBaseActions from "@knowledge/knowledge-bases/KnowledgeBaseActions";
import KnowledgeBaseList from "@knowledge/knowledge-bases/KnowledgeBaseList";
import KnowledgeBaseModel from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import KnowledgeBasePage from "@knowledge/pages/KnowledgeBasePage";
import { IKnowledgeAppStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import Container from "@library/layout/components/Container";
import Loader from "@library/loaders/Loader";
import DocumentTitle from "@library/routing/DocumentTitle";
import { t } from "@library/utility/appUtils";
import React, { useEffect } from "react";
import { connect } from "react-redux";
import { RouteComponentProps } from "react-router";
import { AnalyticsData } from "@library/analytics/AnalyticsData";
import { useBackgroundContext } from "@library/layout/Backgrounds";
import TitleBar from "@vanilla/library/src/scripts/headers/TitleBar";
import { bannerVariables } from "@vanilla/library/src/scripts/banner/bannerStyles";
import Banner from "@library/banner/Banner";

const HomePage = (props: IProps) => {
    const splashVars = bannerVariables({});
    const title = t(splashVars.title.text);
    const { loadStatus, knowledgeBases } = props;

    const { setIsHomePage } = useBackgroundContext();

    useEffect(() => {
        setIsHomePage(true);
    });

    if ([LoadStatus.PENDING, LoadStatus.LOADING].includes(loadStatus)) {
        return <Loader />;
    }

    if (knowledgeBases.length === 1) {
        const { urlCode } = knowledgeBases[0];
        return <KnowledgeBasePage {...props} isOnlyKb match={{ ...props.match, params: { urlCode } }} />;
    }

    return (
        <>
            <AnalyticsData uniqueKey="homePage" />
            <Banner title={title} />
            <Container>
                <DocumentTitle title={t("Home")}>
                    <TitleBar hasSubNav />
                </DocumentTitle>
                <KnowledgeBaseList />
            </Container>
        </>
    );
};

interface IOwnProps extends RouteComponentProps<any> {
    className?: string;
}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps> & ReturnType<typeof mapDispatchToProps>;
function mapStateToProps(state: IKnowledgeAppStoreState) {
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

export default connect(mapStateToProps, mapDispatchToProps)(HomePage);
