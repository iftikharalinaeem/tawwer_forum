/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { Devices, IDeviceProps } from "@library/components/DeviceChecker";
import { IRevisionFragment, IRevision } from "@knowledge/@types/api";
import Container from "@knowledge/layouts/components/Container";
import PanelLayout, { PanelWidget } from "@knowledge/layouts/PanelLayout";
import { withDevice } from "@knowledge/contexts/DeviceContext";
import Modal from "@library/components/modal/Modal";
import ModalSizes from "@library/components/modal/ModalSizes";
import EditorHeader from "@knowledge/modules/editor/components/EditorHeader";
import { t } from "@library/application";
import RevisionsList from "@knowledge/modules/editor/components/RevisionsList";
import RevisionsListItem from "./RevisionsListItem";
import { LoadStatus } from "@library/@types/api";
import { makeRevisionsUrl, makeEditUrl } from "../route";
import UserContent from "@library/components/UserContent";
import { IInjectableRevisionsState } from "../RevisionsPageModel";
import PageTitle from "@knowledge/modules/common/PageTitle";
import { ArticleMeta } from "@knowledge/modules/article/components/ArticleMeta";
import { Redirect, RouteComponentProps, withRouter } from "react-router";
import { uniqueIDFromPrefix } from "@library/componentIDs";

interface IProps extends IDeviceProps, IInjectableRevisionsState, RouteComponentProps<{}> {}

interface IState {
    wasSubmitted: boolean;
}

/**
 * Implements the article's layout
 */
export class ArticleRevisionsLayout extends React.Component<IProps, IState> {
    public state: IState = {
        wasSubmitted: false,
    };

    public render() {
        const { device, revisions, selectedRevisionID, selectedRevision } = this.props;

        if (this.state.wasSubmitted) {
            return <Redirect to={makeEditUrl(selectedRevision.data!)} />;
        }

        const isFullWidth = device === (Devices.DESKTOP || Devices.NO_BLEED); // This compoment doesn't care about the no bleed, it's the same as desktop

        return (
            <Modal size={ModalSizes.FULL_SCREEN} exitHandler={this.navigateToBacklink} label={t("Article Revisions")}>
                <form className="richEditorForm inheritHeight" onSubmit={this.onSubmit}>
                    <EditorHeader
                        device={this.props.device}
                        canSubmit={selectedRevision.status === LoadStatus.SUCCESS}
                        isSubmitLoading={false}
                        className="richEditorRevisionsForm-header"
                        callToAction={t("Restore")}
                    />
                    <Container className="richEditorRevisionsForm-body">
                        <PanelLayout device={this.props.device} forceRenderLeftTop={isFullWidth}>
                            <PanelLayout.MiddleTop>
                                {selectedRevision.status === LoadStatus.SUCCESS && selectedRevision.data ? (
                                    <>
                                        <PageTitle title={selectedRevision.data.name} />
                                        <ArticleMeta
                                            updateUser={selectedRevision.data.insertUser!}
                                            dateUpdated={selectedRevision.data.dateInserted}
                                            permaLink={makeRevisionsUrl(selectedRevision.data)}
                                        />
                                    </>
                                ) : null}
                            </PanelLayout.MiddleTop>
                            <PanelLayout.MiddleBottom>
                                {this.props.selectedRevision.data && (
                                    <PanelWidget>
                                        <UserContent content={this.props.selectedRevision.data.body} />
                                    </PanelWidget>
                                )}
                            </PanelLayout.MiddleBottom>
                            <PanelLayout.RightTop>
                                <PanelWidget className="isSelfPadded">
                                    {revisions.status === LoadStatus.SUCCESS &&
                                        revisions.data && (
                                            <RevisionsList>
                                                {revisions.data.reverse().map(item => (
                                                    <RevisionsListItem
                                                        {...item}
                                                        isSelected={item.articleRevisionID === selectedRevisionID}
                                                        url={makeRevisionsUrl(item)}
                                                        key={item.articleRevisionID}
                                                    />
                                                ))}
                                            </RevisionsList>
                                        )}
                                </PanelWidget>
                            </PanelLayout.RightTop>
                        </PanelLayout>
                    </Container>
                </form>
            </Modal>
        );
    }

    /**
     * Route back to the previous location if its available.
     */
    private navigateToBacklink = () => {
        if (this.props.history.length > 1) {
            this.props.history.goBack();
        } else {
            this.props.history.push("/kb");
        }
    };

    /**
     * Form submit handler. Fetch the values out of the form and pass them to the callback prop.
     */
    private onSubmit = (event: React.FormEvent) => {
        this.setState({ wasSubmitted: true });
    };
}

export default withRouter(withDevice<IProps>(ArticleRevisionsLayout));
