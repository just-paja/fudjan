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
						is_array($t['args']) && yacms_show_backtrace($t['args']);
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

?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta name="generator" content="<?=Core\System\Output::introduce()?>" />
		<meta name="generated-at" content="Sat, 20 Aug 2011 18:52:40 Europe/Prague" />
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" type="text/css" href="/share/styles/global.css.php" />
		<title>Systémová chyba!</title>
	</head>
	
	<body class="fatal-error">
		<section>
			<h1><?=_('Stala se chyba')?></h1>
			<p><?=_('Omlouváme se, na webu se vyskytla fatální chyba, kterou nešlo obejít. Pochopitelně v současné době děláme všechno pro to, abychom tuto chybu odstranili.')?></p>
			<ul>
				<?
					$errors = Core\System\Status::format_errors($desc);
					foreach ($errors as $e) {
						?>
						<li class="error-desc"><?=$e?></li>
						<?
					}
				?>
			</ul>
			<?
				yacms_show_backtrace();
			?>
		</section>
	</body>
</html>
