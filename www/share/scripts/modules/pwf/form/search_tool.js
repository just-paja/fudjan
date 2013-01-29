pwf.register('search_tool', function()
{
	var
		instances = {},
		search_tool_helper = function(param_container, param_data)
		{
			var
				container = param_container,
				attrs = param_data,
				els = {},
				last_val = null,
				results = {};

			this.create = function()
			{
				if (typeof els.input === 'undefined') {
					container.find('span.data').remove();
					this.el('input',  $('<input type="text" name="search_tool_input" autocomplete="off">'));
					this.el('result', $('<ul class="search_tool_result"></ul>'));
					this.bind();

					if (this.attr('placeholder') !== null) {
						this.el('input').attr('placeholder', this.attr('placeholder'));
					}

					container.append([
						this.el('input'),
						this.el('result'),
					]);
				}
			};


			this.update = function(el_container)
			{
				container = el_container;
				container.append([this.el('input'), this.el('input')]);
				this.bind();
			};


			this.bind = function()
			{
				this.el('input')
					.unbind('keydown.search_tool')
					.unbind('keyup.search_tool')
					.unbind('change.search_tool')
					.bind('keydown.search_tool', {"obj":this}, function(e) {
						if (e.which === 13) {
							e.preventDefault();
						}
					})
					.bind('keyup.search_tool', {"obj":this}, function(e) { e.data.obj.check_val(); })
					.bind('change.search_tool', {"obj":this}, function(e) { e.data.obj.check_val(); });

			};


			this.check_val = function()
			{
				var val = this.el('input').val();
				if (val.length >= 3 && val !== this.get_last_val()) {
					setTimeout(function(obj, val) {
						return function() {
							if (val == obj.el('input').val()) {
								last_val = val;
								obj.refresh();
							}
						};
					}(this, val), 300);
				}
			};


			this.get_last_val = function()
			{
				return last_val;
			};


			this.refresh = function()
			{
				var val = this.el('input').val();

				if (typeof results[val] === 'undefined') {
					var data = attrs;
					data.value = val;
					results[val] = [];

					$.ajax({
						"url":'/api/form_search_query/',
						"data":data,
						"dataType":'json',
						"context":{ "obj":this, "val":val},
						"success":function(data) {
							this.obj.save_results(this.val, data);
							this.obj.update_list(data);
						},
						"error":function(a, b, c) {
							v([a,b,c]);
						}
					});
				} else this.update_list(results[val]);
			};


			this.save_results = function(value, data)
			{
				results[value] = data;
				return this;
			};


			this.update_list = function(data)
			{
				this.el('result').html('');

				for (var i = 0; i<data.length; i++) {
					var
						label_id = this.attr('name')+'_' + data[i].id,
						elli = $(
							'<li>'
								+ '<div class="input-container"><input type="checkbox" name="'+this.attr('name')+'[]" id="' + label_id + '" value="' + data[i].id + '"></div>'
								+ '<label for="' + label_id + '" class="label-right">'+this.get_data_label(data[i])+'</label>' +
							'</li>'
						);

					this.el('result').append(elli);
				}
			};


			this.get_data_label = function(item)
			{
				var
					display = this.attr('display'),
					result = [];

				for (var i = 0; i < display.length; i++) {
					result.push(item[display[i]]);
				}

				return result.join(' ');
			};


			this.attr = function(name, value)
			{
				if (typeof value !== 'undefined') {
					attrs[name] = value;
				}

				if (typeof attrs[name] !== 'undefined') {
					return attrs[name];
				} else throw "Attribute '" + name + "' does not exist for search tool '" + attrs.name + "'";
			};


			this.el = function(name, value)
			{
				if (typeof value !== 'undefined') {
					els[name] = value;
				}

				return typeof els[name] === 'undefined' ? null:els[name];
			};
		};


	this.init = function(container)
	{
		var tools = typeof container === 'undefined' ? $('form .search_tool'):container.find('.search_tool');

		for (var i = 0; i<tools.length; i++) {
			var container = $(tools[i]);
			var data_container;

			if ((data_container = container.find('span.data')).length === 1) {
				create_instance(container, JSON.parse(data_container.html()));
			}
		}
	};


	var create_instance = function(container, data)
	{
		if (typeof instances[data.name] === 'undefined') {
			instances[data.name] = new search_tool_helper(container, data);
			instances[data.name].create();
		} else {
			instances[data.name].update(container);
		}
	};
});
