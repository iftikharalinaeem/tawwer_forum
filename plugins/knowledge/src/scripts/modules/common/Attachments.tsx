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
import { fileExcel, fileWord, filePDF, fileGeneric } from "@library/components/Icons";
import Paragraph from "@library/components/Paragraph";

export enum AttachmentType {
    FILE = "file",
    PDF = "PDF",
    EXCEL = "excel",
    WORD = "word",
}

export enum AttachmentDisplay {
    ICON = "icon",
    DETAILED = "details",
}

// Common to both attachment types
interface IAttachmentCommon {
    name: string;
    type: AttachmentType;
}

// Attachment of type icon
export interface IIconAttachment extends IAttachmentCommon {}

// Attachment of type detailed
export interface IDetailedAttachment extends IAttachmentCommon {
    url: string;
    metas?: Sentence[];
}

// Array of icon attachments
export interface IAttachmentsIcons {
    display: AttachmentDisplay.ICON;
    children: IIconAttachment[];
}

// Array of detailed attachments
export interface IAttachmentsDetailed {
    display: AttachmentDisplay.DETAILED;
    children: IDetailedAttachment[];
}

interface IState {
    id: string;
}

export default class SearchResult extends React.Component<IAttachmentsIcons | IAttachmentsDetailed, IState> {
    // public static defaultProps = {
    //     selectedIndex: 0,
    // };

    constructor(props) {
        super(props);
        this.state = {
            id: getRequiredID(props, "attachments-"),
        };
    }

    public get titleID() {
        return this.state.id + "-title";
    }

    public getAttachmentIcon(type: AttachmentType, className?: string) {
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

    public render() {
        const display = this.props.display;
        if (display === AttachmentDisplay.ICON) {
            const attachments = loopableArray(this.props.children)
                ? this.props.display === AttachmentDisplay.ICON &&
                  this.props.children.map((attachment, i) => {
                      return (
                          <li className="attachmentsIcons-item attachmentsIcons-items" key={`attachment-${i}`}>
                              <div
                                  className={classNames(
                                      "attachmentsIcons-file",
                                      `attachmentsIcons-${attachment.type.toLocaleLowerCase()}`,
                                  )}
                                  title={t(attachment.type)}
                              >
                                  <span className="sr-only">
                                      <Paragraph>{`${attachment.name} (${t("Type: ")}} ${t(
                                          attachment.type,
                                      )}})`}</Paragraph>
                                  </span>
                                  {this.getAttachmentIcon(attachment.type)}
                              </div>
                          </li>
                      );
                  })
                : null;

            if (attachments) {
                return (
                    <section className="attachments attachmentsIcons">
                        <h3 id={this.titleID} className="sr-only">
                            {t("Attachments: ")}
                        </h3>
                        <ul aria-labelledby={this.titleID} className="attachmentsIcons-items">
                            {attachments}
                        </ul>
                    </section>
                );
            } else {
                return null;
            }
        } else {
            return "TODO";
        }
    }
}
