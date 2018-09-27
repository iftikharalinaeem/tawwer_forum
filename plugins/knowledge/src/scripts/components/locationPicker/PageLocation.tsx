/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { t } from "../../../../../../library/src/scripts/application";
import classNames from "classnames";
import { IBreadcrumbsProps } from "../Breadcrumbs";

/**
 * Opens modal with the LocationChooser
 */
function openLocationChooser() {
    alert("do thing");
}

export interface IState {
    id: string;
}

/**
 * This component allows to display and edit the location of the current page.
 * Calls the LocationChooser component when clicked.
 */
export default class PageLocation extends React.Component<IBreadcrumbsProps, IState> {
    public render() {
        const accessibleCrumbSeparator = `/`;
        let content;
        let crumbTitle;

        if (this.props.children && this.props.children.length > 0) {
            const crumbCount = this.props.children.length - 1;
            crumbTitle = t("Page Location: ") + accessibleCrumbSeparator;
            const crumbs = this.props.children.map((crumb, index) => {
                const lastElement = index === crumbCount;
                const crumbSeparator = `›`;
                crumbTitle += crumb.name;
                if (!lastElement) {
                    crumbTitle += accessibleCrumbSeparator;
                }
                return (
                    <React.Fragment key={`locationBreadcrumb-${index}`}>
                        <span className="breadcrumb-link">{crumb.name}</span>
                        {!lastElement && (
                            <span className="breadcrumb-item breadcrumbs-separator">
                                <span aria-hidden={true} className="breadcrumbs-separatorIcon">
                                    {crumbSeparator}
                                </span>
                                <span className="sr-only">{accessibleCrumbSeparator}</span>
                            </span>
                        )}
                    </React.Fragment>
                );
            });

            content = <span className="breadcrumbs">{crumbs}</span>;
        } else {
            content = t("Set Page Location");
            crumbTitle = content;
        }

        return (
            <fieldset className={classNames("pageLocation", this.props.className)}>
                <legend className="pageLocation-label">
                    <span className="sr-only">
                        {/* We need more context for screen readers*/}
                        {t("Article Location:")}
                    </span>
                    <span className="pageLocation-label" aria-hidden={true}>
                        {t("To:")}
                    </span>
                </legend>
                <button title={crumbTitle} type="button" className="pageLocation" onClick={openLocationChooser}>
                    {content}
                </button>
            </fieldset>
        );
    }
}
