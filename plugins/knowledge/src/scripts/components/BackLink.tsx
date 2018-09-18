/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import classNames from "classnames";
import { t } from "@library/application";
// import { leftChevron } from "@library/components/Icons";

interface IBackLink {
    url?: string;
    title?: string;
    className?: string;
}

export default class BackLink extends React.Component<IBackLink> {
    public static defaultProps = {
        title: t("Back"),
    };
    public render() {
        if (this.props.url) {
            return (
                <div className={classNames("backLink", this.props.className)}>
                    <a
                        href={this.props.url}
                        aria-label={this.props.title}
                        title={this.props.title}
                        className="backLink-link"
                    >
                        {/* {leftChevron("backLink-icon")} */}
                    </a>
                </div>
            );
        } else {
            return null;
        }
    }
}
