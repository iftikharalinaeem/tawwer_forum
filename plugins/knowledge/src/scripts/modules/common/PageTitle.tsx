/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";
import { PanelWidget } from "@knowledge/layouts/PanelLayout";
import PageHeading from "@library/components/PageHeading";
import { IArticle } from "@knowledge/@types/api";
import Sentence, { ISentence } from "@library/components/translation/Sentence";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import { IDeviceProps, Devices } from "@library/components/DeviceChecker";

interface IProps extends IDeviceProps {
    title: string;
    menu?: JSX.Element;
    meta?: ISentence;
    backUrl?: string | null;
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
            <PanelWidget>
                <PageHeading backUrl={backUrl} title={this.props.title} menu={this.props.menu} />
                {this.props.meta && (
                    <div className="pageMetas metas">
                        <Sentence {...this.props.meta} recursiveChildClass="meta pageMetas-meta" />
                    </div>
                )}
            </PanelWidget>
        );
    }
}

export default withDevice<IProps>(PageTitle);
