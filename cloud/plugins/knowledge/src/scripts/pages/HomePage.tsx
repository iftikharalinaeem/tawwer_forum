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
import Loader from "@library/loaders/Loader";
import DocumentTitle from "@library/routing/DocumentTitle";
import { t, getSiteSection } from "@library/utility/appUtils";
import React, { useEffect } from "react";
import { connect } from "react-redux";
import { RouteComponentProps } from "react-router";
import { AnalyticsData } from "@library/analytics/AnalyticsData";
import { useBackgroundContext } from "@library/layout/Backgrounds";
import TitleBar from "@library/headers/TitleBar";
import { bannerVariables } from "@library/banner/bannerStyles";
import Banner from "@library/banner/Banner";
import { ArticlesWidget } from "@knowledge/widgets/ArticlesWidget";
import { tilesVariables } from "@vanilla/library/src/scripts/features/tiles/tilesStyles";
import { useArticleList } from "@knowledge/modules/article/ArticleModel";
import { ISearchRequestBody } from "@knowledge/@types/api/search";
import { KbErrorPage } from "@knowledge/pages/KbErrorPage";

const HomePage = (props: IProps) => {
    const splashVars = bannerVariables();
    const title = t(splashVars.title.text);
    const { loadable, knowledgeBases } = props;

    const { setIsHomePage } = useBackgroundContext();
    const widgetParams: ISearchRequestBody = {
        featured: true,
        siteSectionGroup: getSiteSection().sectionGroup === "vanilla" ? undefined : getSiteSection().sectionGroup,
        locale: getSiteSection().contentLocale,
    };
    const articleList = useArticleList(widgetParams);

    useEffect(() => {
        setIsHomePage(true);
    });

    if (
        [LoadStatus.PENDING, LoadStatus.LOADING].includes(loadable.status) ||
        [LoadStatus.PENDING, LoadStatus.LOADING].includes(articleList.status)
    ) {
        return <Loader />;
    }

    if (loadable.error) {
        return <KbErrorPage error={loadable.error}></KbErrorPage>;
    }

    const nonUniversalKBs = knowledgeBases.filter(kb => !kb.isUniversalSource);
    if (nonUniversalKBs.length === 1 || knowledgeBases.length === 1) {
        // If we have just 1 universal source we don't want to pretend it doesn't exist.
        const { urlCode } = nonUniversalKBs[0] ?? knowledgeBases[0];
        return <KnowledgeBasePage {...props} isOnlyKb match={{ ...props.match, params: { urlCode } }} />;
    }

    const tileColumnCount = tilesVariables().options.columns;
    const recommendedColumnCount = [1, 2].includes(tileColumnCount) ? 1 : 3;
    const maxItems = [1, 2].includes(tileColumnCount) ? 4 : 3;

    return (
        <>
            <AnalyticsData uniqueKey="homePage" />
            <DocumentTitle title={t("Home")}>
                <></>
            </DocumentTitle>
            <TitleBar useMobileBackButton={false} />
            <main className="page-minHeight">
                <Banner title={title} />
                <KnowledgeBaseList />
                <ArticlesWidget
                    title={t("Recommended Articles")}
                    maxItemCount={maxItems}
                    containerOptions={{
                        maxWidth: tilesVariables().calculatedMaxWidth,
                        maxColumnCount: recommendedColumnCount,
                        viewAll: { to: "/kb/articles?recommended=true" },
                    }}
                    params={widgetParams}
                />
            </main>
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
        loadable: state.knowledge.knowledgeBases.knowledgeBasesByID,
    };
}

function mapDispatchToProps(dispatch: any) {
    const kbActions = new KnowledgeBaseActions(dispatch, apiv2);
    return {
        requestKnowledgeBases: kbActions.getAll,
    };
}

export default connect(mapStateToProps, mapDispatchToProps)(HomePage);
