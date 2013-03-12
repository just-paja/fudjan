<?

/** Special page for page not found error
 * @package errors
 */

echo section_heading(l('core_page_not_found'));
Tag::p(array("class" => 'desc', "content" => l('core_page_not_found_text')));
