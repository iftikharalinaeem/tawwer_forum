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
import { ButtonTypes } from "@library/forms/buttonStyles";
import { NewFolderIcon } from "@library/icons/common";
import { getCurrentLocale, LocaleDisplayer } from "@vanilla/i18n";
import { ToolTip } from "@library/toolTip/ToolTip";
import Translate from "@library/content/Translate";

interface IProps {
    className?: string;
    knowledgeBase: IKnowledgeBase;
    showDivider: boolean;
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

    const newCategoryButton = (
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

    return (
        <Permission permission="articles.add">
            <ul className={classNames("siteNavAdminLinks", props.className, classes.root)}>
                {props.showDivider && (
                    <hr role="separator" className={classNames("siteNavAdminLinks-divider", classes.divider)} />
                )}
                <h3 className="sr-only">{t("Admin Links")}</h3>
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
            <NewCategoryForm
                isVisible={modalOpen}
                buttonRef={categoryButtonRef}
                exitHandler={closeModal}
                parentCategoryID={knowledgeBase.rootCategoryID}
            />
        </Permission>
    );
}
