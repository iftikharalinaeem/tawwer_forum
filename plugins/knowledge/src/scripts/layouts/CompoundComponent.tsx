/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import * as React from "react";

export default class CompoundComponent<Props = {}, State = {}> extends React.Component<Props, State> {
    protected childIsOfType = (child: React.ReactNode, component: React.ComponentType): boolean => {
        const type = [component.displayName, component.name];

        // @ts-ignore
        const childType = child && child.type && (child.type.displayName || child.type.name);
        return type.includes(childType);
    };
}
