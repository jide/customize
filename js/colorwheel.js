function ColorWheel(id)
{
	this.hue = 60;
	this.adeg = 60;
	this.sat = 1;
	this.val = 1;
	this.squarecolor = "#ffff00"; //starting hue
	this.input = null;
	this.preview = null;
	this.raised = false;

	this.threec = new Array("#ffff00", "#ffff00", "#ffff00"); // the three colors

	document.write('<div id="colorpicker">');
	document.write('<span id="cw_indicator"></span>');
	document.write('<div id="colorwheel" onclick="javascript:colorwheel.pickColor();return false;"></div>');
	document.write('<div id="cw_gui">');	
	document.write('<span id="cw_preview"></span>');
	document.write('<label for="cw_color_type_0">safe<input onclick="colorwheel.hoverColor(true)" type="radio" id="cw_color_type_0" name="cw_color_type" value="0"/></label><input type="text" id="cw_input_0" size="7"/>');
	document.write('<label for="cw_color_type_1">smart<input onclick="colorwheel.hoverColor(true)" type="radio" id="cw_color_type_1" name="cw_color_type" value="1"/></label><input type="text" id="cw_input_1" size="7"/>');
	document.write('<label for="cw_color_type_2">real<input onclick="colorwheel.hoverColor(true)" type="radio" id="cw_color_type_2" name="cw_color_type" value="2" checked="checked"/></label><input type="text" id="cw_input_2" size="7"/>');
	document.write('<input class="cw_button" type="button" value="Ok" onclick="colorwheel.endPicker();return false;"/>');
	document.write('<input class="cw_button" type="button" value="Cancel" onclick="colorwheel.cancel();return false;"/>');
	document.write('</div>');
	document.write('</div>');
	
	document.body.onmouseup = function(e) {colorwheel.looseFocus(e)};
	this.capture();
}

ColorWheel.prototype.looseFocus = function(e)
{
	if (document.layers) {
		x = e.layerX;
		y = e.layerY;
	}
	else if (document.all) {
		x = event.clientX;
		y = event.clientY;
		return;
	}
	else if (document.getElementById) {
		x = e.pageX;
		y = e.pageY;
	}
	if (x < this.findPosX(document.getElementById("colorpicker")) || x > this.findPosX(document.getElementById("colorpicker")) + document.getElementById("colorpicker").clientWidth)
	{
		this.cancel();
	}
	else if (y < this.findPosY(document.getElementById("colorpicker")) || y > this.findPosY(document.getElementById("colorpicker")) + document.getElementById("colorpicker").clientHeight)
	{
		this.cancel();		
	}
}

ColorWheel.prototype.hsv2rgb = function(Hdeg,S,V)
{
  H = Hdeg/360;
  if (S==0) {
    R = V*255;
    G = V*255;
    B = V*255;}
  else {
    var_h = H*6;
    var_i = Math.floor( var_h );
    var_1 = V*(1-S);
    var_2 = V*(1-S*(var_h-var_i));
    var_3 = V*(1-S*(1-(var_h-var_i)));
    if (var_i==0)      {var_r=V ;    var_g=var_3; var_b=var_1}
    else if (var_i==1) {var_r=var_2; var_g=V;     var_b=var_1}
    else if (var_i==2) {var_r=var_1; var_g=V;     var_b=var_3}
    else if (var_i==3) {var_r=var_1; var_g=var_2; var_b=V}
    else if (var_i==4) {var_r=var_3; var_g=var_1; var_b=V}
    else               {var_r=V;     var_g=var_1; var_b=var_2}
    R = Math.round(var_r*255);
    G = Math.round(var_g*255);
    B = Math.round(var_b*255);
  }
  return new Array(R,G,B);
}

ColorWheel.prototype.rgb2hex = function(rgbary)
{
  cary = new Array; 
  cary[3] = "#";
  for (i=0; i < 3; i++) {
    cary[i] = parseInt(rgbary[i]).toString(16);
    if (cary[i].length < 2) cary[i] = "0"+ cary[i];
    cary[3] = cary[3] + cary[i];
    cary[i+4] = rgbary[i];
  }
  return cary;
}

