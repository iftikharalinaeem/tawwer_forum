import * as React from "react";
import className from "classnames";
import { t } from "@dashboard/application";
import Panel from "./Panel";
import Breadcrumbs, {IBreadcrumbsProps} from "./Breadcrumbs";

interface IPanelLayoutBreadcrumbs {
    breadcrumbs?: IBreadcrumbsProps;
    renderLeftPanel?: boolean;
    className?: string;
}

export default class PanelLayoutBreadcrumbs extends React.Component<IPanelLayoutBreadcrumbs> {
    public static defaultProps = {
        renderLeftPanel: true,
    };

    public render() {
        if (this.props.breadcrumbs && this.props.breadcrumbs.children.length > 1) {
            return (
                <div className={className("panelLayout-top", this.props.className)}>
                    <Panel className="panelLayout-leftPanel" render={this.props.renderLeftPanel}>
                        {
                            {
                                top: {
                                    className: "panelLayout-breadcrumbEmpty",
                                    children: ` `,
                                },
                            }
                        }
                    </Panel>
                    <Panel className="panelLayout-breadcrumbs" render={this.props.renderLeftPanel}>
                        {
                            {
                                top: {
                                    className: "panelLayout-breadcrumbArea",
                                    children: <Breadcrumbs {...this.props.breadcrumbs} />,
                                },
                            }
                        }
                    </Panel>
                </div>
            );
        } else {
            return null;
        }
    }
}
