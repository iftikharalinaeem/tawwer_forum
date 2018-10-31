/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { PanelWidget } from "@knowledge/layouts/PanelLayout";
import PageHeading from "@library/components/PageHeading";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import { IDeviceProps, Devices } from "@library/components/DeviceChecker";
import classNames from "classnames";

interface IProps extends IDeviceProps {
    title: string;
    actions?: React.ReactNode;
    meta?: React.ReactNode;
    backUrl?: string | null;
    className?: string;
}

/**
 * Generates main title for page as well as possibly a back link and some meta information about the page
 */
export class PageTitle extends React.Component<IProps> {
    public render() {
        const { device } = this.props;
        const isDesktop = device === Devices.DESKTOP;
        const backUrl = isDesktop ? this.props.backUrl : null;
        return (
            <div className={classNames("pageTitleContainer", this.props.className)}>
                <PageHeading backUrl={backUrl} actions={this.props.actions}>
                    {this.props.title}
                </PageHeading>
                {this.props.meta && <div className="pageMetas metas">{this.props.meta}</div>}
            </div>
        );
    }
}

export default withDevice<IProps>(PageTitle);
