# Analytics User Interface Architecture

## Introduction

This document explains the architecture for the analytics UI. It's used to drive the backend and frontend development of the analytics UI.

## Structure

The basic object structure of the analytics UI is as follows:

- Sections
- Section (name, slug)
    - Dashboards
    - Dashboard (id, name, shared, locked)
        - Panels
        - Panel (widget types)
            - Widgets
            - Widget (type: metric/chart, attributes)

Here is an explaination on plain English:

- There are several sections. Each section is represented as a heading in the side menu and is used to group similar analytics together.

- Each section contains several dashboards. A dashboard looks like a page of charts and metrics. Some dashboards are shared amongst all users while others can be created by a single user for their own private set of charts. Some dashboards are created by developers and are locked from editing while other dashboards can have widgets added, removed, and reordered.

- Each dashboard contains several panels. A panel is used to group widgets of a certain type. This is so widgets of similar size will be together and the UI will look clean. Initially each dashboard will be hard-coded to have two panels: The first panel will take metrics and the second panel will take charts.

- Each panel contains several widgets. The widgets can be ordered and get output one after another. CSS will decide how wide the widgets are and how they display. As a guideline, if widgets are less than 100% width then they must be all the same height. If they are 100% width then they can be different heights. Most widgets of a type should be the exact same size though.

- Metric widgets display a single count, average, or some other calculation. An example would be showing the total discussions for a time period.

- Chart widgets display a chart of a particular query for a time period. Some charts might show a total such as a pie chart while some charts will show data broken into segments such as a line chart.

- When looking at a dashboard the top portion will contain some way of filtering the data in all of the widgets on the dashboard. There will be a way of selecting the data range that the queries apply to. There will be a way of selecting whether to view the data in daily, weekly, or monthly segments. There will be a way of filtering all of the data by the top-level category. In future versions there will be additional top-level filters.

## Personal Dashboards

Users should be able to create their own dashboards. When creating a dashboard there will be the following information:

- **Name**: The name of the dashboard
- **Shared**: Whether or not the dashboard is shared with other users.

One the dashboard is created it will be blank. There should be a message instructing the user how to add analytics to it.

### Behaviour

- All user-generated dashboards cannot be locked.
- All user-generated dashboards will go in a single section called "Dashboards".
- All shared dashboards must have a unique name.
- Users should be able to edit their name/shared information of their dashboards.
- There should be a permission to allow roles to edit other users' shared dashboards.
- When viewing other dashboards there will be a button on each widget to allow a user to add that widget to their dashboard. If they have several dashoards then they can choose which one to add the widget to.
- Users should be able to reorder widgets in their dashboards.

## Built-In Dashboards

Built-In dashboard contain all of the initial hard-coded dashboards for analytics. They can be defined in a source-code file somewhere, rather than saved in the database. Built-in dashboards are all locked, signifying that they are immutable. The built-in dashboards are how the personal dashboards are populated.

## Programming

The architecture presented here should have some sort of data structure. The data structure will be represented on the server and partially on the client.

- The data structure should probably contain a mixture of classes and arrays. - The data structure will be presented in the database in the case of personal dashboards and in a PHP file in the case of built-in dashboards.
- The database structure will be loaded and merged with the built-in dashboard. This will then be passed off to the client where the client will interpret the rendering of the widgets. The process can be done dashboard-by-dashboard, but eventually the entire data structure will be client-side to allow for a full javascript analytics application.
- It should be possible to add a widget to a built-in dashboard in a plugin or by adding another row in the database. If it's added in the database it will just be merged with the rest of the personal dashboards. This feature will be useful for adding custom analytics reports for VIP customers.

## Version 1

The version 1 of anlytics will limit some of the features described above.

- In terms of sections/dashboards. The initial development will not display sections, but just use them for ordering. There will be one section on the side-menu called "Analytics". Then each dashboard will just be a link under that.
- There won't be a way to add personal dashboards. Instead, the button on widgets should say "add to my dashboard". The first time a user does this the system will create a personal dashboard called "My Dashboard".
- **Recommendation**: Get the basics of built-in dashboards working before doing any personal dashboard work. This way we can add as many built-in widgets as possible and iterate on the needs of them before the customization work is one. There is a chance all personal dashboard functionality will be released post version 1.
