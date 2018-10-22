/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import classNames from "classnames";
import { t } from "@library/application";
import { fileExcel, fileWord, filePDF, fileGeneric } from "@library/components/Icons";
import Paragraph from "@library/components/Paragraph";

export enum AttachmentType {
    FILE = "file",
    PDF = "PDF",
    EXCEL = "excel",
    WORD = "word",
}

// Common to both attachment types
export interface IAttachmentIcon {
    name: string;
    type: AttachmentType;
}

// Attachment of type icon
interface IProps extends IAttachmentIcon {}

/**
 * Component representing 1 icon attachment.
 */
export default class AttachmentIcon extends React.Component<IProps> {
    public render() {
        return (
            <li className="attachmentsIcons-item">
                <div
                    className={classNames("attachmentsIcons-file", `attachmentsIcons-${this.props.type}`)}
                    title={t(this.props.type)}
                >
                    <span className="sr-only">
                        <Paragraph>{`${this.props.name} (${t("Type: ")}} ${t(this.props.type)}})`}</Paragraph>
                    </span>
                    {this.getAttachmentIcon(this.props.type)}
                </div>
            </li>
        );
    }

    private getAttachmentIcon(type: AttachmentType, className?: string) {
        switch (type) {
            case AttachmentType.EXCEL:
                return fileExcel(className);
            case AttachmentType.PDF:
                return filePDF(className);
            case AttachmentType.WORD:
                return fileWord(className);
            default:
                return fileGeneric(className);
        }
    }
}
