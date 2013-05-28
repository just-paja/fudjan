pwf.register('time_picker', function()
{
	var
		self = this,
		selectors = ["input[type=time]", "input.timepicker"],
		marker = 'pwf-timepicker',
		widget = 'pwf_timepicker';


	this.init = function()
	{
		var ready = this.is_ready();

		if (ready) {
			this.scan();
		}

		return ready;
	};


	this.is_ready = function()
	{
		return $.isReady;
	};


	this.scan = function(container)
	{
		var els = typeof container === 'undefined' ? $(selectors.join(', ')):container.find(selectors.join(', '))

		for (var i = 0; i < els.length; i++) {
			var el = $(els[i]);

			if (!el.hasClass(marker)) {
				this.bind(el);
			}
		}
	};


	this.bind = function(el)
	{
		var els = create_els(el);
		el.addClass(marker);
	};


	var create_els = function(el)
	{
		var els = {
			"container":$('<div class="timepicker"/>'),
			"inner":$('<div class="inner"/>'),
			"target":el,
			"hours":$('<input class="time hours" type="text" name="" value=""/>'),
			"minutes":$('<input class="time minutes" type="text" name="" value=""/>'),
			"time_sep":$('<span class="sep">:</span>'),
			"value":null
		};

		els.target.wrap(els.container);
		els.container = els.target.parent();
		els.container.append(els.inner);
		els.target.hide();

		if (els.target.val()) {
			try {
			} catch(e) {
				v(e);
			}
		}

		if (el.val()) {
			var val = el.val().split(':');

			if (val.length === 3) {
				els.hours.val(val[0]);
				els.minutes.val(val[1]);
			}
		}

		els.hours.bind('keyup.'+widget, {"els":els, "type":'hours'}, callback_keyup);
		els.minutes.bind('keyup.'+widget, {"els":els, "type":'minutes'}, callback_keyup);

		$([els.hours[0], els.minutes[0]]).bind('click', els, callback_click);

		els.inner.append([els.hours, els.time_sep, els.minutes]);
	};


	var callback_clear = function(e)
	{
		e.preventDefault();
		e.stopPropagation();

		e.data.input.val('');
		e.data.target.val('');
	};


	var callback_void = function(e)
	{
		e.stopPropagation();
		e.preventDefault();
	};


	var callback_keyup = function(e)
	{
		e.preventDefault();
		e.stopPropagation();

		var val = parseInt(e.data.els[e.data.type].val());

		if (self['validate_'+e.data.type]) {
			use_value(e.data.els);
		}
	};


	var callback_click = function(e)
	{
		e.stopPropagation();
		e.preventDefault();
		this.select();
	};


	this.validate_minutes = function(val)
	{
		return !isNaN(val) && isNumber(val) && val < 60 && val >= 59;
	};


	this.validate_hours = function(val)
	{
		return !isNaN(val) && isNumber(val) && val < 24 && val >= 0;
	};


	var use_value = function(els)
	{
		var hrs   = els.hours.val() + '';
		var min   = els.minutes.val() + '';
		var sys;

		(hrs.length < 2) && (hrs = '0' + hrs);
		(min.length < 2) && (min = '0' + min);
		(hrs.length < 2) && (hrs = '0' + hrs);
		(min.length < 2) && (min = '0' + min);

		sys = hrs + ':' + min + ':' + '00';

		els.target.val(sys);
		v(sys);
	};
});
