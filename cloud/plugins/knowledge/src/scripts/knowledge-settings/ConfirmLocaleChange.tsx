/*
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import React from "react";
import ModalConfirm from "@library/modal/ModalConfirm";
import { t } from "@library/utility/appUtils";
import ModalSizes from "@library/modal/ModalSizes";
import Paragraph from "@library/layout/Paragraph";
import Translate from "@library/content/Translate";

export function ConfirmLocaleChange(props: { onCancel?: () => void; onConfirm: () => void; isVisible: boolean }) {
    return (
        <ModalConfirm
            isVisible={props.isVisible}
            title={t("Are you sure?")}
            onCancel={props.onCancel}
            onConfirm={props.onConfirm}
            size={ModalSizes.SMALL}
        >
            <Paragraph>
                <Translate
                    source="Changing your source locale can lead to articles disappearing and is not recommended. Are you sure you want to change the source locale? <0>More information</0>."
                    c0={content => {
                        return (
                            <a
                                href="https://success.vanillaforums.com/kb/articles/118"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                {content}
                            </a>
                        );
                    }}
                />
            </Paragraph>
        </ModalConfirm>
    );
}
