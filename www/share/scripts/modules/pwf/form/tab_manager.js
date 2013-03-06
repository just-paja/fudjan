pwf.register('tab_manager', function() {

	var
		ready = false,
		selectors = ['.tab_group'],
		instances = [];

	this.init = function()
	{
		if (ready = this.is_ready()) {
			this.scan();
		}

		return ready;
	};


	this.is_ready = function()
	{
		return $('body').length >= 1;
	};


	this.scan = function(container)
	{
		if (typeof container === 'undefined') {
			els = $(selectors.join(', '));
		} else {
			els = container.find(selectors.join(', '));
		}

		for (var i = 0; i < els.length; i++) {
			this.bind_to($(els[i]));
		}

		return this;
	};


	this.bind_to = function(el)
	{
		var binder = {
			"label_container":$('<div class="tab_labels"></div>'),
			"tabs":{

			}
		};

		el.prepend(binder.label_container);
		var tabs = el.find('.tab');

		for (var i = 0; i < tabs.length; i++) {
			var tab = $(tabs[i]);
			var label = tab.find('.tab_label');

			label.html('<div class="inner">' + label.html() + '</div>');
			binder.label_container.append(label);

			label.bind('click', {"manager":this, "group":el, "tab_number":i}, callback_tab_label);

			if (i === 0) {
				this.show(el, i);
			}
		}
	};


	var callback_tab_label = function(e)
	{
		return e.data.manager.show(e.data.group, e.data.tab_number);
	};


	this.show = function(group, tab_number)
	{
		var tabs = group.find('.tab');

		for (var i = 0; i < tabs.length; i++) {
			var tab = $(tabs[i]);

			if (i == tab_number) {
				tab.show();
			} else {
				this.hide(tab);
			}
		}

		return this;
	};


	this.hide = function(tab)
	{
		tab.hide();
	};
});
