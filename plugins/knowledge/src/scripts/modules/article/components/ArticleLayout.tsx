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
import Breadcrumbs, { ICrumb } from "@library/navigation/Breadcrumbs";
import Container from "@library/layout/components/Container";
import PanelLayout, { PanelWidget } from "@library/layout/PanelLayout";
import UserContent from "@library/content/UserContent";
import * as React from "react";
import NextPrevious from "@library/navigation/NextPrevious";
import { t } from "@library/utility/appUtils";
import { withDevice, Devices, IDeviceProps } from "@library/layout/DeviceContext";
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
import OtherLangaugesPlaceHolder from "@knowledge/modules/article/components/OtherLanguagesPlaceHolder";

/**
 * Implements the article's layout
 */
export class ArticleLayout extends React.Component<IProps> {
    public render() {
        const {
            article,
            currentNavCategory,
            messages,
            device,
            nextNavArticle,
            prevNavArticle,
            articlelocales,
            relatedArticles,
        } = this.props;

        const { articleID } = article;

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

        const renderPanelBackground =
            this.props.device !== Devices.MOBILE &&
            this.props.device !== Devices.XS &&
            panelBackgroundVariables().config.render;

        const relatedArticlesComponent = relatedArticles ? (
            <RelatedArticles articles={relatedArticles} />
        ) : (
            <RelatedArticlesPlaceHolder />
        );

        const otherLanguagesComponent = !articlelocales ? (
            <OtherLangaugesPlaceHolder />
        ) : (
            <OtherLanguages articleLocaleData={articlelocales} />
        );

        return (
            <>
                {renderPanelBackground && <PanelBackground />}
                <Container>
                    <TitleBar
                        useMobileBackButton={this.props.useBackButton}
                        isFixed={true}
                        backgroundColorForMobileDropdown={true} // Will be conditional, based on the settings, but it's true in the sense that it can be colored.
                        extraBurgerNavigation={
                            <Navigation
                                collapsible={true}
                                activeRecord={activeRecord}
                                kbID={article.knowledgeBaseID}
                                inHamburger
                            />
                        }
                    />

                    <PanelLayout
                        renderLeftPanelBackground={renderPanelBackground}
                        breadcrumbs={
                            (this.props.device === Devices.XS || this.props.device === Devices.MOBILE) &&
                            article.breadcrumbs
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
                                />
                            </PanelWidget>
                        }
                        middleTop={
                            <PanelWidget>
                                <PageTitle
                                    title={article.name}
                                    headingClassName={typographyClasses().largeTitle}
                                    actions={
                                        <ArticleMenu
                                            article={article}
                                            buttonClassName={classNames("pageTitle-menu", classesButtons.icon)}
                                            device={this.props.device}
                                        />
                                    }
                                    meta={
                                        <ArticleMeta
                                            updateUser={article.updateUser!}
                                            dateUpdated={article.dateUpdated}
                                            permaLink={article.url}
                                        />
                                    }
                                    includeBackLink={
                                        this.props.device !== Devices.MOBILE &&
                                        this.props.device !== Devices.XS &&
                                        this.props.useBackButton
                                    }
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
                                {device !== Devices.MOBILE &&
                                    device !== Devices.TABLET &&
                                    article.outline &&
                                    article.outline.length > 0 && (
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
}

interface IProps extends IDeviceProps {
    useBackButton?: boolean;
    article: IArticle;
    messages?: React.ReactNode;
    prevNavArticle: IKbNavigationItem<KbRecordType.ARTICLE> | null;
    nextNavArticle: IKbNavigationItem<KbRecordType.ARTICLE> | null;
    currentNavCategory: IKbNavigationItem<KbRecordType.CATEGORY> | null;
    articlelocales: IArticleLocale[] | null;
    relatedArticles: IRelatedArticle[] | null;
}

export default withDevice(ArticleLayout);
