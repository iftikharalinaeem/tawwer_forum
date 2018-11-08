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

export interface IDraftPreview {
    id: number;
    name: string | null;
    body: string | null;
    dateUpdated: string;
    url: string;
    location?: IKbCategoryFragment[] | string[];
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
        const HeadingTag = `h${this.props.headingLevel}`;
        // We can't nest links, so we need to simulate a click on the <li> element
        if (this.state.doRedirect) {
            return <Redirect to={this.props.url} />;
        } else {
            return (
                <li className={classNames("draftPreview", this.props.className)} onClick={this.doRedirect}>
                    <article className="draftPreview-item">
                        <div className="draftPreview-header">
                            <HeadingTag className="searchResult-title" level={this.props.headingLevel}>
                                <Link to={this.props.url} className="draftPreview-link">
                                    {this.props.name}
                                </Link>
                            </HeadingTag>
                            <DraftActions
                                className="draftPreview-menu"
                                deleteFunction={this.deleteArticle}
                                url={this.props.url}
                            />
                        </div>
                        <Paragraph className="draftPreview-excerpt">{this.props.body}</Paragraph>
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
