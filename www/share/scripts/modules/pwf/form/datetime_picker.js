pwf.register('datetime_picker', function()
{
	var
		selectors = [
			"input[type=date]",
			"input[type=time]",
			"input[type=datetime]",
			"input.datetime_picker",
			"input.date_picker",
			"input.time_picker",
		],
		instances = {},
		class_datetime_picker = function(el)
		{
			var
				attrs = {
					"id":el.attr('id'),
					"on":false,
					"week_start":1,
					"seconds":false,
					"minutes":true,
					"hours":true
				},
				els = {
					"container":null,
					"input":el,
					"calendar":null
				};


			this.el = function(name, obj)
			{
				if (typeof obj !== 'undefined') {
					els[name] = obj;
				}

				return typeof els[name] === 'undefined' ? null:els[name];
			};


			this.attr = function(name, val)
			{
				if (typeof val !== 'undefined') {
					attrs[name] = val;
				}

				return typeof attrs[name] === 'undefined' ? null:attrs[name];
			};


			this.create_html_structure = function()
			{
				this.el('container') === null && this.el('container', $('<div class="datetime_picker" id="'+this.attr('id')+'"></div>'));
				this.el('calendar') === null && this.el('calendar', $('<div class="calendar"></div>'));
				this.el('parent', this.el('input').parent());
				this.el('container_date', $('<div class="container_date"></div>'));
				this.el('container_time', $('<div class="container_time"></div>'));

				this.el('input').remove();
				this.el('parent').append(this.el('container').append(this.el('input')));

				this.el('input_date', $('<input class="date" type="text" name="" value="" id="'+this.attr('id')+'_input_date">'));
				this.el('input_hours', $('<input class="time" type="text" step="1" max="23" min="0" maxlength="2" name="" value="" id="'+this.attr('id')+'_input_time_hours">'));
				this.el('input_minutes', $('<input class="time" type="text" step="1" max="59" min="0" maxlength="2" name="" value="" id="'+this.attr('id')+'_input_time_minutes">'));
				this.el('input_seconds', $('<input class="time" type="text" step="1" max="59" min="0" maxlength="2" name="" value="" id="'+this.attr('id')+'_input_time_seconds">'));
				this.el('icon_calendar', $('<span class="icon cal" style=""></span>'));
				this.el('time_separator', '<span class="sep">:</span>');

				this.el('container_date').append([this.el('input_date'), this.el('icon_calendar')]);

				if (this.attr('hours')) {
					this.el('container_time').append(this.el('input_hours'));

					if (this.attr('minutes') || this.attr('seconds')) {
						this.el('container_time').append(this.el('time_separator'));
					}
				}

				if (this.attr('minutes')) {
					this.el('container_time').append(this.el('input_minutes'));

					if (this.attr('seconds')) {
						this.el('container_time').append(this.el('time_separator'));
					}
				}

				if (this.attr('seconds')) {
					this.el('container_time').append(this.el('input_seconds'));
				}

				this.el('container').append([this.el('container_date'), this.el('container_time')]);
				this.el('input_hours').bind('keyup.pwf_datetime_picker', {"obj":this, "type":'hrs', "el":this.el('input_hours')}, callback_timeinput);
				this.el('input_minutes').bind('keyup.pwf_datetime_picker', {"obj":this, "type":'min', "el":this.el('input_minutes')}, callback_timeinput);
				this.el('input_seconds').bind('keyup.pwf_datetime_picker', {"obj":this, "type":'sec', "el":this.el('input_seconds')}, callback_timeinput);

				this.el('input')
					.addClass('hidden')
					.unbind('click.pwf_datetime_picker')
					.bind('click.pwf_datetime_picker', {"obj":this}, callback_calendar);

				this.el('input_date')
					.unbind('click.pwf_datetime_picker')
					.bind('click.pwf_datetime_picker', {"obj":this}, callback_calendar);

				this.el('icon_calendar')
					.unbind('click.pwf_datetime_picker')
					.bind('click.pwf_datetime_picker', {"obj":this}, callback_calendar);

			};


			this.switch_visibility = function()
			{
				return this.attr("on") ? this.hide():this.show();
			};


			this.hide = function()
			{
				this.el('calendar').stop(true).fadeOut(200, function(el) {
					return function() { el.remove(); };
				}(this.el('calendar')));

				this.attr('on', false);
				return this;
			};


			this.show = function()
			{
				this.create_month_grid();
				this.el('calendar').stop(true).fadeIn();
				this.attr('on', true);
				return this;
			};


			this.create_month_grid = function(year, month)
			{
				if (typeof month === 'undefined') {
					var date = this.get_date();
				} else {
					var date = new Date(Date.UTC(year, month, 1));
				}

				date.setUTCHours(0);
				date.setUTCMinutes(0);
				date.setUTCSeconds(0);

				var days = get_day_count(date.getUTCMonth());
				var start = get_cal_month_start(this.attr('week_start'), date);
				var end = get_cal_month_end(this.attr('week_start'), date);
				var current = new Date(start.getTime());

				create_month_grid_html(this, days, date, start, end, current);
				this.el('container').append(this.el('calendar'));
				this.attr('on', true);
				return this;
			};


			this.select_date = function(microtime)
			{
				this.hide();
				var date = new Date();
				var current = this.get_date();
				date.setTime(microtime);
				date.setUTCHours(current.getUTCHours());
				date.setUTCMinutes(current.getUTCMinutes());
				date.getUTCSeconds(current.getUTCSeconds());
				this.update_value(date);
			};


			this.get_date = function()
			{
				var val = this.el('input').val();
				var date = typeof val !== 'undefined' && val.length > 0 ? new Date(val):new Date();

				date = new Date(Date.UTC(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate(), date.getUTCHours(), date.getUTCMinutes(), date.getUTCSeconds()));
				date.setTime(date.getTime() + date.getTimezoneOffset()*60000);
				return date;
			};


			this.update_value = function(date, skip)
			{
				var date = typeof date === 'undefined' ? this.get_date():date;
				var skip = typeof skip === 'undefined' ? false:skip;
				var
					yrs = date.getUTCFullYear() + '',
					mon = (date.getUTCMonth() + 1) + '',
					day = date.getUTCDate() + '',
					hrs = date.getUTCHours() + '',
					min = date.getUTCMinutes() + '',
					sec = date.getUTCSeconds() + '',
					tzbase = date.getTimezoneOffset(),
					tzh = (Math.floor(tzbase/60) * (tzbase >= 0 ? 1:-1)) + '',
					tzm = ((tzbase%60) * 60) + '';

				this.el('input_date').val(day + '.' + mon + '.' + yrs);

				(mon.length <= 1) && (mon = '0' + mon);
				(day.length <= 1) && (day = '0' + day);
				(hrs.length <= 1) && (hrs = '0' + hrs);
				(min.length <= 1) && (min = '0' + min);
				(sec.length <= 1) && (sec = '0' + sec);
				(tzh.length <= 1) && (tzh = '0' + tzh);
				(tzm.length <= 1) && (tzm = '0' + tzm);

				(skip !== 'hrs') && this.el('input_hours').val(hrs);
				(skip !== 'min') && this.el('input_minutes').val(min);
				(skip !== 'sec') && this.el('input_seconds').val(sec);

				var tz = (tzbase >= 0 ? '+':'-') + tzh + ':' + tzm;
				var val = yrs + '-' + mon + '-' + day + 'T' + hrs + ':' + min + ':' + sec + tz;
				this.el('input').attr('value', val);
				return this;
			};
		};


	var get_day_count = function(month)
	{
		return new Date(Date.UTC(get_year(), month, 0)).getUTCDate();
	};


	var get_year = function()
	{
		return (new Date()).getUTCFullYear();
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


	var create_month_grid_html = function(obj, days, date, start, end, current)
	{
		var day_def = get_day_def();
		var head = $('<ul class="head"></ul>');
		var body = $('<ul class="body"></ul>');
		var controls = $('<div class="controls"></div>');
		var cpp = $('<span class="cpp">&lt;&lt;</span>');
		var cnn = $('<span class="cnn">&gt;&gt;</span>');
		var cp =  $('<span class="cp">&lt;</span>');
		var cn =  $('<span class="cn">&gt;</span>');
		var cd = '<span class="month_name">' + get_month(date.getUTCMonth()+1)[2] + ' ' + date.getUTCFullYear() + '</span>';
		var cleaner = '<span class="cleaner"></span>';

		controls.append([cpp, cp, cd, cn, cnn]);

		cpp.bind('click', {"obj":obj, "date":date}, function(e) {
			e.data.obj.create_month_grid(e.data.date.getUTCFullYear()-1, e.data.date.getUTCMonth());
			e.preventDefault();
		});

		cnn.bind('click', {"obj":obj, "date":date}, function(e) {
			e.data.obj.create_month_grid(e.data.date.getUTCFullYear()+1, e.data.date.getUTCMonth());
			e.preventDefault();
		});

		cp.bind('click', {"obj":obj, "date":date}, callback_calendar_cp);
		cn.bind('click', {"obj":obj, "date":date}, callback_calendar_cn);

		for (var i = 0; i<day_def.length; i++) {
			head.append('<li class="day">'+day_def[i][1]+'</li>');
		}

		while (current.getTime() <= end.getTime()) {
			var day = $('<li>'+current.getUTCDate()+'</li>');
			var day_class = ['day'];

			if (current.getUTCMonth() === date.getUTCMonth()) {
				day_class.push('active');
			} else {
				day_class.push('inactive');
			}

			if (current.getUTCDay() === obj.attr('week_start')) {
				day_class.push('week_start');
			}

			day.addClass(day_class.join(' '));
			day.bind('click', {"obj":obj, "date":current.getTime()}, function(e) {
				e.preventDefault();
				e.stopPropagation();
				e.data.obj.select_date(e.data.date);
			});

			body.append(day);
			current.setTime(current.getTime()+(86400*1000));
		}

		obj.el('calendar').html('');
		obj.el('calendar').append([controls, head, body, cleaner]);
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


	var callback_calendar_cp = function(e)
	{
		var mon = e.data.date.getUTCMonth()-1, yrs = e.data.date.getUTCFullYear();

		if (mon < 0) {
			yrs = yrs - 1;
			mon = 12 + mon;
		}

		e.data.obj.create_month_grid(yrs, mon);
		e.preventDefault();
	};


	var callback_calendar_cn = function(e)
	{
		var mon = e.data.date.getUTCMonth()+1, yrs = e.data.date.getUTCFullYear();

		if (mon > 12) {
			yrs = yrs + Math.floor(mon/12);
			mon = mon-12;
		}

		e.data.obj.create_month_grid(yrs, mon);
		e.preventDefault();
	};


	var callback_timeinput = function(e)
	{
		var val  = e.data.el.val();
		var date = e.data.obj.get_date();

		if (e.data.type === 'hrs') {
			date.setUTCHours(val);
		}

		if (e.data.type === 'min') {
			date.setUTCMinutes(val);
		}

		if (e.data.type === 'sec') {
			date.setUTCSeconds(val);
		}

		e.data.obj.update_value(date, e.data.type);
	};


	var callback_calendar = function(e) {
		e.preventDefault();
		e.data.obj.switch_visibility();
	};


	this.init = function()
	{
		this.scan();
	};


	this.scan = function(container)
	{
		var container = typeof container === 'undefined' ? $('body'):container;
		var els = container.find(selectors.join(', '));

		for (var i = 0; i < els.length; i++) {
			this.bind($(els[i]));
		}
	};


	this.bind = function(el)
	{
		var id = get_el_id(el);

		if (typeof instances[id] === 'undefined') {
			var inst = new class_datetime_picker(el);
			instances[el.attr('id')] = inst;
			inst.create_html_structure();
			inst.update_value();
		}

		return this.get_instance(el.attr('id'));
	};


	this.get_instance = function(id)
	{
		return instances[id];
	};


	var get_el_id = function(el)
	{
		return typeof el.attr('id') === 'undefined' ? el.attr('id', create_id()):el.attr('id');
	};


	var create_id = function()
	{
		var id = "datetime_picker_" + Math.round(Math.random()*1000000);
		return typeof instances[id] === 'undefined' ? id:create_id();
	};
});
