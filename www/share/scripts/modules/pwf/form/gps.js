pwf.register('gps', function()
{
	var
		loaded = false,
		bind_cache = [],
		selectors = [".input-gps"],
		ready = false,
		self = this,
		instances = {};


	this.init = function()
	{
		if (this.is_ready()) {
			this.scan();
		}

		return ready;
	};


	this.is_ready = function()
	{
		return ready = typeof google === 'object' && typeof google.maps === 'object';
	};


	this.scan = function(container)
	{
		var els;

		if (typeof container === 'undefined') {
			els = $(selectors.join(', '));
		} else {
			els = container.find(selectors.join(', '));
		}

		if (els.length > 0 && !loaded) {
			this.load_gm();
		}

		for (var i = 0; i < els.length; i++) {
			el = $(els[i]);

			if (!loaded) {
				bind_cache.push(el);
			} else this.bind(el);
		}
	};


	this.bind = function(el)
	{
		var id = get_el_id(el);
		var inputs = el.find('input');
		var binder = {
			"container":el,
			"location":el.parents('.input-location').length >= 1
		};

		for (var i = 0; i < inputs.length; i++) {
			var input = $(inputs[i]);

			if (input.attr('name').match(/_lat$/)) {
				binder['lat'] = input;
			}

			if (input.attr('name').match(/_lng$/)) {
				binder['lng'] = input;
			}
		}

		if (typeof binder.lat !== 'undefined' && typeof binder.lng !== 'undefined') {
			binder.lat.parents('.gps-options').first().hide();
			binder.lat.parents('.input-gps').css('display', 'block');
			binder = this.create_map(binder);
		}

		if (binder.location) {
			var loc_inputs = el.parents('.input-location').find('input');
			for (var i = 0; i < loc_inputs.length; i++) {
				var input = $(loc_inputs[i]);

				if (input.attr('name').match(/_addr$/)) {
					binder['addr'] = input;
					binder['addr'].bind('keyup.gps', {"obj":this, "input":binder}, callback_addr_input);
				}
			}
		}

		var pass = {"obj":this, "input":binder, "coord":'lat'};
		binder.lat.bind("keyup.gps", pass, callback_gps_input);
		binder.lng.bind("keyup.gps", pass, callback_gps_input);
		binder.update = function(pass) {
			return function() {
				pass.skip_addr = true;
				callback_gps_input({"data":pass});
			}
		}(pass);
		instances[id] = binder;
		v(id);
	};


	var callback_gps_input = function(e)
	{
		var m = e.data.input.map.gMap('getMarker', 'pointer');
		var pos = self.get_pos(e.data.input);

		e.data.input.map.data('$gmap').setCenter(pos);
		m.setPosition(pos);

		if (typeof e.data.skip_addr === 'undefined') {
			e.data.obj.update_addr(e.data.input);
		}
	};


	var callback_addr_input = function(e)
	{
		e.data.obj.update_by_addr(e.data.input);
	};


	this.get_pos = function(input)
	{
		return new google.maps.LatLng(input.lat.val(), input.lng.val());
	};


	this.update_addr = function(input)
	{
		if (typeof input['addr'] !== 'undefined') {
			var pos = this.get_pos(input);
			this.update_addr_helper(input, {"latLng":pos});
		}
	};


	this.update_by_addr = function(input)
	{
		setTimeout(function(obj, input, val) {
			return function() {
				if (typeof input['addr'] !== 'undefined' && input['addr'].val() === val) {
					obj.update_addr_helper(input, {"address":val});
				}
			}
		}(this, input, input.addr.val()), 500);
	};


	this.update_addr_helper = function(input, geocoder_rq)
	{
		var coder = new google.maps.Geocoder();
		coder.geocode(geocoder_rq, function(binder, rq) {
				return function(res, stat) {
					if (stat === 'OK') {
						if (typeof res[0] !== 'undefined') {
							var pos = res[0].geometry.location;
							var m = input.map.gMap('getMarker', 'pointer');

							m.setPosition(pos);
							input.map.data('$gmap').setCenter(pos);
							input.lat.val(pos.lat());
							input.lng.val(pos.lng());

							if (typeof rq['address'] === 'undefined') {
								input.addr.val(res[0].formatted_address);
							}
						}
					}
				};
		}(input, geocoder_rq));
	};


	this.create_map = function(input)
	{
		input['map'] = $('<div class="map"></div>');
		input['map'].css({"min-height":200});
		input.container.append(input['map']);

		input['map'].gMap({
			"markers":[{
				"latitude":input.lat.val(),
				"longitude":input.lng.val(),
				"draggable":true,
				"key":"pointer"
			}],
			"streetViewControl":false,
			"latitude":input.lat.val(),
			"longitude":input.lng.val(),
			"zoom":(!input.lat.val() || !input.lng.val()) ? 5:14,
		});

		var m = input['map'].gMap('getMarker', 'pointer');
		google.maps.event.addListener(m, 'dragend', function(obj, m, input) { return function(e) {
			var pos = m.getPosition();

			input.lat.val(pos.lat());
			input.lng.val(pos.lng());
			obj.update_addr(input);
		}; }(this, m, input));

		return input;
	};


	this.load_gm = function()
	{
		var url_maps = 'https://www.google.com/jsapi';
		$.getScript(url_maps, function(obj) {
			return function() {
				google.load("maps", "3", {
					"other_params":'sensor=true',
					"callback" : function(obj) {
						return function() {
							$.getScript('/share/scripts/noess:libs/jquery/gmap', function(obj) {
								return function() {
									obj.proceed();
								}
							}(obj));
						};
					}(obj)
				});
			};
		}(this));
	};


	this.proceed = function()
	{
		loaded = true;

		for (var i = 0; i < bind_cache.length; i++) {
			this.bind(bind_cache[i]);
		}

		bind_cache = [];
	};


	this.get_instance = function(id)
	{
		return instances[id];
	};


	var get_el_id = function(el)
	{
		typeof el.attr('id') === 'undefined' && el.attr('id', create_id());
		return el.attr('id');
	};


	var create_id = function()
	{
		var id = "gps_picker_" + Math.round(Math.random()*1000000);
		return typeof instances[id] === 'undefined' ? id:create_id();
	};
});
