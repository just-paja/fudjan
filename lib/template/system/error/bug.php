<?

/** This page is displayed on error
 * @format any
 * @package errors
 */

try {
	$generator = \System\Status::introduce();
} catch(Exception $e) { $generator = 'pwf-unknown'; }

$heading = 'core_bug_report_heading';
$info    = 'core_bug_report_sorry';
$trace   = 'core_bug_report_trace';
$reason  = 'core_bug_report_reason';

try {
	$heading = $ren->trans($heading);
	$info    = $ren->trans($info);
	$trace   = $ren->trans($trace);
	$reason  = $ren->trans($reason);
} catch(Exception $e) {}


$exp = $desc->get_explanation();

echo div('header');
	if (isset($exp[0])) {
		Tag::h1(array("content" => array_shift($exp)));
		Tag::strong(array("content" => get_class($desc)));
	} else {
		Tag::h1(array("content" => get_class($desc)));
	}


close('div');

if (count($exp) > 1) {

	echo div('params');
		Tag::h2(array("content" => $reason));
		Tag::ul();

		foreach ($exp as $ex) {
			Tag::li(array('class' => 'point', "content" => $ex));
		}

		Tag::close('ul');
	close('div');

}

echo div('advice');

	Tag::h2(array("content" => $trace));
	$back = $desc->get_backtrace();
	$num = 0;

	Tag::ul(array('class' => 'trace'));

		foreach ($back as $b) {

			$skip = isset($b['object']) && $b['object'] === $desc;
			$num++;

			Tag::li(array("class" => 'err err_'.$num));
				$str = array();
				$str_desc = array();
				$str_args = array();
				$str_obj  = array();

				if (isset($b['file'])) {
					$str_desc[] = $b['file'];

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

						$str_args[] = STag::h3(array("content" => 'Arguments:'));
						$str_args[] = Stag::ol(array("content" => $args));
					}
				}

				//~ if (!$skip && isset($b['object'])) {
					//~ $str_obj[] = STag::heading(array("content" => 'Object:'));
					//~ $str_obj[] = Tag::div(array(
						//~ "content" => '<pre>'.@var_export($b['object'], true).'</pre>',
						//~ "output"  => false,
					//~ ));
				//~ }

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

	close('ul');
close('div');
