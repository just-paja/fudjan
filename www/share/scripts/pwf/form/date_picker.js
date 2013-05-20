pwf.register('date_picker', function()
{
	var
		selectors = ["input[type=date]", "input.datepicker"],
		marker = 'pwf-datepicker',
		widget = 'pwf_datepicker';



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
			"container":$('<div class="datepicker"/>'),
			"inner":$('<div class="inner"/>'),
			"target":el,
			"input":$('<input class="date" type="text" name="" value=""/>'),
			"value":null,
			"cal":null
		};

		els.target.wrap(els.container);
		els.container = els.target.parent();
		els.container.append(els.inner);
		els.target.hide();

		if (els.target.val()) {
			try {
				var date = new Date(els.target.val());
				use_value(els, date);
			} catch(e) {
				v(e);
			}
		}

		els.inner.append(els.input);
		els.input.unbind('click.'+widget).bind('click.'+widget, els, callback_open_calendar);

		if (els.target.attr('required')) {
			els['cleaner'] = $('<span class="icon clear" style=""></span>');
			els.inner.append(els.cleaner);
			els.cleaner.unbind('click.'+widget).bind('click.'+widget, els, callback_clear);
		}
	};


	var callback_clear = function(e)
	{
		e.preventDefault();
		e.stopPropagation();

		e.data.input.val('');
		e.data.target.val('');
	};


	var callback_open_calendar = function(e)
	{
		e.preventDefault();
		e.stopPropagation();

		var cal = $('<div class="calendar"/>');
		create_calendar(e.data, cal);
		e.data.inner.append(cal);

		e.data.input.unbind('click.'+widget);
		$([$('html')[0], e.data.input[0]]).bind('click.'+widget, e.data, callback_hide);
	};


	var callback_hide = function(e)
	{
		e.preventDefault();
		e.stopPropagation();

		hide_cal(e.data);
	};


	var hide_cal = function(els)
	{
		var cal = els.inner.find('.calendar');
		if (cal.length) cal.fadeOut(200, function() { $(this).remove(); });

		$([$('html')[0], els.input[0]]).unbind('click.'+widget);
		els.input.bind('click.'+widget, els, callback_open_calendar);
	};


	var create_calendar = function(els, tar, date)
	{
		var date = typeof date === 'undefined' ? (els.cal === null ? get_date(els):date):date;
		var day_def = get_day_def();
		var month_name = get_month(date.getUTCMonth()+1);
		var head = $('<ul class="head"></ul>');
		var body = $('<ul class="body"></ul>');
		var controls = $('<div class="controls"></div>');
		var cpp = $('<span class="cpp">&lt;&lt;</span>');
		var cnn = $('<span class="cnn">&gt;&gt;</span>');
		var cp =  $('<span class="cp">&lt;</span>');
		var cn =  $('<span class="cn">&gt;</span>');
		var cd = '<span class="month_name">' + month_name[2] + ' ' + date.getUTCFullYear() + '</span>';
		var cleaner = '<span class="cleaner"></span>';
		var week_start = 1;
		var current = get_cal_month_start(week_start, date);
		var end = get_cal_month_end(week_start, date);

		tar.html('');

		controls.append([cpp, cp, cd, cn, cnn]);

		cpp.bind('click', {"els":els, "date":date, "cal":tar}, callback_calendar_cpp);
		cnn.bind('click', {"els":els, "date":date, "cal":tar}, callback_calendar_cnn);

		cp.bind('click', {"els":els, "date":date, "cal":tar}, callback_calendar_cp);
		cn.bind('click', {"els":els, "date":date, "cal":tar}, callback_calendar_cn);
		tar.unbind('click').bind('click', callback_void);

		for (var i = 0; i<day_def.length; i++) {
			head.append('<li class="day">'+day_def[i][1]+'</li>');
		}

		while (current.getTime() <= end.getTime()) {
			var day = $('<li>'+current.getUTCDate()+'</li>');
			var day_class = ['day'];
			var cd = new Date(Date.UTC(current.getUTCFullYear(), current.getUTCMonth(), current.getUTCDate()));

			day_class.push(current.getUTCMonth() === date.getUTCMonth() ? 'active':'inactive');
			if (current.getUTCDay() === week_start) day_class.push('week_start');

			day.addClass(day_class.join(' '));
			day.bind('click', {"els":els, "date":cd}, callback_calendar_day);

			body.append(day);
			current.setTime(current.getTime()+(86400*1000));
		}

		tar.html([controls, head, body, cleaner]);
	};


	var callback_void = function(e)
	{
		e.stopPropagation();
		e.preventDefault();
	};


	var callback_calendar_day = function(e)
	{
		e.stopPropagation();
		e.preventDefault();

		use_value(e.data.els, e.data.date);
	};


	var use_value = function(els, date)
	{
		els.value = date;

		var val   = els.value;
		var day   = val.getUTCDate() + '';
		var mon   = (val.getUTCMonth() + 1) + '';
		var human = day + '.' + mon + '.' + val.getUTCFullYear();
		var sys   = '';

		(day.length < 2) && (day = '0' + day);
		(mon.length < 2) && (mon = '0' + mon);

		sys = val.getUTCFullYear() + '-' + mon + '-' + day;

		hide_cal(els);
		els.target.val(sys);
		els.input.val(human);
	};


	var callback_calendar_cp = function(e)
	{
		e.preventDefault();
		e.stopPropagation();

		var mon = e.data.date.getUTCMonth()-1, yrs = e.data.date.getUTCFullYear();

		if (mon < 0) {
			yrs = yrs - 1;
			mon = 12 + mon;
		}

		create_calendar(e.data.els, e.data.cal, new Date(Date.UTC(yrs, mon, 1)));
	};


	var callback_calendar_cn = function(e)
	{
		e.preventDefault();
		e.stopPropagation();

		var mon = e.data.date.getUTCMonth()+1, yrs = e.data.date.getUTCFullYear();

		if (mon > 12) {
			yrs = yrs + Math.floor(mon/12);
			mon = mon-12;
		}

		create_calendar(e.data.els, e.data.cal, new Date(Date.UTC(yrs, mon, 1)));
	};


	var callback_calendar_cnn = function(e)
	{
		e.preventDefault();
		e.stopPropagation();

		var mon = e.data.date.getUTCMonth(), yrs = e.data.date.getUTCFullYear() + 1;
		create_calendar(e.data.els, e.data.cal, new Date(Date.UTC(yrs, mon, 1)));
	};


	var callback_calendar_cpp = function(e)
	{
		e.preventDefault();
		e.stopPropagation();

		var mon = e.data.date.getUTCMonth(), yrs = e.data.date.getUTCFullYear() - 1;
		create_calendar(e.data.els, e.data.cal, new Date(Date.UTC(yrs, mon, 1)));
	};


	var get_date = function(els)
	{
		var val = els.target.val();
		var date = typeof val !== 'undefined' && val.length > 0 ? new Date(val):null;

		if (date !== null) {
			date = new Date(Date.UTC(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate(), 0, 0, 0));
		} else {
			date = new Date();
		}

		return date;
	};


	var get_day_count = function(month)
	{
		return new Date(Date.UTC(get_year(), month, 0)).getUTCDate();
	};


	var get_year = function()
	{
		return (new Date()).getUTCFullYear();
	};


	var get_month = function(id)
	{
		var months = get_month_def();
		for (var i=0; i<months.length; i++) {
			if (months[i][0] === id) {
				return months[i];
			}
		}

		return null;
	};


	var get_cal_month_start = function(week_start, date)
	{
		var start = new Date(Date.UTC(date.getUTCFullYear(), date.getUTCMonth(), 1));
		start.setHours(0);

		while (start.getUTCDay() !== week_start) {
			start.setTime(start.getTime()-86400*1000);
		}

		return start;
	};


	var get_cal_month_end = function(week_start, date)
	{
		var added = 0;
		var end = new Date(Date.UTC(date.getUTCFullYear(), date.getUTCMonth(), get_day_count(date.getUTCMonth()+1)));
		end.setHours(0);

		if (end.getDay() == week_start) {
			end.setTime(end.getTime()+86400*1000);
		}

		while (true) {
			end.setTime(end.getTime()+86400*1000);
			added ++;

			if (end.getUTCDay() === week_start) {
				end.setTime(end.getTime()-86400*1000);
				break;
			}
		}

		return end;
	};


	var get_day_def = function()
	{
		return [
			[1, "Po", "Pondělí"],
			[2, "Út", "Úterý"],
			[3, "St", "Středa"],
			[4, "Čt", "Čtvrtek"],
			[5, "Pá", "Pátek"],
			[6, "So", "Sobota"],
			[0, "Ne", "Neděle"]
		];
	};


	var get_month_def = function()
	{
		return [
			[1,  "Led", "Leden"],
			[2,  "Úno", "Únor"],
			[3,  "Bře", "Březen"],
			[4,  "Dub", "Duben"],
			[5,  "Kvě", "Květen"],
			[6,  "Čer", "Červen"],
			[7,  "Čec", "Červenec"],
			[8,  "Srp", "Srpen"],
			[9,  "Zář", "Září"],
			[10, "Říj", "Říjen"],
			[11, "Lis", "Listopad"],
			[12, "Pro", "Prosinec"],
		]
	};

});
