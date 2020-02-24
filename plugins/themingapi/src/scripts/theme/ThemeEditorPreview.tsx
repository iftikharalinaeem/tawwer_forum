/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect, useRef, useState } from "react";
import { BrowserRouter, RouteComponentProps, useHistory } from "react-router-dom";
import TitleBar from "@library/headers/TitleBar";
import Banner from "@vanilla/library/src/scripts/banner/Banner";
import Container from "@library/layout/components/Container";
import Paragraph from "@vanilla/library/src/scripts/layout/Paragraph";
import { themeEditorPreviewClasses } from "./themeEditorPreviewStyles";
import { ButtonTypes } from "@vanilla/library/src/scripts/forms/buttonStyles";
import Button from "@vanilla/library/src/scripts/forms/Button";
import InputTextBlock from "@vanilla/library/src/scripts/forms/InputTextBlock";
import { t } from "@vanilla/i18n";

export default function ThemeStylePreview() {
    const classes = themeEditorPreviewClasses();
    return (
        <BrowserRouter>
            <div className={classes.contentContainer}>
                <div className={classes.themeTitleBar}>
                    <TitleBar />
                </div>

                <Banner />
                <Container fullGutter>
                    <div className={classes.content}>
                        <Paragraph className={classes.description}>
                            This is a style guide of your theme. It has examples of the visual elements used throught
                            the application. You can click on the various widgets such as the menu or hero to edit their
                            properties in the side panel. In addion to the widgets there are also global styles. To edit
                            global styles click anywhere else on the page, such as this text.
                        </Paragraph>

                        <div className={classes.buttonStyles}>
                            <h2 className={classes.title}>Buttons</h2>
                            <p>There are two types of buttons in the application: primary and secondary.</p>
                            <div className={classes.styleContent}>
                                <Button baseClass={ButtonTypes.PRIMARY}>Primary Button</Button>
                                <Button>Secondary Button</Button>{" "}
                            </div>
                        </div>
                        <div className={classes.inputStyles}>
                            <h2 className={classes.title}>Inputs</h2>
                            <p>User inputs are based on the global background and text colors.</p>
                            <div className={classes.styleContent}>
                                <InputTextBlock inputProps={{ value: "Text Input" }} />
                            </div>
                        </div>
                    </div>
                </Container>
            </div>
        </BrowserRouter>
    );
}
