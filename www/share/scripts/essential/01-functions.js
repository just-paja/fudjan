function v(msg)
{
	if (typeof console != 'undefined' && typeof console.log == 'function') {
		console.log(msg);
	}
};


/** Is jQuery object attached to DOM?
 * @param jQuery ref
 * @returns bool
 */
function is_attached(ref)
{
	return ref.parents(':last').is('html');
}
