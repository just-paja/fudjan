<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta name="generator" content="<?=System\Output::introduce()?>" />
		<meta name="generated-at" content="Sat, 20 Aug 2011 18:52:40 Europe/Prague" />
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" type="text/css" href="/share/styles/global.css.php" />
		<title>Systémová chyba!</title>
	</head>
	
	<body class="fatal-error">
		<h1>Přístup odepřen</h1>
		<section>
			<? if ($desc) { ?>
				<p><?=$desc?></p>
			<? } ?>
			<p><?=_('Do této sekce nemáte povolený přístup. Zkuste se prosím přihlásit, pokud již přihlášen nejste.')?></p>
			<menu>
				<li><a href="/"><?=_('Úvodní stránka')?></a></li>
				<li><a href="/"><?=_('Hledání')?></a></li>
			</menu>
		</section>
	</body>
</html>

