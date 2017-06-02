# Category Banners

A plugin to add a banner image upload per category

## Compiling assets

The following instructions assume that you have already installed Node.js on your computer. If this is not the case, please download and install the latest stable release from the official [Node.js download page](http://nodejs.org/download/). If you are using [Homebrew](http://brew.sh/), you can also install Node.js via the command line:

```sh
$ brew install node
```

> __Notice__: It is important that you install Node in a way that does not require you to `sudo`.

Once you have Node.js up and running, you will need to install the local dependencies using [yarn](http://yarnpkg.org):

```sh
$ yarn
```

This project uses 2 build tools. Webpack is used for bundling javascript and styles (scss) and gulp is used to copy and minify/sprite images and svgs.

## Tasks

### Build - `yarn run build`
This task runs the `build:code` and `build:assets` simultaneously.

### Build Code - `yarn run build:code`
Bundle all styles and scripts in the projects. 
SCSS stylesheets will be compiled to [`design/style.css`](design/style.css) and javascript will be bundled and outputed to [`js/custom.js`](js/custom.js).

If you want to add additional css/scss/js file, be sure to `import` or `require()` in a file that is already included. 
Webpack builds its dependancy tree starting from [`src/js/main.js`](src/js/main.js) so make sure you include your dependancy somewhere in it.

### Build Assets - `yarn run build:assets`
Compresses all images in the [`src/images`](src/images) folder and moves them to [`design/images`](design/images).
Strips down all svgs in the [`src/svgs`](src/svgs) folder, and exports them as svg symbols named after the file name.
This compiled svg symbols file is place in [`views/partials/svg-symbols.tpl`](views/partials/svg-symbols.tpl) and should be included at the top of your [`views/default.master`](views/default.master).

#### Example

If you had an svg `src/svgs/my-icon-image.svg` it would used in your html as

```
<svg class="icon">
  <use xlink:href="#my-icon-image" />
</svg>
```

### Build - `yarn run Watch`
This task runs the `watch:code` and `watch:assets` simultaneously.

### Watch Code - `yarn run watch:code`
Runs a the webpack-dev-server with the [`webpack/webpack.config.dev.js`](webpack/webpack.config.dev.js) configuration. This dev-server watches for changes in the code, compiles and keeps the bundles in memory and serves them from `http://localhost:3000`. Include a script pointing to `http://localhost:3000/custom.js` in your page somewhere and it will wire up hot-reloading of your styles and javascript.

If you make changes to a webpack config or the devServer it will reload itself using [`nodemon`](https://github.com/remy/nodemon).

This tasks deletes all artifacts from the `build:code` task. Be sure to build again before deploying!

> __Notice__: Webpack dev server keeps everything in memory and does not write to disk. To output built files you must use `yarn run build:code` or `yarn run build`.


### Watch Assets - `yarn run watch:assets`
Watches for changes and re-runs the `build:assets` task.


## Linters

#### CSS - [stylelint](https://github.com/stylelint/stylelint)

---
Copyright &copy; 2017 Adam Charron.
