/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { OrganizeCategoriesRoute } from "@knowledge/routes/pageRoutes";
import { t } from "@library/utility/appUtils";
import { organize } from "@knowledge/navigation/navigationManagerIcons";
import Permission from "@library/features/users/Permission";
import classNames from "classnames";
import React, { useState, useRef } from "react";
import { siteNavAdminLinksClasses } from "@knowledge/navigation/subcomponents/navigationAdminLinksStyles";
import { IKnowledgeBase } from "@knowledge/knowledge-bases/KnowledgeBaseModel";
import NewCategoryForm from "@knowledge/modules/locationPicker/components/NewCategoryForm";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { NewFolderIcon } from "@library/icons/common";
import { getCurrentLocale, LocaleDisplayer } from "@vanilla/i18n";
import { ToolTip } from "@library/toolTip/ToolTip";
import Translate from "@library/content/Translate";
import DropDownSection from "@vanilla/library/src/scripts/flyouts/items/DropDownSection";
import DropDownItemSeparator from "@vanilla/library/src/scripts/flyouts/items/DropDownItemSeparator";
import DropDownItemLink from "@vanilla/library/src/scripts/flyouts/items/DropDownItemLink";
import DropDownItemButton from "@vanilla/library/src/scripts/flyouts/items/DropDownItemButton";
import { dropDownClasses } from "@vanilla/library/src/scripts/flyouts/dropDownStyles";
import { KbPermission } from "@knowledge/knowledge-bases/KbPermission";
import { useUniqueID } from "@library/utility/idUtils";

interface IProps {
    className?: string;
    knowledgeBase: IKnowledgeBase;
    showDivider: boolean;
    inHamburger?: boolean;
}

/**
 * Implementation of SiteNav component
 */
export default function NavigationAdminLinks(props: IProps) {
    const classes = siteNavAdminLinksClasses();
    const { knowledgeBase } = props;
    const [modalOpen, setModalOpen] = useState(false);
    const categoryButtonRef = useRef<HTMLButtonElement>(null);
    const closeModal = () => setModalOpen(false);
    const openModal = () => setModalOpen(true);
    const currentLocale = getCurrentLocale();
    const sourceLocale = knowledgeBase.sourceLocale;
    const isDisabled = sourceLocale !== currentLocale;
    const adminLinksTitle = useUniqueID("adminLinksTitle");
    const classesDropdown = dropDownClasses();

    let newCategoryButton = (
        <Button
            onClick={openModal}
            buttonRef={categoryButtonRef}
            baseClass={ButtonTypes.CUSTOM}
            className={classNames(classes.link)}
            disabled={isDisabled}
        >
            <NewFolderIcon className={classes.linkIcon} />
            {t("New Category")}
        </Button>
    );

    if (props.inHamburger) {
        newCategoryButton = (
            <DropDownItemButton onClick={openModal} buttonRef={categoryButtonRef} disabled={isDisabled}>
                <NewFolderIcon className={dropDownClasses().actionIcon} />
                {t("New Category")}
            </DropDownItemButton>
        );
    }

    newCategoryButton = isDisabled ? (
        <ToolTip
            label={
                <Translate
                    source="You can only add categories in the source locale: <0/>."
                    c0={<LocaleDisplayer localeContent={sourceLocale || " "} displayLocale={sourceLocale || " "} />}
                />
            }
            ariaLabel={"You can only add categories in the source locale."}
        >
            <span>{newCategoryButton}</span>
        </ToolTip>
    ) : (
        newCategoryButton
    );

    let content: React.ReactNode;

    if (props.inHamburger) {
        content = (
            <>
                <hr className={classesDropdown.separator} />
                <ul>
                    <DropDownItemLink to={OrganizeCategoriesRoute.url({ kbID: knowledgeBase.knowledgeBaseID })}>
                        {organize(classesDropdown.actionIcon)}
                        {t("Organize Categories")}
                    </DropDownItemLink>
                    {newCategoryButton}
                </ul>
            </>
        );
    } else {
        content = (
            <>
                {props.showDivider && <hr className={classNames("siteNavAdminLinks-divider", classes.divider)} />}
                <nav aria-describedby={adminLinksTitle}>
                    <h3 id={adminLinksTitle} className="sr-only">
                        {t("Admin Links")}
                    </h3>
                    <ul className={classNames("siteNavAdminLinks", props.className, classes.root)}>
                        <li className={classNames("siteNavAdminLinks-item", classes.item)}>
                            <OrganizeCategoriesRoute.Link
                                className={classNames(classes.link)}
                                data={{ kbID: knowledgeBase.knowledgeBaseID }}
                            >
                                {organize(classes.linkIcon)}
                                {t("Organize Categories")}
                            </OrganizeCategoriesRoute.Link>
                        </li>
                        <li className={classNames("siteNavAdminLinks-item", classes.item)}>
                            {isDisabled ? (
                                <ToolTip
                                    label={
                                        <Translate
                                            source="You can only add categories in the source locale: <0/>."
                                            c0={
                                                <LocaleDisplayer
                                                    localeContent={sourceLocale || " "}
                                                    displayLocale={sourceLocale || " "}
                                                />
                                            }
                                        />
                                    }
                                    ariaLabel={"You can only add categories in the source locale."}
                                >
                                    <span>{newCategoryButton}</span>
                                </ToolTip>
                            ) : (
                                newCategoryButton
                            )}
                        </li>
                    </ul>
                </nav>
            </>
        );
    }

    return (
        <KbPermission kbID={knowledgeBase.knowledgeBaseID} permission="articles.add">
            {content}
            <NewCategoryForm
                isVisible={modalOpen}
                buttonRef={categoryButtonRef}
                exitHandler={closeModal}
                parentCategoryID={knowledgeBase.rootCategoryID}
            />
        </KbPermission>
    );
}
