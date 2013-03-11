pwf.register = function()
{
	this.options = {
		xOffset: 10,
		yOffset: 25,
		tooltipId: "yawf_tooltip",
		clickRemove: false,
		content: "",
		useElement: ""
	};

	$("body").append("<div id='"+ this.options.tooltipId +"'></div>");
	this.lastTitle = '';
	this.el = $("body").children('#'+this.options.tooltipId);
	this.el.hide();


	var calc_horizontal_position = function(mouse_pos)
	{
		var x = mouse_pos + yawf.tooltip.options.xOffset;
		return (($(window).width()/2 - x - yawf.tooltip.getWidth()) < 0) ?
			x - yawf.tooltip.getWidth() - yawf.tooltip.options.xOffset*4:x;
	}


	this.getWidth = function()
	{
		return this.el.width();
	}


	this.hide = function(e)
	{
		yawf.tooltip.el.hide();
		$(this).attr("title", yawf.tooltip.lastTitle);
	};


	this.show = function(e, el)
	{
		yawf.tooltip.lastTitle = el.title;
		$(this).attr("title","");

		if (yawf.tooltip.lastTitle != undefined && yawf.tooltip.lastTitle != "") {
			yawf.tooltip.el.html(yawf.tooltip.lastTitle).css("position", "fixed").css("display", "inline-block");
			yawf.tooltip.move(e);
		}

		e.stopPropagation();
	};


	this.move = function(e)
	{
		yawf.tooltip.el
			.css("top", (e.pageY - yawf.tooltip.options.yOffset) + "px")
			.css("left", calc_horizontal_position(e.pageX) + "px");
	};


	this.init = function()
	{
		objects = $(typeof yawf_tooltip_selector == 'undefined' ?
			'a[title], span[title]':yawf_tooltip_selector
		);

		objects.each(function() {
			$(this).hover(function(e) { yawf.tooltip.show(e, this) }, function (e) { yawf.tooltip.hide(e, this) });
			$(this).mousemove(function(e) { yawf.tooltip.move(e); });
		});
		return true;
	};
};
