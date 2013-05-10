var browser = function()
{
	return new function()
	{
		var
			browsers = [
				{"string":navigator.userAgent, "search":"Chrome", "ident": "chrome", "name":"Google Chrome"},
				{"string":navigator.userAgent, "search": "OmniWeb", "versionSearch": "OmniWeb/", "ident": "omniweb", "name":"OmniWeb"},
				{"string":navigator.vendor, "search":"Apple", "ident": "safari", "versionSearch": "Version", "name":"Safari"},
				{"prop": window.opera, "ident": "opera", "versionSearch": "Version", "name":"Opera"},
				{"string":navigator.vendor, "search":"iCab", "ident":"icab", "name":"iCab"},
				{"string": navigator.vendor, "search": "KDE", "ident": "konqueror", "name":"Konqueror"},
				{"string": navigator.userAgent, "search": "Firefox", "ident": "firefox", "name":"Mozilla Firefox"},
				{"string": navigator.vendor, "search": "Camino", "ident": "camino", "name":"Camino"},
				{"string": navigator.userAgent, "search": "Netscape", "ident": "netscape", "name":"Netscape"},
				{"string": navigator.userAgent, "search": "MSIE", "ident": "ie", "versionSearch": "MSIE", "name":"Microsoft Internet Explorer"},
				{"string": navigator.userAgent, "search": "Gecko", "ident": "mozilla", "versionSearch": "rv", "name":"Mozilla"},
				{"string": navigator.userAgent, "search": "Mozilla", "ident": "netscape", "versionSearch": "Mozilla", "name":"Netscape"}
			],
			os = [
				{"string": navigator.platform, "search": "Win", "ident": "win"},
				{"string": navigator.platform, "search": "Mac", "ident": "osx"},
				{"string": navigator.userAgent, "search": "iPhone", "ident": "iphone"},
				{"string": navigator.userAgent, "search": "iPad", "ident": "ipad"},
				{"string": navigator.platform, "search": "Linux", "ident": "linux"}
			],
			platforms = [
				{"search":'x86_32', "ident":'x86_32'},
				{"search":'x86_64', "ident":'x86_64'},
				{"search":'Win32', "ident":'x86_32'},
				{"search":'Win64', "ident":'x86_32'},
			],
			version_search = null;


		this.search_list = function(data, prop)
		{
			for (var i = 0; i<data.length; i++) {
				var dataString = data[i].string;
				var dataProp = data[i].prop;

				version_search = data[i].versionSearch || data[i].search;
				if (dataString) {
					if (dataString.indexOf(data[i].search) != -1)
						return data[i][prop];
				} else if (dataProp)
					return data[i][prop];
			}
		};


		this.get_tag = function()
		{
			return [this.get_platform(), this.get_os(), this.get_ident(), this.get_version()].join(' ');
		};


		this.get_os = function()
		{
			return this.search_list(os, 'ident') || 'unknown';
		};


		this.get_platform = function()
		{
			var platform = '';

			for (var i = 0; i<platforms.length; i++) {
				if (navigator.platform.indexOf(platforms[i].search) !== -1) {
					platform = platforms[i].ident;
					break;
				}
			}

			return platform;
		};


		this.get_ident = function()
		{
			return this.search_list(browsers, 'ident') || 'unknown';
		};


		this.get_name = function()
		{
			return this.search_list(browsers, 'name') || 'unknown';
		};


		this.get_version = function()
		{
			return get_version_helper(navigator.userAgent) || get_version_helper(navigator.userAgent) || 'unknown';
		};


		this.get_version_name = function()
		{
			return this.get_ident() + '-' + this.get_version();
		};


		var get_version_helper = function(dataString)
		{
			var index = dataString.indexOf(version_search);
			if (index == -1) return;
			return parseFloat(dataString.substring(index + version_search.length + 1));
		};
	};
}();


$(function() { $('html').addClass(browser.get_tag()); });
