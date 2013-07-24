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
		 * @param bool     create_instance Create instance of the function
		 * @return void
		 */
		this.register = function(name, module, create_instance)
		{
			var create_instance = typeof create_instance === 'undefined' ? true:!!create_instance;

			if (typeof module == 'function' && typeof this[name] == 'undefined') {
				if (create_instance) {
					this[name] = new module();
				} else {
					this[name] = module;
				}

				this.module_status[name] = false;

				if (this.component_ready(name)) {
					this.init(name);
				} else {
					init_later.push(name);
				}

				if (typeof this[name].scan == 'function') {
					init_scan.push(name);
				}
			}
		};


		this.init = function(component)
		{
			if (typeof this[component].init == 'function') {
				if (!this.module_status[component]) {
					this.module_status[component] = this[component].init();

					if (!this.module_status[component]) {
						throw 'Init of module ' + component + ' failed. It must return true!';
					}
				}
			} else {
				this.module_status[component] = true;
			}

			if (component == 'jquery') {
				this[component](function(obj) { 
					return function() {
						obj.init_remaining(); 
					};
				}(this));
			}

			this.run_callbacks();
			this.init_remaining();
		};


		/** Check if dependencies of all modules that were not initialized yet are met and if so, initialize them
		 * @return void
		 */
		this.init_remaining = function()
		{
			for (var i = 0; i < init_later.length; i++) {
				var component = init_later[i];
				var ready = typeof this[component].is_ready != 'function' || this[component].is_ready();
				var initialized = this.module_status[component];

				if (ready && !initialized) {
					this.remove_late_init(component);
					this.init(component);
					break;
				}
			}
		};


		this.remove_late_init = function(name)
		{
			var init_later_tmp = [];

			for (var i = 0; i < init_later.length; i++) {
				if (init_later[i] != name) {
					init_later_tmp.push(init_later[i]);
				}
			}

			init_later = init_later_tmp;
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
			var ready;

			for (var comp_i = 0; comp_i < components.length; comp_i++) {
				ready = this.component_ready(components[comp_i]);
				if (!ready) break;
			}

			return ready;
		};


		/** Dear sir, is this component ready to use?
		 * @param string component Component name
		 * @return bool
		 */
		this.component_ready = function(component)
		{
			return this.component_exists(component) && (typeof this[component].is_ready !== 'function' || this[component].is_ready());
		};


		/** Dear sir, does component carrying this noble name exist?
		 * @param string component Component name
		 * @return bool
		 */
		this.component_exists = function(component)
		{
			return typeof this[component] != 'undefined';
		};


		this.component_initialized = function(component)
		{
			return typeof this.module_status[component] != 'undefined' && this.module_status[component];
		};


		this.components_initialized = function(components)
		{
			var ready;

			for (var comp_i = 0; comp_i < components.length; comp_i++) {
				ready = this.component_initialized(components[comp_i]);
				if (!ready) break;
			}

			return ready;
		};
	};
}();
