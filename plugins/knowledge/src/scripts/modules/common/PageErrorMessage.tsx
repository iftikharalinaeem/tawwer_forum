/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { EditorRoute } from "@knowledge/routes/pageRoutes";
import { IApiError, LoadStatus } from "@library/@types/api/core";
import Permission from "@library/features/users/Permission";
import { IInjectableUserState, isUserGuest, mapUsersStoreState } from "@library/features/users/userModel";
import { buttonClasses, ButtonTypes } from "@library/forms/buttonStyles";
import { SearchErrorIcon } from "@library/icons/common";
import Heading from "@library/layout/Heading";
import Paragraph from "@library/layout/Paragraph";
import LinkAsButton from "@library/routing/LinkAsButton";
import { globalVariables } from "@library/styles/globalStyleVars";
import { debugHelper, flexHelper } from "@library/styles/styleHelpers";
import { formatUrl, t } from "@library/utility/appUtils";
import classNames from "classnames";
import React, { ReactNode } from "react";
import { connect } from "react-redux";
import { pageErrorMessageClasses } from "@knowledge/modules/common/pageErrorMessageStyles";

class PageErrorMessage extends React.Component<IProps> {
    public render() {
        const error = this.getError(this.props);
        const classes = pageErrorMessageClasses();
        const { message, messageAsParagraph, description, actionItem, icon } = error;
        return (
            <div className={classNames(this.props.className, classes.root)}>
                {error.icon}
                {!messageAsParagraph && <Heading depth={1} className={classes.title} title={message} />}
                {messageAsParagraph && <Paragraph className={classes.titleAsParagraph}>{message}</Paragraph>}
                {error.description && <Paragraph className={classes.description}>{description}</Paragraph>}
                {error.actionItem && <div className={classes.cta}>{error.actionItem}</div>}
            </div>
        );
    }

    private getError(errorMessageProps: IErrorMessageProps) {
        const errorCode = getErrorCode(errorMessageProps);
        return {
            ...this.parseErrorCode(errorCode),
            ...(errorMessageProps.error || {}),
        };
    }

    private parseErrorCode(errorCode?: string | number): IError {
        const globalVars = globalVariables();
        const debug = debugHelper("errorPage");
        const classes = pageErrorMessageClasses();
        const buttons = buttonClasses();
        const message = messageFromErrorCode(errorCode);

        switch (errorCode) {
            case 403:
            case DefaultError.PERMISSION: {
                return {
                    message,
                    description: t("You don't have permission to view this resource."),
                    actionItem: this.renderSignin(),
                    icon: <SearchErrorIcon message={message} className={classes.errorIcon} />,
                };
            }
            case 404:
            case DefaultError.NOT_FOUND: {
                return {
                    message,
                    description: t("The page you were looking for could not be found."),
                    actionItem: (
                        <LinkAsButton baseClass={ButtonTypes.PRIMARY} to={"/kb"}>
                            {t("Back to home page")}
                        </LinkAsButton>
                    ),
                    icon: <SearchErrorIcon message={message} className={classes.errorIcon} />,
                };
            }
            case DefaultError.NO_KNOWLEDGE_BASE: {
                return {
                    message,
                    description: (
                        <Permission permission="Garden.Settings.Manage">{t("Create one to get started!")}</Permission>
                    ),
                    actionItem: (
                        <Permission permission="Garden.Settings.Manage">
                            <LinkAsButton className={buttons.primary} to={"/knowledge-settings/knowledge-bases"}>
                                {t("New Knowledge Base")}
                            </LinkAsButton>
                        </Permission>
                    ),
                    icon: <SearchErrorIcon message={message} className={classes.errorIcon} />,
                };
            }
            case DefaultError.NO_ARTICLES: {
                const { knowledgeBaseID, knowledgeCategoryID } = this.props;
                return {
                    message,
                    description: knowledgeBaseID ? (
                        <Permission permission="articles.add">{t("Create one to get started!")}</Permission>
                    ) : null,
                    actionItem: knowledgeBaseID ? (
                        <Permission permission="articles.add">
                            <EditorRoute.Link
                                className={buttons.primary}
                                data={{ knowledgeBaseID, knowledgeCategoryID }}
                            >
                                {t("New Article")}
                            </EditorRoute.Link>
                        </Permission>
                    ) : null,
                    icon: <SearchErrorIcon message={message} className={classes.errorIcon} />,
                };
            }
            case DefaultError.CATEGORY_NO_ARTICLES: {
                const { knowledgeBaseID, knowledgeCategoryID } = this.props;
                return {
                    message,
                    messageAsParagraph: true,
                    icon: null,
                };
            }
            case DefaultError.GENERIC:
            default: {
                return {
                    message,
                    description: t("Please try again later."),
                    actionItem: (
                        <LinkAsButton className={buttons.primary} to={"/kb"}>
                            {t("Back to Home")}
                        </LinkAsButton>
                    ),
                    icon: <SearchErrorIcon message={message} className={classes.errorIcon} />,
                };
            }
        }
    }

    private renderSignin() {
        const { currentUser } = this.props;
        if (currentUser.status === LoadStatus.SUCCESS && currentUser.data && isUserGuest(currentUser.data)) {
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

export function messageFromErrorCode(errorCode?: string | number) {
    switch (errorCode) {
        case 403:
        case DefaultError.PERMISSION:
            return t("No Permission");
        case 404:
        case DefaultError.NOT_FOUND:
            return t("Page not found");
        case DefaultError.NO_KNOWLEDGE_BASE:
            return t("There are no knowledge bases");
        case DefaultError.NO_ARTICLES:
            return t("This knowledge base does not have any articles.");
        case DefaultError.CATEGORY_NO_ARTICLES:
            return t("This category does not have any articles.");
        case DefaultError.GENERIC:
        default:
            return t("There was an error");
    }
}

export function getErrorCode(errorMessageProps: IErrorMessageProps) {
    if (errorMessageProps.apiError) {
        return errorMessageProps.apiError.response.status;
    } else if (errorMessageProps.error && errorMessageProps.error.status) {
        return errorMessageProps.error.status;
    } else {
        return errorMessageProps.defaultError;
    }
}

export interface IErrorMessageProps {
    defaultError?: DefaultError;
    error?: Partial<IError>;
    apiError?: IApiError;
    knowledgeBaseID?: number;
    knowledgeCategoryID?: number;
}

export interface IError {
    status?: number;
    message: string;
    messageAsParagraph?: boolean;
    description?: ReactNode;
    actionItem?: ReactNode;
    icon?: ReactNode;
}

export enum DefaultError {
    GENERIC = "generic",
    PERMISSION = "permission",
    NOT_FOUND = "notfound",
    NO_KNOWLEDGE_BASE = "noknowledgebase",
    NO_ARTICLES = "noarticles",
    CATEGORY_NO_ARTICLES = "categorynoarticles",
}

interface IProps extends IErrorMessageProps, IInjectableUserState {
    className?: string;
}

const withCurrentUser = connect(mapUsersStoreState);

export default withCurrentUser(PageErrorMessage);
