<?

/** Special page for page not found error
 * @package errors
 */

echo $renderer->heading($ren->trans('core_page_not_found'));
Tag::p(array("class" => 'advice desc', "content" => $ren->trans('core_page_not_found_text')));
