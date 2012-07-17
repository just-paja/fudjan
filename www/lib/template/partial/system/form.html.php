<?

content_for("styles", "rte");

if (!defined("EDITOR_HAS_TEMPLATE_HEADER")) {
	define('EDITOR_HAS_TEMPLATE_HEADER', true);

	function form_error($msg){ return '<span class="form-error">'.$msg.'</span>'; }


	function calendar_script(&$f, &$obj, $add_time = false) {
		/* TODO: Create datepicker for non-html5 browsers
		if($f->dialogs_enabled && !!JS_JSCAL_ENABLED){
			$f->form_js[] = 'if(Browser.Engine.name != "presto"){';
			$f->form_js[] = 'Calendar.setup({';
			$f->form_js[] = 'button     : "'.$obj['id'].'", inputField : "'.$obj['id'].'", eventName  : "focus",';
			$f->form_js[] = 'ifFormat   : "%Y-%m-%d'.($add_time?' %H:%M:%S':'').'", showsTime  : '.($add_time?'true':'false').',';
			$f->form_js[] = 'firstDay   : 1, weekNumbers: false });';
			$f->form_js[] = '}';
		}*/
	}
}

$sub_li = $li = 0;
?>

<div class="plain-form" id="<?=$f->get('id')?>-container">

<? if(!empty($object['anchor'])){ ?><a name="<?=$object['anchor']?>"></a><? } ?>
<? if($f->get('heading')){ echo section_heading($f->get('heading')); } ?>
<? if($f->get('desc')){ echo '<p>'.$f->get('desc').'</p>'; } ?>


<form<?=html_attrs('form', $f->get())?>>
<?
$hidden = &$f->get_hidden();
if(!empty($hidden)){
	?>
	<fieldset class="hidden">
		<?
		foreach ($hidden as $obj) {
			$val = $obj['value'];
			unset($obj['value']);
			echo '<input value=\''.$val.'\''.html_attrs('input', $obj).' />';
		}
		?>
	</fieldset>
	<?
}

$objects = &$f->get_objects();

