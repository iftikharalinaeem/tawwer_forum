/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { Provider } from "react-redux";
import getStore from "@library/state/getStore";
import KnowledgeRoutes from "@knowledge/routes/KnowledgeRoutes";
import DeviceContext from "@knowledge/contexts/DeviceContext";
import DeviceChecker, { Devices } from "@library/components/DeviceChecker";
import { Route, BrowserRouter } from "react-router-dom";
import CategoryActions from "@knowledge/modules/categories/CategoryActions";
import apiv2 from "@library/apiv2";
import { IStoreState } from "@knowledge/state/model";
import { LoadStatus } from "@library/@types/api";
import { LinkContext } from "@library/components/navigation/SmartLink";
import { formatUrl } from "@library/application";

/*
 * Top level application component for knowledge.
 * This is made to mounted with ReactDOM.
 */
export default class KnowledgeApp extends React.Component {
    public deviceChecker: React.RefObject<DeviceChecker> = React.createRef();

    private categoryActions = new CategoryActions(getStore().dispatch, apiv2);

    private store = getStore<IStoreState>();

    /**
     * Device checker detects device and calls a force update if needed to update the current device.
     */
    public render() {
        return (
            <Provider store={this.store}>
                <LinkContext.Provider value={formatUrl("/kb", true)}>
                    <React.Fragment>
                        <DeviceChecker ref={this.deviceChecker} doUpdate={this.doUpdate} />
                        <DeviceContext.Provider
                            value={this.deviceChecker.current ? this.deviceChecker.current.device : Devices.DESKTOP}
                        >
                            <BrowserRouter>
                                <Route component={KnowledgeRoutes} />
                            </BrowserRouter>
                        </DeviceContext.Provider>
                    </React.Fragment>
                </LinkContext.Provider>
            </Provider>
        );
    }

    /**
     * After the component mounts we need to update it so the results from the deviceChecker ref get passed through.
     */
    public componentDidMount() {
        this.doUpdate();
        if (this.store.getState().knowledge.categories.status !== LoadStatus.SUCCESS) {
            void this.categoryActions.getAllCategories();
        }
    }

    /**
     * Function to force rerendering
     */
    private doUpdate = () => {
        this.forceUpdate();
    };
}
