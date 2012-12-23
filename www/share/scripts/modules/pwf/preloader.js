pwf.register('preloader', function() {

	this.STATUS_WAIT   = 0;
	this.STATUS_READY  = 1;
	this.STATUS_FAILED = 2;

	var
		ready = false,
		loaded = false,
		self = this,
		codecs = {
			"ogv":'video/ogg; codecs="theora, vorbis"',
			"webm":'video/webm; codecs="vorbis, vp8"',
			"mp4":'video/mp4; codecs="avc1.42E01E, mp4a.40.2"'
		};


	this.init = function()
	{
		return ready = true;
	};


	this.add_resource = function(obj, type, src)
	{
		if (src !== null && src.length > 0) {
			var filter = false;

			for (var l = 0; l < obj.resources.length; l++) {
				if (obj.resources[l].src === src ) {
					filter = true;
					break;
				}
			}

			if (!filter) {
				var id = obj.resources.length;
				obj.resources.push({
					"id":id,
					"type":type,
					"src":src,
					"status":self.STATUS_WAIT
				});
			}
		}
	};


	this.preload_with = function(obj)
	{
		this.reset(obj);
		var resources = obj.gather_resources();
		loaded = false;

		if (resources.length > 0 ) {
			for (var i = 0; i<resources.length; i++) {
				preload_resource(obj, resources[i]);
			}
		} else {
			this.ready(obj);
		}
	};


	this.include_images_from_styles = function(object)
	{
		var styles = document.styleSheets,
			style_props = ['background', 'backgroundImage'];

		this.resources = [];

		// Gather all images from styles
		for (var i = 0; i<styles.length; i++) {
			var style_list = typeof document.styleSheets[i].cssRules === 'undefined' ?
				document.styleSheets[i].rules:document.styleSheets[i].cssRules;

			for (var j = 0; j < style_list.length; j++) {
				var s = style_list[j].style;

				if (typeof s !== 'undefined') {
					for (var k = 0; k < style_props.length; k++) {
						if (typeof s[style_props[k]] === 'string' && s[style_props[k]].indexOf('url') === 0) {
							var src = s[style_props[k]].split('(')[1].split(')')[0].replace(/[\'\"]/g, '');
							if (src.indexOf('data:') !== 0) {
								this.add_resource(object, 'image', src);
							}
						}
					}
				}
			}
		}
	};


	var preload_resource = function(obj, res)
	{
		if (res.status !== self.STATUS_READY) {
			var helper = get_helper_from_res_type(res.type);

			if (res.type === 'image') {
				helper.onload = function(lead_obj, obj, helper, res) {
					return function() {
						lead_obj.update_status(obj, res.id, self.STATUS_READY);
					};
				}(self, obj, helper, res);
			}

			if (typeof res.src === 'object') {
				if (helper.canPlayType === 'function') {
					for (var i = 0; i<res.src.length; i++) {
						var format = '';

						for (var form in codecs) {
							if (res.src[i].indexOf('.' + form)) {
								format = codecs[form];
								break;
							}
						}

						if (helper.canPlayType(format)) {
							helper.src = res.src[i];
						}
					}

					if (!helper.src) {
						self.update_status(obj, res.id, self.STATUS_FAILED);
						return;
					}
				} else {
					self.update_status(obj, res.id, self.STATUS_FAILED);
					return;
				}
			} else {
				helper.src = res.src;
			}

			if (res.type === 'video') {
				check_video_status(self, obj, helper, res);
			}
		}
	};


	var check_video_status = function(lead_obj, obj, helper, res)
	{
		if (helper.readyState === 0) {
			setTimeout(function() { return function() { check_video_status(lead_obj, obj, helper, res); }; }(lead_obj, obj, helper, res), 50);
		} else {
			lead_obj.update_status(obj, res.id, self.STATUS_READY);
		}
	};


	var check_page_status = function(lead_obj, obj, page, res)
	{
		if (!page.attr('loaded')) {
			setTimeout(function() { return function() { check_page_status(lead_obj, obj, page, res); }; }(lead_obj, obj, page, res), 50);
		} else {
			lead_obj.update_status(obj, res.id, self.STATUS_READY);
		}
	};


	var get_helper_from_res_type = function(type)
	{
		if (type === 'image') {
			return new Image;
		}

		if (type === 'video') {
			if (typeof Video === 'undefined') {
				return document.createElement('video');
			} else {
				return new Video();
			}
		}
	};


	this.reset = function(obj)
	{
		obj.el('label_progress').html(0 + '%');
		obj.el('progress').css({"width": 0 + '%'});
	};


	this.update_status = function(obj, id, status)
	{
		var ready_count = 0;

		for (var i = 0; i < obj.resources.length; i++) {
			if (obj.resources[i].id === id) {
				obj.resources[i].status = status;
			}

			// Count ready, ignore failed
			if (obj.resources[i].status === this.STATUS_READY || obj.resources[i].status === this.STATUS_FAILED) {
				ready_count++;
			}
		}

		var percentil = Math.round(ready_count/obj.resources.length * 100);
		obj.el('label_progress').html(percentil + '%');
		obj.el('progress').css({"width": percentil + '%'});

		if (percentil === 100) {
			loaded = true;
			this.ready(obj)
		}
	};


	this.ready = function(obj)
	{
		if (typeof obj.ready === 'function') {
			obj.ready();
		} else throw "Preloader has finished loading but there is no function 'ready' to call back.";
	};
});
