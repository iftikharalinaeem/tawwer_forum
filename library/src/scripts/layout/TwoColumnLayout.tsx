/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React from "react";
import PanelLayout, { IPanelLayoutProps } from "@library/layout/PanelLayout";
import { withLayout, LayoutProvider } from "@library/layout/LayoutContext";
import { LayoutTypes } from "@library/layout/types/interface.layoutTypes";

interface IProps extends Omit<IPanelLayoutProps, "leftTop" | "leftBottom" | "renderLeftPanelBackground"> {}

function TwoColumnLayout(props: IProps) {
    return (
        <LayoutProvider type={LayoutTypes.TWO_COLUMNS}>
            <PanelLayout {...props} />
        </LayoutProvider>
    );
}

export default withLayout(TwoColumnLayout);
