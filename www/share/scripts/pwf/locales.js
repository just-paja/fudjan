pwf.register('locales', function()
{
	var messages = null;


	this.init = function init()
	{
		if (this.is_ready()) {
			init_trans();
			return true;
		}

		return false;
	};

	this.is_ready = function()
	{
		return pwf_trans;
	};


	var init_trans = function()
	{
		if (typeof pwf_trans === 'undefined') {
			messages = {};
			v("Failed to load pwf translations");
		} else messages = pwf_trans['messages'];
	};


	this.trans = function(key)
	{
		return typeof messages[key] === 'undefined' ? key:messages[key];
	};
});

