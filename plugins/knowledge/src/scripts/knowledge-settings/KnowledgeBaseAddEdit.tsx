/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { DashboardMediaItem } from "@dashboard/tables/DashboardMediaItem";
import { DashboardTable } from "@dashboard/tables/DashboardTable";
import { DashboardTableOptions } from "@dashboard/tables/DashboardTableOptions";
import { IKnowledgeBase, KnowledgeBaseStatus } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { AlertIcon, DeleteIcon, EditIcon } from "@library/icons/common";
import LinkAsButton from "@library/routing/LinkAsButton";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import { getMeta } from "@library/utility/appUtils";
import { t } from "@vanilla/i18n";
import React, {useMemo, useState} from "react";
import Button from "@library/forms/Button";
import ModalConfirm from "@library/modal/ModalConfirm";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import Frame from "@library/layout/frame/Frame";
import FrameHeader from "@library/layout/frame/FrameHeader";
import FrameBody from "@library/layout/frame/FrameBody";
import SmartAlign from "@library/layout/SmartAlign";
import classNames from "classnames";
import FrameFooter from "@library/layout/frame/FrameFooter";
import ButtonLoader from "@library/loaders/ButtonLoader";
import {uniqueIDFromPrefix} from "@library/utility/idUtils";

interface IProps {}

const onCancel = () => {};

export function KnowledgeBaseAddEdit(props: IProps) {
    const [open, setOpen] = useState(false);

    const toggleButtonRef = React.createRef<HTMLButtonElement>();

    const titleID = useMemo(() => {
        return uniqueIDFromPrefix("addKnowledgeBase");
    }, []);

    const title =

    return (
        <>
            <Button baseClass={ButtonTypes.ICON_COMPACT} onClick={() => setOpen(true)}>
                {t("Add Knowledgebase")}
            </Button>
            {open && (
                <Modal
                    size={ModalSizes.LARGE}
                    elementToFocus={toggleButtonRef.current as HTMLElement}
                    exitHandler={onCancel}
                    titleID={titleID}
                    elementToFocusOnExit={this.props.elementToFocusOnExit}
                >
                    <Frame
                        header={
                            <FrameHeader
                                titleID={titleID}
                                closeFrame={onCancel}
                                srOnlyTitle={srOnlyTitle!}
                                title={title}
                            />
                        }
                        body={<FrameBody>{t("body")}</FrameBody>}
                        footer={
                            <FrameFooter justifyRight={true}>
                                <Button
                                    className={classFrameFooter.actionButton}
                                    baseClass={ButtonTypes.TEXT}
                                    buttonRef={this.cancelRef}
                                    onClick={onCancel}
                                >
                                    {t("Cancel")}
                                </Button>
                                <Button
                                    className={classFrameFooter.actionButton}
                                    onClick={onConfirm}
                                    baseClass={ButtonTypes.TEXT_PRIMARY}
                                    disabled={isConfirmLoading}
                                >
                                    {isConfirmLoading ? <ButtonLoader /> : this.props.confirmTitle}
                                </Button>
                            </FrameFooter>
                        }
                    />
                </Modal>
            )}
        </>
    );
}
