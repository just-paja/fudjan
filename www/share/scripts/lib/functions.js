var v = function(msg)
{
	if (typeof console != 'undefined' && typeof console.log == 'function') {
		console.log(msg);
	}
};


/** Is jQuery object attached to DOM?
 * @param jQuery ref
 * @returns bool
 */
var is_attached = function(ref)
{
	return ref.parents(':last').is('html');
};



/** Stop jQuery event from propagation and doing anything else
 * @returns jQuery event
 */
var stop_event = function(e)
{
	e.stopPropagation();
	e.preventDefault();
	return e;
};
