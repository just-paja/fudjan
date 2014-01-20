pwf.register('location_picker', function()
{
	var
		selectors = [".input-location"],
		instances = {},
		class_location_picker = function(el)
		{
			var el = el;


			this.bind = function()
			{
				var inputs = el.find('input');

				for (var i = 0; i < inputs.length; i++) {
					var input = pwf.jquery(inputs[i]);

					if (input.attr('name').match(/namepwf.jquery/)) {

						pwf.autocompleter.bind(input, {
							"model":"System::Location",
							"filter":["name"],
							"display":["name"],
							"fetch":["addr", "site", "gps"],
							"placeholder":"",
							"callback_item":callback_autocompleter,
							"callback_attrs":{"picker":this}
						});
					}
				}
			};


			this.el = function()
			{
				return el;
			};

		};

	var callback_autocompleter = function(e)
	{
		e.stopPropagation();
		e.data.ac.el('input').val(e.data.label);
		var inputs = e.data.extra.picker.el().find('input');
		var gps_id = e.data.extra.picker.el().find('.input-gps').attr('id');
		var gps = JSON.parse(e.data.data.gps);
		var lng;


		for (var i = 0; i<inputs.length; i++) {
			var input = pwf.jquery(inputs[i]);

			if (input.attr('name').match(/addrpwf.jquery/)) {
				input.val(e.data.data.addr);
			}

			if (input.attr('name').match(/sitepwf.jquery/)) {
				input.val(e.data.data.site);
			}

			if (input.attr('name').match(/latpwf.jquery/)) {
				input.val(gps.lat);
			}

			if (input.attr('name').match(/lngpwf.jquery/)) {
				lng = input;
				input.val(gps.lng);
			}
		}

		if (typeof lng === 'object') {
			var gps_picker = pwf.gps.get_instance(gps_id);
			if (typeof gps_picker === 'object') {
				gps_picker.update();
			}
		}


		e.data.ac.hide();
	};


	this.init = function()
	{
		this.scan();
		return true;
	};


	this.scan = function(container)
	{
		var els;

		if (typeof container === 'undefined') {
			els = pwf.jquery(selectors.join(', '));
		} else {
			els = container.find(selectors.join(', '));
		}

		for (var i = 0; i < els.length; i++) {
			this.bind(pwf.jquery(els[i]));
		}
	};


	this.bind = function(el)
	{
		var id = get_el_id(el);

		if (typeof instances[id] === 'undefined') {
			var inst = new class_location_picker(el);
			instances[el.attr('id')] = inst;
			inst.bind();
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
		var id = "location_picker_" + Math.round(Math.random()*1000000);
		return typeof instances[id] === 'undefined' ? id:create_id();
	};
});
