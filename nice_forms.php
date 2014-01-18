<?php

/*
* example :

	$options_pattern = array();
	$options_pattern['an_option']['title'] = "Is this nice ?";

	$my_form = new Nice_forms(basename(__FILE__),'My Form','my_options',$options_pattern);

*
*/

class Nice_forms
{
	var $options_name;
	var $options_title;
	var $options;
	var $options_values;
	var $patterns;
	var $url;
	var $page;
	var $message;
	var $action;
	var $wp_options;
	
	function Nice_forms($filename,$options_title='',$options_name='',$options='',$action='add_options_page',$init=true)
	{
		if (!is_admin()) return;

		$this->options_name = $options_name;
		$this->options_title = $options_title;
		$this->action = $action;

		$this->options = $options;
		$this->wp_options = get_option($options_name);
		
		$this->assign_patterns();
		
		$this->page = $filename;
		$filename = explode('\\',__FILE__);
		if (count($filename) <= 1) $filename = explode('/',__FILE__);
		$this->url = $filename[count($filename)-4].'/'.$filename[count($filename)-3].'/'.$filename[count($filename)-2];
		$this->url = get_bloginfo('wpurl').'/'.$this->url.'/';
		
		$this->message = '';

		if ($this->wp_options)
		{
			foreach ($this->wp_options as $key=>$value)
			{
				if ($this->options[$key]) $this->options[$key]['value'] = $value;
			}
		}

		if ($init == true) $this->init();
		
		if(!defined("NF_HEADER_LOADED")) {
			define("NF_HEADER_LOADED",TRUE);
			add_action('admin_head',array($this,'admin_head'));
		}
	}
	
	function init()
	{
		$this->add_options();
	}
	
	function assign_patterns()
	{
		$this->patterns['rating']['values'] = '0|1|2|3|4|5';
	}
	
	function filters()
	{
		$this->options = apply_filters($this->options_name.'_options',$this->options);
		$this->patterns = apply_filters($this->options_name.'_patterns',$this->patterns);
	}

	function admin_head()
	{
		if ($_GET['page'] == basename(__FILE__) || $_GET['page'] == 'nice_theme.php') {
			print '<link rel="stylesheet" href="'.$this->url.'css/luna/luna.css" type="text/css" media="screen"/>';
			print '<link rel="stylesheet" href="'.$this->url.'css/admin_style.css" type="text/css" media="screen"/>';
			print '<link rel="stylesheet" href="'.$this->url.'css/colorwheel.css" type="text/css" media="screen"/>';
			print '<style type="text/css">* html div#colorwheel {filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src=\''.$this->url.'/css/hsvwheel.png\', sizingMethod=\'crop\');}</style>';
	
			print '<script type="text/javascript" src="'.$this->url.'js/colorwheel.js"></script>';
			print '<script type="text/javascript" src="'.$this->url.'js/range.js"></script>';
			print '<script type="text/javascript" src="'.$this->url.'js/timer.js"></script>';
			print '<script type="text/javascript" src="'.$this->url.'js/slider.js"></script>';
			print '<script type="text/javascript" src="'.$this->url.'js/prototype.js"></script>';
		}
	}
	
	function add_options()
	{
		if (method_exists($this, $this->action)) add_action('admin_menu',array($this, $this->action));
		else add_action('admin_menu', $this->action);
	}
	
	function add_options_page()
	{
		add_options_page($this->options_title, $this->options_title, 8, $this->page, array($this, 'options_page'));
	}
	
	function add_theme_page()
	{
		add_theme_page($this->options_title, $this->options_title, 'edit_themes', $this->page, array($this,'options_page'));
	}
	
	function options_page()
	{
		if ($this->message!='') print $this->message;
		?>
		<div class="wrap">
			<h2><a name="top"></a><?php print $this->options_title ?></h2>
			<?php $this->save_form(); ?>
			<?php $this->form(); ?>
		</div>
		<?php
	}
	
	function save_form()
	{
		if (isset($_POST['nf_posted_'.$this->options_name])) // options
		{
			$this->save_options();
			$this->filters();
			$this->save_options();
			update_option($this->options_name, $this->options_values);
		}
	}
	
	function save_options()
	{
		foreach ($this->options as $key=>$value)
		{
			if ($this->options[$key]['type'] == 'checkbox')
			{
				if (isset($_POST[$key])) $this->options[$key]['value'] = true;
				else $this->options[$key]['value'] = false;
			}
			else if (isset($_POST[$key])) $this->options[$key]['value'] = $_POST[$key];
			
			if (is_array($this->wp_options)) $this->options_values[$key] = $this->options[$key]['value'];
			else $this->options_values->$key = $this->options[$key]['value'];
		}
	}

