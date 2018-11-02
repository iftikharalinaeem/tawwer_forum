/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { uniqueIDFromPrefix } from "@library/componentIDs";
import { t } from "@library/application";
import Translate from "@library/components/translation/Translate";
import DateTime from "@library/components/DateTime";
import { download } from "@library/components/Icons";
import { getAttachmentIcon, AttachmentType } from "@library/components/attachments";

export interface IFileAttachment {
    name: string; // File name
    title?: string; // Optional other label for file
    type: AttachmentType;
    url: string;
    dateUploaded: string;
    sizeValue: string;
    sizeUnit: string;
    mimeType?: string;
}

export function getUnabbreviatedFileSizeUnit(unit: string) {
    switch (unit.toLowerCase()) {
        case "bit":
            return t("Bit");
        case "byte":
            return t("Byte");
        case "kilobyte":
            return t("Kilobyte");
        case "megabyte":
            return t("Megabyte");
        default:
            return null;
    }
}

/**
 * Implements file attachment item
 */
export default class AttachmentItem extends React.PureComponent<IFileAttachment> {
    public render() {
        const { title, name, type, url, dateUploaded, mimeType, sizeValue, sizeUnit } = this.props;
        const unabbreviatedTitle = getUnabbreviatedFileSizeUnit(sizeUnit);
        return (
            <li className="attachment">
                <a href={url} className="attachment-link" type={mimeType} download={name} tabIndex={-1}>
                    <div className="attachment-format">{getAttachmentIcon(type)}</div>
                    <div className="attachment-main">
                        <div className="attachment-title">{title || name}</div>
                        <div className="attachment-metas metas">
                            {dateUploaded && (
                                <span className="meta">
                                    <Translate source="Uploaded <0/>" c0={<DateTime timestamp={dateUploaded} />} />
                                </span>
                            )}
                            <span className="meta">
                                {sizeValue}
                                <abbr title={unabbreviatedTitle || undefined}>{sizeUnit}</abbr>
                            </span>
                        </div>
                    </div>
                    <div className="attachment-download">{download()}</div>
                </a>
            </li>
        );
    }
}
