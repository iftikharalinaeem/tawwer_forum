/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { t } from "@library/application";
import Paragraph from "@library/components/Paragraph";
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
 * Generates a list of drafts
 */
export default class DraftsList extends React.Component<IProps> {
    public render() {
        const { hasMoreResults, data, className } = this.props;
        const Tag = hasMoreResults ? `ul` : `div`;
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
                {hasMoreResults && (
                    <Button className="draftList-loadMore" onClick={this.props.loadMoreResults}>
                        {t("Next")}
                    </Button>
                )}
            </div>
        );
    }
}
