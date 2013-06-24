pwf.register('locales', function()
{
	var messages = {};

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
		return typeof pwf_trans != 'undefined' && pwf_trans['messages'] != 'undefined';
	};


	var init_trans = function()
	{
		messages = pwf_trans['messages'];
	};


	this.trans = function(key)
	{
		return typeof messages[key] === 'undefined' ? key:messages[key];
	};
});

