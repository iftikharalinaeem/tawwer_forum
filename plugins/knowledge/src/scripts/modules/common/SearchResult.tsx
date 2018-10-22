/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import classNames from "classnames";
import { t } from "@library/application";
import Sentence, { ISentence } from "@library/components/translation/Sentence";
import { Link } from "react-router-dom";
import Paragraph from "@library/components/Paragraph";
import AttachmentIcons from "@knowledge/modules/common/AttachmentIcons";
import { IAttachmentIcon } from "@knowledge/modules/common/AttachmentIcon";

export interface IResult {
    name: string;
    className?: string;
    meta: React.ReactNode;
    url: string;
    excerpt: string;
    image?: string;
    headingLevel?: 2 | 3;
    attachments: IAttachmentIcon[];
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
        const showImage = this.props.image && !hasAttachments;
        const hasMedia = hasAttachments || showImage;
        const image = showImage ? (
            <img
                src={this.props.image}
                className="searchResult-image"
                alt={t("Thumbnail for: " + this.props.name)}
                aria-hidden={true}
            />
        ) : null;

        let attachmentOutput;
        if (hasAttachments && this.props.attachments) {
            attachmentOutput = <AttachmentIcons attachments={this.props.attachments} />;
        }
        const HeadingTag = `h${this.props.headingLevel}`;

        const media = hasMedia ? (
            <div className={classNames("searchResult-media", { hasImage: showImage })}>
                {showImage && image}
                {attachmentOutput}
            </div>
        ) : null;

        return (
            <li className={classNames("searchResults-item", this.props.className)}>
                <article className="searchResults-result">
                    <Link to={this.props.url} className="searchResult">
                        <div className={classNames("searchResult-main", { hasMedia: !!media })}>
                            <HeadingTag className="searchResult-title">{this.props.name}</HeadingTag>
                            {this.props.meta && <div className="searchResult-metas">{this.props.meta}</div>}
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
