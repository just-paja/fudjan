yawf.register('storage', function()
{
	var p3p_needed = false;
	var p3p_available = false;
	var driver = "locCookie";
	var utime = null;
	var ready = false;
	var storage = this;


	/* Init is autorun on any of get(), store() or drop()
	 * @return void
	 */
	this. init = function() {
		this.p3p_available = is_browser_p3p_compatible();
		this.p3p_needed    = typeof iframe_external != 'undefined' && iframe_external;
		this.utime = Math.round(new Date().getTime() / 1000);

		drivers.locStorage.available  = typeof localStorage != 'undefined';
		drivers.sessStorage.available = typeof sessionStorage != 'undefined';
		drivers.locCookie.available = !this.p3p_needed || (this.p3p_needed && this.p3p_available);

		// prefer localStorage
		this.driver = drivers.locStorage.available ? 'locStorage':(drivers.locCookie.available ? 'locCookie':'remoteCookie');
		return true;
	};


	var drivers = {

		"locStorage": {
			"id":1,
			"available":false,
			"ttl_key":"data_ttl",

			get: function(key) {
				try {
					if (key == this.ttl_key || this.check_ttl(key))
						return localStorage.getItem(key);
				} catch (e) {}

				return null;
			},


			/* Save value in localStorage
			 * @param string key   identifier
			 * @param string value
			 * return void;
			 */
			store: function(key, value, ttl) {
				try {
					localStorage.setItem(key, value);
					(typeof ttl != 'undefined') && (key != this.ttl_key) && this.store_ttl(key, ttl);
				} catch (e) {}
			},


			/* Delete value from localStorage
			 * @param string key   identifier
			 * @return void
			 */
			drop: function(key) {
				try {
					localStorage.removeItem(key);
				} catch(e) {}
			},


			/* Save ttl of information
			 */
			store_ttl: function(key, ttl) {
				var date = new Date();
				var ttl_arr = {};

				(ttl_str = this.get(this.ttl_key)) && (ttl_arr = JSON.parse(ttl_str));
				ttl_arr[key] = ttl + Math.floor(date.getTime()/1000);
				this.store(this.ttl_key, JSON.stringify(ttl_arr));
			},


			/* Check if the data are valid
			 */
			check_ttl: function(key) {
				var ttl_arr = {};
				(ttl_str = this.get(this.ttl_key)) && (ttl_arr = JSON.parse(ttl_str));

				return true;
				return (typeof ttl_arr == 'undefined') || (typeof ttl_arr[key] == 'undefined') || (ttl_arr[key] > (new Date).getTime()/1000);
			}
		},


		"locCookie": {
			"id":2,
			"available":true,

			/* Get cookie value
			 * @param	string key identifier
			 * @param	object origin object to save with
			 * @return	mixed
			 */
			get: function(key, origin) {
				var origin    = (typeof origin == 'undefined' || origin == 'self' || !this.p3p_needed) ? document:origin;
				var force_p3p = origin != document || this.p3p_needed;
				var value     = null;

				if (!force_p3p || (force_p3p && this.p3p_available)) {
					if (origin.cookie.length > 0) {
						c_start = origin.cookie.indexOf(key + "=");

						if (c_start != -1) {
							c_start = c_start + key.length+1;
							c_end = origin.cookie.indexOf(";", c_start);

							if (c_end == -1)
								c_end = origin.cookie.length;

							value = unescape(origin.cookie.substring(c_start, c_end));
						}
					}
				} else throw "Browser will deny reading cookies";

				return value;
			},


			/* Save value using cookies
			 * @param string key identifier
			 * @param string value value
			 * @param int ttl time to live in days
			 * @param object origin object to save with
			 * @param string path path to save with
			 * @return void
			 */
			store: function(key, value, ttl, origin, path) {
				var origin    = (typeof origin == 'undefined' || origin == 'self' || !this.p3p_needed) ? document:origin;
				var force_p3p = origin != document || this.p3p_needed;
				var cookie = '';
				var exdate = new Date();

				if(!force_p3p || (force_p3p && this.p3p_available)) {
					(value === '' || value === null) && (ttl = -86400);
					(ttl === null) && (ttl = 365*86400);
					exdate.setTime(exdate.getTime() + ttl * 1000);

					cookie = key + "=" + escape(value) + ((ttl == null) ? "" : ";expires=" + exdate.toGMTString());

					if(typeof path != 'undefined' && path != null)
						cookie += ';path=' + path;

					if(typeof domain != 'undefined' && domain != null)
						cookie += ';domain=' + domain;

					origin.cookie = cookie;
				} else throw "Browser will deny storing cookies";
			},


			/* Destroy cookie
			 * @param string key
			 */
			drop: function(key) {
				this.store(key, '', -5000);
			}
		},


		"sessStorage": {
			"id":4,
			"available":false,
			"ttl_key":"data_ttl",

			/* Get value from html5 session storage
			 * @param string key identifier
			 * @return mixed
			 */
			get: function(key, origin) {
				if(this.available) {
					try {
						if (key == this.ttl_key || this.check_ttl(key)) {
							return sessionStorage.getItem(key);
						}
					} catch(e) {}
				} else {
					return storage.drivers.locCookie.get(key, origin);
				}

				return null;
			},


			store: function(key, value, ttl, origin, path) {
				if(this.available) {
					try {
						sessionStorage.setItem(key, value);
						typeof ttl != 'undefined' && ttl !== null && this.store_ttl(key, ttl);
					} catch(e) {}
				} else {
					storage.drivers.locCookie.store(key, value, ttl, origin, path);
				}
			},


			/* Destroy value in session storage
			 */
			drop: function(key) {
				try {
					localStorage.removeItem(key);
				} catch (e) {}
			},


			/* Save ttl of information
			 */
			store_ttl: function(key, ttl) {
				var date = new Date();
				var ttl_arr = {};

				(ttl_str = this.get(this.ttl_key)) && (ttl_arr = JSON.parse(ttl_str));
				ttl_arr[key] = ttl + Math.floor(date.getTime()/1000);
				this.store(this.ttl_key, JSON.stringify(ttl_arr));
			},


			/* Check if the data are valid
			 */
			check_ttl: function(key) {
				var ttl_arr = {};
				(ttl_str = this.get(this.ttl_key)) && (ttl_arr = JSON.parse(ttl_str));

				return (typeof ttl_arr == 'undefined') || (typeof ttl_arr[key] == 'undefined') ||  (ttl_arr[key] > (new Date).getTime()/1000);
			}
		}
	};


	/* Do we have P3P useable browser?
	 * @return boolean
	 */
	var is_browser_p3p_compatible = function() {
		if (navigator.userAgent && navigator.userAgent.indexOf("MSIE") !== -1)
		{
			sub = navigator.appVersion.substr(navigator.appVersion.indexOf("MSIE")+5);
			ver = parseInt(sub.substr(0, sub.indexOf('.')));
			return ver >= 8;
		}

		return true;
	};


	/* Store value within yStorage. Can be chained
	 * @param	string key identifier
	 * @param	string value value
	 * @param	int ttl [null] time to live in days
	 * @param	object origin [document] object to save with (used by cookies)
	 * @param	string domain [null] domain to save with (used by cookies)
	 * @return	yStorage
	 */
	this.store = function(key, value, ttl, origin, path) {
		!this.ready && this.init();

		var ttl = (typeof ttl == 'undefined' ? null:ttl);
		var path = (typeof path == 'undefined' ? null:path);

		drivers[this.driver].store(key, value, ttl, origin, path);
		return this;
	};


	/* Get value
	 * @param	string key                identifier
	 * @param object origin [document]  object to save with (used by cookies)
	 * @return string
	 */
	this.get = function(key, origin) {
		!this.ready && this.init();
		return drivers[this.driver].get(key, origin);
	};


	/* Try to store a value by custom driver priorities
	 * @param array driver_suggestions array of driver names
	 */
	this.store_in = function(driver_suggestions, key, value, ttl, origin, path) {
		for (i in driver_suggestions)
		{
			var dr = drivers[driver_suggestions[i]];
			if (dr.available) return dr.store(key, value, ttl, origin, path);
		}
	};


	/* Try to get a value by custom driver priorities
	 * @param array driver_suggestions array of driver names
	 */
	this.get_by = function(driver_suggestions, key, origin) {
		for (i in driver_suggestions)
		{
			var dr = drivers[driver_suggestions[i]];
			if (dr.available) return dr.get(key, origin);
		}
	};


	/* Destroy key
	 * @return this;
	 */
	this.drop = function(key) {
		!this.ready && this.init();
		drivers[this.driver].drop(key);
		return this;
	};
});
