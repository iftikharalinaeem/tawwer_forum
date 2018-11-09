/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { t } from "@library/application";
import { Link, Redirect } from "react-router-dom";
import Paragraph from "@library/components/Paragraph";
import { IAttachmentIcon } from "@knowledge/modules/common/AttachmentIcon";
import { IKbCategoryFragment } from "@knowledge/@types/api/kbCategory";
import DraftActions from "@knowledge/modules/drafts/components/DraftActions";
import { DraftPreviewMeta } from "@knowledge/modules/drafts/components/DraftPreviewMeta";

export interface IDraftPreview {
    id: number;
    name: string | null;
    body: string | null;
    dateUpdated: string;
    url: string;
    location?: IKbCategoryFragment[];
}

interface IProps extends IDraftPreview {
    headingLevel?: 2 | 3 | 4 | 5 | 6;
    className?: string;
}

interface IState {
    doRedirect: boolean;
}

/**
 * Generates search result list. Note that this template is used in other contexts, such as the flat category list
 */
export default class DraftPreview extends React.Component<IProps, IState> {
    public static defaultProps = {
        headingLevel: 2,
    };

    public constructor(props) {
        super(props);
        this.state = {
            doRedirect: false,
        };
    }

    public render() {
        const { name, body, dateUpdated, url, location, headingLevel, className } = this.props;
        const HeadingTag = `h${headingLevel}`;
        const hasMeta = dateUpdated || location;
        // We can't nest links, so we need to simulate a click on the <li> element
        if (this.state.doRedirect) {
            return <Redirect to={url} />;
        } else {
            return (
                <li className={classNames("draftPreview", className)} onClick={this.doRedirect}>
                    <article className="draftPreview-item">
                        <div className="draftPreview-header">
                            <HeadingTag className="draftPreview-title" level={this.props.headingLevel}>
                                <Link to={url} className="draftPreview-link">
                                    {!!name ? name : <em>{t("(Untitled)")}</em>}
                                </Link>
                            </HeadingTag>
                            <DraftActions className="draftPreview-menu" deleteFunction={this.deleteArticle} url={url} />
                        </div>
                        <Paragraph className="draftPreview-excerpt">
                            {!!body ? body : <em>{t("(No Body)")}</em>}
                        </Paragraph>
                        <DraftPreviewMeta dateUpdated={dateUpdated} location={location!} />
                    </article>
                </li>
            );
        }
    }

    private deleteArticle() {
        alert(`To do - delete draft no: ${this.props.id}`);
    }
    private doRedirect = e => {
        e.stopPropagation();
        this.setState({
            doRedirect: true,
        });
    };
}
