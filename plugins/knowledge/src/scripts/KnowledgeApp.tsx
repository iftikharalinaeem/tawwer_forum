/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { Provider } from "react-redux";
import getStore from "@dashboard/state/getStore";
import KnowledgeRoutes from "@knowledge/KnowledgeRoutes";
import DeviceContext from "./contexts/DeviceContext";
import DeviceChecker, {Devices} from "./components/DeviceChecker";

export default class KnowledgeApp extends React.Component {
    public deviceChecker: React.RefObject<DeviceChecker> = React.createRef();

    public render() {
        const store = getStore();
        return (
            <Provider store={store}>
                <React.Fragment>
                    <DeviceChecker ref={this.deviceChecker} doUpdate={this.forceUpdate}/>
                    <DeviceContext.Provider value={this.deviceChecker.current ? this.deviceChecker.current.device : Devices.DESKTOP}>
                        <KnowledgeRoutes />
                    </DeviceContext.Provider>
                </React.Fragment>
            </Provider>
        );
    }
}
