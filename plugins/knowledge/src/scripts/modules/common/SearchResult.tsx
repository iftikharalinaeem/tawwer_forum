/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { getRequiredID } from "@library/componentIDs";
import { t } from "@library/application";

interface IProps {
    className?: string;
    children: any[];
}
interface IState {}

export default class SearchResult extends React.Component<IProps, IState> {
    // public static defaultProps = {
    //     selectedIndex: 0,
    // };

    // public constructor(props) {
    //     super(props);
    //     this.state = {
    //         id: getRequiredID(props, "selectBox-"),
    //         selectedIndex: this.props.selectedIndex,
    //     };
    // }

    public render() {
        return (
            <li className={classNames("searchResult", this.props.className)}>
                <span className="searchResult-main" />
                <span className="searchResult-media">
                    <span className="searchResult-mediaFrame" />
                </span>
            </li>
        );
    }
}
