/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { getRequiredID } from "@library/componentIDs";
import { t } from "@library/application";
import SearchResult from "./SearchResult";
import Paragraph from "@library/components/Paragraph";
import Sentence, { InlineTypes } from "@library/components/Sentence";
import { loopableArray } from "@library/utility";

interface IProps {
    className?: string;
    children: any[];
    searchTerm?: string;
}
interface IState {
    children: any[];
}

export default class SearchResults extends React.Component<IProps, IState> {
    public render() {
        const hasResults = loopableArray(this.props.children);
        let content;

        const noResultsMessage = {
            children: [
                {
                    children: "No results for '",
                    type: InlineTypes.TEXT,
                },
                {
                    children: this.props.searchTerm,
                    type: InlineTypes.TEXT,
                },
                {
                    children: "'.",
                    type: InlineTypes.TEXT,
                },
            ],
        };

        if (hasResults) {
            this.props.children.map((result, i) => {
                return <SearchResult {...result} />;
            });
        } else if (!this.props.searchTerm || this.props.searchTerm === "") {
            content = <Paragraph className="searchResults-noResults">{t("No results")}</Paragraph>;
        } else {
            content = (
                <Paragraph className="searchResults-noResults isEmpty">
                    <Sentence children={noResultsMessage as any} />
                </Paragraph>
            );
        }

        const Tag = hasResults ? `ul` : `div`;

        return <Tag className={classNames("searchResults", this.props.className)}>{content}</Tag>;
    }
}
