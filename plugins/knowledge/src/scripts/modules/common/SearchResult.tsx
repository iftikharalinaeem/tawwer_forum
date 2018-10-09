/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { getRequiredID } from "@library/componentIDs";
import { t } from "@library/application";
import { ISentence } from "@library/components/Sentence";
import { loopableArray } from "@library/utility";
import Attachments, { IDetailedAttachment, IIconAttachment, AttachmentDisplay } from "./AttachmentIcons";

export interface IResult {
    name: string;
    className?: string;
    meta: ISentence[];
    url: string;
    excerpt: string;
    image?: string;
    children: IIconAttachment[] | IDetailedAttachment[];
    display: AttachmentDisplay;
}

interface IProps {
    className?: string;
    children: IResult[];
}

interface IState {}

export default class SearchResult extends React.Component<IResult, IState> {
    // public static defaultProps = {
    //     selectedIndex: 0,
    // };

    // public constructor(props) {
    //     super(props);
    //     this.state = {
    //         id: getRequiredID(props, "selectBox-"),
    //         selectedIndex: this.props.selectedIndex,
    //     };
    // }

    public render() {
        const hasAttachments = loopableArray(this.props.children);
        const image = this.props.image ? (
            <img src={this.props.image} alt={t("Thumbnail for: " + this.props.name)} aria-hidden={true} />
        ) : null;

        return (
            <li className={classNames("searchResult", this.props.className)}>
                <article className="searchResult-contents">
                    <div className="searchResult-main" />
                    <div className="searchResult-media">
                        {!hasAttachments && image}
                        <Attachments children={this.props.children} display={this.props.display} />
                    </div>
                </article>
            </li>
        );
    }
}
