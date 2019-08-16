/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { t } from "@library/utility/appUtils";
import { RouteComponentProps, withRouter } from "react-router-dom";
import Paragraph from "@library/layout/Paragraph";
import DraftMenu from "@knowledge/modules/drafts/components/DraftMenu";
import { DraftPreviewMeta } from "@knowledge/modules/drafts/components/DraftPreviewMeta";
import { IResponseArticleDraft } from "@knowledge/@types/api/article";
import { EditorRoute } from "@knowledge/routes/pageRoutes";
import { searchResultClasses, searchResultsClasses } from "@library/features/search/searchResultsStyles";
import TruncatedText from "@library/content/TruncatedText";
import { draftPreviewClasses } from "@knowledge/modules/drafts/components/DraftPreviewStyles";

interface IProps extends IResponseArticleDraft, RouteComponentProps<any> {
    headingLevel?: 2 | 3 | 4 | 5 | 6;
    className?: string;
    menuOverwrite?: JSX.Element;
}

/**
 * Implements draft preview
 */
export class DraftPreview extends React.Component<IProps> {
    public static defaultProps: Partial<IProps> = {
        headingLevel: 2,
    };

    public render() {
        const { dateUpdated, draftID, headingLevel, className, excerpt, menuOverwrite } = this.props;
        const { name } = this.props.attributes;
        const HeadingTag = `h${headingLevel}` as "h1" | "h3" | "h4" | "h5" | "h6";
        const classesSearchResults = searchResultsClasses();
        const classesResult = searchResultClasses();
        const classes = draftPreviewClasses();

        return (
            <li
                className={classNames("draftPreview", classesSearchResults.item, classesResult.root, className)}
                onClick={this.handleClick}
            >
                <article className={classNames("draftPreview-item", classesSearchResults.result)}>
                    <div className={classNames("draftPreview-main", classesResult.main)}>
                        <div className={classNames("draftPreview-header", classes.header)}>
                            <a
                                href={EditorRoute.url(this.props)}
                                onClick={this.handleClick}
                                className={classNames("draftPreview-link", classesResult.title)}
                            >
                                <HeadingTag className={classNames("draftPreview-title", classesResult.title)}>
                                    {name ? name : <em>{t("(Untitled)")}</em>}
                                </HeadingTag>
                            </a>

                            {menuOverwrite ? (
                                menuOverwrite
                            ) : (
                                <DraftMenu
                                    className={classNames("draftPreview-menu")}
                                    draftID={draftID}
                                    url={EditorRoute.url(this.props)}
                                />
                            )}
                        </div>
                        <DraftPreviewMeta
                            className={classNames(classesResult.metas, classes.metas)}
                            dateUpdated={dateUpdated}
                        />
                        <Paragraph className={classNames("draftPreview-excerpt", classesResult.excerpt)}>
                            <TruncatedText>{excerpt ? <em>{excerpt}</em> : <em>{t("(No Body)")}</em>}</TruncatedText>
                        </Paragraph>
                    </div>
                </article>
            </li>
        );
    }

    private handleClick = (event: React.MouseEvent) => {
        event.preventDefault();
        this.props.history.push(EditorRoute.url(this.props));
    };
}

export default withRouter(DraftPreview);
