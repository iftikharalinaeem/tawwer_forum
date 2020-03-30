/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import DropDown, { DropDownOpenDirection, FlyoutType } from "@library/flyouts/DropDown";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { DownTriangleIcon, GlobeIcon } from "@library/icons/common";
import { Devices, useDevice } from "@library/layout/DeviceContext";
import Frame from "@library/layout/frame/Frame";
import FrameBody from "@library/layout/frame/FrameBody";
import { FrameHeaderMinimal } from "@library/layout/frame/FrameHeaderMinimal";
import { LocaleChooser } from "@subcommunities/chooser/LocaleChooser";
import { ProductChooser } from "@subcommunities/chooser/ProductChooser";
import {
    subcommunityChooserClasses,
    subcommunityChooserVariables,
} from "@subcommunities/chooser/subcommunityChooserStyles";
import {
    useCurrentSubcommunity,
    useAvailableSubcommunityLocales,
    useSubcommunities,
} from "@subcommunities/subcommunities/subcommunitySelectors";
import classNames from "classnames";
import React, { useEffect, useRef, useState } from "react";
import { useLocaleInfo, t } from "@vanilla/i18n";
import { dropDownClasses } from "@vanilla/library/src/scripts/flyouts/dropDownStyles";
import DropDownItemSeparator from "@vanilla/library/src/scripts/flyouts/items/DropDownItemSeparator";
import { useSubcommunityActions } from "@subcommunities/subcommunities/SubcommunityActions";
import { LoadStatus } from "@vanilla/library/src/scripts/@types/api/core";

type SectionName = "locale" | "product";

interface IDropdownProps {
    buttonType?: ButtonTypes;
    fullWidth?: boolean;
    buttonClass?: string;
}

export function SubcommunityChooserHamburgerGroup() {
    if (!subcommunityChooserVariables().options.enabled) {
        return null;
    }
    return (
        <>
            <DropDownItemSeparator />
            <SubcommunityChooserDropdown
                buttonType={ButtonTypes.CUSTOM}
                buttonClass={dropDownClasses().action}
                fullWidth
            />
        </>
    );
}

export function SubcommunityChooserDropdown(props: IDropdownProps) {
    const subcommunity = useCurrentSubcommunity();
    const [activeSection, setActiveSection] = useState<SectionName>("locale");
    const buttonRef = useRef<HTMLButtonElement>(null);
    const [isOpen, setIsOpen] = useState(false);
    const availableLocales = useAvailableSubcommunityLocales();
    const device = useDevice();
    const showHeader = device === Devices.MOBILE || device === Devices.XS;
    const { options } = subcommunityChooserVariables();
    const classes = subcommunityChooserClasses();
    const forceIcon = options.forceIcon && !props.fullWidth;
    const { subcommunitiesByID } = useSubcommunities();

    if (!availableLocales || !options.enabled) {
        return null;
    }

    const hasMultipleLocales = Object.values(availableLocales).length > 1;

    let toggleName: React.ReactNode = <GlobeIcon />;
    if (hasMultipleLocales && subcommunity && !forceIcon) {
        toggleName = `${subcommunity.name} (${subcommunity.locale}) `;
    } else if (props.fullWidth) {
        toggleName = hasMultipleLocales ? t("Language") : t("Product");
    }

    if (props.fullWidth) {
        toggleName = (
            <>
                <GlobeIcon className={dropDownClasses().actionIcon} />
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
                    showHeader && (
                        <FrameHeaderMinimal
                            onClose={() => {
                                setIsOpen(false);
                            }}
                        >
                            {hasMultipleLocales && (
                                <>
                                    <Button
                                        baseClass={
                                            activeSection === "locale" ? ButtonTypes.TEXT_PRIMARY : ButtonTypes.TEXT
                                        }
                                        onClick={() => setActiveSection("locale")}
                                    >
                                        Locales
                                    </Button>
                                    <hr className={classes.headingDivider} />
                                </>
                            )}
                            <Button
                                disabled={activeSection === "locale"}
                                baseClass={activeSection === "product" ? ButtonTypes.TEXT_PRIMARY : ButtonTypes.TEXT}
                                onClick={() => setActiveSection("product")}
                            >
                                Products
                            </Button>
                        </FrameHeaderMinimal>
                    )
                }
                body={
                    <FrameBody selfPadded className={classes.body}>
                        <SubcommunityChooser
                            activeSubcommunityID={subcommunity ? subcommunity.subcommunityID : undefined}
                            activeSection={activeSection}
                            setActiveSection={setActiveSection}
                        />
                    </FrameBody>
                }
                footer={null}
            />
        </DropDown>
    );
}

export function SubcommunityChooser(props: {
    activeSection: SectionName;
    setActiveSection: (section: SectionName) => void;
    activeSubcommunityID?: number;
}) {
    const { activeSection, setActiveSection, activeSubcommunityID } = props;
    const subcommunity = useCurrentSubcommunity();
    const [selectedLocale, setSelectedLocale] = useState<string | null>(subcommunity ? subcommunity.locale : null);
    const availableLocales = useAvailableSubcommunityLocales();

    const { currentLocale } = useLocaleInfo();

    useEffect(() => {
        if (currentLocale) {
            setSelectedLocale(currentLocale);
            setActiveSection("product");
        }
    }, [currentLocale, setActiveSection, subcommunity]);

    const [communityID] = useState<number | null>(null);

    if (availableLocales && Object.values(availableLocales).length <= 1 && currentLocale) {
        return <ProductChooser forLocale={currentLocale} communityID={communityID} />;
    }

    if (activeSection === "product" && selectedLocale) {
        return (
            <ProductChooser
                activeSubcommunityID={activeSubcommunityID}
                forLocale={selectedLocale}
                communityID={communityID}
                onBack={() => {
                    setActiveSection("locale");
                }}
            />
        );
    } else {
        return (
            <LocaleChooser
                value={selectedLocale}
                onChange={newLocale => {
                    setSelectedLocale(newLocale);
                    setActiveSection("product");
                }}
            />
        );
    }
}
