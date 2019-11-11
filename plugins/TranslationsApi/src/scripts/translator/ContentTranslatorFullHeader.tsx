/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import Container from "@library/layout/components/Container";
import { PanelArea, PanelWidgetHorizontalPadding } from "@library/layout/PanelLayout";
import { modalClasses } from "@library/modal/modalStyles";
import BackLink from "@library/routing/links/BackLink";
import { t } from "@vanilla/i18n";
import classNames from "classnames";
import React from "react";
import { contentTranslatorClasses } from "./contentTranslatorStyles";

export function ContentTranslaterFullHeader(props: { onBack: () => void; onSave: () => void }) {
    const classesModal = modalClasses();

    const classes = contentTranslatorClasses();

    return (
        <nav className={classNames(classesModal.pageHeader)}>
            <Container>
                <PanelArea>
                    <PanelWidgetHorizontalPadding>
                        <ul className={classNames(classes.header)}>
                            <li>
                                <BackLink
                                    visibleLabel={true}
                                    onClick={e => {
                                        e.preventDefault();
                                        props.onBack();
                                    }}
                                />
                            </li>
                            <li>
                                <Button
                                    baseClass={ButtonTypes.TEXT_PRIMARY}
                                    onClick={() => {
                                        props.onSave();
                                    }}
                                >
                                    {t("Save")}
                                </Button>
                            </li>
                        </ul>
                    </PanelWidgetHorizontalPadding>
                </PanelArea>
            </Container>
        </nav>
    );
}
