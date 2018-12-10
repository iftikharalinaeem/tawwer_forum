/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import Loadable, { LoadableComponent } from "react-loadable";
import FullPageLoader from "@library/components/FullPageLoader";
import { Route, NavLinkProps, NavLink } from "react-router-dom";
import { Omit } from "react-redux";

type LoadFunction = () => Promise<any>;

/**
 * Class for managing routing and matching a particular page.
 */
export default class RouteHandler<GeneratorProps> {
    public loadable;
    public route: React.ReactNode;
    private key: string;

    public constructor(
        componentPromise: LoadFunction,
        public path: string,
        public url: (data: GeneratorProps) => string,
        loadingComponent: React.ReactNode = FullPageLoader,
        key?: string,
    ) {
        this.loadable = Loadable({
            loading: loadingComponent,
            loader: componentPromise,
        });
        this.key = key || path;
        this.route = <Route exact path={this.path} component={this.loadable} key={this.key} />;
    }

    public Link = (props: Omit<NavLinkProps, "to"> & { data: GeneratorProps }) => {
        return <NavLink {...props} to={this.url(props.data)} onMouseOver={this.preload} />;
    };

    public preload = () => {
        (this.loadable as LoadableComponent).preload();
    };
}
