var pwf = function()
{
	return new function()
	{
		/** Status of all registered modules */
		this.module_status = {};

		var
			/** Reference to this object accessible from inside this function */
			self = this,

			/** Queue for modules that are registered but could not be initialized yet */
			init_later = [],

			/** List of modules that have std scan method */
			init_scan  = [],

			/** Queue for callbacks that will be run on run_callbacks */
			callbacks  = [];


		/** Register function under name as a module under pwf
		 *
		 * Example:
		 * pwf.register('some_module', function() {
		 *   this.say_hello = function() { alert('hello'); };
		 * });
		 *
		 * @param string   name
		 * @param function module
		 * @return void
		 */
		this.register = function(name, module)
		{
			if (typeof module == 'function' && typeof this[name] == 'undefined') {
				this[name] = new module();

				if (typeof this[name].init == 'function') {
					if (!this.module_status[name]) {
						if (typeof this[name].is_ready == 'function') {
							if (this[name].is_ready()) {
								if (!(this.module_status[name] = this[name].init())) {
									init_later(name);
								}
							} else init_later.push(name);
						} else {
							if (this[name].init()) {
								if (!(this.module_status[name] = this[name].init())) {
									init_later.push(name);
								}
							} else init_later.push(name);
						}

					} else {
						this.run_callbacks();
					}
				}

				if (typeof this[name].scan == 'function') {
					init_scan.push(name);
				}
			}
		};


		/** Check if dependencies of all modules that were not initialized yet are met and if so, initialize them
		 * @return void
		 */
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


		/** Perform a scan of element for all modules with std scan method. Standard scan method takes one not mandatory argument as jQuery object to search and binds its functions to elements found by it's selector.
		 * @see /share/scripts/pwf/form/date_picker.js
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


		/** Plain getter for scan_list
		 * @return Object
		 */
		this.get_scan_list = function()
		{
			return init_scan;
		};


		/** Run lambda callback when listed components are ready
		 * @param Object   components List (array) of component names (string)
		 * @param function lambda     Callback to call when ready
		 * @param Object   args       Arguments to pass to lambda
		 * @return void
		 */
		this.when_ready = function(components, lambda, args)
		{
			if (this.components_ready(components)) {
				lambda(args);
			} else callbacks.push([components, lambda, args]);
		};


		/** Check if dependencies for callbacks from when_ready() are met and run them if so.
		 * @return void
		 */
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


		/** Dear sir, are all components of this list ready?
		 * @param Object components List (array) of component names
		 * @return bool
		 */
		this.components_ready = function(components)
		{
			var ready = false;

			for (var comp_i = 0 in components) {
				if (!(ready = this.component_ready(components[comp_i]))) break;
			}

			return ready;
		};


		/** Dear sir, is this component ready to use?
		 * @param string component Component name
		 * @return bool
		 */
		this.component_ready = function(component)
		{
			return typeof this[component] != 'undefined' && this[component].is_ready();
		};
	};
}();


$(function() { pwf.init_remaining(); });
