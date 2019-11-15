/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "@library/utility/appUtils";
import DraftPreview from "@knowledge/modules/drafts/components/DraftPreview";
import { IResponseArticleDraft } from "@knowledge/@types/api/article";
import ResultList from "@library/result/ResultList";

interface IProps {
    className?: string;
    data: IResponseArticleDraft[];
}

/**
 * Generates a list of drafts
 */
export default class DraftsList extends React.Component<IProps> {
    public render() {
        return (
            <ResultList
                results={this.props.data}
                result={DraftPreview as any}
                emptyMessage={t("You do not have any drafts.")}
                className={this.props.className}
            />
        );
    }
}
