/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { t } from "@library/application";
import { RouteComponentProps, withRouter } from "react-router-dom";
import Paragraph from "@library/components/Paragraph";
import DraftMenu from "@knowledge/modules/drafts/components/DraftMenu";
import { DraftPreviewMeta } from "@knowledge/modules/drafts/components/DraftPreviewMeta";
import { IResponseArticleDraft } from "@knowledge/@types/api/article";
import { EditorRoute } from "@knowledge/routes/pageRoutes";

interface IProps extends IResponseArticleDraft, RouteComponentProps<any> {
    headingLevel?: 2 | 3 | 4 | 5 | 6;
    className?: string;
}

/**
 * Implements draft preview
 */
export class DraftPreview extends React.Component<IProps> {
    public static defaultProps: Partial<IProps> = {
        headingLevel: 2,
    };

    public render() {
        const { dateUpdated, draftID, headingLevel, className, excerpt } = this.props;
        const { name } = this.props.attributes;
        const HeadingTag = `h${headingLevel}`;

        return (
            <li className={classNames("draftPreview", className)} onClick={this.handleClick}>
                <article className="draftPreview-item">
                    <div className="draftPreview-header">
                        <HeadingTag className="draftPreview-title" level={this.props.headingLevel}>
                            <EditorRoute.Link
                                data={{
                                    articleID: this.props.recordID,
                                    draftID: this.props.draftID,
                                }}
                                className="draftPreview-link"
                            >
                                {!!name ? name : <em>{t("(Untitled)")}</em>}
                            </EditorRoute.Link>
                        </HeadingTag>
                        <DraftMenu className="draftPreview-menu" draftID={draftID} url={EditorRoute.url(this.props)} />
                    </div>
                    <Paragraph className="draftPreview-excerpt">
                        {excerpt ? <em>{excerpt}</em> : <em>{t("(No Body)")}</em>}
                    </Paragraph>
                    <DraftPreviewMeta dateUpdated={dateUpdated} />
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
