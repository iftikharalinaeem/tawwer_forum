/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React from "react";
import PanelLayout, { IPanelLayoutProps } from "@library/layout/PanelLayout";
import { LayoutProvider, useLayout, withLayout } from "@library/layout/LayoutContext";
import { threeColumnLayoutClasses } from "@library/layout/types/layout.threeColumns";
import { LayoutTypes } from "@library/layout/types/interface.layoutTypes";

interface IProps extends Omit<IPanelLayoutProps, "classes"> {}

export function ThreeColumnLayout(props: IProps) {
    const { mediaQueries } = useLayout();
    return (
        <LayoutProvider type={LayoutTypes.THREE_COLUMNS}>
            <PanelLayout {...props} classes={threeColumnLayoutClasses()} />;
        </LayoutProvider>
    );
}

export default withLayout(ThreeColumnLayout);
