/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { PanelWidget } from "@library/components/layouts/PanelLayout";
import PageHeading from "@library/components/PageHeading";
import { withDevice } from "@library/contexts/DeviceContext";
import { IDeviceProps, Devices } from "@library/components/DeviceChecker";
import classNames from "classnames";

interface IProps {
    title: string;
    actions?: React.ReactNode;
    meta?: React.ReactNode;
    className?: string;
}

/**
 * Generates main title for page as well as possibly a back link and some meta information about the page
 */
export default class PageTitle extends React.Component<IProps> {
    public render() {
        return (
            <div className={classNames("pageTitleContainer", this.props.className)}>
                <PageHeading actions={this.props.actions} title={this.props.title} />
                {this.props.meta && <div className="pageMetas metas">{this.props.meta}</div>}
            </div>
        );
    }
}