ColorWheel.prototype.webRounder = function(c,d)
{
  //safe divisor is 51, smart divisor is 17 
  thec = "#";
  for (i=0; i<3; i++) {
      num = Math.round(c[i+4]/d) * d;
      numc = num.toString(16);
      if (String(numc).length < 2) numc = "0" + numc;
      thec += numc;
  }
  return thec;
}

ColorWheel.prototype.hexColorArray = function (c) {
    this.threec[2] = c[3];
    this.threec[1] = this.webRounder(c,17);
    this.threec[0] = this.webRounder(c,51);
    return false;
}

ColorWheel.prototype.capture = function () {
  if (document.getElementById("colorwheel")) document.getElementById("colorwheel").onmousemove = this.mouseMoved;
}

ColorWheel.prototype.uncapture = function () {
  document.getElementById("colorwheel").onmousemove = null;
}

ColorWheel.prototype.mouseMoved = function (e)
{
	if (document.layers) {
		x = e.layerX;
		y = e.layerY;
	}
	else if (document.all) {
		x = event.offsetX;
		y = event.offsetY;
	}
	else if (document.getElementById) {
		x = e.pageX - colorwheel.findPosX(document.getElementById("colorwheel"));
		y = e.pageY - colorwheel.findPosY(document.getElementById("colorwheel"));
	}
	colorwheel.mousePosition = x;
	colorwheel.mousePositionY = y;
	if (this.raised == true) return false;
	else if (x >= 296 && y <= 256 ) {
	colorwheel.greyMoved(x,y);
	return false;
	}
	else if (y > 256 || x >= 553 || x<0 || y<0) return false;

    cartx = x - 128;
    carty = 128 - y;
    cartx2 = cartx * cartx;
    carty2 = carty * carty;
    cartxs = (cartx < 0)?-1:1;
    cartys = (carty < 0)?-1:1;
    cartxn = cartx/128;
    rraw = Math.sqrt(cartx2 + carty2);
    rnorm = rraw/128;
    if (rraw == 0) {
      colorwheel.sat = 0;
      colorwheel.val = 0;
      rgb = new Array(0,0,0);
    }
    else {
      arad = Math.acos(cartx/rraw);
      aradc = (carty>=0)?arad:2*Math.PI - arad;
      colorwheel.adeg = 360 * aradc/(2*Math.PI);
      if (rnorm > 1) {
          rgb = new Array(255,255,255);
          colorwheel.sat = 1;
          colorwheel.val = 1;            
      }
      else if (rnorm >= .5) {
	      colorwheel.sat = 1 - ((rnorm - .5) *2);
          colorwheel.val = 1;
	      rgb = colorwheel.hsv2rgb(colorwheel.adeg,colorwheel.sat,colorwheel.val);
	  }
      else {
	      colorwheel.sat = 1;
	  	  colorwheel.val = rnorm * 2;
	  	  rgb = colorwheel.hsv2rgb(colorwheel.adeg,colorwheel.sat,colorwheel.val);
	  }
   }
   c = colorwheel.rgb2hex(rgb);
   colorwheel.hexColorArray(c);
   colorwheel.hoverColor();
   return false;
}

ColorWheel.prototype.hoverColor = function(force)
{
	if (this.raised == true && force != true) return; // && this.mousePosition >= 296
	//document.getElementById('cw_indicator').style.display = 'none';
	document.getElementById('cw_input_0').value = this.threec[0];
	document.getElementById('cw_input_1').value = this.threec[1];
	document.getElementById('cw_input_2').value = this.threec[2];
	this.updatePreview();
	return false;
}

