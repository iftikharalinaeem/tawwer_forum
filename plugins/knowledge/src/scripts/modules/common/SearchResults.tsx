/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import classNames from "classnames";
import { getRequiredID } from "@library/componentIDs";
import { t } from "@library/application";
import SearchResult, { IResult } from "./SearchResult";
import Paragraph from "@library/components/Paragraph";
import Sentence, { InlineTypes } from "@library/components/Sentence";

interface IProps {
    className?: string;
    searchTerm?: string;
    children: IResult[];
}
interface IState {
    children: IResult[];
}

/**
 * Generates a single search result. Note that this template is used in other contexts, such as the flat category list
 */
export default class SearchResults extends React.Component<IProps, IState> {
    public render() {
        const hasResults = this.props.children && this.props.children.length > 0;
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
            content = this.props.children.map((result, i) => {
                return <SearchResult {...result} key={`searchResults-${i}`} />;
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
