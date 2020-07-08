/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useState } from "react";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Button from "@library/forms/Button";
import { userCardClasses } from "@library/features/users/ui/popupUserCardStyles";
import classNames from "classnames";
import { IUserCardModule, UserCardModule } from "@library/features/users/modules/UserCardModule";

export interface IProps extends Omit<IUserCardModule, "fallbackButton"> {}

export function UserCardModuleLazyLoad(props: IProps) {
    const { buttonContent, children } = props;
    const [ready, setReady] = useState(false);

    const fallbackButton = (
        <Button
            onClick={() => {
                setReady(true);
            }}
            baseClass={ButtonTypes.TEXT}
            className={classNames(userCardClasses().link, {
                isLoading: ready,
            })}
            disabled={ready}
        >
            {children || buttonContent}
        </Button>
    );

    if (!ready) {
        return <>{fallbackButton}</>;
    } else {
        return <UserCardModule {...props} fallbackButton={fallbackButton} visible={true} />;
    }
}
