<?

/** Special page for access denied error
 * @package errors
 */

echo section_heading(l('core_access_denied'));
Tag::p(array("class" => 'desc', "content" => l('core_access_denied_text')));
