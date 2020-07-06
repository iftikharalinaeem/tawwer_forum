/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */
import ArticleMenu from "@knowledge/modules/article/ArticleMenu";
import { ArticleMeta } from "@knowledge/modules/article/components/ArticleMeta";
import ArticleTOC from "@knowledge/modules/article/components/ArticleTOC";
import OtherLanguages from "@knowledge/modules/article/components/OtherLanguages";
import PageTitle from "@knowledge/modules/common/PageTitle";
import Navigation from "@knowledge/navigation/Navigation";
import { KbRecordType, IKbNavigationItem } from "@knowledge/navigation/state/NavigationModel";
import Breadcrumbs from "@library/navigation/Breadcrumbs";
import Container from "@library/layout/components/Container";
import PanelLayout from "@library/layout/PanelLayout";
import UserContent from "@library/content/UserContent";
import * as React from "react";
import NextPrevious from "@library/navigation/NextPrevious";
import { t } from "@library/utility/appUtils";
import ArticleReactions from "@knowledge/modules/article/components/ArticleReactions";
import { IArticle, IArticleLocale, IRelatedArticle } from "@knowledge/@types/api/article";
import classNames from "classnames";
import TitleBar from "@library/headers/TitleBar";
import { buttonClasses } from "@library/forms/buttonStyles";
import { typographyClasses } from "@library/styles/typographyStyles";
import { panelBackgroundVariables } from "@library/layout/panelBackgroundStyles";
import { PanelBackground } from "@library/layout/PanelBackground";
import RelatedArticles from "@knowledge/modules/article/components/RelatedArticles";
import { RelatedArticlesPlaceHolder } from "@knowledge/modules/article/components/RelatedArticlesPlaceholder";
import OtherLanguagesPlaceHolder from "@knowledge/modules/article/components/OtherLanguagesPlaceHolder";
import { useKnowledgeBase } from "@knowledge/knowledge-bases/knowledgeBaseHooks";
import { KbPermission } from "@knowledge/knowledge-bases/KbPermission";
import Banner from "@vanilla/library/src/scripts/banner/Banner";
import { useLayout } from "@library/layout/LayoutContext";
import PanelWidget from "@vanilla/library/src/scripts/layout/components/PanelWidget";
import ThreeColumnLayout from "@vanilla/library/src/scripts/layout/ThreeColumnLayout";

interface IProps {
    useBackButton?: boolean;
    article: IArticle;
    messages?: React.ReactNode;
    prevNavArticle: IKbNavigationItem<KbRecordType.ARTICLE> | null;
    nextNavArticle: IKbNavigationItem<KbRecordType.ARTICLE> | null;
    currentNavCategory: IKbNavigationItem<KbRecordType.CATEGORY> | null;
    articlelocales: IArticleLocale[] | null;
    relatedArticles: IRelatedArticle[] | null;
    featured: boolean;
}

/**
 * Implements the article's layout
 */
export default function ArticleLayout(props: IProps) {
    const { isCompact, isFullWidth } = useLayout();

    const {
        article,
        currentNavCategory,
        messages,
        nextNavArticle,
        prevNavArticle,
        articlelocales,
        relatedArticles,
        featured,
    } = props;

    const { articleID } = article;
    const knowledgeBase = useKnowledgeBase(article.knowledgeBaseID);

    const activeRecord = {
        recordID: articleID,
        recordType: KbRecordType.ARTICLE,
    };
    const classesButtons = buttonClasses();

    let title = "";
    if (currentNavCategory) {
        title = currentNavCategory.name;
    }
    const crumbs = article.breadcrumbs;
    const lastCrumb = crumbs && crumbs.length > 1 ? crumbs.slice(t.length - 1) : crumbs;

    const renderPanelBackground = !isCompact && panelBackgroundVariables().config.render;

    const relatedArticlesComponent = relatedArticles ? (
        <RelatedArticles articles={relatedArticles} />
    ) : (
        <RelatedArticlesPlaceHolder />
    );

    const otherLanguagesComponent = !articlelocales ? (
        <OtherLanguagesPlaceHolder />
    ) : (
        <OtherLanguages articleLocaleData={articlelocales} knowledgeBaseID={article.knowledgeBaseID} />
    );

    return (
        <>
            {renderPanelBackground && <PanelBackground />}
            <TitleBar
                useMobileBackButton={props.useBackButton}
                backgroundColorForMobileDropdown={true} // Will be conditional, based on the settings, but it's true in the sense that it can be colored.
                extraBurgerNavigation={
                    <Navigation
                        collapsible={true}
                        activeRecord={activeRecord}
                        kbID={article.knowledgeBaseID}
                        inHamburger
                        knowledgeCategoryID={article.knowledgeCategoryID}
                        knowledgeCategoryName={title}
                    />
                }
            />
            <Banner
                isContentBanner
                backgroundImage={knowledgeBase.data?.bannerImage}
                contentImage={knowledgeBase.data?.bannerContentImage}
            />
            <Container>
                <ThreeColumnLayout
                    renderLeftPanelBackground={renderPanelBackground}
                    breadcrumbs={
                        isCompact && article.breadcrumbs
                            ? lastCrumb && <Breadcrumbs forceDisplay={false}>{lastCrumb}</Breadcrumbs>
                            : article.breadcrumbs && (
                                  <Breadcrumbs forceDisplay={false}>{article.breadcrumbs}</Breadcrumbs>
                              )
                    }
                    leftBottom={
                        <PanelWidget>
                            <Navigation
                                collapsible={true}
                                activeRecord={activeRecord}
                                kbID={article.knowledgeBaseID}
                                knowledgeCategoryID={article.knowledgeCategoryID}
                                knowledgeCategoryName={title}
                            />
                        </PanelWidget>
                    }
                    middleTop={
                        <PanelWidget>
                            <PageTitle
                                title={article.name}
                                headingClassName={typographyClasses().largeTitle}
                                actions={
                                    <KbPermission permission="articles.add" kbID={article.knowledgeBaseID}>
                                        <ArticleMenu
                                            knowledgeBase={knowledgeBase.data}
                                            article={article}
                                            buttonClassName={classNames("pageTitle-menu", classesButtons.icon)}
                                        />
                                    </KbPermission>
                                }
                                meta={
                                    <ArticleMeta
                                        updateUser={article.updateUser!}
                                        dateUpdated={article.dateUpdated}
                                        permaLink={article.url}
                                        featured={featured}
                                    />
                                }
                                includeBackLink={!isCompact && props.useBackButton}
                            />
                            {messages && <div className="messages">{messages}</div>}
                        </PanelWidget>
                    }
                    middleBottom={
                        <>
                            <PanelWidget>
                                <UserContent content={article.body} />
                            </PanelWidget>
                            <PanelWidget>
                                <ArticleReactions reactions={article.reactions} articleID={article.articleID} />
                            </PanelWidget>
                            {(!!prevNavArticle || !!nextNavArticle) && (
                                <PanelWidget>
                                    <NextPrevious
                                        accessibleTitle={t("More Articles")}
                                        prevItem={prevNavArticle}
                                        nextItem={nextNavArticle}
                                    />
                                </PanelWidget>
                            )}
                            <PanelWidget>{relatedArticlesComponent}</PanelWidget>
                        </>
                    }
                    rightTop={
                        <>
                            {!isCompact && !isFullWidth && article.outline && article.outline.length > 0 && (
                                <PanelWidget>
                                    <ArticleTOC items={article.outline} />
                                </PanelWidget>
                            )}
                            <PanelWidget>{otherLanguagesComponent}</PanelWidget>
                        </>
                    }
                />
            </Container>
        </>
    );
}