foreach($objects as $obj) {
	def($obj['value'], '');
	def($obj['errors'], array());
	def($obj['required'], false);

	if (isset($obj['onfocus-show-tab']) && $obj['onfocus-show-tab']){
		$t = &$obj['onfocus-show-tab'];
		$obj['onFocus'] = 'tabber = $(\''.$t[0]['id'].'\').tabber; if(tabber){ tabber.tabShow('.$t[1].'); }';
	}


	switch($obj['kind']){

		case 'inputs-start': {
			echo '<fieldset><ul class="inputs">';
			break;
		}


		case 'inputs-end': {
			echo '<li style="clear:both;width:0px;height:0px;float:none;display:block"></li></ul></fieldset>';
			break;
		}


		case 'tabs-group-start': {
			content_for("scripts", "jquery.ui");
			?>
			<div class="tabs" id="<?=$obj['id']?>">
				<ul>
					<? foreach ($obj['tabs'] as $tab) { ?>
						<li><a href="#<?=$tab['id']?>"><?=$tab['title']?></a></li>
					<? } ?>
				</ul>
			<?
			break;
		}


		case 'tab-start':{
			echo '<div'.html_attrs('div', $obj).'>';
			break;
		}


		case 'tabs-group-end':
		case 'tab-end': {
			echo '</div>';
			break;
		}


		case 'input': {
			$multi_checkbox = ($obj['type'] == 'checkbox' && isset($obj['options']) && is_array($obj['options']));

			if (strpos($obj['label'], ':::')) {
				$obj['label'] = str_replace(':::', null, $obj['label']);
				$sep = '';
			} else {
				$sep = ':';
			}

			if ($obj['value'] instanceof DateTime) {
				$obj['value'] = format_date($obj['value'], "html5");
			}

			?>
			<li class="form-li-<?=++$li?> <?=$obj['type']?><?=$obj['required'] ? ' required':null?><?=($obj['eclass'] ? ' ':null).$obj['eclass']?>"<?=$obj['required'] ? ' title="'._('Toto pole je povinné').'"':NULL?>>
				<? if($obj['type'] != 'checkbox' || ($multi_checkbox && $obj['type'] == 'checkbox')){ ?>
					<label class="label-left<?=$multi_checkbox?' label-multi-checkbox':null?>" for="<?=$obj['id']?>"><?=$obj['label'].$sep?></label>
				<? } ?>
				<?
					if ($obj['type'] == 'radio' || $multi_checkbox) {
						?>
						<ul<?=html_attrs('ul', $obj)?>>
							<?
							$i = 0;
							foreach ($obj['options'] as $label=>$val) {
								$i++;
								?>
								<li class="form-subli form-subli-<?=++$sub_li?>">
									<input type="<?=$obj['type']?>" name="<?=$obj['name']?><?=$obj['type'] == 'checkbox' ? '[]':null?>" value="<?=$val?>" id="<?=$obj['name']?>_input_<?=$i?>"<?=($obj['value'] == $val || (is_array($obj['value']) && in_array($val, $obj['value']))) || (is_object($obj['value']) && $val == $obj['value']->id) ? ' checked="true"':null?> />
									<label class="label-right" for="<?=$obj['name']?>_input_<?=$i?>"><?=$label?></label>
								</li>
							<? } ?>
						</ul>
						<?
					} else {
						?>
						<span class="form-input">
							<input<?=html_attrs('input', $obj)?> />
							<? if (strpos($obj['type'], 'date') !== false) {
								calendar_script($f, $obj, strpos($obj['type'], 'time'));
							} ?>
						</span>
						<?
						if ($obj['type'] == 'checkbox') {
							?><label class="label-right" for="<?=$obj['id']?>"><?=$obj['label']?></label>
						<?
						}
					}
					if (is_array($obj['errors'])) {
						foreach ($obj['errors'] as $e) {
							echo form_error($e);
						}
					}
			?></li><?
			break;
		}


		case 'list': {
			$obj['class'] = "list list-values-".count($obj['options']);
			$val = 'json:'.json_encode($obj['options']);
			$obj['type'] = 'text';
			?>
			<li class="form-li-<?=++$li?><?=($obj['eclass'] ? ' ':null).$obj['eclass']?> input-list">
				<label><?=cflc($obj['label'])?>:</label>
				<div class="input-list-container<?=$obj['show-keys'] ? '':' hide-keys'?>" rel="<?=$obj['bind-store']?>">
					<span class="warning hidden"><?=_('Žádné položky v tomto seznamu')?></span>
					<span class="controls hidden">
						<a href="" class="edit"><?=icon('actions/edit', 16)?></a>
						<a href="" class="drop"><?=icon('actions/delete', 16)?></a>
					</span>
					<ul class="input-list-content noli" id="il_<?=$obj['id']?>">
						<? foreach ($obj['value'] as $key=>$val) { ?>
							<li id="il_<?=$obj['id']?>_<?=preg_replace("/([^[:alnum:]])/", "", $key)?>">
								<span class="key"><?=$key?>: </span>
								<span class="val"><?=$val?></span>
							</li>
						<? } ?>
					</ul>
					<a href="#" class="list-link-add"><?=icon('actions/add', 16)?><span class="t"><?=_('Přidat položku na seznam')?></span></a>
				</div>
				<?
					if (is_array($obj['errors'])) {
						foreach ($obj['errors'] as $e) {
							echo form_error($e);
						}
					}
				?>
			</li>
			<?
			break;
		}


		case 'list-editor':{
			echo '<li class="form-li-'.(++$li).' input-list-editor" id="ile_'.$obj['target'].'"><div class="input-list ilve target-'.$obj['target'].($obj['show-keys'] ? '':' hide-keys').'">';
			break;
		}
		
		
		case 'list-editor-end':{
			echo '</div></li>';
			break;
		}


		case 'textarea': {
			$attrs = $obj;
			unset($attrs['value']);
			?>
				<li class="form-li-<?=++$li?> textarea<?=$obj['required'] ? ' required':null?><?=($obj['eclass'] ? ' ':null).$obj['eclass']?>">
					<label><?=$obj['label']?>:</label>
					<span class="form-input"><textarea<?=html_attrs('textarea', $attrs)?>><?=$obj['value']?></textarea></span>
				</li>
			<?
			break;
		}


		case 'select': {
			if (isset($obj['multiple']) && $obj['multiple']) $obj['name'] .= '[]';
			if (isset($obj['entry']) && $obj['entry']) {
				$script = "if(this.value == '-?-'){ document.getElementById('".$obj['name']."-entry').style.display = '';} else {"
						."document.getElementById('".$obj['name']."-entry').style.display = 'none'; }";
				$obj['onchange'] = $script.$obj['onchange'];
				$obj['onkeyup'] = $script.$obj['onkeyup'];
			}
			?>
				<li class="form-li-<?=++$li?> select<?=$obj['required'] ? ' required':null?><?=(isset($obj['eclass']) && $obj['eclass'] ? ' '.$obj['eclass']:null)?>">
					<label class="label-left" for="<?=$obj['id']?>"><?=$obj['label']?>:</label>
					<select<?=html_attrs('select', $obj)?>>
						<? foreach($obj['options'] as $opt=>$val){
								if(is_array($val)){ ?>
									<optgroup label="<?=$opt?>">
										<? foreach($val as $l=>$v){ ?>
											<option value="<?=$v?>"<?=in_array($v, (array) $obj['value']) ? ' selected="true"':null?>><?=$l?></option>
										<? } ?>
									</optgroup>
							<? }else{ ?>
								<option value="<?=$val?>"<?=in_array($val, (array) $obj['value']) ? ' selected="true"':null?>><?=$opt?></option>
							<? } ?>
						<? } ?>
					</select>
					<? if(isset($obj['entry']) && $obj['entry']){ ?>
						<div class="select-entry">
							<input type="text" name="<?=$obj['name']?>-entry" id="<?=$obj['name']?>-entry" value="<?=$obj['value']?>" style="display:none" />
						</div>
					<? } ?>
				</li><?
			break;
		}


		case 'separator': {
			?><span class="form-separator"></span><?
			break;
		}


		case 'text': {
			?><li class="form-li-<?=++$li?> formel-text"><? if(any($obj['label'])){ ?><label class="label-left"><?=$obj['label']?>:</label><? } ?><p class="form-text"><?=$obj['text']?></p></li><?
			break;
		}


		case 'tip': {
			?><li class="form-li-<?=++$li?> formel-tip"><label></label><p class="form-tip"><?=$obj['text']?></p></li><?
			break;
		}


		case 'label': {
			?><li class="form-li-<?=++$li?> formel-label"><label><?=$obj['text']?></label></li><?
			break;
		}


		case 'group-start': {
			def($obj['class'], '');
			$class_val = array(
				'form-li-'.(++$li),
				'formel-group',
				'form-group'.($obj['class'] ? '-'.$obj['class']:'').'-cont'
			);

			echo '<li class="'.implode(' ', $class_val).'"><ul class="form-group'.($obj['class'] ? '-'.$obj['class']:'').'">';
			break;
		}


		case 'group-end': {
			echo '</ul></li>';
			break;
		}


		case 'html': {
			echo $obj['html'];
			break;
		}


		case 'button': {
			echo '<button'.html_attrs('button', $obj).'><span>'.$obj['label'].'</span></button>';
			break;
		}


		case 'clear': {
			echo '<span class="clear"></span>';
			break;
		}


		case 'input-switch': {
			?><li class="form-li-<?=++$li?> formel-switch <?=$obj['name']?>">
				<? if ($obj['label']) { ?>
					<label class="label-left"><?=$obj['label']?></label>
				<? } ?>
				<select<?=html_attrs('select', $obj)?>>
					<?
					foreach ($f->get_switch_opts($obj['switch-id']) as $k=>$label) {
						echo '<option value="'.$k.'">'.$label.'</option>';
					}
					?>
				</select>
			</li><?
			break;
		}
	}
}

if(!empty($f->footnote)){?>
	<p><?=implode('<br />', (array) $f->footnote)?></p>
<?}?>

</form>
</div>

<?
	if(!empty($f->form_js)){
		?>
		<script type="text/javascript">
			//<![CDATA[
				$(function() {
					<?
					if(is_array($f->form_js)){
						foreach($f->form_js as $line){
							echo $line."\n";
						}
					}
					?>
				});
			//]]>
		</script>
	<?
	}
?>
