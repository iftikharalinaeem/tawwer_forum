/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import * as React from "react";
import { t } from "@library/application";
import { IDraftPreview } from "@knowledge/modules/drafts/components/DraftPreview";
import { ButtonBaseClass } from "@library/components/forms/Button";
import { LoadStatus } from "@library/@types/api/core";
import BackLink from "@library/components/BackLink";
import classNames from "classnames";
import Container from "@knowledge/layouts/components/Container";
import { PanelArea, PanelWidgetHorizontalPadding } from "@knowledge/layouts/PanelLayout";

interface IProps {
    className?: string;
}

/**
 * Implements actions to take on draft
 */
export default class DraftHeader extends React.Component<IProps> {
    public render() {
        return (
            <nav className={classNames("editorHeader", "modal-pageHeader", this.props.className)}>
                <Container>
                    <PanelArea>
                        <PanelWidgetHorizontalPadding>
                            <BackLink title={t("Back")} className="draftPage-backLink" visibleLabel={true} />
                        </PanelWidgetHorizontalPadding>
                    </PanelArea>
                </Container>
            </nav>
        );
    }
}
