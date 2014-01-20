pwf.register('picker_image', function()
{
	var
		self = this,
		selectors = [".widget-image"],
		marker = 'pwf-imagepicker',
		widget = 'pwf_imagepicker';

	this.KEEP   = 1;
	this.UPLOAD = 2;
	this.URL    = 3;
	this.NONE   = 4;


	this.init = function()
	{
		this.scan();
		return true;
	};


	this.is_ready = function()
	{
		return pwf.mi(['jquery']);
	};


	this.scan = function(container)
	{
		var els = typeof container === 'undefined' ? pwf.jquery(selectors.join(', ')):container.find(selectors.join(', '))

		for (var i = 0; i < els.length; i++) {
			var el = pwf.jquery(els[i]);

			if (!el.hasClass(marker)) {
				this.bind(el);
				el.addClass(marker);
			}
		}
	};


	this.bind = function(el)
	{
		var buttons = el.find('.actionkit input[type=radio]');

		var els = {
			"actionkit":[],
			"image":el.find('div.image'),
			"uploader":el.find('input[type=file]').parents('li').first(),
			"url":el.find('input[type=url]').parents('li').first(),
		}

		for (var i = 0 ; i <buttons.length; i++) {
			var button = pwf.jquery(buttons[i]);
			button.bind('change', els, callback_button_change);

			if (button.prop('checked')) {
				callback_change(button.val(), els);
			}

			els.actionkit.push(button);
		}
	};


	var callback_button_change = function(e)
	{
		var button = pwf.jquery(this);

		e.preventDefault();
		e.stopPropagation();

		if (button.prop('checked')) {
			callback_change(button.val(), e.data);
		}
	};


	var callback_change = function(type, els)
	{
		if (type == pwf.picker_image.KEEP) {
			els.image.show();
			els.uploader.hide();
			els.url.hide();
		} else if (type == pwf.picker_image.UPLOAD) {
			els.uploader.show();
			els.image.hide();
			els.url.hide();
		} else if (type == pwf.picker_image.NONE) {
			els.uploader.hide();
			els.image.hide();
			els.url.hide();
		} else if (type == pwf.picker_image.URL) {
			els.uploader.hide();
			els.image.hide();
			els.url.show();
		}
	};





});
