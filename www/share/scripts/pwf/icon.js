pwf.register('icon', function()
{
	this.init = function()
	{
		return true;
	};


	this.is_ready = function()
	{
		return true;
	};


	this.html = function(path, size)
	{
		return '<span class="icon" style="width:'+size+'px; height:'+size+'px; background-image:url(/share/icons/'+size+'/'+path+')"></span>';
	};
});
