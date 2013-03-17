pwf.register('rte', function() {

	var ready = false;
	var selectors = ['textarea'];
	var editor = null;

	this.init = function()
	{
		if (ready = this.is_ready()) {
			if (editor === null) {
				editor = new Proper();
			}

			this.scan();
		}

		return ready;
	};


	this.is_ready = function()
	{
		return $('body').length >= 1;
	};


	this.scan = function(el)
	{
		if (typeof el === 'undefined') {
			var els = $(get_selectors());
		} else {
			var els = el.find(get_selectors());
		}

		for (var i = 0; i<els.length; i++) {
			this.bind($(els[i]));
		}
	};


	this.bind = function(el)
	{
		var parent = el.parent();
		var container = $('<div class="rte_container"></div>');
		var controls = $('<div class="rte_controls"></div>');
		var content = $('<div class="rte_content"></div>');
		var html = el.html();

		content.html(html.length ? html:'Enter text');
		container.append(el);
		container.append(controls);
		container.append(content);
		parent.append(container);
		el.hide();

		content.bind('click', {"txt":el, "controls":controls}, callback_activate);
		//~ content.bind('focusout', {"txt":el, "controls":controls}, function(e) {
			//~ editor.deactivate($(this));
		//~ });
	};


	var callback_activate = function(e)
	{
		e.stopPropagation();

		editor.activate($(this),  {
			placeholder: 'Enter Text',
			controlsTarget: e.data.controls,
			codeFontFamily: 'Monaco, Consolas, "Lucida Console", monospace'
		});

		// Update node when editor commands are applied
		editor.bind('changed', function(txt) {
			return function() {
				txt.text(editor.content());
			};
		}(e.data.txt));
	};


	var get_selectors = function()
	{
		return selectors.join(', ');
	};


	this.deactivate = function()
	{
		return editor.deactivate();
	};
});
