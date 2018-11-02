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
import Translate from "@library/components/translation/Translate";

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
            <li className="attachments-item">
                <div
                    className={classNames("attachment-file", `attachment-${this.props.type}`)}
                    title={t(this.props.type)}
                >
                    <span className="sr-only">
                        <Paragraph>
                            <Translate source="<0/> (Type: <1/>)" c0={this.props.name} c1={this.props.type} />
                        </Paragraph>
                    </span>
                    {getAttachmentIcon(this.props.type)}
                </div>
            </li>
        );
    }
}

export function getAttachmentIcon(type: AttachmentType, className?: string) {
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

export function getUnabbreviatedAttachmentType(type: AttachmentType) {
    switch (type) {
        case AttachmentType.EXCEL:
            return t("Microsoft Excel Document");
        case AttachmentType.PDF:
            return t("Adobe Portable Document Format");
        case AttachmentType.WORD:
            return t("Microsoft Word Document");
        default:
            return null;
    }
}
