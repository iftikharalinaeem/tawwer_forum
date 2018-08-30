import * as React from "react";
import className from "classnames";
import PanelArea, {IPanelArea} from "./PanelArea";

interface IPanel {
    className?: string;
    children: {
        top: IPanelArea;
        bottom?: IPanelArea;
    };
    render?: boolean;
}


export default class Panel extends React.Component<IPanel> {
    public static defaultProps = {
        render: true,
    };

    public render() {
        if (this.props.render) {
            const top = this.props.children.top;
            const bottom = this.props.children.bottom;

            let bottomPanel;
            if (bottom) {
                bottomPanel = <PanelArea className={ bottom.className } render={bottom.render}>
                    { bottom.children }
                </PanelArea>;
            }
            return (
                <div className={className('panelLayout-panel', this.props.className)}>
                    <PanelArea className={ top.className } render={top.render}>
                        { this.props.children.top.children }
                    </PanelArea>
                    {bottomPanel}
                </div>
            );
        } else {
            return null;
        }
    }
}
