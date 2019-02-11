/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { ReactNode } from "react";
import { IDeviceProps } from "@library/components/DeviceChecker";
import { withDevice } from "@library/contexts/DeviceContext";
import Paragraph from "@library/components/Paragraph";
import Container from "@library/components/layouts/components/Container";
import { formatUrl, t } from "@library/application";
import { LoadStatus, IApiError } from "@library/@types/api";
import DocumentTitle from "@library/components/DocumentTitle";
import UsersModel, { IInjectableUserState } from "@library/users/UsersModel";
import { connect } from "react-redux";
import Heading from "@library/components/Heading";
import VanillaHeader from "@library/components/headers/VanillaHeader";
import LinkAsButton from "@library/components/LinkAsButton";
import { EditorRoute } from "@knowledge/routes/pageRoutes";
import Permission from "@library/users/Permission";
import { ButtonBaseClass } from "@library/components/forms/Button";
import { searchError } from "@library/components/icons";
import { style } from "typestyle";
import { globalVariables } from "@library/styles/globalStyleVars";
import { inheritHeightClass, flexHelper } from "@library/styles/styleHelpers";
import classNames from "classnames";
import { PanelWidget, PanelWidgetVerticalPadding } from "@library/components/layouts/PanelLayout";
import { percent, px, viewHeight } from "csx";
import { debugHelper } from "@library/styles/styleHelpers";
import { buttonClasses, ButtonTypes } from "@library/styles/buttonStyles";

export class ErrorPage extends React.Component<IProps> {
    public render() {
        const vars = globalVariables();
        const error = this.getError();
        const flexClasses = flexHelper();
        const debug = debugHelper("errorPage");
        const classes = {
            inheritHeight: inheritHeightClass(),
            root: style({
                ...flexClasses.middle(),
                maxWidth: percent(100),
                width: px(400),
                margin: "auto",
                marginBottom: viewHeight(25),
                ...debug.name(),
            }),
            title: style({
                fontSize: px(vars.fonts.size.smallTitle),
                lineHeight: vars.lineHeights.condensed,
                fontWeight: vars.fonts.weights.semiBold,
                textAlign: "center",
                ...debug.name("title"),
            }),
            description: style({
                textAlign: "center",
                fontSize: px(vars.fonts.size.large),
                marginTop: px(12),
                ...debug.name("description"),
            }),
            cta: style({
                marginTop: px(21),
                ...debug.name("cta"),
            }),
        };

        return (
            <DocumentTitle title={error.message}>
                <VanillaHeader />
                <Container className={classes.inheritHeight}>
                    <PanelWidgetVerticalPadding className={classes.inheritHeight}>
                        <PanelWidget className={classes.inheritHeight}>
                            <div className={classNames(classes.root, classes.inheritHeight)}>
                                {error.icon}
                                <Heading depth={1} className={classes.title} title={error.message} />
                                {error.description && (
                                    <Paragraph className={classes.description}>{error.description}</Paragraph>
                                )}
                                {error.actionItem && <div className={classes.cta}>{error.actionItem}</div>}
                            </div>
                        </PanelWidget>
                    </PanelWidgetVerticalPadding>
                </Container>
            </DocumentTitle>
        );
    }

    private getError() {
        return {
            ...this.parseDefaultError(),
            ...(this.props.error || {}),
        };
    }

    private parseDefaultError(): IError {
        const errorCode = this.props.apiError ? this.props.apiError.status : null;
        const globalVars = globalVariables();
        const debug = debugHelper("errorPage");
        const errorIconClass = style({
            display: "block",
            color: globalVars.mainColors.primary.toString(),
            height: px(85),
            width: px(85),
            marginBottom: px(12),
            ...debug.name("icon"),
        });
        const buttons = buttonClasses();
        switch (errorCode || this.props.defaultError) {
            case 403:
            case DefaultError.PERMISSION: {
                const message = t("No Permission");
                return {
                    message,
                    description: t("You don't have permission to view this resource."),
                    actionItem: this.renderSignin(),
                    icon: searchError(message, errorIconClass),
                };
            }
            case 404:
            case DefaultError.NOT_FOUND: {
                const message = t("Page not found");
                return {
                    message,
                    description: t("The page you were looking for could not be found."),
                    actionItem: (
                        <LinkAsButton className={buttons(ButtonTypes.PRIMARY)} to={"/kb"}>
                            {t("Back to home page")}
                        </LinkAsButton>
                    ),
                    icon: searchError(message, errorIconClass),
                };
            }
            case DefaultError.NO_KNOWLEDGE_BASE: {
                const message = t("There are no knowledge bases");
                return {
                    message,
                    description: (
                        <Permission permission="Garden.Settings.Manage">{t("Create one to get started!")}</Permission>
                    ),
                    actionItem: (
                        <Permission permission="Garden.Settings.Manage">
                            <LinkAsButton
                                className={buttons(ButtonTypes.PRIMARY)}
                                to={"/knowledge-settings/knowledge-bases"}
                            >
                                {t("New Knowledge Base")}
                            </LinkAsButton>
                        </Permission>
                    ),
                    icon: searchError(message, errorIconClass),
                };
            }
            case DefaultError.NO_ARTICLES: {
                const { knowledgeBaseID, knowledgeCategoryID } = this.props;
                const message = t("This knowledge base does not have any articles.");
                return {
                    message,
                    description: knowledgeBaseID ? (
                        <Permission permission="articles.add">{t("Create one to get started!")}</Permission>
                    ) : null,
                    actionItem: knowledgeBaseID ? (
                        <Permission permission="articles.add">
                            <EditorRoute.Link
                                className={buttons(ButtonTypes.PRIMARY)}
                                data={{ knowledgeBaseID, knowledgeCategoryID }}
                            >
                                {t("New Article")}
                            </EditorRoute.Link>
                        </Permission>
                    ) : null,
                    icon: searchError(message, errorIconClass),
                };
            }
            case DefaultError.GENERIC:
            default: {
                const message = t("There was an error");
                return {
                    message,
                    description: t("Please try again later."),
                    actionItem: (
                        <LinkAsButton className={buttons(ButtonTypes.PRIMARY)} to={"/kb"}>
                            {t("Back to Home")}
                        </LinkAsButton>
                    ),
                    icon: searchError(message, errorIconClass),
                };
            }
        }
    }

    private renderSignin() {
        const { currentUser } = this.props;
        if (
            currentUser.status === LoadStatus.SUCCESS &&
            currentUser.data &&
            currentUser.data.userID === UsersModel.GUEST_ID
        ) {
            return (
                <LinkAsButton to={formatUrl(`/entry/signin?Target=${encodeURIComponent(window.location.href)}`)}>
                    {t("Sign In")}
                </LinkAsButton>
            );
        } else {
            return null;
        }
    }
}

interface IProps extends IDeviceProps, IInjectableUserState {
    defaultError?: DefaultError;
    error?: Partial<IError>;
    apiError?: IApiError;
    knowledgeBaseID?: number;
    knowledgeCategoryID?: number;
}

interface IError {
    message: string;
    description: ReactNode;
    actionItem: ReactNode;
    icon?: ReactNode;
}

export enum DefaultError {
    GENERIC = "generic",
    PERMISSION = "permission",
    NOT_FOUND = "notfound",
    NO_KNOWLEDGE_BASE = "noknowledgebase",
    NO_ARTICLES = "noarticles",
}

const withCurrentUser = connect(UsersModel.mapStateToProps);

export default withCurrentUser(withDevice(ErrorPage));
