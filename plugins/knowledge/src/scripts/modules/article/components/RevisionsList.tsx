/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { t } from "@library/application";
import Heading from "@library/components/Heading";
import { IArticleRevisionFragment } from "@knowledge/@types/api";
import { Link } from "react-router-dom";
import DateTime from "@library/components/DateTime";
import classNames from "classnames";
import {
    revisionStatus_revision,
    revisionStatus_draft,
    revisionStatus_pending,
    revisionStatus_published,
    revisionStatus_deleted,
} from "@library/components/Icons";
import RevisionsListItem, { IArticleRevisionWithUrl } from "./RevisionsListItem";

interface IProps {
    children: IArticleRevisionWithUrl[];
}

/**
 * Implements the Article Revision History component
 */
export default class RevisionsList extends React.Component<IProps> {
    public render() {
        const history = this.props.children.map((child, index) => {
            return (
                <React.Fragment key={`revisionList-${index}`}>
                    <RevisionsListItem {...child} />
                </React.Fragment>
            );
        });

        return (
            <div className="revisionsList related">
                <Heading
                    className="panelList-title revisionsList-title"
                    title={t("Revisions")}
                    depth={1}
                    renderAsDepth={2}
                />
                <ul className="revisionsList-items panelList-items">{history}</ul>
            </div>
        );
    }
}
