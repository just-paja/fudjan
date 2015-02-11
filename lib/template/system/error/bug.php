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

?>

<div class="header">
	<?
		if (isset($exp[0])) {
			?>
				<h1><?=array_shift($exp)?></h1>
				<strong><?=get_class($desc)?></strong>
			<?
		} else {
			?>
				<h1><?=get_class($desc)?></h1>
			<?
		}
	?>
</div>

<?

if (count($exp) >= 1) {
	?>
	<div class="params">
		<h2><?=$reason?></h2>

		<ul>
			<?
				foreach ($exp as $ex) {
					?>
						<li class="point"><?=$ex?></li>
					<?
				}
			?>
		</ul>
	</div>
	<?
}
?>

<div class="advice">
	<h2><?=$trace?></h2>
	<?
		$back = $desc->getTrace();
		$num = 0;
	?>

	<ul class="trace">
		<?
			foreach ($back as $b) {

				$skip = isset($b['object']) && $b['object'] === $desc;
				$num++;

				?>
					<li class="err err_<?=$num?>">
						<?
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

										$args[] = '<li>'.implode('', $arg_content).'</li>';
									}

									$str_args[] = '<h3>Arguments:</h3>';
									$str_args[] = '<ol>'.implode('', $args).'</ol>';
								}
							}

							$str[] = '<div class="desc">'.implode('', $str_desc).'</div>';
						?>

						<details>
							<summary><?=implode('', $str)?></summary>
							<div class="cont"><?=implode('', array_merge($str_args, $str_obj))?></div>
						</details>
					</li>
				<?
			}
		?>
	</ul>
</div>
