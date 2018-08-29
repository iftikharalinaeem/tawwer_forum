/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

// Import stylesheets
import "../../scss/knowledge-styles.scss";

// Vendors
import React from "react";
import ReactDOM from "react-dom";

// Our own libraries
import { onReady } from "@dashboard/application";
import { registerReducer } from "@dashboard/state/reducerRegistry";

// Knowledge Modules
import HelloKnowledge from "@knowledge/components/HelloKnowledge";
import rootReducer from "@knowledge/rootReducer";
import getStore from "@dashboard/state/getStore";
import { IGetArticleResponseBody, Format } from "@knowledge/@types/api";
import { getArticleActions } from "@knowledge/pages/article/articlePageActions";
import { Provider } from "react-redux";

onReady(() => {
    registerReducer("knowledge", rootReducer);
    const app = document.querySelector("#app");
    ReactDOM.render(
        <Provider store={getStore()}>
            <HelloKnowledge />
        </Provider>,
        app,
    );

    // TODO: remove this once we have the API endpoints setup.
    createDummyArticleData();
});

/**
 * Create dummy article data and insert it into the redux store.
 */
function createDummyArticleData() {
    const article: IGetArticleResponseBody = {
        name: "Test Dummy article",
        locale: "en",
        body: "[{}]", // This shouldn't be needed for the view page.
        format: Format.RICH,
        articleCategoryID: 0,
        seoName: "Knowledge Base Site Name - Test Dummy Article",
        seoDescription:
            "You will find a common format across all the hosting directories. We have information structured on three bits – Shared Unix or Shared Linux Packages, Shared Windows Packages, Reseller Packages.",
        slug: "test-dummy-article",
        sort: 0,
        articleID: 0,
        insertUserID: 0,
        insertUser: {
            userID: 0,
            name: "John Doe",
            photoUrl: "https://us.v-cdn.net/5022541/uploads/userpics/314/p0CECMSM6JR3L.jpeg",
            dateLastActive: "2018-08-20T13:08:49+00:00",
        },
        updateUserID: 0,
        dateInserted: "2018-08-20T13:08:49+00:00",
        dateUpdated: "2018-08-20T13:08:49+00:00",
        score: 17,
        countViews: 10,
        url: "https://testsite.com/knowledge/articles/test-dummy-article-0",
        bodyRendered: dummyArticleContent,
    };

    const store = getStore();
    store.dispatch(getArticleActions.success({ data: article, status: 200, headers: {} }, undefined));
}

const dummyArticleContent = `
<div class="_userContent">
    <h3 class="_pageSubTitle">Overview</h3>

    <p>You will find a common format across all the hosting directories. We have information structured on three bits – Shared Unix or Shared Linux Packages, Shared Windows Packages, Reseller Packages. Each Hosting Directory <s>Strikethrough</s> has a list of plans. Each plan is accompanies by a short synopsis of the plan details. Hence, expect to find the price, the web <strong>Bold</strong>, Data This is a <a href="#">link</a> and the <em>Italic</em> platform.</p>

    <ul>
        <li>Unordered list example one</li>
        <li>Unordered list example two</li>
        <li>
            Unordered list example two
            <ul>
                <li>Unordered list example two</li>
                <li>Unordered list example two</li>
            </ul>
        </li>
        <li>Unordered list example three</li>
        <li>Unordered list example four</li>
        <li>Unordered list example five</li>
    </ul>

    <p>At one shot, you would be able to sort these plans by any of the following factors – Price, rating, Web Space, Data Transfer. With such a high range of flexibility in filtering of data, you could almost be assured of making the right choice as long as your hosting company is considered.</p>

    <h3 class="pageSubTitle">Changing Themes</h3>

    <p>We are one of the ones that provide web talk forums for you to discuss with other members of the forums. Basically, through these forums you could ask your questions about a particular plan and in essence also gather information on the best hosting plan for their needs. In fact, these forums would give you a good opportunity for you to get your questions answered.</p>

    <ol>
        <li>Ordered list example one</li>
        <li>Ordered list example two</li>
        <li>
            Ordered list example two
            <ol>
                <li>Ordered list example two</li>
                <li>Ordered list example two</li>
            </ol>
        </li>
        <li>Ordered list example three</li>
        <li>Ordered list example four</li>
        <li>Ordered list example five</li>
    </ol>

    <p>Unlike some of the other web hosting reviews, this website offers you reviews for free. You just do need to pay us a dime for all the information you get. It does not matter if you search Shared Unix, Shared Linux Packages or Shared Windows Packages. Our idea is to provide you quality information. We leave the decision up to you once we think we have done a good job of the deal.</p>
</div>
`;
