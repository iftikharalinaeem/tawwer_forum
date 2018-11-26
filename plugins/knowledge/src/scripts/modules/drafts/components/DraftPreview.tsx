/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { t } from "@library/application";
import { Link, RouteComponentProps, withRouter } from "react-router-dom";
import Paragraph from "@library/components/Paragraph";
import { IKbCategoryFragment } from "@knowledge/@types/api/kbCategory";
import DraftMenu from "@knowledge/modules/drafts/components/DraftMenu";
import { DraftPreviewMeta } from "@knowledge/modules/drafts/components/DraftPreviewMeta";
import { IResponseArticleDraft } from "@knowledge/@types/api/article";
import { makeDraftUrl } from "@knowledge/modules/editor/route";

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
        const { dateUpdated, draftID, headingLevel, className } = this.props;
        const { body, name } = this.props.attributes;
        const HeadingTag = `h${headingLevel}`;
        const url = makeDraftUrl(this.props);

        return (
            <li className={classNames("draftPreview", className)} onClick={this.handleClick}>
                <article className="draftPreview-item">
                    <div className="draftPreview-header">
                        <HeadingTag className="draftPreview-title" level={this.props.headingLevel}>
                            <Link to={url} className="draftPreview-link">
                                {!!name ? name : <em>{t("(Untitled)")}</em>}
                            </Link>
                        </HeadingTag>
                        <DraftMenu className="draftPreview-menu" draftID={draftID} url={url} />
                    </div>
                    <Paragraph className="draftPreview-excerpt">
                        {!!body ? <em>{t("(Temporary Placeholder)")}</em> : <em>{t("(No Body)")}</em>}
                    </Paragraph>
                    <DraftPreviewMeta dateUpdated={dateUpdated} />
                </article>
            </li>
        );
    }

    private handleClick = (event: React.MouseEvent) => {
        event.preventDefault();
        this.props.history.push(makeDraftUrl(this.props));
    };
}

export default withRouter(DraftPreview);
