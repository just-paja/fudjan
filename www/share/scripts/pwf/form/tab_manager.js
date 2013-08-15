pwf.register('tab_manager', function() {

	var
		selectors = ['.tab_group']

	this.init = function()
	{
		this.scan();
		return true;
	};


	this.is_ready = function()
	{
		return $.isReady;
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
		var labels = group.find('.tab_label');

		for (var i = 0; i < labels.length; i++) {
			var label = $(labels[i]);
			if (i == tab_number) {
				label.addClass('active');
			} else {
				label.removeClass('active');
			}
		}

		for (var i = 0; i < tabs.length; i++) {
			var tab = $(tabs[i]);
			i == tab_number ? this.show_tab(tab):this.hide_tab(tab);
		}

		return this;
	};


	this.show_tab = function(tab)
	{
		tab.show().addClass('active');
	};


	this.hide_tab = function(tab)
	{
		tab.hide().removeClass('active');
	};
});
