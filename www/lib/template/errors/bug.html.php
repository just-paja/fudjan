<?

if (!defined("H_BUG_REPORT")) {
	define("H_BUG_REPORT", true);

	function yacms_show_backtrace($trace = null)
	{
		if (is_null($trace)) {
			$trace = debug_backtrace();
		}

		if (any($trace)) {
			echo '<ul>';
				foreach ($trace as $t) {
					echo '<li>';

					if (is_array($t)) {
						if (isset($t['file'])) echo $t['file'].':'.$t['line'];
						if (isset($t['args'])) {
							is_array($t['args']) && yacms_show_backtrace($t['args']);
						} else {
							yacms_show_backtrace($t);
						}
					} elseif ($t instanceof Exception) {
						echo get_class($t).': '.$t->getMessage();
					} else {
						echo '<pre>';
						var_export($t);
						echo '</pre>';
					}

					echo '</li>';
				}
			echo '</ul>';
		}
	}
}

try {
	$generator = System\Output::introduce();
} catch(Exception $e) { $generator = 'pwf'; }

$heading = 'There was an error while processing your request';
$info    = 'We are doing everything in our power to fix that. If you do not share this opinion, please let us know.';

try {
	$heading = l($heading);
	$info = l($info);
} catch(Exception $e) {}

?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta name="generator" content="<?=$generator?>" />
		<meta name="generated-at" content="Sat, 20 Aug 2011 18:52:40 Europe/Prague" />
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" type="text/css" href="/share/styles/global.css.php?" />
		<title>Systémová chyba!</title>
	</head>

	<body class="fatal-error">
		<section>
			<h1><?=$heading?></h1>
			<p><?=$info?></p>
			<ul>
				<?
					$errors = System\Status::format_errors($desc);
					foreach ($errors as $e) {
						?>
						<li class="error-desc"><?=$e?></li>
						<?
					}
				?>
			</ul>
		</section>
	</body>
</html>
