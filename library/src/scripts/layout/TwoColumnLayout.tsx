/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React from "react";
import PanelLayout, { IPanelLayoutProps } from "@library/layout/PanelLayout";
import { withLayout } from "@library/layout/LayoutContext";

interface IProps extends Omit<IPanelLayoutProps, "leftTop" | "leftBottom" | "renderLeftPanelBackground"> {}

export function TwoColumnLayout(props: IProps) {
    return <PanelLayout {...props} isFixed={props.isFixed !== undefined ? props.isFixed : true} />;
}

export default withLayout(TwoColumnLayout);
