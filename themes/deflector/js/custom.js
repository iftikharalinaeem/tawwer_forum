!function(t){if("object"==typeof exports)module.exports=t();else if("function"==typeof define&&define.amd)define(t);else{var r;"undefined"!=typeof window?r=window:"undefined"!=typeof global?r=global:"undefined"!=typeof self&&(r=self),r.GeoPattern=t()}}(function(){return function t(r,s,e){function i(n,a){if(!s[n]){if(!r[n]){var h="function"==typeof require&&require;if(!a&&h)return h(n,!0);if(o)return o(n,!0);throw new Error("Cannot find module '"+n+"'")}var l=s[n]={exports:{}};r[n][0].call(l.exports,function(t){var s=r[n][1][t];return i(s?s:t)},l,l.exports,t,r,s,e)}return s[n].exports}for(var o="function"==typeof require&&require,n=0;n<e.length;n++)i(e[n]);return i}({1:[function(t,r){!function(s){"use strict";function e(t){return function(r,s){return"object"==typeof r&&(s=r,r=null),(null===r||void 0===r)&&(r=(new Date).toString()),s||(s={}),t.call(this,r,s)}}var i=t("./lib/pattern"),o=r.exports={generate:e(function(t,r){return new i(t,r)})};s&&(s.fn.geopattern=e(function(t,r){return this.each(function(){var e=s(this).attr("data-title-sha");e&&(r=s.extend({hash:e},r));var i=o.generate(t,r);s(this).css("background-image",i.toDataUrl())})}))}("undefined"!=typeof jQuery?jQuery:null)},{"./lib/pattern":3}],2:[function(t,r){"use strict";function s(t){var r=/^#?([a-f\d])([a-f\d])([a-f\d])$/i;t=t.replace(r,function(t,r,s,e){return r+r+s+s+e+e});var s=/^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(t);return s?{r:parseInt(s[1],16),g:parseInt(s[2],16),b:parseInt(s[3],16)}:null}function e(t){return"#"+["r","g","b"].map(function(r){return("0"+t[r].toString(16)).slice(-2)}).join("")}function i(t){var r=t.r,s=t.g,e=t.b;r/=255,s/=255,e/=255;var i,o,n=Math.max(r,s,e),a=Math.min(r,s,e),h=(n+a)/2;if(n===a)i=o=0;else{var l=n-a;switch(o=h>.5?l/(2-n-a):l/(n+a),n){case r:i=(s-e)/l+(e>s?6:0);break;case s:i=(e-r)/l+2;break;case e:i=(r-s)/l+4}i/=6}return{h:i,s:o,l:h}}function o(t){function r(t,r,s){return 0>s&&(s+=1),s>1&&(s-=1),1/6>s?t+6*(r-t)*s:.5>s?r:2/3>s?t+(r-t)*(2/3-s)*6:t}var s,e,i,o=t.h,n=t.s,a=t.l;if(0===n)s=e=i=a;else{var h=.5>a?a*(1+n):a+n-a*n,l=2*a-h;s=r(l,h,o+1/3),e=r(l,h,o),i=r(l,h,o-1/3)}return{r:Math.round(255*s),g:Math.round(255*e),b:Math.round(255*i)}}r.exports={hex2rgb:s,rgb2hex:e,rgb2hsl:i,hsl2rgb:o,rgb2rgbString:function(t){return"rgb("+[t.r,t.g,t.b].join(",")+")"}}},{}],3:[function(t,r){(function(s){"use strict";function e(t,r,s){return parseInt(t.substr(r,s||1),16)}function i(t,r,s,e,i){var o=parseFloat(t),n=s-r,a=i-e;return(o-r)*a/n+e}function o(t){return t%2===0?C:j}function n(t){return i(t,0,15,M,W)}function a(t){var r=t,s=r/2,e=Math.sin(60*Math.PI/180)*r;return[0,e,s,0,s+r,0,2*r,e,s+r,2*e,s,2*e,0,e].join(",")}function h(t,r){var s=.66*r;return[[0,0,t/2,r-s,t/2,r,0,s,0,0],[t/2,r-s,t,0,t,s,t/2,r,t/2,r-s]].map(function(t){return t.join(",")})}function l(t){return[[t,0,t,3*t],[0,t,3*t,t]]}function c(t){var r=t,s=.33*r;return[s,0,r-s,0,r,s,r,r-s,r-s,r,s,r,0,r-s,0,s,s,0].join(",")}function f(t,r){var s=t/2;return[s,0,t,r,0,r,s,0].join(",")}function u(t,r){return[t/2,0,t,r/2,t/2,r,0,r/2].join(",")}function p(t){return[0,0,t,t,0,t,0,0].join(",")}function g(t,r,s,e,i){var a=p(e),h=n(i[0]),l=o(i[0]),c={stroke:S,"stroke-opacity":A,"fill-opacity":h,fill:l};t.polyline(a,c).transform({translate:[r+e,s],scale:[-1,1]}),t.polyline(a,c).transform({translate:[r+e,s+2*e],scale:[1,-1]}),h=n(i[1]),l=o(i[1]),c={stroke:S,"stroke-opacity":A,"fill-opacity":h,fill:l},t.polyline(a,c).transform({translate:[r+e,s+2*e],scale:[-1,-1]}),t.polyline(a,c).transform({translate:[r+e,s],scale:[1,1]})}function v(t,r,s,e,i){var a=n(i),h=o(i),l=p(e),c={stroke:S,"stroke-opacity":A,"fill-opacity":a,fill:h};t.polyline(l,c).transform({translate:[r,s+e],scale:[1,-1]}),t.polyline(l,c).transform({translate:[r+2*e,s+e],scale:[-1,-1]}),t.polyline(l,c).transform({translate:[r,s+e],scale:[1,1]}),t.polyline(l,c).transform({translate:[r+2*e,s+e],scale:[-1,1]})}function y(t,r){var s=t/2;return[0,0,r,s,0,t,0,0].join(",")}var d=t("extend"),b=t("./color"),m=t("./sha1"),k=t("./svg"),x={baseColor:"#933c3c"},w=["octogons","overlappingCircles","plusSigns","xes","sineWaves","hexagons","overlappingRings","plaid","triangles","squares","concentricCircles","diamonds","tessellation","nestedSquares","mosaicSquares","chevrons"],j="#222",C="#ddd",S="#000",A=.02,M=.02,W=.15,H=r.exports=function(t,r){return this.opts=d({},x,r),this.hash=r.hash||m(t),this.svg=new k,this.generateBackground(),this.generatePattern(),this};H.prototype.toSvg=function(){return this.svg.toString()},H.prototype.toString=function(){return this.toSvg()},H.prototype.toBase64=function(){var t,r=this.toSvg();return t="undefined"!=typeof window&&"function"==typeof window.btoa?window.btoa(r):new s(r).toString("base64")},H.prototype.toDataUri=function(){return"data:image/svg+xml;base64,"+this.toBase64()},H.prototype.toDataUrl=function(){return'url("'+this.toDataUri()+'")'},H.prototype.generateBackground=function(){var t,r,s,o;this.opts.color?s=b.hex2rgb(this.opts.color):(r=i(e(this.hash,14,3),0,4095,0,359),o=e(this.hash,17),t=b.rgb2hsl(b.hex2rgb(this.opts.baseColor)),t.h=(360*t.h-r+360)%360/360,t.s=o%2===0?Math.min(1,(100*t.s+o)/100):Math.max(0,(100*t.s-o)/100),s=b.hsl2rgb(t)),this.color=b.rgb2hex(s),this.svg.rect(0,0,"100%","100%",{fill:b.rgb2rgbString(s)})},H.prototype.generatePattern=function(){var t=this.opts.generator;if(t){if(w.indexOf(t)<0)throw new Error("The generator "+t+" does not exist.")}else t=w[e(this.hash,20)];return this["geo"+t.slice(0,1).toUpperCase()+t.slice(1)]()},H.prototype.geoHexagons=function(){var t,r,s,h,l,c,f,u,p=e(this.hash,0),g=i(p,0,15,8,60),v=g*Math.sqrt(3),y=2*g,d=a(g);for(this.svg.setWidth(3*y+3*g),this.svg.setHeight(6*v),s=0,u=0;6>u;u++)for(f=0;6>f;f++)c=e(this.hash,s),t=f%2===0?u*v:u*v+v/2,h=n(c),r=o(c),l={fill:r,"fill-opacity":h,stroke:S,"stroke-opacity":A},this.svg.polyline(d,l).transform({translate:[f*g*1.5-y/2,t-v/2]}),0===f&&this.svg.polyline(d,l).transform({translate:[6*g*1.5-y/2,t-v/2]}),0===u&&(t=f%2===0?6*v:6*v+v/2,this.svg.polyline(d,l).transform({translate:[f*g*1.5-y/2,t-v/2]})),0===f&&0===u&&this.svg.polyline(d,l).transform({translate:[6*g*1.5-y/2,5*v+v/2]}),s++},H.prototype.geoSineWaves=function(){var t,r,s,a,h,l,c,f=Math.floor(i(e(this.hash,0),0,15,100,400)),u=Math.floor(i(e(this.hash,1),0,15,30,100)),p=Math.floor(i(e(this.hash,2),0,15,3,30));for(this.svg.setWidth(f),this.svg.setHeight(36*p),r=0;36>r;r++)l=e(this.hash,r),s=n(l),t=o(l),c=f/4*.7,h={fill:"none",stroke:t,opacity:s,"stroke-width":""+p+"px"},a="M0 "+u+" C "+c+" 0, "+(f/2-c)+" 0, "+f/2+" "+u+" S "+(f-c)+" "+2*u+", "+f+" "+u+" S "+(1.5*f-c)+" 0, "+1.5*f+", "+u,this.svg.path(a,h).transform({translate:[-f/4,p*r-1.5*u]}),this.svg.path(a,h).transform({translate:[-f/4,p*r-1.5*u+36*p]})},H.prototype.geoChevrons=function(){var t,r,s,a,l,c,f,u=i(e(this.hash,0),0,15,30,80),p=i(e(this.hash,0),0,15,30,80),g=h(u,p);for(this.svg.setWidth(6*u),this.svg.setHeight(6*p*.66),r=0,f=0;6>f;f++)for(c=0;6>c;c++)l=e(this.hash,r),s=n(l),t=o(l),a={stroke:S,"stroke-opacity":A,fill:t,"fill-opacity":s,"stroke-width":1},this.svg.group(a).transform({translate:[c*u,f*p*.66-p/2]}).polyline(g).end(),0===f&&this.svg.group(a).transform({translate:[c*u,6*p*.66-p/2]}).polyline(g).end(),r+=1},H.prototype.geoPlusSigns=function(){var t,r,s,a,h,c,f,u,p=i(e(this.hash,0),0,15,10,25),g=3*p,v=l(p);for(this.svg.setWidth(12*p),this.svg.setHeight(12*p),s=0,u=0;6>u;u++)for(f=0;6>f;f++)c=e(this.hash,s),a=n(c),r=o(c),t=u%2===0?0:1,h={fill:r,stroke:S,"stroke-opacity":A,"fill-opacity":a},this.svg.group(h).transform({translate:[f*g-f*p+t*p-p,u*g-u*p-g/2]}).rect(v).end(),0===f&&this.svg.group(h).transform({translate:[4*g-f*p+t*p-p,u*g-u*p-g/2]}).rect(v).end(),0===u&&this.svg.group(h).transform({translate:[f*g-f*p+t*p-p,4*g-u*p-g/2]}).rect(v).end(),0===f&&0===u&&this.svg.group(h).transform({translate:[4*g-f*p+t*p-p,4*g-u*p-g/2]}).rect(v).end(),s++},H.prototype.geoXes=function(){var t,r,s,a,h,c,f,u,p=i(e(this.hash,0),0,15,10,25),g=l(p),v=3*p*.943;for(this.svg.setWidth(3*v),this.svg.setHeight(3*v),s=0,u=0;6>u;u++)for(f=0;6>f;f++)c=e(this.hash,s),a=n(c),t=f%2===0?u*v-.5*v:u*v-.5*v+v/4,r=o(c),h={fill:r,opacity:a},this.svg.group(h).transform({translate:[f*v/2-v/2,t-u*v/2],rotate:[45,v/2,v/2]}).rect(g).end(),0===f&&this.svg.group(h).transform({translate:[6*v/2-v/2,t-u*v/2],rotate:[45,v/2,v/2]}).rect(g).end(),0===u&&(t=f%2===0?6*v-v/2:6*v-v/2+v/4,this.svg.group(h).transform({translate:[f*v/2-v/2,t-6*v/2],rotate:[45,v/2,v/2]}).rect(g).end()),5===u&&this.svg.group(h).transform({translate:[f*v/2-v/2,t-11*v/2],rotate:[45,v/2,v/2]}).rect(g).end(),0===f&&0===u&&this.svg.group(h).transform({translate:[6*v/2-v/2,t-6*v/2],rotate:[45,v/2,v/2]}).rect(g).end(),s++},H.prototype.geoOverlappingCircles=function(){var t,r,s,a,h,l,c,f=e(this.hash,0),u=i(f,0,15,25,200),p=u/2;for(this.svg.setWidth(6*p),this.svg.setHeight(6*p),r=0,c=0;6>c;c++)for(l=0;6>l;l++)h=e(this.hash,r),s=n(h),t=o(h),a={fill:t,opacity:s},this.svg.circle(l*p,c*p,p,a),0===l&&this.svg.circle(6*p,c*p,p,a),0===c&&this.svg.circle(l*p,6*p,p,a),0===l&&0===c&&this.svg.circle(6*p,6*p,p,a),r++},H.prototype.geoOctogons=function(){var t,r,s,a,h,l,f=i(e(this.hash,0),0,15,10,60),u=c(f);for(this.svg.setWidth(6*f),this.svg.setHeight(6*f),r=0,l=0;6>l;l++)for(h=0;6>h;h++)a=e(this.hash,r),s=n(a),t=o(a),this.svg.polyline(u,{fill:t,"fill-opacity":s,stroke:S,"stroke-opacity":A}).transform({translate:[h*f,l*f]}),r+=1},H.prototype.geoSquares=function(){var t,r,s,a,h,l,c=i(e(this.hash,0),0,15,10,60);for(this.svg.setWidth(6*c),this.svg.setHeight(6*c),r=0,l=0;6>l;l++)for(h=0;6>h;h++)a=e(this.hash,r),s=n(a),t=o(a),this.svg.rect(h*c,l*c,c,c,{fill:t,"fill-opacity":s,stroke:S,"stroke-opacity":A}),r+=1},H.prototype.geoConcentricCircles=function(){var t,r,s,a,h,l,c=e(this.hash,0),f=i(c,0,15,10,60),u=f/5;for(this.svg.setWidth(6*(f+u)),this.svg.setHeight(6*(f+u)),r=0,l=0;6>l;l++)for(h=0;6>h;h++)a=e(this.hash,r),s=n(a),t=o(a),this.svg.circle(h*f+h*u+(f+u)/2,l*f+l*u+(f+u)/2,f/2,{fill:"none",stroke:t,opacity:s,"stroke-width":u+"px"}),a=e(this.hash,39-r),s=n(a),t=o(a),this.svg.circle(h*f+h*u+(f+u)/2,l*f+l*u+(f+u)/2,f/4,{fill:t,"fill-opacity":s}),r+=1},H.prototype.geoOverlappingRings=function(){var t,r,s,a,h,l,c,f=e(this.hash,0),u=i(f,0,15,10,60),p=u/4;for(this.svg.setWidth(6*u),this.svg.setHeight(6*u),r=0,c=0;6>c;c++)for(l=0;6>l;l++)h=e(this.hash,r),s=n(h),t=o(h),a={fill:"none",stroke:t,opacity:s,"stroke-width":p+"px"},this.svg.circle(l*u,c*u,u-p/2,a),0===l&&this.svg.circle(6*u,c*u,u-p/2,a),0===c&&this.svg.circle(l*u,6*u,u-p/2,a),0===l&&0===c&&this.svg.circle(6*u,6*u,u-p/2,a),r+=1},H.prototype.geoTriangles=function(){var t,r,s,a,h,l,c,u,p=e(this.hash,0),g=i(p,0,15,15,80),v=g/2*Math.sqrt(3),y=f(g,v);for(this.svg.setWidth(3*g),this.svg.setHeight(6*v),r=0,u=0;6>u;u++)for(c=0;6>c;c++)l=e(this.hash,r),s=n(l),t=o(l),h={fill:t,"fill-opacity":s,stroke:S,"stroke-opacity":A},a=u%2===0?c%2===0?180:0:c%2!==0?180:0,this.svg.polyline(y,h).transform({translate:[c*g*.5-g/2,v*u],rotate:[a,g/2,v/2]}),0===c&&this.svg.polyline(y,h).transform({translate:[6*g*.5-g/2,v*u],rotate:[a,g/2,v/2]}),r+=1},H.prototype.geoDiamonds=function(){var t,r,s,a,h,l,c,f,p=i(e(this.hash,0),0,15,10,50),g=i(e(this.hash,1),0,15,10,50),v=u(p,g);for(this.svg.setWidth(6*p),this.svg.setHeight(3*g),s=0,f=0;6>f;f++)for(c=0;6>c;c++)l=e(this.hash,s),a=n(l),r=o(l),h={fill:r,"fill-opacity":a,stroke:S,"stroke-opacity":A},t=f%2===0?0:p/2,this.svg.polyline(v,h).transform({translate:[c*p-p/2+t,g/2*f-g/2]}),0===c&&this.svg.polyline(v,h).transform({translate:[6*p-p/2+t,g/2*f-g/2]}),0===f&&this.svg.polyline(v,h).transform({translate:[c*p-p/2+t,g/2*6-g/2]}),0===c&&0===f&&this.svg.polyline(v,h).transform({translate:[6*p-p/2+t,g/2*6-g/2]}),s+=1},H.prototype.geoNestedSquares=function(){var t,r,s,a,h,l,c,f=i(e(this.hash,0),0,15,4,12),u=7*f;for(this.svg.setWidth(6*(u+f)+6*f),this.svg.setHeight(6*(u+f)+6*f),r=0,c=0;6>c;c++)for(l=0;6>l;l++)h=e(this.hash,r),s=n(h),t=o(h),a={fill:"none",stroke:t,opacity:s,"stroke-width":f+"px"},this.svg.rect(l*u+l*f*2+f/2,c*u+c*f*2+f/2,u,u,a),h=e(this.hash,39-r),s=n(h),t=o(h),a={fill:"none",stroke:t,opacity:s,"stroke-width":f+"px"},this.svg.rect(l*u+l*f*2+f/2+2*f,c*u+c*f*2+f/2+2*f,3*f,3*f,a),r+=1},H.prototype.geoMosaicSquares=function(){var t,r,s,o=i(e(this.hash,0),0,15,15,50);for(this.svg.setWidth(8*o),this.svg.setHeight(8*o),t=0,s=0;4>s;s++)for(r=0;4>r;r++)r%2===0?s%2===0?v(this.svg,r*o*2,s*o*2,o,e(this.hash,t)):g(this.svg,r*o*2,s*o*2,o,[e(this.hash,t),e(this.hash,t+1)]):s%2===0?g(this.svg,r*o*2,s*o*2,o,[e(this.hash,t),e(this.hash,t+1)]):v(this.svg,r*o*2,s*o*2,o,e(this.hash,t)),t+=1},H.prototype.geoPlaid=function(){var t,r,s,i,a,h,l,c=0,f=0;for(r=0;36>r;)i=e(this.hash,r),c+=i+5,l=e(this.hash,r+1),s=n(l),t=o(l),a=l+5,this.svg.rect(0,c,"100%",a,{opacity:s,fill:t}),c+=a,r+=2;for(r=0;36>r;)i=e(this.hash,r),f+=i+5,l=e(this.hash,r+1),s=n(l),t=o(l),h=l+5,this.svg.rect(f,0,h,"100%",{opacity:s,fill:t}),f+=h,r+=2;this.svg.setWidth(f),this.svg.setHeight(c)},H.prototype.geoTessellation=function(){var t,r,s,a,h,l=i(e(this.hash,0),0,15,5,40),c=l*Math.sqrt(3),f=2*l,u=l/2*Math.sqrt(3),p=y(l,u),g=3*l+2*u,v=2*c+2*l;for(this.svg.setWidth(g),this.svg.setHeight(v),r=0;20>r;r++)switch(h=e(this.hash,r),s=n(h),t=o(h),a={stroke:S,"stroke-opacity":A,fill:t,"fill-opacity":s,"stroke-width":1},r){case 0:this.svg.rect(-l/2,-l/2,l,l,a),this.svg.rect(g-l/2,-l/2,l,l,a),this.svg.rect(-l/2,v-l/2,l,l,a),this.svg.rect(g-l/2,v-l/2,l,l,a);break;case 1:this.svg.rect(f/2+u,c/2,l,l,a);break;case 2:this.svg.rect(-l/2,v/2-l/2,l,l,a),this.svg.rect(g-l/2,v/2-l/2,l,l,a);break;case 3:this.svg.rect(f/2+u,1.5*c+l,l,l,a);break;case 4:this.svg.polyline(p,a).transform({translate:[l/2,-l/2],rotate:[0,l/2,u/2]}),this.svg.polyline(p,a).transform({translate:[l/2,v- -l/2],rotate:[0,l/2,u/2],scale:[1,-1]});break;case 5:this.svg.polyline(p,a).transform({translate:[g-l/2,-l/2],rotate:[0,l/2,u/2],scale:[-1,1]}),this.svg.polyline(p,a).transform({translate:[g-l/2,v+l/2],rotate:[0,l/2,u/2],scale:[-1,-1]});break;case 6:this.svg.polyline(p,a).transform({translate:[g/2+l/2,c/2]});break;case 7:this.svg.polyline(p,a).transform({translate:[g-g/2-l/2,c/2],scale:[-1,1]});break;case 8:this.svg.polyline(p,a).transform({translate:[g/2+l/2,v-c/2],scale:[1,-1]});break;case 9:this.svg.polyline(p,a).transform({translate:[g-g/2-l/2,v-c/2],scale:[-1,-1]});break;case 10:this.svg.polyline(p,a).transform({translate:[l/2,v/2-l/2]});break;case 11:this.svg.polyline(p,a).transform({translate:[g-l/2,v/2-l/2],scale:[-1,1]});break;case 12:this.svg.rect(0,0,l,l,a).transform({translate:[l/2,l/2],rotate:[-30,0,0]});break;case 13:this.svg.rect(0,0,l,l,a).transform({scale:[-1,1],translate:[-g+l/2,l/2],rotate:[-30,0,0]});break;case 14:this.svg.rect(0,0,l,l,a).transform({translate:[l/2,v/2-l/2-l],rotate:[30,0,l]});break;case 15:this.svg.rect(0,0,l,l,a).transform({scale:[-1,1],translate:[-g+l/2,v/2-l/2-l],rotate:[30,0,l]});break;case 16:this.svg.rect(0,0,l,l,a).transform({scale:[1,-1],translate:[l/2,-v+v/2-l/2-l],rotate:[30,0,l]});break;case 17:this.svg.rect(0,0,l,l,a).transform({scale:[-1,-1],translate:[-g+l/2,-v+v/2-l/2-l],rotate:[30,0,l]});break;case 18:this.svg.rect(0,0,l,l,a).transform({scale:[1,-1],translate:[l/2,-v+l/2],rotate:[-30,0,0]});break;case 19:this.svg.rect(0,0,l,l,a).transform({scale:[-1,-1],translate:[-g+l/2,-v+l/2],rotate:[-30,0,0]})}}}).call(this,t("buffer").Buffer)},{"./color":2,"./sha1":4,"./svg":5,buffer:7,extend:8}],4:[function(t,r){"use strict";function s(){function t(){for(var t=16;80>t;t++){var r=f[t-3]^f[t-8]^f[t-14]^f[t-16];f[t]=r<<1|r>>>31}var s,e,i=n,o=a,p=h,g=l,v=c;for(t=0;80>t;t++){20>t?(s=g^o&(p^g),e=1518500249):40>t?(s=o^p^g,e=1859775393):60>t?(s=o&p|g&(o|p),e=2400959708):(s=o^p^g,e=3395469782);var y=(i<<5|i>>>27)+s+v+e+(0|f[t]);v=g,g=p,p=o<<30|o>>>2,o=i,i=y}for(n=n+i|0,a=a+o|0,h=h+p|0,l=l+g|0,c=c+v|0,u=0,t=0;16>t;t++)f[t]=0}function r(r){f[u]|=(255&r)<<p,p?p-=8:(u++,p=24),16===u&&t()}function s(t){var s=t.length;g+=8*s;for(var e=0;s>e;e++)r(t.charCodeAt(e))}function e(t){if("string"==typeof t)return s(t);var e=t.length;g+=8*e;for(var i=0;e>i;i++)r(t[i])}function i(t){for(var r="",s=28;s>=0;s-=4)r+=(t>>s&15).toString(16);return r}function o(){r(128),(u>14||14===u&&24>p)&&t(),u=14,p=24,r(0),r(0),r(g>0xffffffffff?g/1099511627776:0),r(g>4294967295?g/4294967296:0);for(var s=24;s>=0;s-=8)r(g>>s);return i(n)+i(a)+i(h)+i(l)+i(c)}var n=1732584193,a=4023233417,h=2562383102,l=271733878,c=3285377520,f=new Uint32Array(80),u=0,p=24,g=0;return{update:e,digest:o}}r.exports=function(t){if(void 0===t)return s();var r=s();return r.update(t),r.digest()}},{}],5:[function(t,r){"use strict";function s(){return this.width=100,this.height=100,this.svg=i("svg"),this.context=[],this.setAttributes(this.svg,{xmlns:"http://www.w3.org/2000/svg",width:this.width,height:this.height}),this}var e=t("extend"),i=t("./xml");r.exports=s,s.prototype.currentContext=function(){return this.context[this.context.length-1]||this.svg},s.prototype.end=function(){return this.context.pop(),this},s.prototype.currentNode=function(){var t=this.currentContext();return t.lastChild||t},s.prototype.transform=function(t){return this.currentNode().setAttribute("transform",Object.keys(t).map(function(r){return r+"("+t[r].join(",")+")"}).join(" ")),this},s.prototype.setAttributes=function(t,r){Object.keys(r).forEach(function(s){t.setAttribute(s,r[s])})},s.prototype.setWidth=function(t){this.svg.setAttribute("width",Math.floor(t))},s.prototype.setHeight=function(t){this.svg.setAttribute("height",Math.floor(t))},s.prototype.toString=function(){return this.svg.toString()},s.prototype.rect=function(t,r,s,o,n){var a=this;if(Array.isArray(t))return t.forEach(function(t){a.rect.apply(a,t.concat(n))}),this;var h=i("rect");return this.currentContext().appendChild(h),this.setAttributes(h,e({x:t,y:r,width:s,height:o},n)),this},s.prototype.circle=function(t,r,s,o){var n=i("circle");return this.currentContext().appendChild(n),this.setAttributes(n,e({cx:t,cy:r,r:s},o)),this},s.prototype.path=function(t,r){var s=i("path");return this.currentContext().appendChild(s),this.setAttributes(s,e({d:t},r)),this},s.prototype.polyline=function(t,r){var s=this;if(Array.isArray(t))return t.forEach(function(t){s.polyline(t,r)}),this;var o=i("polyline");return this.currentContext().appendChild(o),this.setAttributes(o,e({points:t},r)),this},s.prototype.group=function(t){var r=i("g");return this.currentContext().appendChild(r),this.context.push(r),this.setAttributes(r,e({},t)),this}},{"./xml":6,extend:8}],6:[function(t,r){"use strict";var s=r.exports=function(t){return this instanceof s?(this.tagName=t,this.attributes=Object.create(null),this.children=[],this.lastChild=null,this):new s(t)};s.prototype.appendChild=function(t){return this.children.push(t),this.lastChild=t,this},s.prototype.setAttribute=function(t,r){return this.attributes[t]=r,this},s.prototype.toString=function(){var t=this;return["<",t.tagName,Object.keys(t.attributes).map(function(r){return[" ",r,'="',t.attributes[r],'"'].join("")}).join(""),">",t.children.map(function(t){return t.toString()}).join(""),"</",t.tagName,">"].join("")}},{}],7:[function(){},{}],8:[function(t,r){function s(t){if(!t||"[object Object]"!==i.call(t)||t.nodeType||t.setInterval)return!1;var r=e.call(t,"constructor"),s=e.call(t.constructor.prototype,"isPrototypeOf");if(t.constructor&&!r&&!s)return!1;var o;for(o in t);return void 0===o||e.call(t,o)}var e=Object.prototype.hasOwnProperty,i=Object.prototype.toString;r.exports=function o(){var t,r,e,i,n,a,h=arguments[0]||{},l=1,c=arguments.length,f=!1;for("boolean"==typeof h&&(f=h,h=arguments[1]||{},l=2),"object"!=typeof h&&"function"!=typeof h&&(h={});c>l;l++)if(null!=(t=arguments[l]))for(r in t)e=h[r],i=t[r],h!==i&&(f&&i&&(s(i)||(n=Array.isArray(i)))?(n?(n=!1,a=e&&Array.isArray(e)?e:[]):a=e&&s(e)?e:{},h[r]=o(f,a,i)):void 0!==i&&(h[r]=i));return h}},{}]},{},[1])(1)});
(function(f){f.fn.noisy=function(a){function m(a){return(a=/^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(a))?{r:parseInt(a[1],16),g:parseInt(a[2],16),b:parseInt(a[3],16)}:null}a=f.extend({},f.fn.noisy.defaults,a);"undefined"!==typeof a.color&&(a.randomColors=!1);var g,k,b=!1;try{k=!0,localStorage.setItem("test",""),localStorage.removeItem("test"),b=localStorage.getItem(window.JSON.stringify(a))}catch(q){k=!1}if(b&&!a.disableCache)g=b;else{b=document.createElement("canvas");if(b.getContext){b.width=
b.height=a.size;for(var l=b.getContext("2d"),d=l.createImageData(b.width,b.height),h=Math.round(a.intensity*Math.pow(a.size,2)),n=255*a.opacity;h--;){var e=~~(Math.random()*b.width),c=~~(Math.random()*b.height),e=4*(e+c*d.width);a.randomColors?(c=h%255,a.colorChannels===parseInt(a.colorChannels)?c=h%a.colorChannels:f.isArray(a.colorChannels)&&(c=a.colorChannels[0]+h%(a.colorChannels[1]-a.colorChannels[0])),d.data[e]=c,d.data[e+1]=a.monochrome?c:~~(255*Math.random()),d.data[e+2]=a.monochrome?c:~~(255*
Math.random())):(c=m(a.color),d.data[e]=c.r,d.data[e+1]=c.g,d.data[e+2]=c.b);d.data[e+3]=~~(Math.random()*n)}l.putImageData(d,0,0);g=b.toDataURL("image/png");0!=g.indexOf("data:image/png")&&(g=a.fallback)}else g=a.fallback;if(window.JSON&&k&&!a.disableCache)try{localStorage.setItem(window.JSON.stringify(a),g)}catch(p){console.warn(p.message)}}return this.each(function(){f(this).css("background-image","url('"+g+"'),"+f(this).css("background-image"))})};f.fn.noisy.defaults={intensity:0.9,size:200,opacity:0.08,
fallback:"",monochrome:!1,colorChannels:255,randomColors:!0,disableCache:!1}})(jQuery);

/*!
 * iCheck v2.0.0, http://git.io/arlzeA
 * ===================================
 * Cross-platform checkboxes and radio buttons customization
 *
 * (c) Damir Sultanov - http://fronteed.com
 * MIT Licensed
 */

(function(win, doc, $) {

  // prevent multiple includes
  if (!win.ichecked) {
    win.ichecked = function() {
      $ = win.jQuery || win.Zepto;

      // default options
      var defaults = {

        // auto init on domready
        autoInit: true,

        // auto handle ajax loaded inputs
        autoAjax: true,

        // remove 300ms click delay on touch devices
        tap: true,

        // customization class names
        checkboxClass: 'icheckbox',
        radioClass: 'iradio',

        checkedClass: 'checked',
        disabledClass: 'disabled',
        indeterminateClass: 'indeterminate',

        hoverClass: 'hover',
        // focusClass: 'focus',
        // activeClass: 'active',

        // default callbacks
        callbacks: {
          ifCreated: false
        },

        // appended class names
        classes: {
          base: 'icheck',
          div: '#-item', // {base}-item
          area: '#-area-', // {base}-area-{value}
          input: '#-input', // {base}-input
          label: '#-label' // {base}-label
        }
      };

      // extend default options
      win.icheck = $.extend(defaults, win.icheck);

      // useragent sniffing
      var ua = win.navigator.userAgent;
      var ie = /MSIE [5-8]/.test(ua) || doc.documentMode < 9;
      var operaMini = /Opera Mini/.test(ua);

      // classes cache
      var baseClass = defaults.classes.base;
      var divClass = defaults.classes.div.replace('#', baseClass);
      var areaClass = defaults.classes.area.replace('#', baseClass);
      var nodeClass = defaults.classes.input.replace('#', baseClass);
      var labelClass = defaults.classes.label.replace('#', baseClass);

      // unset init classes
      delete defaults.classes;

      // default filter
      var filter = 'input[type=checkbox],input[type=radio]';

      // clickable areas container
      var areas = {};

      // hashes container
      var hashes = {};

      // hash recognizer
      var recognizer = new RegExp(baseClass + '\\[(.*?)\\]');

      // hash extractor
      var extract = function(className, matches, value) {
        if (!!className) {
          matches = recognizer.exec(className);

          if (matches && hashes[matches[1]]) {
            value = matches[1];
          }
        }

        return value;
      };

      // detect computed style support
      var computed = win.getComputedStyle;

      // detect pointer events support
      var isPointer = win.PointerEvent || win.MSPointerEvent;

      // detect touch events support
      var isTouch = 'ontouchend' in win;

      // detect mobile users
      var isMobile = /mobile|tablet|phone|ip(ad|od)|android|silk|webos/i.test(ua);

      // setup events
      var mouse = ['mouse', 'down', 'up', 'over', 'out']; // bubbling hover
      var pointer = win.PointerEvent ? ['pointer', mouse[1], mouse[2], mouse[3], mouse[4]] : ['MSPointer', 'Down', 'Up', 'Over', 'Out'];
      var touch = ['touch', 'start', 'end'];
      var noMouse = (isTouch && isMobile) || isPointer;

      // choose events
      var hoverStart = noMouse ? (isTouch ? touch[0] + touch[1] : pointer[0] + pointer[3]) : mouse[0] + mouse[3];
      var hoverEnd = noMouse ? (isTouch ? touch[0] + touch[2] : pointer[0] + pointer[4]) : mouse[0] + mouse[4];
      var tapStart = noMouse ? (isTouch ? false : pointer[0] + pointer[1]) : mouse[0] + mouse[1];
      var tapEnd = noMouse ? (isTouch ? false : pointer[0] + pointer[2]) : mouse[0] + mouse[2];
      var hover = !operaMini ? hoverStart + '.i ' + hoverEnd + '.i ' : '';
      var tap = !operaMini && tapStart ? tapStart + '.i ' + tapEnd + '.i' : '';

      // styles options
      var styleTag;
      var styleList;
      var styleArea = defaults.areaStyle !== false ? 'position:absolute;display:block;content:"";top:#;bottom:#;left:#;right:#;' : 0;
      var styleInput = 'position:absolute!;display:block!;outline:none!;' + (defaults.debug ? '' : 'opacity:0!;z-index:-99!;clip:rect(0 0 0 0)!;');

      // styles addition
      var style = function(rules, selector, area) {
        if (!styleTag) {

          // create container
          styleTag = doc.createElement('style');

          // append to header
          (doc.head || doc.getElementsByTagName('head')[0]).appendChild(styleTag);

          // webkit hack
          if (!win.createPopup) {
            styleTag.appendChild(doc.createTextNode(''));
          }

          styleList = styleTag.sheet || styleTag.styleSheet;
        }

        // choose selector
        if (!selector) {
          selector = 'div.' + (area ? areaClass + area + ':after' : divClass + ' input.' + nodeClass);
        }

        // replace shorthand rules
        rules = rules.replace(/!/g, ' !important');

        // append styles
        if (styleList.addRule) {
          styleList.addRule(selector, rules, 0);
        } else {
          styleList.insertRule(selector + '{' + rules + '}', 0);
        }
      };

      // append input's styles
      style(styleInput);

      // append styler's styles
      if ((isTouch && isMobile) || operaMini) {

        // force custor:pointer for mobile devices
        style('cursor:pointer!;', 'label.' + labelClass + ',div.' + divClass);
      }

      // append iframe's styles
      style('display:none!', 'iframe.icheck-frame'); // used to handle ajax-loaded inputs

      // class toggler
      var toggle = function(node, className, status, currentClass, updatedClass, addClass, removeClass) {
        currentClass = node.className;

        if (!!currentClass) {
          updatedClass = ' ' + currentClass + ' ';

          // add class
          if (status === 1) {
            addClass = className;

          // remove class
          } else if (status === 0) {
            removeClass = className;

          // add and remove class
          } else {
            addClass = className[0];
            removeClass = className[1];
          }

          // add class
          if (!!addClass && updatedClass.indexOf(' ' + addClass + ' ') < 0) {
            updatedClass += addClass + ' ';
          }

          // remove class
          if (!!removeClass && ~updatedClass.indexOf(' ' + removeClass + ' ')) {
            updatedClass = updatedClass.replace(' ' + removeClass + ' ', ' ');
          }

          // trim class
          updatedClass = updatedClass.replace(/^\s+|\s+$/g, '');

          // update class
          if (updatedClass !== currentClass) {
            node.className = updatedClass;
          }

          // return updated class
          return updatedClass;
        }
      };

      // traces remover
      var tidy = function(node, key, trigger, settings, className, parent) {
        if (hashes[key]) {
          settings = hashes[key];
          className = settings.className;
          parent = $(closest(node, 'div', className));

          // prevent overlapping
          if (parent.length) {

            // input
            $(node).removeClass(nodeClass + ' ' + className).attr('style', settings.style);

            // label
            $('label.' + settings.esc).removeClass(labelClass + ' ' + className);

            // parent
            $(parent).replaceWith($(node));

            // callback
            if (trigger) {
              callback(node, key, trigger);
            }
          }

          // unset current key
          hashes[key] = false;
        }
      };

      // nodes inspector
      var inspect = function(object, node, stack, direct, indirect) {
        stack = [];
        direct = object.length;

        // inspect object
        while (direct--) {
          node = object[direct];

          // direct input
          if (node.type) {

            // checkbox or radio button
            if (~filter.indexOf(node.type)) {
              stack.push(node);
            }

          // indirect input
          } else {
            node = $(node).find(filter);
            indirect = node.length;

            while (indirect--) {
              stack.push(node[indirect]);
            }
          }
        }

        return stack;
      };

      // parent searcher
      var closest = function(node, tag, className, parent) {
        while (node && node.nodeType !== 9) {
          node = node.parentNode;

          if (node && node.tagName == tag.toUpperCase() && ~node.className.indexOf(className)) {
            parent = node;
            break;
          }
        }

        return parent;
      };

      // callbacks farm
      var callback = function(node, key, name) {
        name = 'if' + name;

        // callbacks are allowed
        if (hashes[key].callbacks !== false) {

          // direct callback
          if (typeof hashes[key].callbacks[name] == 'function') {
            hashes[key].callbacks[name](node, hashes[key]);
          }

          // indirect callback
          if (hashes[key].callbacks[name] !== false) {
            $(node).trigger(name);
          }
        }
      };

      // selection processor
      var process = function(data, options, ajax, silent) {

        // get inputs
        var elements = inspect(data);
        var element = elements.length;

        // loop through inputs
        while (element--) {
          var node = elements[element];
          var nodeAttr = node.attributes;
          var nodeAttrCache = {};
          var nodeAttrLength = nodeAttr.length;
          var nodeAttrName;
          var nodeAttrValue;
          var nodeData = {};
          var nodeDataCache = {}; // merged data
          var nodeDataProperty;
          var nodeId = node.id;
          var nodeInherit;
          var nodeInheritItem;
          var nodeInheritLength;
          var nodeString = node.className;
          var nodeStyle;
          var nodeType = node.type;
          var queryData = $.cache ? $.cache[node[$.expando]] : 0; // cached data
          var settings;
          var key = extract(nodeString);
          var keyClass;
          var handle;
          var styler;
          var stylerClass = '';
          var stylerStyle;
          var area = false;
          var label;
          var labelDirect;
          var labelIndirect;
          var labelKey;
          var labelString;
          var labels = [];
          var labelsLength;
          var fastClass = win.FastClick ? ' needsclick' : '';

          // parse options from HTML attributes
          while (nodeAttrLength--) {
            nodeAttrName = nodeAttr[nodeAttrLength].name;
            nodeAttrValue = nodeAttr[nodeAttrLength].value;

            if (~nodeAttrName.indexOf('data-')) {
              nodeData[nodeAttrName.substr(5)] = nodeAttrValue;
            }

            if (nodeAttrName == 'style') {
              nodeStyle = nodeAttrValue;
            }

            nodeAttrCache[nodeAttrName] = nodeAttrValue;
          }

          // parse options from jQuery or Zepto cache
          if (queryData && queryData.data) {
            nodeData = $.extend(nodeData, queryData.data);
          }

          // parse merged options
          for (nodeDataProperty in nodeData) {
            nodeAttrValue = nodeData[nodeDataProperty];

            if (nodeAttrValue == 'true' || nodeAttrValue == 'false') {
              nodeAttrValue = nodeAttrValue == 'true';
            }

            nodeDataCache[nodeDataProperty.replace(/checkbox|radio|class|id|label/g, function(string, position) {
              return position === 0 ? string : string.charAt(0).toUpperCase() + string.slice(1);
            })] = nodeAttrValue;
          }

          // merge options
          settings = $.extend({}, defaults, win.icheck, nodeDataCache, options);

          // input type filter
          handle = settings.handle;

          if (handle !== 'checkbox' && handle !== 'radio') {
            handle = filter;
          }

          // prevent unwanted init
          if (settings.init !== false && ~handle.indexOf(nodeType)) {

            // tidy before processing
            if (key) {
              tidy(node, key);
            }

            // generate random key
            while(!hashes[key]) {
              key = Math.random().toString(36).substr(2, 5); // 5 symbols

              if (!hashes[key]) {
                keyClass = baseClass + '[' + key + ']';
                break;
              }
            }

            // prevent unwanted duplicates
            delete settings.autoInit;
            delete settings.autoAjax;

            // save settings
            settings.style = nodeStyle || '';
            settings.className = keyClass;
            settings.esc = keyClass.replace(/(\[|\])/g, '\\$1');
            hashes[key] = settings;

            // find direct label
            labelDirect = closest(node, 'label', '');

            if (labelDirect) {

              // normalize "for" attribute
              if (!!!labelDirect.htmlFor && !!nodeId) {
                labelDirect.htmlFor = nodeId;
              }

              labels.push(labelDirect);
            }

            // find indirect label
            if (!!nodeId) {
              labelIndirect = $('label[for="' + nodeId + '"]');

              // merge labels
              while (labelIndirect.length--) {
                label = labelIndirect[labelIndirect.length];

                if (label !== labelDirect) {
                  labels.push(label);
                }
              }
            }

            // loop through labels
            labelsLength = labels.length;

            while (labelsLength--) {
              label = labels[labelsLength];
              labelString = label.className;
              labelKey = extract(labelString);

              // remove previous key
              if (labelKey) {
                labelString = toggle(label, baseClass + '[' + labelKey + ']', 0);
              } else {
                labelString = (!!labelString ? labelString + ' ' : '') + labelClass;
              }

              // update label's class
              label.className = labelString + ' ' + keyClass + fastClass;
            }

            // prepare styler
            styler = doc.createElement('div');

            // parse inherited options
            if (!!settings.inherit) {
              nodeInherit = settings.inherit.split(/\s*,\s*/);
              nodeInheritLength = nodeInherit.length;

              while (nodeInheritLength--) {
                nodeInheritItem = nodeInherit[nodeInheritLength];

                if (nodeAttrCache[nodeInheritItem] !== undefined) {
                  if (nodeInheritItem == 'class') {
                    stylerClass += nodeAttrCache[nodeInheritItem] + ' ';
                  } else {
                    styler.setAttribute(nodeInheritItem, nodeInheritItem == 'id' ? baseClass + '-' + nodeAttrCache[nodeInheritItem] : nodeAttrCache[nodeInheritItem]);
                  }
                }
              }
            }

            // set input's type class
            stylerClass += settings[nodeType + 'Class'];

            // set styler's key
            stylerClass += ' ' + divClass + ' ' + keyClass;

            // append area styles
            if (settings.area && styleArea) {
              area = ('' + settings.area).replace(/%|px|em|\+|-/g, '') | 0;

              if (area) {

                // append area's styles
                if (!areas[area]) {
                  style(styleArea.replace(/#/g, '-' + area + '%'), false, area);
                  areas[area] = true;
                }

                stylerClass += ' ' + areaClass + area;
              }
            }

            // update styler's class
            styler.className = stylerClass + fastClass;

            // update node's class
            node.className = (!!nodeString ? nodeString + ' ' : '') + nodeClass + ' ' + keyClass;

            // replace node
            node.parentNode.replaceChild(styler, node);

            // append node
            styler.appendChild(node);

            // append additions
            if (!!settings.insert) {
              $(styler).append(settings.insert);
            }

            // set relative position
            if (area) {

              // get styler's position
              if (computed) {
                stylerStyle = computed(styler, null).getPropertyValue('position');
              } else {
                stylerStyle = styler.currentStyle.position;
              }

              // update styler's position
              if (stylerStyle == 'static') {
                styler.style.position = 'relative';
              }
            }

            // operate
            operate(node, styler, key, 'updated', true, false, ajax);
            hashes[key].done = true;

            // ifCreated callback
            if (!silent) {
              callback(node, key, 'Created');
            }
          }
        }
      };

      // operations center
      var operate = function(node, parent, key, method, silent, before, ajax) {
        var settings = hashes[key];
        var states = {};
        var changes = {};

        // current states
        states.checked = [node.checked, 'Checked', 'Unchecked'];

        if ((!before || ajax) && method !== 'click') {
          states.disabled = [node.disabled, 'Disabled', 'Enabled'];
          states.indeterminate = [node.getAttribute('indeterminate') == 'true' || !!node.indeterminate, 'Indeterminate', 'Determinate'];
        }

        // methods
        if (method == 'updated' || method == 'click') {
          changes.checked = before ? !states.checked[0] : states.checked[0];

          if ((!before || ajax) && method !== 'click') {
            changes.disabled = states.disabled[0];
            changes.indeterminate = states.indeterminate[0];
          }

        } else if (method == 'checked' || method == 'unchecked') {
          changes.checked = method == 'checked';

        } else if (method == 'disabled' || method == 'enabled') {
          changes.disabled = method == 'disabled';

        } else if (method == 'indeterminate' || method == 'determinate') {
          changes.indeterminate = method !== 'determinate';

        // "toggle" method
        } else {
          changes.checked = !states.checked[0];
        }

        // apply changes
        change(node, parent, states, changes, key, settings, method, silent, before, ajax);
      };

      // state changer
      var change = function(node, parent, states, changes, key, settings, method, silent, before, ajax, loop) {
        var type = node.type;
        var typeCapital = type == 'radio' ? 'Radio' : 'Checkbox';
        var property;
        var value;
        var classes;
        var inputClass;
        var label;
        var labelClass = 'LabelClass';
        var form;
        var radios;
        var radiosLength;
        var radio;
        var radioKey;
        var radioStates;
        var radioChanges;
        var changed;
        var toggled;

        // check parent
        if (!parent) {
          parent = closest(node, 'div', settings.className);
        }

        // continue if parent exists
        if (parent) {

          // detect changes
          for (property in changes) {
            value = changes[property];

            // update node's property
            if (states[property][0] !== value && method !== 'updated' && method !== 'click') {
              node[property] = value;
            }

            // update ajax attributes
            if (ajax) {
              if (value) {
                node.setAttribute(property, property);
              } else {
                node.removeAttribute(property);
              }
            }

            // update key's property
            if (settings[property] !== value) {
              settings[property] = value;
              changed = true;

              if (property == 'checked') {
                toggled = true;

                // find assigned radios
                if (!loop && value && (!!hashes[key].done || ajax) && type == 'radio' && !!node.name) {
                  form = closest(node, 'form', '');
                  radios = 'input[name="' + node.name + '"]';
                  radios = form && !ajax ? $(form).find(radios) : $(radios);
                  radiosLength = radios.length;

                  while (radiosLength--) {
                    radio = radios[radiosLength];
                    radioKey = extract(radio.className);

                    // toggle radios
                    if (node !== radio && hashes[radioKey] && hashes[radioKey].checked) {
                      radioStates = {checked: [true, 'Checked', 'Unchecked']};
                      radioChanges = {checked: false};

                      change(radio, false, radioStates, radioChanges, radioKey, hashes[radioKey], 'updated', silent, before, ajax, true);
                    }
                  }
                }
              }

              // cache classes
              classes = [
                settings[property + 'Class'], // 0, example: checkedClass
                settings[property + typeCapital + 'Class'], // 1, example: checkedCheckboxClass
                settings[states[property][1] + 'Class'], // 2, example: uncheckedClass
                settings[states[property][1] + typeCapital + 'Class'], // 3, example: uncheckedCheckboxClass
                settings[property + labelClass] // 4, example: checkedLabelClass
              ];

              // value == false
              inputClass = [classes[3] || classes[2], classes[1] || classes[0]];

              // value == true
              if (value) {
                inputClass.reverse();
              }

              // update parent's class
              toggle(parent, inputClass);

              // update labels's class
              if (!!settings.mirror && !!classes[4]) {
                label = $('label.' + settings.esc);

                while (label.length--) {
                  toggle(label[label.length], classes[4], value ? 1 : 0);
                }
              }

              // callback
              if (!silent || loop) {
                callback(node, key, states[property][value ? 1 : 2]); // ifChecked or ifUnchecked
              }
            }
          }

          // additional callbacks
          if (!silent || loop) {
            if (changed) {
              callback(node, key, 'Changed'); // ifChanged
            }

            if (toggled) {
              callback(node, key, 'Toggled'); // ifToggled
            }
          }

          // cursor addition
          if (!!settings.cursor && !isMobile) {

            // 'pointer' for enabled
            if (!settings.disabled && !settings.pointer) {
              parent.style.cursor = 'pointer';
              settings.pointer = true;

            // 'default' for disabled
            } else if (settings.disabled && settings.pointer) {
              parent.style.cursor = 'default';
              settings.pointer = false;
            }
          }

          // update settings
          hashes[key] = settings;
        }
      };

      // plugin definition
      $.fn.icheck = function(options, fire) {

        // detect methods
        if (/^(checked|unchecked|indeterminate|determinate|disabled|enabled|updated|toggle|destroy|data|styler)$/.test(options)) {
          var items = inspect(this);
          var itemsLength = items.length;

          // loop through inputs
          while (itemsLength--) {
            var item = items[itemsLength];
            var key = extract(item.className);

            if (key) {

              // 'data' method
              if (options == 'data') {
                return hashes[key];

              // 'styler' method
              } else if (options == 'styler') {
                return closest(item, 'div', hashes[key].className);

              } else {
                if (options == 'destroy') {
                  tidy(item, key, 'Destroyed');
                } else {
                  operate(item, false, key, options);
                }

                // callback
                if (typeof fire == 'function') {
                  fire(item);
                }
              }
            }
          }

        // basic setup
        } else if (typeof options == 'object' || !options) {
          process(this, options || {});
        }

        // chain
        return this;
      };

      // cached last key
      var lastKey;

      // bind label and styler
      $(doc).on('click.i ' + hover + tap, 'label.' + labelClass + ',div.' + divClass, function(event) {
        var self = this;
        var key = extract(self.className);

        if (key) {
          var emitter = event.type;
          var settings = hashes[key];
          var className = settings.esc; // escaped class name
          var div = self.tagName == 'DIV';
          var input;
          var target;
          var partner;
          var activate;
          var states = [
            ['label', settings.activeLabelClass, settings.hoverLabelClass],
            ['div', settings.activeClass, settings.hoverClass]
          ];

          // reverse array
          if (div) {
            states.reverse();
          }

          // active state
          if (emitter == tapStart || emitter == tapEnd) {

            // toggle self's active class
            if (!!states[0][1]) {
              toggle(self, states[0][1], emitter == tapStart ? 1 : 0);
            }

            // toggle partner's active class
            if (!!settings.mirror && !!states[1][1]) {
              partner = $(states[1][0] + '.' + className);

              while (partner.length--) {
                toggle(partner[partner.length], states[1][1], emitter == tapStart ? 1 : 0);
              }
            }

            // fast click
            if (div && emitter == tapEnd && !!settings.tap && isMobile && isPointer && !operaMini) {
              activate = true;
            }

          // hover state
          } else if (emitter == hoverStart || emitter == hoverEnd) {

            // toggle self's hover class
            if (!!states[0][2]) {
              toggle(self, states[0][2], emitter == hoverStart ? 1 : 0);
            }

            // toggle partner's hover class
            if (!!settings.mirror && !!states[1][2]) {
              partner = $(states[1][0] + '.' + className);

              while (partner.length--) {
                toggle(partner[partner.length], states[1][2], emitter == hoverStart ? 1 : 0);
              }
            }

            // fast click
            if (div && emitter == hoverEnd && !!settings.tap && isMobile && isTouch && !operaMini) {
              activate = true;
            }

          // click
          } else if (div) {
            if (!(isMobile && (isTouch || isPointer)) || !!!settings.tap || operaMini) {
              activate = true;
            }
          }

          // trigger input's click
          if (activate) {

            // currentTarget hack
            setTimeout(function() {
              target = event.currentTarget || {};

              if (target.tagName !== 'LABEL') {
                if (!settings.change || (+new Date() - settings.change > 100)) {
                  input = $(self).find('input.' + className).click();

                  if (ie || operaMini) {
                    input.change();
                  }
                }
              }
            }, 2);
          }
        }

      // bind input
      }).on('click.i change.i focusin.i focusout.i keyup.i keydown.i', 'input.' + nodeClass, function(event) {
        var self = this;
        var key = extract(self.className);

        if (key) {
          var emitter = event.type;
          var settings = hashes[key];
          var className = settings.esc; // escaped class name
          var parent = emitter == 'click' ? false : closest(self, 'div', settings.className);
          var label;
          var states;

          // click
          if (emitter == 'click') {
            hashes[key].change = +new Date();

            // prevent event bubbling to parent
            event.stopPropagation();

          // change
          } else if (emitter == 'change') {

            if (parent && !self.disabled) {
              operate(self, parent, key, 'click'); // 'click' method
            }

          // focus state
          } else if (~emitter.indexOf('focus')) {
            states = [settings.focusClass, settings.focusLabelClass];

            // toggle parent's focus class
            if (!!states[0] && parent) {
              toggle(parent, states[0], emitter == 'focusin' ? 1 : 0);
            }

            // toggle label's focus class
            if (!!settings.mirror && !!states[1]) {
              label = $('label.' + className);

              while (label.length--) {
                toggle(label[label.length], states[1], emitter == 'focusin' ? 1 : 0);
              }
            }

          // keyup or keydown (event fired before state is changed, except Opera 9-12)
          } else if (parent && !self.disabled) {

            // keyup
            if (emitter == 'keyup') {

              // spacebar or arrow
              if (self.type == 'checkbox' && event.keyCode == 32 && settings.keydown || self.type == 'radio' && !self.checked) {
                operate(self, parent, key, 'click', false, true); // 'toggle' method
              }

              hashes[key].keydown = hashes[lastKey].keydown = false;

            // keydown
            } else {
              lastKey = key;
              hashes[key].keydown = true;
            }
          }
        }

      // domready
      }).ready(function() {

        // auto init
        if (win.icheck.autoInit) {
          $('.' + baseClass).icheck();
        }

        // auto ajax
        if (win.jQuery) {

          // body selector cache
          var body = doc.body || doc.getElementsByTagName('body')[0];

          // apply converter
          $.ajaxSetup({
            converters: {
              'text html': function(data) {
                if (win.icheck.autoAjax && body) {
                  var frame = doc.createElement('iframe'); // create container
                  var frameData;

                  // set attributes
                  if (!ie) {
                    frame.style = 'display:none';
                  }

                  frame.className = 'iframe.icheck-frame';
                  frame.src ='about:blank';

                  // append container to document
                  body.appendChild(frame);

                  // save container's content
                  frameData = frame.contentDocument ? frame.contentDocument : frame.contentWindow.document;

                  // append data to content
                  frameData.open();
                  frameData.write(data);
                  frameData.close();

                  // remove container from document
                  body.removeChild(frame);

                  // setup object
                  frameData = $(frameData);

                  // customize inputs
                  process(frameData.find('.' + baseClass), {}, true);

                  // extract HTML
                  frameData = frameData[0];
                  data = (frameData.body || frameData.getElementsByTagName('body')[0]).innerHTML;
                  frameData = null; // prevent memory leaks
                }

                return data;
              }
            }
          });
        }
      });
    };

    // expose iCheck as an AMD module
    if (typeof define == 'function' && define.amd) {
      define('icheck', [win.jQuery ? 'jquery' : 'zepto'], win.ichecked);
    } else {
      win.ichecked();
    }
  }
}(window, document));

/*
 * ScrollToFixed
 * https://github.com/bigspotteddog/ScrollToFixed
 * 
 * Copyright (c) 2011 Joseph Cava-Lynch
 * MIT license
 */
(function($) {
    $.isScrollToFixed = function(el) {
        return !!$(el).data('ScrollToFixed');
    };

    $.ScrollToFixed = function(el, options) {
        // To avoid scope issues, use 'base' instead of 'this' to reference this
        // class from internal events and functions.
        var base = this;

        // Access to jQuery and DOM versions of element.
        base.$el = $(el);
        base.el = el;

        // Add a reverse reference to the DOM object.
        base.$el.data('ScrollToFixed', base);

        // A flag so we know if the scroll has been reset.
        var isReset = false;

        // The element that was given to us to fix if scrolled above the top of
        // the page.
        var target = base.$el;

        var position;
        var originalPosition;
        var originalOffsetTop;
        var originalZIndex;

        // The offset top of the element when resetScroll was called. This is
        // used to determine if we have scrolled past the top of the element.
        var offsetTop = 0;

        // The offset left of the element when resetScroll was called. This is
        // used to move the element left or right relative to the horizontal
        // scroll.
        var offsetLeft = 0;
        var originalOffsetLeft = -1;

        // This last offset used to move the element horizontally. This is used
        // to determine if we need to move the element because we would not want
        // to do that for no reason.
        var lastOffsetLeft = -1;

        // This is the element used to fill the void left by the target element
        // when it goes fixed; otherwise, everything below it moves up the page.
        var spacer = null;

        var spacerClass;

        var className;

        // Capture the original offsets for the target element. This needs to be
        // called whenever the page size changes or when the page is first
        // scrolled. For some reason, calling this before the page is first
        // scrolled causes the element to become fixed too late.
        function resetScroll() {
            // Set the element to it original positioning.
            target.trigger('preUnfixed.ScrollToFixed');
            setUnfixed();
            target.trigger('unfixed.ScrollToFixed');

            // Reset the last offset used to determine if the page has moved
            // horizontally.
            lastOffsetLeft = -1;

            // Capture the offset top of the target element.
            offsetTop = target.offset().top;

            // Capture the offset left of the target element.
            offsetLeft = target.offset().left;

            // If the offsets option is on, alter the left offset.
            if (base.options.offsets) {
                offsetLeft += (target.offset().left - target.position().left);
            }

            if (originalOffsetLeft == -1) {
                originalOffsetLeft = offsetLeft;
            }

            position = target.css('position');

            // Set that this has been called at least once.
            isReset = true;

            if (base.options.bottom != -1) {
                target.trigger('preFixed.ScrollToFixed');
                setFixed();
                target.trigger('fixed.ScrollToFixed');
            }
        }

        function getLimit() {
            var limit = base.options.limit;
            if (!limit) return 0;

            if (typeof(limit) === 'function') {
                return limit.apply(target);
            }
            return limit;
        }

        // Returns whether the target element is fixed or not.
        function isFixed() {
            return position === 'fixed';
        }

        // Returns whether the target element is absolute or not.
        function isAbsolute() {
            return position === 'absolute';
        }

        function isUnfixed() {
            return !(isFixed() || isAbsolute());
        }

        // Sets the target element to fixed. Also, sets the spacer to fill the
        // void left by the target element.
        function setFixed() {
            // Only fix the target element and the spacer if we need to.
            if (!isFixed()) {
                // Set the spacer to fill the height and width of the target
                // element, then display it.
                spacer.css({
                    'display' : target.css('display'),
                    'width' : target.outerWidth(true),
                    'height' : target.outerHeight(true),
                    'float' : target.css('float')
                });

                // Set the target element to fixed and set its width so it does
                // not fill the rest of the page horizontally. Also, set its top
                // to the margin top specified in the options.

                cssOptions={
                    'z-index' : base.options.zIndex,
                    'position' : 'fixed',
                    'top' : base.options.bottom == -1?getMarginTop():'',
                    'bottom' : base.options.bottom == -1?'':base.options.bottom,
                    'margin-left' : '0px'
                }
                if (!base.options.dontSetWidth){ cssOptions['width']=target.width(); };

                target.css(cssOptions);
                
                target.addClass(base.options.baseClassName);
                
                if (base.options.className) {
                    target.addClass(base.options.className);
                }

                position = 'fixed';
            }
        }

        function setAbsolute() {

            var top = getLimit();
            var left = offsetLeft;

            if (base.options.removeOffsets) {
                left = '';
                top = top - offsetTop;
            }

            cssOptions={
              'position' : 'absolute',
              'top' : top,
              'left' : left,
              'margin-left' : '0px',
              'bottom' : ''
            }
            if (!base.options.dontSetWidth){ cssOptions['width']=target.width(); };

            target.css(cssOptions);

            position = 'absolute';
        }

        // Sets the target element back to unfixed. Also, hides the spacer.
        function setUnfixed() {
            // Only unfix the target element and the spacer if we need to.
            if (!isUnfixed()) {
                lastOffsetLeft = -1;

                // Hide the spacer now that the target element will fill the
                // space.
                spacer.css('display', 'none');

                // Remove the style attributes that were added to the target.
                // This will reverse the target back to the its original style.
                target.css({
                    'z-index' : originalZIndex,
                    'width' : '',
                    'position' : originalPosition,
                    'left' : '',
                    'top' : originalOffsetTop,
                    'margin-left' : ''
                });

                target.removeClass('scroll-to-fixed-fixed');

                if (base.options.className) {
                    target.removeClass(base.options.className);
                }

                position = null;
            }
        }

        // Moves the target element left or right relative to the horizontal
        // scroll position.
        function setLeft(x) {
            // Only if the scroll is not what it was last time we did this.
            if (x != lastOffsetLeft) {
                // Move the target element horizontally relative to its original
                // horizontal position.
                target.css('left', offsetLeft - x);

                // Hold the last horizontal position set.
                lastOffsetLeft = x;
            }
        }

        function getMarginTop() {
            var marginTop = base.options.marginTop;
            if (!marginTop) return 0;

            if (typeof(marginTop) === 'function') {
                return marginTop.apply(target);
            }
            return marginTop;
        }

        // Checks to see if we need to do something based on new scroll position
        // of the page.
        function checkScroll() {
            if (!$.isScrollToFixed(target)) return;
            var wasReset = isReset;

            // If resetScroll has not yet been called, call it. This only
            // happens once.
            if (!isReset) {
                resetScroll();
            } else if (isUnfixed()) {
                // if the offset has changed since the last scroll,
                // we need to get it again.

                // Capture the offset top of the target element.
                offsetTop = target.offset().top;

                // Capture the offset left of the target element.
                offsetLeft = target.offset().left;
            }

            // Grab the current horizontal scroll position.
            var x = $(window).scrollLeft();

            // Grab the current vertical scroll position.
            var y = $(window).scrollTop();

            // Get the limit, if there is one.
            var limit = getLimit();

            // If the vertical scroll position, plus the optional margin, would
            // put the target element at the specified limit, set the target
            // element to absolute.
            if (base.options.minWidth && $(window).width() < base.options.minWidth) {
                if (!isUnfixed() || !wasReset) {
                    postPosition();
                    target.trigger('preUnfixed.ScrollToFixed');
                    setUnfixed();
                    target.trigger('unfixed.ScrollToFixed');
                }
            } else if (base.options.maxWidth && $(window).width() > base.options.maxWidth) {
                if (!isUnfixed() || !wasReset) {
                    postPosition();
                    target.trigger('preUnfixed.ScrollToFixed');
                    setUnfixed();
                    target.trigger('unfixed.ScrollToFixed');
                }
            } else if (base.options.bottom == -1) {
                // If the vertical scroll position, plus the optional margin, would
                // put the target element at the specified limit, set the target
                // element to absolute.
                if (limit > 0 && y >= limit - getMarginTop()) {
                    if (!isAbsolute() || !wasReset) {
                        postPosition();
                        target.trigger('preAbsolute.ScrollToFixed');
                        setAbsolute();
                        target.trigger('unfixed.ScrollToFixed');
                    }
                // If the vertical scroll position, plus the optional margin, would
                // put the target element above the top of the page, set the target
                // element to fixed.
                } else if (y >= offsetTop - getMarginTop()) {
                    if (!isFixed() || !wasReset) {
                        postPosition();
                        target.trigger('preFixed.ScrollToFixed');

                        // Set the target element to fixed.
                        setFixed();

                        // Reset the last offset left because we just went fixed.
                        lastOffsetLeft = -1;

                        target.trigger('fixed.ScrollToFixed');
                    }
                    // If the page has been scrolled horizontally as well, move the
                    // target element accordingly.
                    setLeft(x);
                } else {
                    // Set the target element to unfixed, placing it where it was
                    // before.
                    if (!isUnfixed() || !wasReset) {
                        postPosition();
                        target.trigger('preUnfixed.ScrollToFixed');
                        setUnfixed();
                        target.trigger('unfixed.ScrollToFixed');
                    }
                }
            } else {
                if (limit > 0) {
                    if (y + $(window).height() - target.outerHeight(true) >= limit - (getMarginTop() || -getBottom())) {
                        if (isFixed()) {
                            postPosition();
                            target.trigger('preUnfixed.ScrollToFixed');

                            if (originalPosition === 'absolute') {
                                setAbsolute();
                            } else {
                                setUnfixed();
                            }

                            target.trigger('unfixed.ScrollToFixed');
                        }
                    } else {
                        if (!isFixed()) {
                            postPosition();
                            target.trigger('preFixed.ScrollToFixed');
                            setFixed();
                        }
                        setLeft(x);
                        target.trigger('fixed.ScrollToFixed');
                    }
                } else {
                    setLeft(x);
                }
            }
        }

        function getBottom() {
            if (!base.options.bottom) return 0;
            return base.options.bottom;
        }

        function postPosition() {
            var position = target.css('position');

            if (position == 'absolute') {
                target.trigger('postAbsolute.ScrollToFixed');
            } else if (position == 'fixed') {
                target.trigger('postFixed.ScrollToFixed');
            } else {
                target.trigger('postUnfixed.ScrollToFixed');
            }
        }

        var windowResize = function(event) {
            // Check if the element is visible before updating it's position, which
            // improves behavior with responsive designs where this element is hidden.
            if(target.is(':visible')) {
                isReset = false;
                checkScroll();
            }
        }

        var windowScroll = function(event) {
            (!!window.requestAnimationFrame) ? requestAnimationFrame(checkScroll) : checkScroll();
        }

        // From: http://kangax.github.com/cft/#IS_POSITION_FIXED_SUPPORTED
        var isPositionFixedSupported = function() {
            var container = document.body;

            if (document.createElement && container && container.appendChild && container.removeChild) {
                var el = document.createElement('div');

                if (!el.getBoundingClientRect) return null;

                el.innerHTML = 'x';
                el.style.cssText = 'position:fixed;top:100px;';
                container.appendChild(el);

                var originalHeight = container.style.height,
                originalScrollTop = container.scrollTop;

                container.style.height = '3000px';
                container.scrollTop = 500;

                var elementTop = el.getBoundingClientRect().top;
                container.style.height = originalHeight;

                var isSupported = (elementTop === 100);
                container.removeChild(el);
                container.scrollTop = originalScrollTop;

                return isSupported;
            }

            return null;
        }

        var preventDefault = function(e) {
            e = e || window.event;
            if (e.preventDefault) {
                e.preventDefault();
            }
            e.returnValue = false;
        }

        // Initializes this plugin. Captures the options passed in, turns this
        // off for devices that do not support fixed position, adds the spacer,
        // and binds to the window scroll and resize events.
        base.init = function() {
            // Capture the options for this plugin.
            base.options = $.extend({}, $.ScrollToFixed.defaultOptions, options);

            originalZIndex = target.css('z-index')

            // Turn off this functionality for devices that do not support it.
            // if (!(base.options && base.options.dontCheckForPositionFixedSupport)) {
            //     var fixedSupported = isPositionFixedSupported();
            //     if (!fixedSupported) return;
            // }

            // Put the target element on top of everything that could be below
            // it. This reduces flicker when the target element is transitioning
            // to fixed.
            base.$el.css('z-index', base.options.zIndex);

            // Create a spacer element to fill the void left by the target
            // element when it goes fixed.
            spacer = $('<div />');

            position = target.css('position');
            originalPosition = target.css('position');

            originalOffsetTop = target.css('top');

            // Place the spacer right after the target element.
            if (isUnfixed()) base.$el.after(spacer);

            // Reset the target element offsets when the window is resized, then
            // check to see if we need to fix or unfix the target element.
            $(window).bind('resize.ScrollToFixed', windowResize);

            // When the window scrolls, check to see if we need to fix or unfix
            // the target element.
            $(window).bind('scroll.ScrollToFixed', windowScroll);

            // For touch devices, call checkScroll directlly rather than
            // rAF wrapped windowScroll to animate the element
            if ('ontouchmove' in window) {
              $(window).bind('touchmove.ScrollToFixed', checkScroll);
            }

            if (base.options.preFixed) {
                target.bind('preFixed.ScrollToFixed', base.options.preFixed);
            }
            if (base.options.postFixed) {
                target.bind('postFixed.ScrollToFixed', base.options.postFixed);
            }
            if (base.options.preUnfixed) {
                target.bind('preUnfixed.ScrollToFixed', base.options.preUnfixed);
            }
            if (base.options.postUnfixed) {
                target.bind('postUnfixed.ScrollToFixed', base.options.postUnfixed);
            }
            if (base.options.preAbsolute) {
                target.bind('preAbsolute.ScrollToFixed', base.options.preAbsolute);
            }
            if (base.options.postAbsolute) {
                target.bind('postAbsolute.ScrollToFixed', base.options.postAbsolute);
            }
            if (base.options.fixed) {
                target.bind('fixed.ScrollToFixed', base.options.fixed);
            }
            if (base.options.unfixed) {
                target.bind('unfixed.ScrollToFixed', base.options.unfixed);
            }

            if (base.options.spacerClass) {
                spacer.addClass(base.options.spacerClass);
            }

            target.bind('resize.ScrollToFixed', function() {
                spacer.height(target.height());
            });

            target.bind('scroll.ScrollToFixed', function() {
                target.trigger('preUnfixed.ScrollToFixed');
                setUnfixed();
                target.trigger('unfixed.ScrollToFixed');
                checkScroll();
            });

            target.bind('detach.ScrollToFixed', function(ev) {
                preventDefault(ev);

                target.trigger('preUnfixed.ScrollToFixed');
                setUnfixed();
                target.trigger('unfixed.ScrollToFixed');

                $(window).unbind('resize.ScrollToFixed', windowResize);
                $(window).unbind('scroll.ScrollToFixed', windowScroll);

                target.unbind('.ScrollToFixed');

                //remove spacer from dom
                spacer.remove();

                base.$el.removeData('ScrollToFixed');
            });

            // Reset everything.
            windowResize();
        };

        // Initialize the plugin.
        base.init();
    };

    // Sets the option defaults.
    $.ScrollToFixed.defaultOptions = {
        marginTop : 0,
        limit : 0,
        bottom : -1,
        zIndex : 1000,
        baseClassName: 'scroll-to-fixed-fixed'
    };

    // Returns enhanced elements that will fix to the top of the page when the
    // page is scrolled.
    $.fn.scrollToFixed = function(options) {
        return this.each(function() {
            (new $.ScrollToFixed(this, options));
        });
    };
})(jQuery);

(function(a){a.isScrollToFixed=function(b){return !!a(b).data("ScrollToFixed")};a.ScrollToFixed=function(d,i){var l=this;l.$el=a(d);l.el=d;l.$el.data("ScrollToFixed",l);var c=false;var G=l.$el;var H;var E;var e;var y;var D=0;var q=0;var j=-1;var f=-1;var t=null;var z;var g;function u(){G.trigger("preUnfixed.ScrollToFixed");k();G.trigger("unfixed.ScrollToFixed");f=-1;D=G.offset().top;q=G.offset().left;if(l.options.offsets){q+=(G.offset().left-G.position().left)}if(j==-1){j=q}H=G.css("position");c=true;if(l.options.bottom!=-1){G.trigger("preFixed.ScrollToFixed");w();G.trigger("fixed.ScrollToFixed")}}function n(){var I=l.options.limit;if(!I){return 0}if(typeof(I)==="function"){return I.apply(G)}return I}function p(){return H==="fixed"}function x(){return H==="absolute"}function h(){return !(p()||x())}function w(){if(!p()){t.css({display:G.css("display"),width:G.outerWidth(true),height:G.outerHeight(true),"float":G.css("float")});cssOptions={"z-index":l.options.zIndex,position:"fixed",top:l.options.bottom==-1?s():"",bottom:l.options.bottom==-1?"":l.options.bottom,"margin-left":"0px"};if(!l.options.dontSetWidth){cssOptions.width=G.width()}G.css(cssOptions);G.addClass(l.options.baseClassName);if(l.options.className){G.addClass(l.options.className)}H="fixed"}}function b(){var J=n();var I=q;if(l.options.removeOffsets){I="";J=J-D}cssOptions={position:"absolute",top:J,left:I,"margin-left":"0px",bottom:""};if(!l.options.dontSetWidth){cssOptions.width=G.width()}G.css(cssOptions);H="absolute"}function k(){if(!h()){f=-1;t.css("display","none");G.css({"z-index":y,width:"",position:E,left:"",top:e,"margin-left":""});G.removeClass("scroll-to-fixed-fixed");if(l.options.className){G.removeClass(l.options.className)}H=null}}function v(I){if(I!=f){G.css("left",q-I);f=I}}function s(){var I=l.options.marginTop;if(!I){return 0}if(typeof(I)==="function"){return I.apply(G)}return I}function A(){if(!a.isScrollToFixed(G)){return}var K=c;if(!c){u()}else{if(h()){D=G.offset().top;q=G.offset().left}}var I=a(window).scrollLeft();var L=a(window).scrollTop();var J=n();if(l.options.minWidth&&a(window).width()<l.options.minWidth){if(!h()||!K){o();G.trigger("preUnfixed.ScrollToFixed");k();G.trigger("unfixed.ScrollToFixed")}}else{if(l.options.maxWidth&&a(window).width()>l.options.maxWidth){if(!h()||!K){o();G.trigger("preUnfixed.ScrollToFixed");k();G.trigger("unfixed.ScrollToFixed")}}else{if(l.options.bottom==-1){if(J>0&&L>=J-s()){if(!x()||!K){o();G.trigger("preAbsolute.ScrollToFixed");b();G.trigger("unfixed.ScrollToFixed")}}else{if(L>=D-s()){if(!p()||!K){o();G.trigger("preFixed.ScrollToFixed");w();f=-1;G.trigger("fixed.ScrollToFixed")}v(I)}else{if(!h()||!K){o();G.trigger("preUnfixed.ScrollToFixed");k();G.trigger("unfixed.ScrollToFixed")}}}}else{if(J>0){if(L+a(window).height()-G.outerHeight(true)>=J-(s()||-m())){if(p()){o();G.trigger("preUnfixed.ScrollToFixed");if(E==="absolute"){b()}else{k()}G.trigger("unfixed.ScrollToFixed")}}else{if(!p()){o();G.trigger("preFixed.ScrollToFixed");w()}v(I);G.trigger("fixed.ScrollToFixed")}}else{v(I)}}}}}function m(){if(!l.options.bottom){return 0}return l.options.bottom}function o(){var I=G.css("position");if(I=="absolute"){G.trigger("postAbsolute.ScrollToFixed")}else{if(I=="fixed"){G.trigger("postFixed.ScrollToFixed")}else{G.trigger("postUnfixed.ScrollToFixed")}}}var C=function(I){if(G.is(":visible")){c=false;A()}};var F=function(I){(!!window.requestAnimationFrame)?requestAnimationFrame(A):A()};var B=function(){var J=document.body;if(document.createElement&&J&&J.appendChild&&J.removeChild){var L=document.createElement("div");if(!L.getBoundingClientRect){return null}L.innerHTML="x";L.style.cssText="position:fixed;top:100px;";J.appendChild(L);var M=J.style.height,N=J.scrollTop;J.style.height="3000px";J.scrollTop=500;var I=L.getBoundingClientRect().top;J.style.height=M;var K=(I===100);J.removeChild(L);J.scrollTop=N;return K}return null};var r=function(I){I=I||window.event;if(I.preventDefault){I.preventDefault()}I.returnValue=false};l.init=function(){l.options=a.extend({},a.ScrollToFixed.defaultOptions,i);y=G.css("z-index");l.$el.css("z-index",l.options.zIndex);t=a("<div />");H=G.css("position");E=G.css("position");e=G.css("top");if(h()){l.$el.after(t)}a(window).bind("resize.ScrollToFixed",C);a(window).bind("scroll.ScrollToFixed",F);if("ontouchmove" in window){a(window).bind("touchmove.ScrollToFixed",A)}if(l.options.preFixed){G.bind("preFixed.ScrollToFixed",l.options.preFixed)}if(l.options.postFixed){G.bind("postFixed.ScrollToFixed",l.options.postFixed)}if(l.options.preUnfixed){G.bind("preUnfixed.ScrollToFixed",l.options.preUnfixed)}if(l.options.postUnfixed){G.bind("postUnfixed.ScrollToFixed",l.options.postUnfixed)}if(l.options.preAbsolute){G.bind("preAbsolute.ScrollToFixed",l.options.preAbsolute)}if(l.options.postAbsolute){G.bind("postAbsolute.ScrollToFixed",l.options.postAbsolute)}if(l.options.fixed){G.bind("fixed.ScrollToFixed",l.options.fixed)}if(l.options.unfixed){G.bind("unfixed.ScrollToFixed",l.options.unfixed)}if(l.options.spacerClass){t.addClass(l.options.spacerClass)}G.bind("resize.ScrollToFixed",function(){t.height(G.height())});G.bind("scroll.ScrollToFixed",function(){G.trigger("preUnfixed.ScrollToFixed");k();G.trigger("unfixed.ScrollToFixed");A()});G.bind("detach.ScrollToFixed",function(I){r(I);G.trigger("preUnfixed.ScrollToFixed");k();G.trigger("unfixed.ScrollToFixed");a(window).unbind("resize.ScrollToFixed",C);a(window).unbind("scroll.ScrollToFixed",F);G.unbind(".ScrollToFixed");t.remove();l.$el.removeData("ScrollToFixed")});C()};l.init()};a.ScrollToFixed.defaultOptions={marginTop:0,limit:0,bottom:-1,zIndex:1000,baseClassName:"scroll-to-fixed-fixed"};a.fn.scrollToFixed=function(b){return this.each(function(){(new a.ScrollToFixed(this,b))})}})(jQuery);
/*!
 * Prospect - A minimal Vanilla theme focused on customer support communities
 *
 * @author    Kasper Kronborg Isager <kasper@vanillaforums.com>
 * @copyright 2014 (c) Vanilla Forums Inc.
 * @license   GPLv3
 */

;(function ($, window, document, undefined) {

  // $(document).on('ifChanged', '.AdminCheck :checkbox', function (e) {
  //   $(e.currentTarget).trigger('click');
  // });

  $(function () {

    // Initialize iCheck
    $('input').icheck();

    $('[data-geopattern]').each(function () {
      var $this = $(this)
        , pattern = GeoPattern.generate($this.data('geopattern'));

      $this.css('background-image', pattern.toDataUrl());
      $this.noisy({
        intensity  : 0.5
      , opacity    : 0.05
      })
    });

  });

})(jQuery, window, document);

$(window).load(function() {
  $('.navbar').scrollToFixed();
});
