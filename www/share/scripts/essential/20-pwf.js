var pwf = function()
{
	return new function()
	{
		var self = this;
		this.module_status = {};

		var init = function() {
			return true;
		};

		this.register = function(name, module)
		{
			if (typeof module == 'function' && typeof this[name] == 'undefined') {
				this[name] = new module();
				if (typeof this[name].init == 'function') {
					this.module_status[name] = this[name].init();
				}
			}
		};

		this.ready = init();
	}();
};
