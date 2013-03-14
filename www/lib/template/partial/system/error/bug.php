<?

/** This page is displayed on error
 * @format any
 * @package errors
 */

try {
	$generator = System\Output::introduce();
} catch(Exception $e) { $generator = 'pwf-unknown'; }

$heading = 'core_bug_report_heading';
$info    = 'core_bug_report_sorry';
$trace   = 'core_bug_report_trace';
$reason  = 'core_bug_report_reason';

try {
	$heading = l($heading);
	$info    = l($info);
	$trace   = l($trace);
	$reason  = l($reason);
} catch(Exception $e) {}


$exp = $desc->get_explanation();
echo section_heading(isset($exp[0]) ? $exp[0].Tag::span(array("class" => 'type', "content" => get_class($desc), "output" => false)):get_class($desc));

if (count($exp) > 1) {

	echo heading($reason, true, 2);
	Tag::ul();
	foreach ($exp as $ex) {
		Tag::li(array("content" => $ex));
	}
	Tag::close('ul');

}

echo heading($trace, true, 2);
$back = $desc->get_backtrace();
$num = 0;
Tag::ul();

	foreach ($back as $b) {

		$skip = isset($b['object']) && $b['object'] === $desc;
		$num++;

		Tag::li(array("class" => 'err err_'.$num));
			$str = array();
			$str_desc = array();
			$str_args = array();
			$str_obj  = array();

			if (isset($b['file'])) {
				$str_desc[] = substr($b['file'], strlen(ROOT));

				if (isset($b['line'])) {
					$str_desc[] = ':'.$b['line'];
				}

				$str_desc[] = ' ';
			}

			if (!$skip && isset($b['function'])) {

				$str_desc[] = 'in function ';

				if (isset($b['class'])) {
					$str_desc[] = $b['class'].'::';
				}

				$str_desc[] = $b['function'].'()';


				if (false && isset($b['args']) && any($b['args'])) {
					$args = array();

					foreach ($b['args'] as $arg) {
						$arg_content = is_object($arg) ? 'Instance of '.get_class($arg):var_export($arg, true);
						$arg_content = is_array($arg) ? 'Array':var_export($arg, true);

						$args[] = Stag::li(array("content" => $arg_content));
					}

					$str_args[] = heading('Arguments:', true, 4);
					$str_args[] = Stag::ol(array("content" => $args));
				}
			}

			if (!$skip && isset($b['object'])) {
				$str_obj[] = heading('Object:', true, 4);
				$str_obj[] = Tag::div(array(
					"content" => '<pre>'.@var_export($b['object'], true).'</pre>',
					"output"  => false,
				));
			}

			$str[] = Tag::div(array(
				"class" => 'desc',
				"content" => implode('', $str_desc),
				"output"  => false
			));

			Tag::details(array(
				"content" => array(
					Stag::summary(array("content" => $str)),
					Stag::div(array("class" => 'cont', "content" => array_merge($str_args, $str_obj))),
				)
			));


		Tag::close('li');
	}

Tag::close('ul');
