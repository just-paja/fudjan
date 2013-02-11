pwf.register('location_picker', function()
{
	var
		selectors = [".input-location"],
		instances = {},
		class_location_picker = function(el)
		{

		};


	this.init = function()
	{
		this.scan();
	};


	this.scan = function(container)
	{
		var els;

		if (typeof container === 'undefined') {
			els = $(selectors.join(', '));
		} else {
			els = container.find(selectors.join(', '));
		}

		for (var i = 0; i < els.length; i++) {
			this.bind($(els[i]));
		}
	};


	this.bind = function(el)
	{
		var id = get_el_id(el);

		if (typeof instances[id] === 'undefined') {
			var inst = new class_location_picker(el);
			instances[el.attr('id')] = inst;
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
		var id = "datetime_picker_" + Math.round(Math.random()*1000000);
		return typeof instances[id] === 'undefined' ? id:create_id();
	};
});