	function form()
	{
		print '<form name="nf_'.$this->options_name.'" method="post"><input type="hidden" name="nf_posted_'.$this->options_name.'" value="update" />';
		$this->form_rows($this->options,$this->patterns);
		print '<p class="submit"><input type="submit" name="Submit" value="'.__('Update Options &raquo;','customize').'"/></p></form>';
	}

	function form_rows($options,$patterns)
	{
		if (!$options) return;

		print '
		<table class="form-table">
			<col class="label"/><col/>';

			foreach ($options as $key=>$option)
			{
				if (!isset($option['name'])) $option['name'] = $key;
				$i++;
				if ($i % 2) $class = 'alternate';
				else $class = 'none';
				
				if ($option['type'] == 'title') print '<tr><td><h3>'.$option['title'].' :</h3></td></tr>';
				else {
				print '
				<tr class="'.$class.'">
					<th scope="row">
						<label for="'.$option['name'].'">'.$option['title'].' :</label>
					</th>
					<td>';
					
					switch ($option['type'])
					{
						case 'size':
						case 'slider':

							$units = $this->split('units',$option);

							print '<input size="4" class="size" type="text" name="'.$option['name'].'" id="'.$option['name'].'" value="'.$option['value'].'"/>';
							if (count($units)>1)
							{
								print '<select class="size" name="'.$option['name'].'_unit">';
								foreach ($units as $unit)
								{
									print '<option value="'.$unit['value'].'"';
									if (strstr($option['value'],$unit['value'])) print ' selected="selected" ';
									print '>';
									print $unit['label'];
									print '</option>';
								}
								print '</select>';
							}
							else print '<span style="float:left">'.$units[0]['label'].'</span>';
							print '<div class="slider" id="slider_'.$option['name'].'" tabIndex="1"><input class="slider-input" id="slider_input_'.$option['name'].'" name="slider_input_'.$option['name'].'"/></div>
							
							<script type="text/javascript">
							//<![CDATA[
							var slider_'.$option['name'].' = new Slider(document.getElementById("slider_'.$option['name'].'"), document.getElementById("slider_input_'.$option['name'].'"));
							slider_'.$option['name'].'.onchange = function () {document.getElementById("'.$option['name'].'").value = slider_'.$option['name'].'.getValue();};';
							if ($option['maximum']) print 'slider_'.$option['name'].'.setMaximum('.$option['maximum'].');';
							else
							{
								if (strstr($option['name'],'border') && strstr($option['value'],'px')) print 'slider_'.$option['name'].'.setMaximum(20);';
								else if (strstr($option['value'],'px')) print 'slider_'.$option['name'].'.setMaximum(1000);';
								else if (strstr($option['value'],'em')) print 'slider_'.$option['name'].'.setMaximum(20);';
								else print 'slider_'.$option['name'].'.setMaximum(100);';
							}
							$val = (float) str_replace('%', '', str_replace('px', '', str_replace('em', '', $option['value'])));
							print 'slider_'.$option['name'].'.setUnitIncrement(1);';
							print 'slider_'.$option['name'].'.setBlockIncrement(1);';
							print 'slider_'.$option['name'].'.setValue('.$val.');';
							//]]>
							print '</script>';
							break;
							
						case 'color':
							
							print '<span id="color_'.$option['name'].'" class="color_preview" style="background:'.$option['value'].'"></span>';
							print '<input
							onkeypress="document.getElementById(\'color_'.$option['name'].'\').style.background=this.value"
							onkeyup="document.getElementById(\'color_'.$option['name'].'\').style.background=this.value"
							onchange="document.getElementById(\'color_'.$option['name'].'\').style.background=this.value"
							onclick="document.getElementById(\'color_'.$option['name'].'\').style.background=this.value"
							size="7" class="color" type="text" name="'.$option['name'].'" id="'.$option['name'].'" value="'.$option['value'].'"/>';
							print '<input type="button" value="'.__('Choose').'" onclick="colorwheel.choose(\''.$option['name'].'\',\'color_'.$option['name'].'\')">';
							break;
							
						case 'image':

							print '<ul class="image_list">';
							foreach ($option['image_list'] as $image_file)
							{
								$image_url=$option['folder'].$image_file;
								$style='background-image:url('.$this->css_url.$image_url.')';
								print '<li>
									<label style="'.$style.'"><input name="'.$option['name'].'" type="radio" value="url('.$option['folder'].$image_file.')" ';
									if ($option['value'] == 'url('.$option['folder'].$image_file.')') print 'checked="checked" ';
									print '/><span>'.$image_file.'</span></label>
								</li>';
							}
							print '<li class="none"><label><input name="'.$option['name'].'" type="radio" value="url('.$option['folder'].'none.gif)" ';
							if ($option['value'] == 'url('.$option['folder'].'none.gif)') print 'checked="checked" ';
							print '/><span>'.__('None','yammyamm').'</span></label></li>';
							print '</ul>';
							print '<br style="clear:both"/> <input type="hidden" name="'.$option['name'].'_upload_folder" value="'.$option['folder'].'" /> <input type="file" id="'.$option['name'].'_upload" name="'.$option['name'].'_upload" size="30"/><input type="submit" name="'.$option['name'].'_upload_submit" value="'.__('Upload &raquo;','customize').'"/>';
							break;
							
						case 'align':
						case 'position':
						case 'display':
						case 'radio':
						case 'rating':
							
							$items = $this->split('values',$option);
							
							foreach ($items as $item)
							{
								print '<label><input ';
								if ($option['value'] == $item['value']) print 'checked="checked" ';
								print 'class="radio" value="'.$item['value'].'" type="radio" name="'.$option['name'].'"/> '.$item['label'].'</label><br/>';
							}
							break;
							
						case 'checkbox':
	
							print '<input ';
							if ($option['value'] == true) print 'checked="checked" ';
							print 'class="checkbox" value="true" type="checkbox" name="'.$option['name'].'"/><br/>';
							break;
							
						case 'select':
							
							$items = $this->split('values',$option);
							
							print '<select class="size" name="'.$option['name'].'">';
							foreach ($items as $item)
							{
								print '<option value="'.$item['value'].'"';
								if ($option['value'] == $item['value']) print ' selected="selected"';
								print '>'.$item['label'].'</option>';
							}
							print '</select>';
							break;
							
						case 'categories':

							print '<select id="'.$option['name'].'" name="'.$option['name'].'">';
							print '<option value="">'.__('none').'</option>';
							wp_dropdown_cats($option['parent'], $option['value'], $option['parent'], 0, 0);
							print '</select>';
							break;
							
						case 'pages':

							print '<select id="'.$option['name'].'" name="'.$option['name'].'">';
							print '<option value="">'.__('none').'</option>';
							parent_dropdown($option['value'],$option['parent']);
							print '</select>';
							break;
							
						case 'sortable':
							
							$items = $this->split('value',$option);

							if (count($items>1))
							{
								print '<input id="'.$option['name'].'_set" name="'.$option['name'].'_set" type="hidden" value="0"/>';
								print '<input id="'.$option['name'].'" name="'.$option['name'].'" type="hidden" value="'.$option['value'].'"/>';
								print '<ul class="sortable" id="'.$option['name'].'_sortable">';
								$i = 0;
								foreach ($items as $item)
								{
									print '<li id="'.$option['name'].'_'.$i.'" name="'.$option['name'].'_$'.$item['value'].'">'.$item['label'].'</li>';
									$i++;
								}
								print '</ul>';
								?>
								<script type="text/javascript" language="javascript">
								// <![CDATA[
								Sortable.create("<?php print $option['name'].'_sortable' ?>",
								 {tag:'li',overlap:'horizontal',constraint: false,
								  onUpdate:function(){
								  	  var return_value = '';
								  	  var container = document.getElementById("<?php print $option['name'] ?>_sortable").getElementsByTagName('li');
								  	  for (var i=0;i<container.length;i++) {
								  	  	  if (i>0) return_value += '|';
								  	  	  return_value +=container[i].getAttribute('name').split('_$')[1]+':'+container[i].innerHTML;
								  	  }
								  	  document.getElementById("<?php print $option['name'].'_set' ?>").value = '1';
								  	  document.getElementById("<?php print $option['name'] ?>").value = return_value;
								  }
								})
								// ]]>
								</script>
						 		<?php
						 	}
							break;

						default :
							
							print '<input class="text" type="text" name="'.$option['name'].'" id="'.$option['name'].'" value="'.$option['value'].'"/>';
							break;
						// hook pour action custom
					}
					print '
					</td>
				</tr>';
				}
		};
		print '
		</table>
		';
	}
	
	function split($type,$option) // explodes a value with "|", and if there are ":", splits again labels and values, else returns the same for labels and values
	{
		if ($option[$type]) $items = explode('|',$option[$type]); // options
		else $items = explode('|',$this->patterns[$option['type']][$type]);
		
		foreach ($items as $key=>$item)
		{
			if (strstr($item,':'))
			{
				$split_item = explode(':',$item);
				$items[$key] = array ('value'=>$split_item[0],'label'=>$split_item[1]);
			}
			else $items[$key] = array ('value'=>$item,'label'=>$item);
		}
		return $items;
	}
}


?>