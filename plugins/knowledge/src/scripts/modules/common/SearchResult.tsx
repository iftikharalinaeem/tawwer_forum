/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import classNames from "classnames";
import { getRequiredID } from "@library/componentIDs";
import { t } from "@library/application";
import Sentence, { ISentence } from "@library/components/Sentence";
import { Link } from "react-router-dom";
import Paragraph from "@library/components/Paragraph";
import AttachmentIcons, { IIconAttachment } from "@knowledge/modules/common/AttachmentIcons";

export interface IResult {
    name: string;
    className?: string;
    meta: ISentence[];
    url: string;
    excerpt: string;
    image?: string;
    headingLevel?: 2 | 3;
    attachments: IIconAttachment[];
}

/**
 * Generates search result list. Note that this template is used in other contexts, such as the flat category list
 */
export default class SearchResult extends React.Component<IResult> {
    public static defaultProps = {
        headingLevel: 3,
    };

    public render() {
        const hasAttachments = this.props.attachments && this.props.attachments.length > 0;
        const image = this.props.image ? (
            <img
                src={this.props.image}
                className="searchResult-image"
                alt={t("Thumbnail for: " + this.props.name)}
                aria-hidden={true}
            />
        ) : null;

        let attachmentOutput;
        if (hasAttachments && this.props.attachments) {
            attachmentOutput = <AttachmentIcons children={this.props.attachments} />;
        }
        const HeadingTag = `h${this.props.headingLevel}`;

        const media =
            hasAttachments || !!image ? (
                <div className="searchResult-media">
                    {!hasAttachments && image}
                    {attachmentOutput}
                </div>
            ) : null;

        return (
            <li className={classNames("searchResults-item", this.props.className)}>
                <article className="searchResults-result">
                    <Link to={this.props.url} className="searchResult">
                        <div className={classNames("searchResult-main", { hasMedia: !!media })}>
                            <HeadingTag className="searchResult-title">{this.props.name}</HeadingTag>
                            {this.props.meta && (
                                <div className="searchResult-metas">
                                    <Sentence
                                        directChildClass="metas"
                                        descendantChildClasses="meta"
                                        children={this.props.meta as any}
                                    />
                                </div>
                            )}
                            {!!this.props.excerpt && (
                                <Paragraph className="searchResult-excerpt">{this.props.excerpt}</Paragraph>
                            )}
                        </div>
                        {media}
                    </Link>
                </article>
            </li>
        );
    }
}
