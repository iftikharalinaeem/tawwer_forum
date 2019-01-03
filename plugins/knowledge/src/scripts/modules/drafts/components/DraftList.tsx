/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { t } from "@library/application";
import Paragraph from "@library/components/Paragraph";
import DraftPreview from "@knowledge/modules/drafts/components/DraftPreview";
import { IResponseArticleDraft } from "@knowledge/@types/api/article";

interface IProps {
    className?: string;
    data: IResponseArticleDraft[];
}

/**
 * Generates a list of drafts
 */
export default class DraftsList extends React.Component<IProps> {
    public render() {
        const { data, className } = this.props;
        const Tag = data.length > 0 ? `ul` : `div`;
        let content;

        if (data.length > 0) {
            content = data.map((result, i) => {
                return <DraftPreview {...result} key={`draftPreview-${i}`} />;
            });
        } else {
            content = <Paragraph className="draftList-noResults">{t("You do not have any drafts.")}</Paragraph>;
        }

        return (
            <div className="draftList">
                <Tag className={classNames("draftList-list", className)}>{content}</Tag>
            </div>
        );
    }
}