ColorWheel.prototype.updatePreview = function()
{
	//document.getElementById('cw_indicator').style.top = (this.mousePositionY-5)+'px';
	//document.getElementById('cw_indicator').style.left = (this.mousePosition-5)+'px';
	
	if (document.getElementById('cw_color_type_0').checked == true) document.getElementById('cw_preview').style.backgroundColor = this.threec[0];
	else if (document.getElementById('cw_color_type_1').checked == true) document.getElementById('cw_preview').style.backgroundColor = this.threec[1];
	else if (document.getElementById('cw_color_type_2').checked == true) document.getElementById('cw_preview').style.backgroundColor = this.threec[2];
	return false;
}

ColorWheel.prototype.greyMoved = function (x,y)
{
    this.adeg = this.hue;
    xside = (x<=553)?x - 296:256;
    yside = (y<=256)?y:256;
    this.sat = xside/256;
    this.val = 1 - (yside/256);
    c = this.rgb2hex(this.hsv2rgb(this.hue,this.sat,this.val));
    this.hexColorArray(c);
    this.hoverColor();
    return false;
}

ColorWheel.prototype.pickColor = function ()
{
	if (this.raised == true && this.mousePosition < 296) {
		this.raised = false;
		document.getElementById('cw_indicator').style.display = 'none';
	}
	else {
		this.raised = true;
		if (document.getElementById('cw_color_type_0').checked == true) this.finalColor = this.threec[0];
		else if (document.getElementById('cw_color_type_1').checked == true) this.finalColor = this.threec[1];
		else if (document.getElementById('cw_color_type_2').checked == true) this.finalColor = this.threec[2];
		document.getElementById('cw_indicator').style.display = 'block';
	}
	this.hoverColor(true);
	this.setSquare(this.adeg);
	
	document.getElementById('cw_indicator').style.top = (this.mousePositionY-5)+'px';
	document.getElementById('cw_indicator').style.left = (this.mousePosition-5)+'px';
	return false;
}

ColorWheel.prototype.setSquare = function (deg)
{
	this.hue = deg;
	this.adeg = deg;
	c = this.rgb2hex(this.hsv2rgb(this.hue,1,1));
	this.squarecolor = c[3];
	document.getElementById("colorwheel").style.backgroundColor = this.squarecolor;
}

ColorWheel.prototype.reHue = function (deg)
{
    deg = deg % 360;
    this.setSquare(deg);
    rgb = this.hsv2rgb(deg,this.sat,this.val);
    c = this.rgb2hex(rgb);
    this.hexColorArray(c);
    this.hoverColor();
    return false;
}

ColorWheel.prototype.choose = function (input,preview)
{
	this.input = document.getElementById(input);
	if (preview != '') this.preview = document.getElementById(preview);
	else this.preview = null;
	this.raised = false;
	document.getElementById('cw_indicator').style.display = 'none';
	document.getElementById("colorpicker").style.visibility='visible';
	document.getElementById("colorpicker").style.left = this.findPosX(this.input)+'px';
	document.getElementById("colorpicker").style.top = this.findPosY(this.input)+this.input.clientHeight+5+'px';
}

ColorWheel.prototype.endPicker = function (input,preview)
{
	this.raised = false;
	document.getElementById("colorpicker").style.visibility='hidden';
	this.input.value = this.finalColor;
	if (this.preview) this.preview.style.backgroundColor = this.finalColor;
}

ColorWheel.prototype.cancel = function (input,preview)
{
	document.getElementById("colorpicker").style.visibility='hidden';
}
		
ColorWheel.prototype.findPosY = function (inputObj)
{
	
  var returnValue = inputObj.offsetTop;
  while((inputObj = inputObj.offsetParent) != null){
  	returnValue += inputObj.offsetTop;
  }
  return returnValue;
}
	
ColorWheel.prototype.findPosX = function (inputObj)
{
  var returnValue = inputObj.offsetLeft;
  while((inputObj = inputObj.offsetParent) != null)returnValue += inputObj.offsetLeft;
  return returnValue;
}

var colorwheel = new ColorWheel('wheel');