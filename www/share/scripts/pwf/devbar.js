pwf.register('devbar', function()
{
	var ready = false;

	this.init = function()
	{
		if (ready = this.is_ready()) {
			this.find_and_bind();
		}

		return ready;
	};


	this.is_ready = function()
	{
		return $('#devbar').length >= 1;
	};


	this.find_and_bind = function()
	{
		var cont = $('#devbar');
		var open = $('<span class="devbar open"></span>');
		var panels = cont.find('.info .panel');
		var context = {"cont":cont};

		cont.find('.status-dump .close').bind('click', context, callback_hide_bar);

		for (var i = 0; i < panels.length; i++) {
			var panel = $(panels[i]);
			var id = panel.attr('id');
			var menu = cont.find('.status-dump .bar-menu a.panel-'+id);
			var context_panel = {"panel":panel, "menu":menu};

			panel.find('.close').bind('click', context_panel, callback_hide_panel);
			menu.bind('click', context_panel, callback_show_panel);
		}

		cont.append(open);
		open.bind('click', context, callback_show_bar);

		if (pwf.storage.get('devbar') !== 'hidden') {
			cont.find('.status-dump').show();
			open.hide();
		}
	};


	var all_panels = function()
	{
		return $('#devbar .info .panel');
	};


	var all_menu_items = function()
	{
		return $('#devbar .bar-menu li a');
	};


	var callback_show_bar = function(e)
	{
		e.preventDefault();
		e.stopPropagation();

		$('#devbar .status-dump, #devbar .info').show();
		$('#devbar .open').hide();
		pwf.storage.store('devbar', 'shown');
	};


	var callback_hide_bar = function(e)
	{
		e.preventDefault();
		e.stopPropagation();

		all_panels().hide();
		all_menu_items().removeClass('selected');

		$('#devbar .status-dump, #devbar .info').hide();
		$('#devbar .open').show();
		pwf.storage.store('devbar', 'hidden');
	};


	var callback_show_panel = function(e)
	{
		e.preventDefault();
		e.stopPropagation();

		all_panels().hide();
		all_menu_items().removeClass('selected');

		e.data.panel.show();
		e.data.menu.addClass('selected');
	};


	var callback_hide_panel = function(e)
	{
		e.preventDefault();
		e.stopPropagation();

		e.data.panel.hide();
		e.data.menu.removeClass('selected');
	};
});
