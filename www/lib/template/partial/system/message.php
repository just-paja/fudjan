<?
def($nopopup, false);
?>

<div class="message<?=$nopopup ? "-nopopup":null?> message-<?=$message->get_status().($message->autohides() ? " message-autohide":null)?>">
	<?if($message->get_title()){?><strong><?=$message->get_title()?></strong><?}?>
	<?if($message->get_message()){?><p><?=$message->get_message()?></p><?}?>
	<?
	$links = $message->get_links();
	if(any($links)){?>
		<ul>
		<?foreach($links as $link){?>
			<li><?=
				isset($link[2]) ?
					icon_for($link[2], 16, $link[1], $link[0], array("label" => true)):
					link_for($link[0], $link[1])
			?></li>
		<?}?>
		</ul>
	<?}?>
</div>
