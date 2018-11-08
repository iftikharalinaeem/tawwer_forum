/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { t } from "@library/application";
import Paragraph from "@library/components/Paragraph";
import Translate from "@library/components/translation/Translate";
import DraftPreview from "@knowledge/modules/drafts/components/DraftPreview";
import { IDraftPreview } from "@knowledge/modules/drafts/components/DraftPreview";
import Button from "@library/components/forms/Button";

interface IProps {
    className?: string;
    data: IDraftPreview[];
    hasMoreResults: boolean;
    loadMoreResults?: () => void;
}

/**
 * Generates a single search result. Note that this template is used in other contexts, such as the flat category list
 */
export default class DraftsList extends React.Component<IProps> {
    public render() {
        const hasResults = this.props.data.length > 0;
        const Tag = hasResults ? `ul` : `div`;
        let content;

        if (hasResults) {
            content = this.props.data.map((result, i) => {
                return <DraftPreview {...result} key={`draftPreview-${i}`} />;
            });
        } else {
            content = <Paragraph className="draftList-noResults">{t("You do not have any drafts.")}</Paragraph>;
        }

        return (
            <Tag className={classNames("draftList-list", this.props.className)}>
                {content}
                {this.props.hasMoreResults && <Button onClick={this.props.loadMoreResults}>{t("Next")}</Button>}
            </Tag>
        );
    }
}
