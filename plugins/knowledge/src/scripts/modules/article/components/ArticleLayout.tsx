/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */
import ArticleMenu from "@knowledge/modules/article/ArticleMenu";
import { ArticleMeta } from "@knowledge/modules/article/components/ArticleMeta";
import ArticleTOC from "@knowledge/modules/article/components/ArticleTOC";
import values from "lodash/values";
import OtherLanguages from "@knowledge/modules/article/components/OtherLanguages";
import PageTitle from "@knowledge/modules/common/PageTitle";
import Navigation from "@knowledge/navigation/Navigation";
import { KbRecordType, IKbNavigationItem } from "@knowledge/navigation/state/NavigationModel";
import Breadcrumbs from "@library/navigation/Breadcrumbs";
import Container from "@library/layout/components/Container";
import PanelLayout, { PanelWidget } from "@library/layout/PanelLayout";
import UserContent from "@library/content/UserContent";
import * as React from "react";
import NextPrevious from "@library/navigation/NextPrevious";
import { t } from "@library/utility/appUtils";
import { withDevice, Devices, IDeviceProps } from "@library/layout/DeviceContext";
import ArticleReactions from "@knowledge/modules/article/components/ArticleReactions";
import { IArticle, IArticleLocales } from "@knowledge/@types/api/article";
import classNames from "classnames";
import TitleBar from "@library/headers/TitleBar";
import { buttonClasses } from "@library/forms/buttonStyles";

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
        return (
            <Container>
                <TitleBar
                    isFixed={true}
                    title={title}
                    mobileDropDownContent={
                        <Navigation collapsible={true} activeRecord={activeRecord} kbID={article.knowledgeBaseID} />
                    }
                />
                <PanelLayout
                    breadcrumbs={
                        this.props.device !== Devices.MOBILE &&
                        this.props.device !== Devices.XS &&
                        article.breadcrumbs && <Breadcrumbs children={article.breadcrumbs} forceDisplay={false} />
                    }
                    leftBottom={
                        <PanelWidget>
                            <Navigation collapsible={true} activeRecord={activeRecord} kbID={article.knowledgeBaseID} />
                        </PanelWidget>
                    }
                    middleTop={
                        <PanelWidget>
                            <PageTitle
                                title={article.name}
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
                                    this.props.device !== Devices.MOBILE && this.props.device !== Devices.XS
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
                            <PanelWidget>
                                <OtherLanguages
                                    //id={}
                                    //={}
                                    data={values(this.props.articlelocales)}
                                />
                            </PanelWidget>
                        </>
                    }
                />
            </Container>
        );
    }
}

interface IProps extends IDeviceProps {
    article: IArticle;
    messages?: React.ReactNode;
    prevNavArticle: IKbNavigationItem<KbRecordType.ARTICLE> | null;
    nextNavArticle: IKbNavigationItem<KbRecordType.ARTICLE> | null;
    currentNavCategory: IKbNavigationItem<KbRecordType.CATEGORY> | null;
    articlelocales: IArticleLocales;
}

export default withDevice(ArticleLayout);
