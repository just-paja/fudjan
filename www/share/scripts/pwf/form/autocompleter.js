pwf.register('autocompleter', function() {

	var
		instances = {},
		self = this,
		class_autocompleter = function(param_el, param_attrs)
		{
			var
				attrs = {
					"id":null,
					"model":null,
					"filter":[],
					"display":[],
					"conds":[],
					"fetch":[],
					"val":'',
					"loading":false,
					"callback_item":callback_item,
					"callback_attrs":{}
				},
				els = {},
				results = {};

			els.input = param_el;
			els.container = param_el.parents('div').first();
			els.container.addClass('input-pwf-autocompleter');

			attrs = $.extend(attrs, param_attrs);
			attrs['val'] = els.input.val();

			els.input.attr('autocomplete', 'off');
			els.input.unbind('keyup.pwf_autocompleter').bind('keyup.autocompleter', {"obj":this}, callback_input);



			this.attr = function(name, value)
			{
				if (typeof value !== 'undefined') {
					attrs[name] = value;
				}

				if (typeof attrs[name] !== 'undefined') {
					return attrs[name];
				} else throw "Attribute '" + name + "' does not exist for autocompleter.";
			};


			this.el = function(name, value)
			{
				if (typeof value !== 'undefined') {
					els[name] = value;
				}

				return typeof els[name] === 'undefined' ? null:els[name];
			};


			this.update = function(val)
			{
				this.attr('val', val);

				if (typeof results[val] !== 'undefined') {
					this.list(results[val]);
				} else {
					this.attr('loading', true);

					var data_send = {
						"model":this.attr('model'),
						"filter":this.attr('filter'),
						"display":this.attr('display'),
						"fetch":this.attr('fetch'),
						"conds":this.attr('conds'),
						"value":val
					};

					$.ajax({
						"type":"POST",
						"url":'/api/form_search_query/',
						"data":data_send,
						"dataType":'json',
						"context":{"obj":this, "val":val},
						"success":function(data) {
							this.obj.attr('loading', false);
							this.obj.save_results(this.val, data);
							this.obj.list(data);
						},
						"error":function(a, b, c) {
							v([a,b,c]);
						}
					});
				}
			};


			this.save_results = function(value, data)
			{
				results[value] = data;
			};


			this.list = function(data)
			{
				if (this.el('list') !== null) {
					this.el('list').html('');
				}

				if (data.length > 0) {
					if (this.el('list') === null) {
						this.el('list', $('<ul class="pwf_autocompleter"></ul>'));
					}

					this.el('list').html('');
					var display = this.attr('display');

					for (var i = 0; i < data.length; i++) {
						var li = $('<li></li>');
						var text = [];

						for (var j = 0; j < display.length; j ++) {
							text.push(data[i][display[j]]);
						}

						var t = text.join(' ');
						li.html('<span class="inner">' + t + '</span>');
						v(this.attr('callback_item'));
						li.bind('click', {"ac":this, "label":t, "data":data[i], "extra":this.attr('callback_attrs')}, this.attr('callback_item'));
						this.el('list').append(li);
					}


					v(this.el('list'));
				}

				this.el('container').append(this.el('list'));
			};


			this.hide = function()
			{
				this.el('list').remove();
			};
		};


	var callback_input = function(e)
	{
		if (e.data.obj.el('input').val() != e.data.obj.attr('val')) {
			setTimeout(function(obj) {
				return function() {
					if (obj.el('input').val() != obj.attr('val')) {
						if (!obj.attr('loading')) {
							obj.update(obj.el('input').val());
						}
					}
				};
			}(e.data.obj), 400);
		}
	};


	var callback_item = function(e)
	{
		e.stopPropagation();
		e.data.ac.el('input').val(e.data.label);
		e.data.ac.hide();
	};


	this.init = function()
	{

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
		var id = "location_picker_" + Math.round(Math.random()*1000000);
		return typeof instances[id] === 'undefined' ? id:create_id();
	};


	this.bind = function(el, data)
	{
		var id = get_el_id(el);
		data.id = id;

		if (typeof instances[id] === 'undefined') {
			instances[id] = new class_autocompleter(el, data);
		} else {
			//~ instances[id].update(el);
		}

		return this.get_instance(id);
	};
});
