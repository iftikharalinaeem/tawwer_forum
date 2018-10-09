/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { getRequiredID } from "@library/componentIDs";
import { t } from "@library/application";
import Sentence, { ISentence } from "@library/components/Sentence";
import { loopableArray } from "@library/utility";
import { Link } from "react-router-dom";
import Attachments, { IAttachmentsIcons, IAttachmentsDetailed, AttachmentDisplay } from "./Attachments";
import Paragraph from "@library/components/Paragraph";

export interface IResult {
    name: string;
    className?: string;
    meta: ISentence[];
    url: string;
    excerpt: string;
    image?: string;
    attachments?: IAttachmentsDetailed | IAttachmentsIcons;
    display: AttachmentDisplay;
}

export default class SearchResult extends React.Component<IResult> {
    public static defaultProps = {
        attachmentDisplay: AttachmentDisplay.ICON,
    };

    public render() {
        const hasAttachments = this.props.attachments && loopableArray(this.props.attachments.children);
        const image = this.props.image ? (
            <img src={this.props.image} alt={t("Thumbnail for: " + this.props.name)} aria-hidden={true} />
        ) : null;

        const attachments = this.props.attachments;
        let attachmentOutput;
        if (hasAttachments && attachments) {
            if (attachments.display === AttachmentDisplay.ICON) {
                attachmentOutput = <Attachments children={attachments.children} display={attachments.display} />;
            } else {
                attachmentOutput = <Attachments children={attachments.children} display={AttachmentDisplay.DETAILED} />;
            }
        }

        return (
            <li className={classNames("searchResults-item", this.props.className)}>
                <article className="searchResult-contents">
                    <Link to={this.props.url} className="searchResult">
                        <div className="searchResult-main">
                            <div className="searchResult-title">{this.props.name}</div>
                            {this.props.meta && <Sentence children={this.props.meta as any} />}
                            {this.props.excerpt && <Paragraph>{this.props.excerpt}</Paragraph>}
                        </div>
                        <div className="searchResult-media">
                            {!hasAttachments && image}
                            {attachmentOutput}
                        </div>
                    </Link>
                </article>
            </li>
        );
    }
}
