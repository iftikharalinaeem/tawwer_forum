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
                    <div className="panelLayout-container">
                        <Panel className="panelLayout-left" render={this.props.renderLeftPanel}>
                            {
                                {
                                    top: {
                                        className: "panelArea-breadcrumbs isLeft",
                                        children: <span dangerouslySetInnerHTML={{ __html: ` ` }}/>,
                                    },
                                }
                            }
                        </Panel>
                        <Panel className="panelLayout-breadcrumbs" render={this.props.renderLeftPanel}>
                            {
                                {
                                    top: {
                                        className: "panelArea-breadcrumbs isRight",
                                        children: <Breadcrumbs {...this.props.breadcrumbs} />,
                                    },
                                }
                            }
                        </Panel>
                    </div>
                </div>
            );
        } else {
            return null;
        }
    }
}
