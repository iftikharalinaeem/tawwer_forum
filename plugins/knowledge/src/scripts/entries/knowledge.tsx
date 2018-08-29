import React from "react";
import ReactDOM from "react-dom";
import Article from "@knowledge/pages/Article";
import { Devices } from "@knowledge/components/DeviceChecker";
import { onReady } from "@dashboard/application";

// Import stylesheets
import "../../scss/knowledge-styles.scss";

onReady(() => {
    const app = document.querySelector("#app");
    ReactDOM.render(<Article device={Devices.DESKTOP} />, app);
});

