/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { t } from "@library/application";
import Heading from "@library/components/Heading";

interface IProps {
    children: React.ReactNode;
    hideTitle?: boolean;
}

/**
 * Implements the Article Revision History component
 */
export default class RevisionsList extends React.Component<IProps> {
    public render() {
        return (
            <div className="revisionsList related">
                {!this.props.hideTitle && (
                    <Heading className="panelList-title revisionsList-title" title={t("Revisions")} depth={2} />
                )}
                <ul className="revisionsList-items panelList-items">{this.props.children}</ul>
            </div>
        );
    }
}
