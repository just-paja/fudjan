var pwf = function()
{
	return new function()
	{
		var self = this;
		this.module_status = {};
		var init_later = [];


		var init = function() {
			return true;
		};


		this.register = function(name, module)
		{
			if (typeof module == 'function' && typeof this[name] == 'undefined') {
				this[name] = new module();
				if (typeof this[name].init == 'function') {
					if (!(this.module_status[name] = this[name].init())) {
						init_later.push(name);
					}
				}
			}
		};


		this.init_remaining = function()
		{
			for (var i = 0; i<init_later.length; i++) {
				if (init_later[i] !== null) {
					if (this.module_status[init_later[i]] = this[init_later[i]].init()) {
						init_later[i] = null;
					}
				}
			}
		};

		this.ready = init();
	};
}();


$(function() {
	pwf.init_remaining();
});
