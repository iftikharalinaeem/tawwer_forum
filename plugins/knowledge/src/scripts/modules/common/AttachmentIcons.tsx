/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import classNames from "classnames";
import { getRequiredID } from "@library/componentIDs";
import { t } from "@library/application";
import Sentence, { ISentence, InlineTypes } from "@library/components/Sentence";
import { fileExcel, fileWord, filePDF, fileGeneric } from "@library/components/Icons";
import Paragraph from "@library/components/Paragraph";

export enum AttachmentType {
    FILE = "file",
    PDF = "PDF",
    EXCEL = "excel",
    WORD = "word",
}

// Common to both attachment types
interface IAttachmentCommon {
    name: string;
    type: AttachmentType;
}

// Attachment of type icon
export interface IIconAttachment extends IAttachmentCommon {}

// Array of icon attachments
export interface IAttachmentsIcons {
    children: IIconAttachment[];
    maxCount?: number;
}

interface IState {
    id: string;
}

/**
 * Generates a list of attachment icons
 */
export default class AttachmentIcons extends React.Component<IAttachmentsIcons, IState> {
    private maxCount;

    constructor(props) {
        super(props);
        this.state = {
            id: getRequiredID(props, "attachments-"),
        };
        this.maxCount = 3;
        if (props.maxCount && props.maxCount > 0 && props.maxCount <= props.children.length) {
            this.maxCount = props.maxCount;
        }
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
        const childrenCount = !!this.props.children ? this.props.children.length : 0;
        const attachments =
            this.props.children && this.props.children.length > 0
                ? this.props.children.map((attachment, i) => {
                      const key = `attachment-${i}`;
                      const index = i + 1;
                      const extraCount = childrenCount - index;
                      if (i < this.maxCount) {
                          return (
                              <li className="attachmentsIcons-item" key={key}>
                                  <div
                                      className={classNames(
                                          "attachmentsIcons-file",
                                          `attachmentsIcons-${attachment.type}`,
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
                      } else if (i === this.maxCount && extraCount > 0) {
                          const moreMessage = {
                              children: [
                                  {
                                      children: "+ ",
                                      type: InlineTypes.TEXT,
                                  },
                                  {
                                      children: `${extraCount}`,
                                      type: InlineTypes.TEXT,
                                      className: "attachmentsIcons-moreCount",
                                  },
                                  {
                                      children: " more",
                                      type: InlineTypes.TEXT,
                                  },
                              ],
                          };

                          return (
                              <li className="attachmentsIcons-item" key={key}>
                                  <span
                                      className={"attachmentsIcons-more"}
                                      title={t(attachment.type.toLocaleLowerCase())}
                                  >
                                      {<Sentence className="metaStyle" children={...moreMessage as any} />}
                                  </span>
                              </li>
                          );
                      } else {
                          return null;
                      }
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
    }
}
