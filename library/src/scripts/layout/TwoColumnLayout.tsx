/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React from "react";
import PanelLayout, { IPanelLayoutProps } from "@library/layout/PanelLayout";
import { LayoutProvider } from "@library/layout/LayoutContext";
import { LayoutTypes } from "@library/layout/types/layouts";

interface IProps extends Omit<IPanelLayoutProps, "leftTop" | "leftBottom" | "renderLeftPanelBackground"> {}

export default function TwoColumnLayout(props: IProps) {
    return (
        <LayoutProvider type={LayoutTypes.TWO_COLUMNS}>
            <PanelLayout {...props} />;
        </LayoutProvider>
    );
}
