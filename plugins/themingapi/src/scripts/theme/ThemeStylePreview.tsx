/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect, useRef, useState } from "react";
import { BrowserRouter, RouteComponentProps, useHistory } from "react-router-dom";
import Modal from "@vanilla/library/src/scripts/modal/Modal";
import ModalSizes from "@vanilla/library/src/scripts/modal/ModalSizes";
import TitleBar from "@library/headers/TitleBar";
import Banner from "@vanilla/library/src/scripts/banner/Banner";
import { classes } from "typestyle";

export default function ThemeStylePreview() {
    return (
        <BrowserRouter>
            <div>
                <TitleBar />
                <Banner />
                <div className={classes.content}>
                    <div>
                        <p>
                            This is a style guide of your theme. It has examples of the visual elements used throught
                            the application. You can click on the various widgets such as the menu or hero to edit their
                            properties in the side panel. In addion to the widgets there are also global styles. To edit
                            global styles click anywhere else on the page, such as this text.
                        </p>
                    </div>
                    <div className={classes.buttonStyles}>
                        <h1>Buttons</h1>
                        <p>There are two types of buttons in the application: primary and secondary.</p>
                    </div>
                    <div className={classes.inputStyles}>
                        <h1>Inputs</h1>
                        <p>User inputs are based on the global background and text colors.</p>
                    </div>
                </div>
            </div>
        </BrowserRouter>
    );
}
