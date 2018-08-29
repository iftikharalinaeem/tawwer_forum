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
                bottomPanel = <PanelArea className={ className('panelLayout-panel', bottom.className) } render={bottom.render}>
                    { bottom.children }
                </PanelArea>;
            }
            return (
                <React.Fragment>
                    <PanelArea className={ className('panelLayout-panel', top.className) } render={top.render}>
                        { this.props.children.top.children }
                    </PanelArea>
                    {bottomPanel}
                </React.Fragment>
            );
        } else {
            return null;
        }
    }
}
