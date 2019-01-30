/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { t } from "@library/application";
import Button from "@library/components/forms/Button";
import classNames from "classnames";
import * as React from "react";

interface IProps {
    onNextClick?: React.MouseEventHandler;
    onPreviousClick?: React.MouseEventHandler;
}

/**
 * Previous/next pagination for search results.
 */
export default class SearchPagination extends React.Component<IProps> {
    public render() {
        const { onNextClick, onPreviousClick } = this.props;

        const isSingle = (onNextClick && !onPreviousClick) || (!onNextClick && onPreviousClick);

        return (
            <div className="simplePager">
                {onPreviousClick && (
                    <Button
                        className={classNames(["simplePager-button", "simplePager-prev", { isSingle }])}
                        onClick={onPreviousClick}
                    >
                        {t("Previous")}
                    </Button>
                )}
                {onNextClick && (
                    <Button
                        className={classNames(["simplePager-button", "simplePager-next", { isSingle }])}
                        onClick={onNextClick}
                    >
                        {t("Next")}
                    </Button>
                )}
            </div>
        );
    }
}
