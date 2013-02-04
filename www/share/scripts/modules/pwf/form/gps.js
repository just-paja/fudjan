pwf.register('gps', function()
{
	var
		loaded = false,
		bind_cache = [],
		selectors = [".input-gps"],
		ready = false;


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
		var inputs = el.find('input');
		var binder = {
			"container":el
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
			binder = this.create_map(binder);
		}
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
			"latitude":input.lat.val(),
			"longitude":input.lng.val(),
			"zoom":14,
		});

		var m = input['map'].gMap('getMarker', 'pointer');
		google.maps.event.addListener(m, 'dragend', function(m, lat, lng) { return function(e) {
			var pos = m.getPosition();
			lat.val(pos.lat());
			lng.val(pos.lng());
		}; }(m, input.lat, input.lng));

		return input;
	};


	this.load_gm = function()
	{
		var url_maps = 'https://maps.googleapis.com/maps/api/js?sensor=true';
		$.getScript(url, function() {
			google.load("maps", "3", {
				"other_params":'sensor=true',
				"callback" : function() {
					$.getScript('https://raw.github.com/fridek/gmap/master/jquery.gmap.min.js', function() {
						pwf.gps.proceed();
					});
				}
			});
		});
	};


	this.proceed = function()
	{
		loaded = true;

		for (var i = 0; i < bind_cache.length; i++) {
			this.bind(bind_cache[i]);
		}

		bind_cache = [];
	};
});
