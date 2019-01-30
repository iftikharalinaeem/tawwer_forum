/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { t } from "@library/application";
import { ILinkPages } from "@library/simplePager/SimplePagerModel";
import classNames from "classnames";
import * as React from "react";
import SearchPageActions from "../SearchPageActions";
import Button from "@library/components/forms/Button";

interface IProps {
    searchActions: SearchPageActions;
    pages: ILinkPages;
}

/**
 * Previous/next pagination for search results.
 */
export default class SearchPagination extends React.Component<IProps> {
    public render() {
        const { next, prev } = this.props.pages;

        const isSingle = (prev && !next) || (!prev && next);

        return (
            <div className="simplePager">
                {prev && (
                    <Button
                        className={classNames(["simplePager-button", "simplePager-prev", { isSingle }])}
                        onClick={e => {
                            this.props.searchActions.search(prev);
                        }}
                    >
                        {t("Previous")}
                    </Button>
                )}
                {next && (
                    <Button
                        className={classNames(["simplePager-button", "simplePager-next", { isSingle }])}
                        onClick={e => {
                            this.props.searchActions.search(next);
                        }}
                    >
                        {t("Next")}
                    </Button>
                )}
            </div>
        );
    }
}
