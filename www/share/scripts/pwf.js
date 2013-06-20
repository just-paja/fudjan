var pwf = function()
{
	return new function()
	{
		this.module_status = {};
		var
			self = this,
			init_later = [],
			init_scan  = [],
			callbacks  = [];


		this.register = function(name, module)
		{
			if (typeof module == 'function' && typeof this[name] == 'undefined') {
				this[name] = new module();

				if (typeof this[name].init == 'function') {
					if (!(this.module_status[name] = this[name].init())) {
						init_later.push(name);
					} else {
						this.run_callbacks();
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
						this.run_callbacks();
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


		this.when_ready = function(components, lambda, args)
		{
			if (this.components_ready(components)) {
				lambda(args);
			} else callbacks.push([components, lambda, args]);
		};


		this.run_callbacks = function()
		{
			for (var i = 0; i < callbacks.length; i++) {
				var cb = callbacks[i];

				if (cb !== null && this.components_ready(cb[0])) {
					cb[1](typeof cb[2] == 'undefined' ? null:cb[2]);
					callbacks[i] = null;
				}
			}
		};


		this.components_ready = function(components)
		{
			var ready = false;

			for (var comp_i = 0 in components) {
				if (!(ready = this.component_ready(components[comp_i]))) break;
			}

			return ready;
		};


		this.component_ready = function(component)
		{
			return typeof this[component] != 'undefined' && this[component].is_ready();
		};
	};
}();


$(function() { pwf.init_remaining(); });
