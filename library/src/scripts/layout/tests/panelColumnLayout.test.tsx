/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { expect } from "chai";
import { render } from "@testing-library/react";
import React from "react";
import ThreeColumnLayout from "@library/layout/ThreeColumnLayout";
import PanelWidget from "@library/layout/components/PanelWidget";
import { twoColumnLayoutClasses, twoColumnLayoutVariables } from "@library/layout/types/layout.twoColumns";
import Container from "@library/layout/components/Container";
import { containerClasses, containerVariables } from "@library/layout/components/containerStyles";
import TwoColumnLayout from "@library/layout/TwoColumnLayout";
import { globalVariables } from "@library/styles/globalStyleVars";
/// <reference types="karma-viewport" />

const smallIpsum = `Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse ac arcu massa. Cras lobortis orci turpis, non viverra ex laoreet a. Sed dolor nisi, condimentum tincidunt gravida nec, pellentesque at felis. Phasellus vitae efficitur nibh, at ultricies tortor. Curabitur mauris lectus, luctus non est sit amet, elementum consectetur augue. Nullam non erat at tellus tincidunt ultricies quis non mi. Quisque vestibulum, nibh a sodales porta, neque diam iaculis lorem, in interdum tortor erat ut eros. Suspendisse magna lorem, euismod at bibendum id, semper non massa. Sed tempus orci dignissim molestie tempus. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Nam eget dolor ut ex consequat egestas quis eget sem. Nulla sit amet orci porta, feugiat nisl scelerisque, ultrices lorem. Suspendisse potenti. Cras et arcu vitae libero congue porta et vel orci. Morbi tincidunt massa et euismod sodales. Aliquam vel massa facilisis, volutpat massa sit amet, laoreet risus.`;
const largeIpsum = smallIpsum + smallIpsum + smallIpsum + smallIpsum + smallIpsum;

const DummyPanel = (props: { bg?: string; children?: React.ReactNode }) => {
    return (
        <PanelWidget>
            <div className="dummyContent" style={{ background: props.bg || "#444", color: "white", padding: 12 }}>
                {props.children}
            </div>
        </PanelWidget>
    );
};

// == THREE COLUMN LAYOUT ==

// -- Widths --
// gutter       : 20
// left panel   : 216
// gutter       : 40
// middle column: 672
// gutter       : 40
// right panel  : 216
// gutter       : 20
// total        : 1224

// -- Padded Widths --
// left panel    : 20 + 216 + 20 = 256
// middle column : 20 + 672 + 20 = 712
// right panel   : 20 + 216 + 20 = 256
// total         : 1172

// == TWO COLUMN LAYOUT ==

// -- Widths --
// gutter       : 20
// right panel  : 343
// gutter       : 40
// main column  : 1172 - 20 - 343 - 40 - 20 = 749
// gutter       : 20
// total        : 1172

// -- Padded Widths --
// right panel    : 20 + 343 + 20 = 383
// middle column : 20 + 749 + 20 = 789
// total         : 1172

// == LEGACY LAYOUT ==

// -- Widths --
// gutter       : 20
// right panel  : 244
// gutter       : 52 (different than other layouts)
// main column  : 1172 - 20 - 244 - 52 - 20 = 836
// gutter       : 20
// total        : 1172

// -- Padded Widths --
// right panel    : 20 + 244 + (52/2) = 290
// middle column : 20 + 836 + (52/2) = 882
// total         : 1172

describe.only("TwoColumnLayout", () => {
    it("Check desktop widths", () => {
        const { container } = render(
            <Container>
                <TwoColumnLayout
                    mainTop={<DummyPanel>Middle Top</DummyPanel>}
                    mainBottom={<DummyPanel>Middle Bottom{largeIpsum}</DummyPanel>}
                    rightTop={<DummyPanel>Right Top{largeIpsum}</DummyPanel>}
                />
            </Container>,
        );

        // @ts-ignore The "tripple slash" reference works, but is not understood by the IDE
        // (https://www.typescriptlang.org/docs/handbook/triple-slash-directives.html)
        viewport.set(globalVariables().contentWidth * 1.5, 800);

        const classesTwoColumnLayout = twoColumnLayoutClasses();

        const containerElement = container.querySelector(`.${containerClasses().root}`) as HTMLElement;

        const twoColumnElement = container.querySelector(`.${classesTwoColumnLayout.root}`) as HTMLElement;
        const mainPanel = container.querySelector(`.${classesTwoColumnLayout.mainColumnMaxWidth}`) as HTMLElement;
        const rightPanel = container.querySelector(`.${classesTwoColumnLayout.panel}`) as HTMLElement;

        const mainContent = mainPanel.querySelector(".dummyContent") as HTMLElement;
        const rightContent = rightPanel.querySelector(".dummyContent") as HTMLElement;

        const widths = {
            window: {
                width: window?.innerWidth,
                height: window?.innerHeight,
            },
            content: {
                paddedWidth: containerElement.offsetWidth,
                width: twoColumnElement.offsetWidth,
            },
            mainColumn: {
                paddedWidth: mainPanel.offsetWidth,
                width: mainContent ? mainContent.offsetWidth : undefined,
            },
            rightColumn: {
                paddedWidth: rightPanel.offsetWidth,
                width: rightContent ? rightContent.offsetWidth : undefined,
            },
        };

        const paddedFullWidth = twoColumnLayoutVariables().contentWidth;
        expect(widths.content.paddedWidth).eq(
            paddedFullWidth,
            `Two column layout - padded width ${
                widths.content.paddedWidth
            }px ≠ ${paddedFullWidth}px. Width debug data: ${JSON.stringify(widths)}`,
        );

        const fullWidth = twoColumnLayoutVariables().contentWidth - containerVariables().spacing.padding.horizontal * 2;
        expect(widths.content.width).eq(
            fullWidth,
            `Two column layout - content width ${
                widths.content.width
            }px ≠ ${fullWidth}px. Width debug data: ${JSON.stringify(widths)}`,
        );
    });
});
