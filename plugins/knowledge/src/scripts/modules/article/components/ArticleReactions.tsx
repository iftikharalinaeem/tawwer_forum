/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import { t } from "@library/application";
import Translate from "@library/components/translation/Translate";
import SmartLink from "@library/components/navigation/SmartLink";
import { connect } from "react-redux";
import UsersModel, { IUsersStoreState } from "@library/users/UsersModel";

function ArticleReactions(props: IProps) {
    const signInLinkCallback = content => (
        <SmartLink to={`/entry/signin?target=${window.location.pathname}`}>{content}</SmartLink>
    );

    return (
        <section className="helpful">
            <h3 className="helpful-title">{t("Was this article helpful?")}</h3>
            <div className="helpful-vote">
                <button className="button helpful-button helpful-buttonNo">{t("No")}</button>
                <button className="button helpful-button helpful-buttonYes">{t("Yes")}</button>
            </div>
            <div className="helpful-results">{t("5 out of 6 people found this helpful")}</div>
            {!props.user ||
                (props.user.userID === UsersModel.GUEST_ID && (
                    <div className="helpful-signIn">
                        <Translate
                            source={"You need to <0>Sign In</0> to vote on this article"}
                            c0={signInLinkCallback}
                        />
                    </div>
                ))}
        </section>
    );
}

interface IOwnProps {}

type IProps = IOwnProps & ReturnType<typeof mapStateToProps>;

function mapStateToProps(state: IUsersStoreState, ownProps: IOwnProps) {
    return {
        user: state.users.current.data,
    };
}

export default connect(mapStateToProps)(ArticleReactions);
