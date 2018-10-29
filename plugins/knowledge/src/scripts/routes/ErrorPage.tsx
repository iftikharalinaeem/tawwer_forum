/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import PanelLayout from "@knowledge/layouts/PanelLayout";
import { IDeviceProps } from "@library/components/DeviceChecker";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import Paragraph from "@library/components/Paragraph";
import PageHeading from "@library/components/PageHeading";
import Container from "@knowledge/layouts/components/Container";
import BackLink from "@library/components/BackLink";
import { formatUrl, t } from "@library/application";
import { LoadStatus, ILoadable } from "@library/@types/api";
import DocumentTitle from "@library/components/DocumentTitle";
import UsersModel, { IInjectableUserState } from "@library/users/UsersModel";
import { connect } from "react-redux";

interface IProps extends IDeviceProps, IInjectableUserState {
    loadable: ILoadable<any>;
}

export const DefaultErrors: { [key: string]: ILoadable<any> } = {
    PERMISSION_LOADABLE: {
        status: LoadStatus.ERROR,
        error: {
            status: 401,
            message: "No Permission",
        },
    },
};

export class ErrorPage extends React.Component<IProps> {
    public render() {
        const { loadable, currentUser } = this.props;
        if (loadable.status !== LoadStatus.ERROR || !loadable.error) {
            return null;
        }

        let title = t("There was an error");
        let message = t("Please try again later.");
        let showSignIn = false;

        switch (loadable.error.status) {
            case 401:
                title = t("No Permission");
                message = t("You don't have permission to view this resource.");
                if (
                    currentUser.status === LoadStatus.SUCCESS &&
                    currentUser.data &&
                    currentUser.data.userID === UsersModel.GUEST_ID
                ) {
                    showSignIn = true;
                }
                break;
            case 404:
                title = t("Page not found");
                message = t("The page you were looking for could not be found.");
                break;
        }

        return (
            <DocumentTitle title={(title as unknown) as string}>
                <Container>
                    <PanelLayout device={this.props.device}>
                        <PanelLayout.LeftTop />
                        <PanelLayout.MiddleTop>
                            <div className="errorPage">
                                <PageHeading backUrl={null}>{title}</PageHeading>
                                <Paragraph>{message}</Paragraph>
                                <div className="errorPage-cta">
                                    {showSignIn ? (
                                        <a
                                            className="button"
                                            href={formatUrl(
                                                `/entry/signin?Target=${encodeURIComponent(window.location.href)}`,
                                            )}
                                        >
                                            {t("Sign In")}
                                        </a>
                                    ) : (
                                        <BackLink url={formatUrl("/kb")} visibleLabel={true} title={t("Home")} />
                                    )}
                                </div>
                            </div>
                        </PanelLayout.MiddleTop>
                        <PanelLayout.RightTop />
                    </PanelLayout>
                </Container>
            </DocumentTitle>
        );
    }
}

const withCurrentUser = connect(UsersModel.mapStateToProps);

export default withCurrentUser(withDevice<IProps>(ErrorPage));
