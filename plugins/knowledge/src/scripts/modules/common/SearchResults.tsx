/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import classNames from "classnames";
import { t } from "@library/application";
import SearchResult, { IResult } from "./SearchResult";
import Paragraph from "@library/components/Paragraph";
import Translate from "@library/components/translation/Translate";

interface IProps {
    className?: string;
    searchTerm?: string;
    results: IResult[];
}

/**
 * Generates a single search result. Note that this template is used in other contexts, such as the flat category list
 */
export default class SearchResults extends React.Component<IProps> {
    public render() {
        const hasResults = this.props.results && this.props.results.length > 0;
        let content;

        if (hasResults) {
            content = this.props.results.map((result, i) => {
                return <SearchResult {...result} key={i} />;
            });
        } else if (this.props.searchTerm === undefined || this.props.searchTerm === "") {
            content = <Paragraph className="searchResults-noResults">{t("No results found.")}</Paragraph>;
        } else {
            content = (
                <Paragraph className="searchResults-noResults isEmpty">
                    <Translate source="No results for '<0/>'." c0={this.props.searchTerm} />
                </Paragraph>
            );
        }

        const Tag = hasResults ? `ul` : `div`;

        return <Tag className={classNames("searchResults", this.props.className)}>{content}</Tag>;
    }
}
