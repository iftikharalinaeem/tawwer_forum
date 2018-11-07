/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { t } from "@library/application";
import { Link } from "react-router-dom";
import Paragraph from "@library/components/Paragraph";
import AttachmentIcons from "@knowledge/modules/common/AttachmentIcons";
import { IAttachmentIcon } from "@knowledge/modules/common/AttachmentIcon";
import { IKbCategoryFragment } from "@knowledge/@types/api/kbCategory";

export interface IDraftPreview {
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

/**
 * Generates search result list. Note that this template is used in other contexts, such as the flat category list
 */
export default class DraftPreview extends React.Component<IProps> {
    public static defaultProps = {
        headingLevel: 2,
    };

    public render() {
        const HeadingTag = `h${this.props.headingLevel}`;
        return (
            <li className={classNames("searchResults-item", this.props.className)}>
                <Link to={this.props.url} className="searchResult">
                    <article className="searchResults-result">
                        <HeadingTag className="searchResult-title">{this.props.name}</HeadingTag>
                        <Paragraph className="searchResult-excerpt">{this.props.body}</Paragraph>
                    </article>
                </Link>
            </li>
        );
    }
}
