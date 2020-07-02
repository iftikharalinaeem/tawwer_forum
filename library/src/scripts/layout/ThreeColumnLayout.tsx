/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React from "react";
import PanelLayout, { IPanelLayoutProps } from "@library/layout/PanelLayout";
import { LayoutProvider, LayoutTypes } from "@library/layout/LayoutContext";

interface IProps extends IPanelLayoutProps {}

export default function ThreeColumnLayout(props: IProps) {
    return (
        <LayoutProvider type={LayoutTypes.THREE_COLUMNS}>
            <PanelLayout {...props} />;
        </LayoutProvider>
    );
}
