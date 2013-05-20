var pwf = function()
{
	return new function()
	{
		this.module_status = {};
		var
			self = this,
			init_later = [],
			init_scan  = [];


		this.register = function(name, module)
		{
			if (typeof module == 'function' && typeof this[name] == 'undefined') {
				this[name] = new module();

				if (typeof this[name].init == 'function') {
					if (!(this.module_status[name] = this[name].init())) {
						init_later.push(name);
					}
				}

				if (typeof this[name].scan == 'function') {
					init_scan.push(name);
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


		/** Perform a scan of element for all modules with scan method
		 * @param jQuery el
		 */
		this.scan = function(el)
		{
			if (typeof el === 'undefined') {
				throw 'You must pass jQuery object referencing HTML element.';
			} else {
				for (var i = 0; i < init_scan.length; i++) {
					this[init_scan[i]].scan(el);
				}
			}
		};


		this.get_scan_list = function()
		{
			return init_scan;
		};
	};
}();


$(function() { pwf.init_remaining(); });
