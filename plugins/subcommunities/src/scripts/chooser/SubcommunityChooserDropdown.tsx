/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

import { MultiLevelChooser, SubcommunityChooserSection } from "@subcommunities/chooser/MultiLevelChooser";
import {
    subcommunityChooserClasses,
    subcommunityChooserVariables,
} from "@subcommunities/chooser/subcommunityChooserStyles";
import {
    useAvailableSubcommunityLocales,
    useCurrentSubcommunity,
    useSubcommunities,
} from "@subcommunities/subcommunities/subcommunitySelectors";
import { t, LocaleDisplayer, getCurrentLocale } from "@vanilla/i18n";
import DropDown, { DropDownOpenDirection, FlyoutType } from "@vanilla/library/src/scripts/flyouts/DropDown";
import Button from "@vanilla/library/src/scripts/forms/Button";
import { ButtonTypes } from "@vanilla/library/src/scripts/forms/buttonTypes";
import { DownTriangleIcon, GlobeIcon } from "@vanilla/library/src/scripts/icons/common";
import Frame from "@vanilla/library/src/scripts/layout/frame/Frame";
import FrameBody from "@vanilla/library/src/scripts/layout/frame/FrameBody";
import { FrameHeaderMinimal } from "@vanilla/library/src/scripts/layout/frame/FrameHeaderMinimal";
import classNames from "classnames";
import React, { useRef, useState } from "react";
import { SingleDepthChooser } from "@subcommunities/chooser/SingleDepthChooser";

interface IProps {
    buttonType?: ButtonTypes;
    fullWidth?: boolean;
    fullWidthIcon?: React.ReactNode;
    toggleName?: string;
    buttonClass?: string;
    defaultSection?: SubcommunityChooserSection;
}

/**
 * Subcommunity Chooser.
 *
 * There are a couple of variants to this:
 *
 * ## Scenario 1 - All same language
 * - Flat list of all subcommunities.
 *
 * ## Scenerio 2 - All same product
 * - Flat list of all subcommunities.
 *
 * ## Scenario 3 - Multiple products & multiple languages
 * - 2 Tier system (language and product).
 * - Which one is shown by default is configured by a variable.
 *   @var subcommunityChooser.options.defaultSection - "locale" | "product"
 * - When viewing products, only products available in the current locale will display.
 * - When viewing locales all locales will display.
 *   - If the current product is available in the displayed language, it will be a link to that subcommunity.
 *   - Otherwise a forward arrow will go to the product section to choose an available product for that language.
 *   - The user can navigate back with a back arrow or with the top tabs.
 *
 * ## Mobile
 *
 * - Actual dropdown is similar to the desktop one.
 * - Displays as a modal when opened.
 * - In Scenario 1 & 2, only 1 button will be displayed in the hamburger navigation.
 * - In scenario 3, 2 buttons will be shown. One to open products, and one to open locales.
 * - Button names:
 *   - Locale Button -> currentSubcommunity.locale ?? "Languages"
 *   - Products Button -> currentSubcommunity.name ?? "Products"
 */
export function SubcommunityChooserDropdown(props: IProps) {
    const subcommunity = useCurrentSubcommunity();
    const { options } = subcommunityChooserVariables();
    const [activeSection, setActiveSection] = useState<SubcommunityChooserSection>(
        props.defaultSection ?? options.defaultSection ?? "locale",
    );
    const buttonRef = useRef<HTMLButtonElement>(null);
    const [isOpen, setIsOpen] = useState(false);
    const availableLocales = useAvailableSubcommunityLocales();
    const classes = subcommunityChooserClasses();
    const forceIcon = options.forceIcon && !props.fullWidth;
    const { subcommunitiesByID, subcommunitiesByProductID } = useSubcommunities();

    if (!availableLocales || !options.enabled) {
        return null;
    }

    const hasMultipleLocales = Object.values(availableLocales).length > 1;
    const hasMultipleProducts =
        subcommunitiesByProductID.data && Object.keys(subcommunitiesByProductID.data).length > 1;

    let toggleName: React.ReactNode = <GlobeIcon />;

    if (props.defaultSection && props.fullWidth) {
        if (props.defaultSection === "product") {
            toggleName = subcommunity?.name ?? t("Products");
        } else {
            if (subcommunity?.locale) {
                toggleName = (
                    <LocaleDisplayer localeContent={subcommunity.locale} displayLocale={subcommunity.locale} />
                );
            } else {
                toggleName = t("Languages");
            }
        }
    } else if (hasMultipleLocales && subcommunity && !forceIcon) {
        toggleName = `${subcommunity.name} (${subcommunity.locale}) `;
    }

    if (props.fullWidth) {
        toggleName = (
            <>
                {props.fullWidthIcon}
                {toggleName}
            </>
        );
    }

    if (subcommunitiesByID.data) {
        if (Object.entries(subcommunitiesByID.data).length === 0) {
            return null;
        }
    }

    return (
        <DropDown
            isVisible={isOpen}
            onVisibilityChange={setIsOpen}
            buttonRef={buttonRef}
            isSmall
            flyoutType={FlyoutType.FRAME}
            buttonBaseClass={props.buttonType || ButtonTypes.STANDARD}
            toggleButtonClassName={classNames(props.buttonClass)}
            openDirection={DropDownOpenDirection.AUTO}
            buttonContents={
                <span className={classNames(classes.toggle, props.fullWidth && classes.toggleFullWidth)}>
                    {toggleName}
                    {!props.fullWidth && <DownTriangleIcon className={classNames(classes.toggleArrow)} />}
                </span>
            }
        >
            <Frame
                header={
                    <FrameHeaderMinimal
                        onClose={() => {
                            setIsOpen(false);
                        }}
                    >
                        {hasMultipleProducts && hasMultipleLocales ? (
                            <>
                                <Button
                                    baseClass={activeSection === "locale" ? ButtonTypes.TEXT_PRIMARY : ButtonTypes.TEXT}
                                    onClick={() => setActiveSection("locale")}
                                >
                                    {t("Languages")}
                                </Button>
                                <hr className={classes.headingDivider} />
                                <Button
                                    baseClass={
                                        activeSection === "product" ? ButtonTypes.TEXT_PRIMARY : ButtonTypes.TEXT
                                    }
                                    onClick={() => setActiveSection("product")}
                                >
                                    {t("Products")}
                                </Button>
                            </>
                        ) : hasMultipleLocales ? (
                            t("Languages")
                        ) : (
                            t("Products")
                        )}
                    </FrameHeaderMinimal>
                }
                body={
                    <FrameBody selfPadded className={classes.body}>
                        {hasMultipleProducts && hasMultipleLocales ? (
                            <MultiLevelChooser
                                activeSubcommunityID={subcommunity ? subcommunity.subcommunityID : undefined}
                                activeSection={activeSection}
                                setActiveSection={setActiveSection}
                            />
                        ) : hasMultipleLocales ? (
                            <SingleDepthChooser subcommunities={Object.values(subcommunitiesByProductID.data!)[0]!} />
                        ) : (
                            <SingleDepthChooser subcommunities={Object.values(subcommunitiesByID.data!)} />
                        )}
                    </FrameBody>
                }
                footer={null}
            />
        </DropDown>
    );
}
