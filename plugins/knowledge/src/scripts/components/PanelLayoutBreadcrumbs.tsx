/**
 * @author Stéphane (slafleche) LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import * as React from "react";
import className from "classnames";
import { t } from "@dashboard/application";
import Panel from "@knowledge/components/Panel";
import Breadcrumbs, { IBreadcrumbsProps } from "@knowledge/components/Breadcrumbs";
import classNames from "classnames";

interface IPanelLayoutBreadcrumbs {
    breadcrumbs?: IBreadcrumbsProps;
    renderLeftPanel?: boolean;
    className?: string;
    render?: boolean;
}

export default class PanelLayoutBreadcrumbs extends React.Component<IPanelLayoutBreadcrumbs> {
    public static defaultProps = {
        render: true,
        renderLeftPanel: true,
    };

    public render() {
        if (this.props.render && this.props.breadcrumbs && this.props.breadcrumbs.children.length > 1) {
            const space = ` `;
            return (
                <div
                    className={className(
                        "panelLayout-top",
                        { noLeftPanel: !this.props.renderLeftPanel },
                        this.props.className,
                    )}
                >
                    <div className="panelLayout-container">
                        <Panel className="panelLayout-left" render={this.props.renderLeftPanel}>
                            {{
                                top: {
                                    className: "panelArea-breadcrumbsSpacer",
                                    children: <React.Fragment>{space}</React.Fragment>,
                                    render: this.props.renderLeftPanel,
                                },
                            }}
                        </Panel>
                        <Panel
                            className={className("panelLayout-breadcrumbs", {
                                hasAdjacentPanel: this.props.renderLeftPanel,
                            })}
                        >
                            {{
                                top: {
                                    className: "panelArea-breadcrumbs",
                                    children: <Breadcrumbs {...this.props.breadcrumbs} />,
                                },
                            }}
                        </Panel>
                    </div>
                </div>
            );
        } else {
            return null;
        }
    }
}
