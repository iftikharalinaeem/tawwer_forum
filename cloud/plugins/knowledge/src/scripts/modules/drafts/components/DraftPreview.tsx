/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { t } from "@library/utility/appUtils";
import { RouteComponentProps, withRouter, useHistory } from "react-router-dom";
import Paragraph from "@library/layout/Paragraph";
import DraftMenu from "@knowledge/modules/drafts/components/DraftMenu";
import { DraftPreviewMeta } from "@knowledge/modules/drafts/components/DraftPreviewMeta";
import { IResponseArticleDraft } from "@knowledge/@types/api/article";
import { EditorRoute } from "@knowledge/routes/pageRoutes";
import { searchResultClasses, searchResultsClasses } from "@library/features/search/searchResultsStyles";
import TruncatedText from "@library/content/TruncatedText";
import { draftPreviewClasses } from "@knowledge/modules/drafts/components/DraftPreviewStyles";
import { useLinkContext } from "@library/routing/links/LinkContextProvider";
import { useLayout } from "@library/layout/LayoutContext";

interface IProps extends IResponseArticleDraft {
    className?: string;
    menuOverwrite?: JSX.Element;
    headingLevel?: 2 | 3 | 4 | 5 | 6;
}

/**
 * Implements draft preview
 */
export function DraftPreview(props: IProps) {
    const { dateUpdated, draftID, headingLevel, className, excerpt, menuOverwrite } = props;
    const { name } = props.attributes;
    const HeadingTag = `h${headingLevel ?? 2}` as "h1" | "h3" | "h4" | "h5" | "h6";
    const { mediaQueries } = useLayout();
    const classesSearchResults = searchResultsClasses(mediaQueries);
    const classesResult = searchResultClasses(mediaQueries);
    const classes = draftPreviewClasses();
    const { pushSmartLocation } = useLinkContext();

    const routeParams = {
        ...props,
        articleID: props.recordID,
        knowledgeCategoryID: props.attributes.knowledgeCategoryID,
    };
    const url = EditorRoute.url(routeParams);

    const handleClick = (event: React.MouseEvent) => {
        event.preventDefault();
        event.stopPropagation();
        pushSmartLocation(url);
    };

    return (
        <li
            className={classNames("draftPreview", classesSearchResults.item, classesResult.root, className)}
            onClick={handleClick}
        >
            <article className={classNames("draftPreview-item", classesSearchResults.result)}>
                <div className={classNames("draftPreview-main", classesResult.main)}>
                    <div className={classNames("draftPreview-header", classes.header)}>
                        <a
                            onClick={handleClick}
                            href={url}
                            className={classNames("draftPreview-link", classesResult.title)}
                        >
                            <HeadingTag className={classNames("draftPreview-title", classesResult.title)}>
                                {name ? name : <em>{t("(Untitled)")}</em>}
                            </HeadingTag>
                        </a>

                        {menuOverwrite ? (
                            menuOverwrite
                        ) : (
                            <DraftMenu className={classNames("draftPreview-menu")} draftID={draftID} url={url} />
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

export default DraftPreview;
