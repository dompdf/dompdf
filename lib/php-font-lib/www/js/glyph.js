var Glyph = {
  glyphs: [],
  ratio: null, 
  head:  null, 
  os2:   null, 
  hmtx:  null,
  width: null,
  height: null,
  scale: 1.0,
  
  splitPath: function(path) {
  	return path.match(/([a-z])|(-?\d+(?:\.\d+)?)/ig);
  },

  drawPath: function(ctx, path) {
    var p = Glyph.splitPath(path);

    if (!p) {
      return;
    }

    var l = p.length;
    var i = 0;

    ctx.beginPath();

    while(i < l) {
      var v = p[i];

      switch(v) {
        case "M":
          ctx.moveTo(p[++i], p[++i]);
          break;

        case "L":
          ctx.lineTo(p[++i], p[++i]);
          break;

        case "Q":
          ctx.quadraticCurveTo(p[++i], p[++i], p[++i], p[++i]);
          break;

        case "z":
          i++;
          break;

        default:
          i++;
      }
    }

    ctx.fill();
    ctx.closePath();
  },
  
  drawSVGContours: function(ctx, contours) {
    // Is the path
    if (!$.isArray(contours)) {
      Glyph.drawPath(ctx, contours);
      return;
    }

    var contour, path, transform;

    for (var ci = 0, cl = contours.length; ci < cl; ci++) {
      contour = contours[ci];
      path = contour.contours;
      transform = contour.transform;

      ctx.save();
      ctx.transform(transform[0], transform[1], transform[2], transform[3], transform[4], transform[5]);
      Glyph.drawSVGContours(ctx, path);
      ctx.restore();
    }
  },
  
  drawHorizLine: function(ctx, y, color) {
    ctx.beginPath();
    ctx.strokeStyle = color;
    ctx.moveTo(0, y);
    ctx.lineTo(Glyph.width * Glyph.ratio, y);
    ctx.closePath();
    ctx.stroke();
  },
  
  draw: function (canvas, shape, gid) {
    var element  = canvas[0];
    var ctx      = element.getContext("2d");
    var ratio    = Glyph.ratio;
    var width    = Glyph.width  * Glyph.scale;
    var height   = Glyph.height * Glyph.scale;
    ctx.clearRect(0, 0, width, height);

    ctx.lineWidth = ratio / Glyph.scale;
    
    // Invert axis
    ctx.translate(0, height);
    ctx.scale(1/ratio, -(1/ratio));
    ctx.scale(Glyph.scale, Glyph.scale);
    
    ctx.translate(0, -Glyph.head.yMin);
    
    // baseline
    Glyph.drawHorizLine(ctx, 0, "rgba(0,255,0,0.2)");
    
    // ascender
    Glyph.drawHorizLine(ctx, Glyph.os2.typoAscender, "rgba(255,0,0,0.2)");
    
    // descender
    Glyph.drawHorizLine(ctx, -Math.abs(Glyph.os2.typoDescender), "rgba(255,0,0,0.2)");
    
    ctx.translate(-Glyph.head.xMin, 0);
    
    ctx.save();
      var s = ratio*3;
      
      ctx.strokeStyle = "rgba(0,0,0,0.5)";
      ctx.lineWidth = (ratio * 1.5) / Glyph.scale;
      
      // origin
      ctx.beginPath();
      ctx.moveTo(-s, -s);
      ctx.lineTo(+s, +s);
      ctx.moveTo(+s, -s);
      ctx.lineTo(-s, +s);
      ctx.closePath();
      ctx.stroke();
      
      // horizontal advance
      var advance = Glyph.hmtx[gid][0];
      ctx.beginPath();
      ctx.moveTo(-s+advance, -s);
      ctx.lineTo(+s+advance, +s);
      ctx.moveTo(+s+advance, -s);
      ctx.lineTo(-s+advance, +s);
      ctx.closePath();
      ctx.stroke();
    ctx.restore();
    
    if (!shape) {
      return;
    }
    
    // glyph bounding box
    ctx.beginPath();
    ctx.strokeStyle = "rgba(0,0,0,0.3)";
    ctx.rect(0, 0, shape.xMin + shape.xMax, shape.yMin + shape.yMax);
    ctx.closePath();
    ctx.stroke();
    
    ctx.strokeStyle = "black";
    //ctx.globalCompositeOperation = "xor";
    
    Glyph.drawSVGContours(ctx, shape.SVGContours);
  },
  drawAll: function(){
    $.each(Glyph.glyphs, function(i, g){
      Glyph.draw($('#glyph-canvas-'+g[0]), g[1], g[0]);
    });
  },
  resize: function(value){
    Glyph.scale = value / 100;

    $.each(document.getElementsByTagName('canvas'), function(i, canvas){
      canvas.height = Glyph.height * Glyph.scale;
      canvas.width  = Glyph.width  * Glyph.scale;
    });

    Glyph.drawAll();
  }
};

$(function(){
  Glyph.drawAll();
});