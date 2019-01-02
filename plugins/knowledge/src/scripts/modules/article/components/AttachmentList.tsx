/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import { t } from "@library/application";
import AttachmentItem, { IFileAttachment } from "@knowledge/modules/article/components/AttachmentItem";

interface IProps {
    attachments: IFileAttachment[];
}

/**
 * Implements file attachment list component
 */
export default class AttachmentList extends React.Component<IProps> {
    private id = uniqueIDFromPrefix("fileAttachments-");

    public get titleID() {
        return this.id + "-title";
    }

    public render() {
        const attachments = this.props.attachments.map((attachment, index) => {
            return <AttachmentItem {...attachment} key={index} />;
        });
        if (attachments) {
            return (
                <section className="attachments">
                    <h3 id={this.titleID} className="sr-only">
                        {t("Attachments: ")}
                    </h3>
                    <ul aria-labelledby={this.titleID} className="attachments-list">
                        {attachments}
                    </ul>
                </section>
            );
        } else {
            return null;
        }
    }
}
