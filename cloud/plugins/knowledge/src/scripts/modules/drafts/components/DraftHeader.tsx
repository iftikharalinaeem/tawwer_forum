/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import * as React from "react";
import { t } from "@library/utility/appUtils";
import BackLink from "@library/routing/links/BackLink";
import classNames from "classnames";
import Container from "@library/layout/components/Container";
import { modalClasses } from "@library/modal/modalStyles";
import MobileDropDown from "@library/headers/pieces/MobileDropDown";
import { editorHeaderClasses } from "@knowledge/modules/editor/components/editorHeaderStyles";
import PanelArea from "@vanilla/library/src/scripts/layout/components/PanelArea";
import PanelWidgetHorizontalPadding from "@vanilla/library/src/scripts/layout/components/PanelWidgetHorizontalPadding";

interface IProps {
    className?: string;
    mobileDropDownTitle?: string;
}

/**
 * Implements actions to take on draft
 */
export default class DraftHeader extends React.Component<IProps> {
    public render() {
        const classesModal = modalClasses();
        const classesEditorHeader = editorHeaderClasses();
        return (
            <nav
                className={classNames(
                    "draftPage-header",
                    "modal-pageHeader",
                    this.props.className,
                    classesModal.pageHeader,
                )}
            >
                <Container>
                    <PanelArea>
                        <ul className={classesEditorHeader.items}>
                            <li className={classNames(classesEditorHeader.item, "isPullLeft")}>
                                <PanelWidgetHorizontalPadding>
                                    <BackLink title={t("Back")} className="draftPage-backLink" visibleLabel={true} />
                                </PanelWidgetHorizontalPadding>
                            </li>
                            {this.props.mobileDropDownTitle && (
                                <>
                                    <li
                                        className={classNames(
                                            classesEditorHeader.centreColumn,
                                            classesEditorHeader.item,
                                        )}
                                    >
                                        <MobileDropDown
                                            title={this.props.mobileDropDownTitle!}
                                            buttonClass="editorHeader-mobileDropDown"
                                        />
                                    </li>
                                    {/*For centering the title*/}
                                    <li aria-hidden={true} className={classNames(classesEditorHeader.backSpacer)}>
                                        <BackLink
                                            title={t("Back")}
                                            className="draftPage-backLink"
                                            visibleLabel={true}
                                        />
                                    </li>
                                </>
                            )}
                        </ul>
                    </PanelArea>
                </Container>
            </nav>
        );
    }
}
