jQuery(document).ready(function($) {
    
   var tokenRegex = /\{([^\}]+)\}/g,
   objNotationRegex = /(?:(?:^|\.)(.+?)(?=\[|\.|$|\()|\[('|")(.+?)\2\])(\(\))?/g, // matches .xxxxx or ["xxxxx"] to run over object properties
   replacer = function (all, key, obj) {
      var res = obj;
      key.replace(objNotationRegex, function (all, name, quote, quotedName, isFunc) {
         name = name || quotedName;
         if (res) {
            if (name in res) {
               res = res[name];
            }
            typeof res == "function" && isFunc && (res = res());
         }
      });
      res = (res == null || res == obj ? all : res) + "";
      return res;
   },
   fill = function (str, obj) {
      return String(str).replace(tokenRegex, function (all, key) {
         return replacer(all, key, obj);
      });
   };
   Raphael.fn.popup = function (X, Y, set, pos, ret) {
      pos = String(pos || "top-middle").split("-");
      pos[1] = pos[1] || "middle";
      var r = 5,
         bb = set.getBBox(),
         w = Math.round(bb.width),
         h = Math.round(bb.height),
         x = Math.round(bb.x) - r,
         y = Math.round(bb.y) - r,
         gap = Math.min(h / 2, w / 2, 10),
         shapes = {
            /*
             // Changing each of these boxes to not have pointers so multi-line graphs aren't pointing to a single line.
            top:    "M{x},{y}h{w4},{w4},{w4},{w4}a{r},{r},0,0,1,{r},{r}v{h4},{h4},{h4},{h4}a{r},{r},0,0,1,-{r},{r}l-{right},0-{gap},{gap}-{gap}-{gap}-{left},0a{r},{r},0,0,1-{r}-{r}v-{h4}-{h4}-{h4}-{h4}a{r},{r},0,0,1,{r}-{r}z",
            bottom: "M{x},{y}l{left},0,{gap}-{gap},{gap},{gap},{right},0a{r},{r},0,0,1,{r},{r}v{h4},{h4},{h4},{h4}a{r},{r},0,0,1,-{r},{r}h-{w4}-{w4}-{w4}-{w4}a{r},{r},0,0,1-{r}-{r}v-{h4}-{h4}-{h4}-{h4}a{r},{r},0,0,1,{r}-{r}z",
            right:  "M{x},{y}h{w4},{w4},{w4},{w4}a{r},{r},0,0,1,{r},{r}v{h4},{h4},{h4},{h4}a{r},{r},0,0,1,-{r},{r}h-{w4}-{w4}-{w4}-{w4}a{r},{r},0,0,1-{r}-{r}l0-{bottom}-{gap}-{gap},{gap}-{gap},0-{top}a{r},{r},0,0,1,{r}-{r}z",
            left:   "M{x},{y}h{w4},{w4},{w4},{w4}a{r},{r},0,0,1,{r},{r}l0,{top},{gap},{gap}-{gap},{gap},0,{bottom}a{r},{r},0,0,1,-{r},{r}h-{w4}-{w4}-{w4}-{w4}a{r},{r},0,0,1-{r}-{r}v-{h4}-{h4}-{h4}-{h4}a{r},{r},0,0,1,{r}-{r}z",
            basic:  "M{x},{y}h{w4},{w4},{w4},{w4}a{r},{r},0,0,1,{r},{r}v{h4},{h4},{h4},{h4}a{r},{r},0,0,1,-{r},{r}h-{w4}-{w4}-{w4}-{w4}a{r},{r},0,0,1-{r}-{r}v-{h4}-{h4}-{h4}-{h4}a{r},{r},0,0,1,{r}-{r}z"
            */
            top:    "M{x},{y}h{w4},{w4},{w4},{w4}a{r},{r},0,0,1,{r},{r}v{h4},{h4},{h4},{h4}a{r},{r},0,0,1,-{r},{r}h-{w4}-{w4}-{w4}-{w4}a{r},{r},0,0,1-{r}-{r}v-{h4}-{h4}-{h4}-{h4}a{r},{r},0,0,1,{r}-{r}z",
            bottom: "M{x},{y}h{w4},{w4},{w4},{w4}a{r},{r},0,0,1,{r},{r}v{h4},{h4},{h4},{h4}a{r},{r},0,0,1,-{r},{r}h-{w4}-{w4}-{w4}-{w4}a{r},{r},0,0,1-{r}-{r}v-{h4}-{h4}-{h4}-{h4}a{r},{r},0,0,1,{r}-{r}z",
            right:  "M{x},{y}h{w4},{w4},{w4},{w4}a{r},{r},0,0,1,{r},{r}v{h4},{h4},{h4},{h4}a{r},{r},0,0,1,-{r},{r}h-{w4}-{w4}-{w4}-{w4}a{r},{r},0,0,1-{r}-{r}v-{h4}-{h4}-{h4}-{h4}a{r},{r},0,0,1,{r}-{r}z",
            left:   "M{x},{y}h{w4},{w4},{w4},{w4}a{r},{r},0,0,1,{r},{r}v{h4},{h4},{h4},{h4}a{r},{r},0,0,1,-{r},{r}h-{w4}-{w4}-{w4}-{w4}a{r},{r},0,0,1-{r}-{r}v-{h4}-{h4}-{h4}-{h4}a{r},{r},0,0,1,{r}-{r}z"
         },
         offset = {
            hx0: X - (x + r + w - gap * 2),
            hx1: X - (x + r + w / 2 - gap),
            hx2: X - (x + r + gap),
            vhy: Y - (y + r + h + r + gap),
            "^hy": Y - (y - gap)
         },
         mask = [{
            x: x + r,
            y: y,
            w: w,
            w4: w / 4,
            h4: h / 4,
            right: 0,
            left: w - gap * 2,
            bottom: 0,
            top: h - gap * 2,
            r: r,
            h: h,
            gap: gap
         }, {
            x: x + r,
            y: y,
            w: w,
            w4: w / 4,
            h4: h / 4,
            left: w / 2 - gap,
            right: w / 2 - gap,
            top: h / 2 - gap,
            bottom: h / 2 - gap,
            r: r,
            h: h,
            gap: gap
         }, {
            x: x + r,
            y: y,
            w: w,
            w4: w / 4,
            h4: h / 4,
            left: 0,
            right: w - gap * 2,
            top: 0,
            bottom: h - gap * 2,
            r: r,
            h: h,
            gap: gap
         }][pos[1] == "middle" ? 1 : (pos[1] == "top" || pos[1] == "left") * 2];
         
         var dx = 0,
            dy = 0,
            out = this.path(fill(shapes[pos[0]], mask)).insertBefore(set);
         // Define the position of the popup
         switch (pos[0]) {
            case "top":
               dx = X - (x + r + mask.left + gap);
               dy = Y - (y + r + h + r + gap);
            break;
            case "bottom":
               dx = X - (x + r + mask.left + gap);
               dy = Y - (y - gap);
            break;
            case "left":
               // Changing the vertical position to always be above the line
               dx = X - (x + r + w + r + gap);
               dy = Y - (y + r + mask.top + gap);
               // Changing the vertical position to always be above the line
               dy = Y - (y + r + h + r + gap);
            break;
            case "right":
               dx = X - (x - gap);
               dy = Y - (y + r + mask.top + gap);
               // Changing the vertical position to always be above the line
               dy = Y - (y + r + h + r + gap);
            break;
         }
         out.translate(dx, dy);
         if (ret) {
            ret = out.attr("path");
            out.remove();
            return {
               path: ret,
               dx: dx,
               dy: dy
            };
         }
         set.translate(dx, dy);
         return out;
   };


   function getAnchors(p1x, p1y, p2x, p2y, p3x, p3y) {
      var l1 = (p2x - p1x) / 2,
         l2 = (p3x - p2x) / 2,
         a = Math.atan((p2x - p1x) / Math.abs(p2y - p1y)),
         b = Math.atan((p3x - p2x) / Math.abs(p2y - p3y));
      a = p1y < p2y ? Math.PI - a : a;
      b = p3y < p2y ? Math.PI - b : b;
      var alpha = Math.PI / 2 - ((a + b) % (Math.PI * 2)) / 2,
         dx1 = l1 * Math.sin(alpha + a),
         dy1 = l1 * Math.cos(alpha + a),
         dx2 = l2 * Math.sin(alpha + b),
         dy2 = l2 * Math.cos(alpha + b);
      return {
         x1: p2x - dx1,
         y1: p2y + dy1,
         x2: p2x + dx2,
         y2: p2y + dy2
      };
    }
    
   function getColor(graphContainer, row, forLine) {
      var index = row;
      if (forLine) {
         var inps = $('input:hidden[name^="graphLabel_"]');
         var onCount = 0;
         for (var i = 0; i < inps.length; i++) {
            if ($(inps[i]).val() == 'off' && onCount <= row) {
               index++;
            } else {
               onCount++;
            }
         }
      }
      return $('#'+graphContainer+' span.Metric'+(index+1)).css('color');
    }
    
   function getRowController(rowIndex) {
      return $('input:hidden[name="graphLabel_'+rowIndex+'"]');
   }

   function getRowState(rowIndex) {
      var inp = getRowController(rowIndex);
      return inp.length > 0 ? inp.val() : "off";
   }
    
   Raphael.fn.drawGrid = function (x, y, w, h, wv, hv, color) {
      color = color || "#000";
      var path = [
         "M",
         Math.round(x) + .5,
         Math.round(y) + .5,
         "L",
         Math.round(x + w) + .5,
         // Math.round(y) + .5,
         // Math.round(x + w) + .5,
         // Math.round(y + h) + .5,
         // Math.round(x) + .5,
         // Math.round(y + h) + 5,
         // Math.round(x) + .5,
         // Math.round(y) + .5
      ],
         rowHeight = h / hv,
         columnWidth = w / wv,
         dashed = {fill: "none", stroke: color, "stroke-dasharray": "- "};

      /* Don't show so many vertical cols?
      var maxCols = 30;
      var colsToDisplay = wv;
      var increment = 1;
      while (colsToDisplay > maxCols) {
          increment++;
          colsToDisplay = Math.round(wv / increment);
      }
  
      for (i = 0; i <= wv; i += increment) {
      */
      for (i = 0; i <= wv; i++) {
         path = path.concat(["M", Math.round(x + i * columnWidth) + .5, Math.round(y) + .5, "V", Math.round(y + h) + .5]);
      }
      return this.path(path.join(",")).attr(dashed);
    }
    
   Raphael.fn.drawFoot = function(labels, height, leftgutter, r, footText, X, bottomgutter) {
      var maxLabels = 12;
      var labelsToDisplay = labels.length;
      var increment = 1;
      while (labelsToDisplay > maxLabels) {
         increment++;
         labelsToDisplay = Math.round(labels.length / increment);
      }
      for (var i = 0; i < labels.length; i += increment) {
         var x = Math.round(leftgutter + X * (i + .5)); // Define the x position of the dot
         var attr = {};
         if (i == 0) {
            attr = {'text-anchor': 'start'};
            x = x - 4;
         }
         if (i == labels.length - 1) {
            attr = {'text-anchor': 'end'};
            x = x + 4;
         }
         var t = r.text(x, height - bottomgutter + 10, labels[i]).attr(footText).attr(attr).toBack(); // This adds the text along the bottom of the grid
      }
      return;    
   }
    
   Raphael.fn.drawLegend = function(graphContainer, raphael, rows, height, leftgutter, X) {
      var legend = raphael.set();
      var xPos = Math.round(leftgutter + 16);
      var boxes = [];
      for (var i = 0; i < rows.length; i++) {
         // Add hidden input
         var inp = getRowController(i);
         if (inp.length == 0)
            $('body').append('<input type="hidden" name="graphLabel_'+i+'" value="on" />');

         var inp = getRowController(i);

         // Draw a box
         var box = raphael.rect(xPos, height - 15, 13, 13);
         var white = 'rgb(255,255,255)';
         var color = getColor(graphContainer, i, false);
         var bgcolor = $('body').css('background-color');
         box.attr({ fill: inp.val() == 'on' ? color : white, stroke: color, "stroke-width": 2, cursor: 'pointer'});
         legend.push(box);
         (function(box, inp, white, color){
            box.node.onclick = function() {
               if (inp.val() == "on") {
                  inp.val('off');
                  box.attr({fill: color});
               } else {
                  inp.val('on');
                  box.attr({fill: white});
               }
               getData();
            }
            box.hover(function() {
               box.attr({stroke: '#444'});
            }, function() {
               box.attr({stroke: color});
            });
         })(box, inp, white, color);
         xPos = xPos + Math.round(box.getBBox().width) + 6;
            
         // Draw a label for the box
         var text = raphael.text(xPos, height - 8, rows[i]).attr({
            'font-size': $('#'+graphContainer+' span.Legend').css('fontSize'),
            'font-family': $('#'+graphContainer+' span.Legend').css('fontFamily'),
            'stroke-width': 0,
            'text-anchor': 'start',
            fill: $('#'+graphContainer+' span.Legend').css('color')
         });
         legend.push(text);
         xPos = xPos + Math.round(text.getBBox().width) + 20;
      }
      return;
   }
    
   drawGraph = function(graphContainer, dataSource) {
      // initialize the chart area
      var graphHolder = $("#" + graphContainer);
      graphHolder.children('*:not(span)').remove();

      // define data containers
      var rowLabels = [],
         visibleRowLabels = [],
         footLabels = [],
         rows = [],
         data = [],
         i = -1;
         
      $.each(dataSource, function(Key, Value) {
         if (Key == 'Dates') {
            footLabels = Value;
         } else {
            rowLabels.push(Key);
            
            // Don't include unchecked rows
            i++;
            if (getRowController(i).val() == "off")
               return;
                 
            visibleRowLabels.push(Key);
            rows.push(Value);
            $.each(Value, function(k, v) {
               data.push(v);
            });
         }
      });
         
      // I realize this identifies the rows being graph'd (Users,
      // Discussions, Comments), but I can't think of a better place to
      // put it right now:
      setSummary(dataSource, 'Page Views', 'li.PageViews strong');
      setSummary(dataSource, 'Users', 'li.NewUsers strong');
      setSummary(dataSource, 'Discussions', 'li.NewDiscussions strong');
      setSummary(dataSource, 'Comments', 'li.NewComments strong');
        
      //create the Raphael object
      var width = graphHolder.width(),
         height = graphHolder.height(),
         leftgutter = 0,
         bottomgutter = 40,
         topgutter = 20,
         r = Raphael(graphContainer, width, height),
         metricFontSize = 11,
         metricText = {font: metricFontSize + "px 'lucida grande','Lucida Sans Unicode', tahoma, sans-serif", fill: "#fff"},
         dateText = {font: "10px 'lucida grande','Lucida Sans Unicode', tahoma, sans-serif", fill: "#fff"},
         footText = {font: $('#'+graphContainer+' span.Headings').css('font-size') + ' ' + $('#'+graphContainer+' span.Headings').css('font-family'), fill: $('#'+graphContainer+' span.Headings').css('color')},
         X = (width - leftgutter) / footLabels.length,
         max = Math.max.apply(Math, data),
         Y = (height - bottomgutter - topgutter) / max;
      
      r.clear();
      r.drawGrid(leftgutter + X * .5 - 1, topgutter + .5, width - leftgutter - X, height - topgutter - bottomgutter, footLabels.length - 1, 10, graphHolder.css('border-bottom-color'));
      r.drawFoot(footLabels, height, leftgutter, r, footText, X, bottomgutter);
      r.drawLegend(graphContainer, r, rowLabels, height, leftgutter, X);

      // Draw the "max" number on the top left
      var text = r.text(leftgutter + 12, topgutter - 2, max.formatThousands()).attr({'text-anchor': 'start'}).attr(footText);
            
      var dots = [];
      var coordinates = [];
      for (var j = 0; j < rows.length; j++) {
         dots.push([]);
         coordinates.push([]);
         var color = getColor(graphContainer, j, true);
         var row = rows[j];
         var path = r.path().attr({stroke: color, "stroke-width": 2}),
            label = r.set(),
            is_label_visible = false,
            leave_timer,
            blanket = r.set(),
            lineHeight = metricFontSize + 2;
         for (m = 0; m < rows.length; m++) {
            label.push(r.text(0, lineHeight, "400 Discussions").attr(metricText).attr({fill: getColor(graphContainer, m, true)}));
            lineHeight += metricFontSize + 2;
         }
         label.push(r.text(0, lineHeight + 4, "16 Sept").attr(dateText));
         label.hide();
         var frame = r.popup(100, 100, label, "right").attr({fill: "#000", stroke: "#474747", "stroke-width": 2, "fill-opacity": .7}).hide();
         var p;
         for (var i = 0; i < row.length; i++) {
            var y = Math.round(height - bottomgutter - Y * row[i]); // Define the y position of the dot
            var x = Math.round(leftgutter + X * (i + .5)); // Define the x position of the dot
            if (!i) {
               p = ["M", x, y, "C", x, y]; // C is for a curving line path
            }
            if (i && i < row.length - 1) {
               var Y0 = Math.round(height - bottomgutter - Y * row[i - 1]),
                  Y2 = Math.round(height - bottomgutter - Y * row[i + 1]),
                  // Defines the radius of the curve of the line paths
                  X0 = Math.round(leftgutter + X * (i + .1)),
                  X2 = Math.round(leftgutter + X * (i + 1.1));
    
               var a = getAnchors(X0, Y0, x, y, X2, Y2);
               p = p.concat([a.x1, a.y1, x, y, a.x2, a.y2]);
            }
            var dot = r.circle(x, y, 4).attr({fill: color, stroke: graphHolder.css('background-color')});
            blanket.push(r.rect(leftgutter + X * i, 0, X, height - bottomgutter).attr({stroke: "none", fill: "#fff", opacity: 0}));
                
            dots[j].push(dot);
            coordinates[j].push([x, y]);
         }
         for (var i = 0; i < blanket.length; i++) {
            var rect = blanket[i];
            // Show/Hide the popup data when the top line is hovered
            (function (i, lbl) {
               var timer;
               rect.hover(function () {
                  yLevel = 100000;
                  for (j = 0; j < rows.length; j++) {
                     // dots[j][i].show();
                     dots[j][i].attr("r", 5);
                     x = coordinates[j][i][0];
                     y = coordinates[j][i][1];
                     if (y < yLevel)
                        yLevel = y;
                  }
                  clearTimeout(leave_timer);
                  var side = "top-middle";
                  if (x + frame.getBBox().width > width)
                     side = "left";
                        
                     if (x < frame.getBBox().width)
                        side = "right";
                            
                     if (yLevel - frame.getBBox().height - 8 < 0)
                        side = "bottom-middle";

                     if (x < frame.getBBox().width && side == "bottom-middle")
                        side = "bottom-left";
                            
                     var ppp = r.popup(x, yLevel, label, side, 1);
                     frame.show().animate({path: ppp.path}, 200 * is_label_visible);
                     for (a = 0; a < rows.length; a++) {
                        label[a].attr({text: (rows[a][i] * 1).formatThousands() + " " + visibleRowLabels[a]}).show().animateWith(frame, {translation: [ppp.dx, ppp.dy]}, 200 * is_label_visible);                            
                     }
                     label[rows.length].attr({text: lbl}).show().animateWith(frame, {translation: [ppp.dx, ppp.dy]}, 200 * is_label_visible);
                     is_label_visible = true;
                  }, function () {
                     for (j = 0; j < rows.length; j++) {
                        // dots[j][i].hide();
                        dots[j][i].attr("r", 4);
                     }
                     leave_timer = setTimeout(function () {
                        frame.hide();
                        label.hide();
                        is_label_visible = false;
                     }, 1);
                  });
               })(i, footLabels[i]);
            }
            p = p.concat([x, y, x, y]);
            path.attr({path: p}); // This adds the line connecting the dots
            frame.toFront();
            label.toFront();
            blanket.toFront();   
      }
   }
   
   // Sum and format data for the summary rows
   Number.prototype.formatThousands = function() {
      var n = this,
         t = ",",
         s = n < 0 ? "-" : "",
         i = parseInt(n = Math.abs(+n || 0).toFixed(0)) + "",
         j = (j = i.length) > 3 ? j % 3 : 0;
      return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t);
   };

   function setSummary(data, rowkey, selector) {
      var sum = 0;
      $.each(data[rowkey], function(Key, Value) {
         sum += Value * 1;
      });
      $(selector).html((sum).formatThousands());
   }
    
   function getData() {
      // Add spinners
      if ($('#Content h1 span').length == 0)
         $('<span class="TinyProgress"></span>').appendTo('#Content h1:last');
            
      if ($('div.DashboardSummaries div.Loading').length == 0)
         $('div.DashboardSummaries').html('<div class="Loading"></div>');
            
      // Load the graph data
      var dataUrl = gdn.url('/dashboard/settings/loadstats');
      
      $.ajax({
         type: "GET",
         url: dataUrl,
         data: {
            'Range': $('input.Range').val(),
            'DateRange': $('input.DateRange').val()
         },
         dataType: 'json',
         error: function(XMLHttpRequest, textStatus, errorThrown) {
            $('#Content h1 span.TinyProgress').remove();
            var graphHolder = $("#GraphHolder");
            graphHolder.children('*:not(span)').remove();
            $('<div class="Messages Errors"><ul><li>Failed to load statistics data.</li></ul></div>').appendTo(graphHolder);
         },
         success: function(data) {
            $('#Content h1 span.TinyProgress').remove();
            drawGraph('GraphHolder', data);
         }
      });
        
      $.get(gdn.url('/dashboard/settings/dashboardsummaries&DeliveryType=VIEW&Range='+$('input.Range').val()+'&DateRange='+$('input.DateRange').val()), function(data) {
            $('div.DashboardSummaries').html(data);
      });
   }

   // Draw the graph when the window is loaded.
   window.onload = function() {
      getData();
   }

   // Redraw the grpah when the window is resized
   $(window).resize(function() {
      getData();
   });

   // Redraw the graph if the date range changes
   $('input.DateRange').live('change', function() {
      getData();
   });

});