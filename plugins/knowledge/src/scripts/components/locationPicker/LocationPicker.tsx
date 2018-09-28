/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { IBreadcrumbsProps } from "@knowledge/components/Breadcrumbs";
import { getRequiredID } from "@library/componentIDs";
import { t } from "@library/application";
import Modal from "@knowledge/components/Modal";
import Frame from "@library/components/frame/Frame";
import FrameHeader from "@library/components/frame/FrameHeader";
import FrameBody from "@library/components/frame/FrameBody";
import FrameFooter from "@library/components/frame/FrameFooter";
import Button from "@dashboard/components/forms/Button";

interface IProps {
    hideLocationChooser: () => void;
    title: string | JSX.Element;
    className?: string;
}

interface IState {
    selectedCategory?: object;
}

/**
 * This component allows to display and edit the location of the current page.
 * Calls the LocationChooser component when clicked.
 */
export default class LocationPicker extends React.Component<IProps> {
    public constructor(props) {
        super(props);
    }

    public tempClick = () => {
        alert("do click");
    };

    public render() {
        // noinspection HtmlDeprecatedTag
        return (
            <Modal className={classNames(this.props.className)}>
                <Frame>
                    <FrameHeader onBackClick={this.tempClick} closeFrame={this.props.hideLocationChooser}>
                        {t("New Category")}
                    </FrameHeader>
                    <FrameBody>
                        <p>{t("in panel")}</p>
                        <p>{t("in panel")}</p>
                        <p>{t("in panel")}</p>
                        <p>{t("in panel")}</p>
                    </FrameBody>
                    <FrameFooter>
                        <Button>{t("Choose")}</Button>
                    </FrameFooter>
                </Frame>
            </Modal>
        );
    }
}
