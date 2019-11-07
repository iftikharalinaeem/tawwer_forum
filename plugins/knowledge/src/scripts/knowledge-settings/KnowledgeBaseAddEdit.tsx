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
import React, { useMemo, useState } from "react";
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
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import { frameFooterClasses } from "@library/layout/frame/frameFooterStyles";
import { DashboardSelect } from "@dashboard/forms/DashboardSelect";
import { LoadStatus } from "@library/@types/api/core";
import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import Translate from "@library/content/Translate";
import SmartLink from "@library/routing/links/SmartLink";
import { ProductManager } from "@subcommunities/products/ProductManager";
import { DashboardFormList } from "@dashboard/forms/DashboardFormList";

interface IProps {}

const doNothing = () => {
    return;
};

export function KnowledgeBaseAddEdit(props: IProps) {
    const [openForm, setOpenForm] = useState(false);
    const [openProductManagement, setOpenProductManagement] = useState(false);
    const [loading, setLoading] = useState(false);

    const toggleButtonRef = React.createRef<HTMLButtonElement>();

    const titleID = useMemo(() => {
        return uniqueIDFromPrefix("addKnowledgeBase");
    }, []);

    const classFrameFooter = frameFooterClasses();

    const onCancel = () => {
        setOpenForm(false);
    };

    const onClose = () => {
        setOpenForm(false);
    };

    const save = () => {
        setOpenForm(false);
    };

    return (
        <>
            <Button
                buttonRef={toggleButtonRef}
                baseClass={ButtonTypes.DASHBOARD_PRIMARY}
                onClick={() => setOpenForm(true)}
            >
                {t("Add Knowledge Base")}
            </Button>
            {openForm && (
                <Modal
                    size={ModalSizes.LARGE}
                    exitHandler={onCancel}
                    titleID={titleID}
                    elementToFocusOnExit={toggleButtonRef.current as HTMLElement}
                    scrollable={true}
                >
                    <Frame
                        header={
                            <FrameHeader titleID={titleID} closeFrame={onClose} title={t("Add/Edit Knowledge Base")} />
                        }
                        body={
                            <FrameBody>
                                <DashboardFormList>
                                    <DashboardFormGroup
                                        label="Product"
                                        description={
                                            <Translate
                                                source="Associate a product with this Subcommunity. <0>Use the product management UI</0> to replace add, edit, or delete products."
                                                c0={content => (
                                                    <Button
                                                        baseClass={ButtonTypes.TEXT_PRIMARY}
                                                        onClick={event => {
                                                            setOpenProductManagement(true);
                                                        }}
                                                    >
                                                        {content}
                                                    </Button>
                                                )}
                                            />
                                        }
                                    >
                                        <DashboardSelect disabled={loading} options={[]} onChange={doNothing} />
                                    </DashboardFormGroup>
                                </DashboardFormList>
                            </FrameBody>
                        }
                        footer={
                            <FrameFooter justifyRight={true}>
                                <Button
                                    className={classFrameFooter.actionButton}
                                    baseClass={ButtonTypes.TEXT}
                                    onClick={onCancel}
                                >
                                    {t("Cancel")}
                                </Button>
                                <Button
                                    className={classFrameFooter.actionButton}
                                    onClick={save}
                                    baseClass={ButtonTypes.TEXT_PRIMARY}
                                    disabled={loading}
                                >
                                    {loading ? <ButtonLoader /> : t("Save")}
                                </Button>
                            </FrameFooter>
                        }
                    />
                </Modal>
            )}
            {openProductManagement && <ProductManager asModal={true} onClose={() => setOpenProductManagement(false)} />}
        </>
    );
}
