<?php


include_once(ABSPATH.'/wp-admin/admin-functions.php');
if (!class_exists('Nice_forms')) require_once('nice_forms.php');

class Nice_theme extends Nice_forms {
	
	var $options;
	var $patterns;
	var $default_file;
	var $custom_file;
	var $file;
	
	var $theme_options;
	var $theme_info;
	
	var $path;
	var $url;
	
	function Nice_theme($filename,$options_title='Customize',$default_file='custom_template.css',$custom_file='custom_template.css')
	{

		// Pre-2.6 compatibility
		if (!defined('WP_CONTENT_URL'))
				define( 'WP_CONTENT_URL', get_option('siteurl').'/wp-content');
		if (!defined('WP_CONTENT_DIR'))
				define( 'WP_CONTENT_DIR', ABSPATH.'wp-content');
				
		$this->theme_info = current_theme_info();
		$this->path = $this->theme_info->template_dir.'/';
		
		parent::Nice_forms($filename,$options_title,$this->theme_info->name,'','add_theme_page',false);

		$filename = explode('\\',__FILE__);
		$this->default_file = $default_file;
		$this->custom_file = $custom_file;
		$this->css_url = get_bloginfo('stylesheet_directory').'/';
		
		$this->file = get_option('nt_file');
		if (!$this->file || $this->file=='')
		{
			$this->set_file($this->default_file);
		}

		$this->themes = $this->list_files($this->path,'custom_',array('custom_template.css'));
		
		$this->init();

		add_action('wp_head',array(&$this,'head'));
	}

	function assign_patterns()
	{
		$this->patterns = array();
		$this->patterns['size']['patterns'] = 'right|left|width|margin|padding|height|size|font-size';
		$this->patterns['size']['units'] = 'px|em|%';
		$this->patterns['color']['patterns'] = 'color';
		$this->patterns['image']['patterns'] = 'image';
		$this->patterns['align']['patterns'] = 'float|align';
		$this->patterns['align']['values'] = 'left|right';
		$this->patterns['position']['patterns'] = 'position';
		$this->patterns['position']['values'] = 'top|bottom|center|left|right';
		$this->patterns['display']['patterns'] = 'display';
		$this->patterns['display']['values'] = 'block|inline|none';
		/*
		$this->patterns['font-style']['patterns'] = 'font-style';
		$this->patterns['font-style']['values'] = 'normal|italic';
		$this->patterns['font-weight']['patterns'] = 'font-weight';
		$this->patterns['font-weight']['values'] = 'normal|bold';
		*/
	}
	
	function filters()
	{
		$this->options = apply_filters('nt_options',$this->options);
		$this->patterns = apply_filters('nt_patterns',$this->patterns);
	}
	
	function head()
	{
		if (isset($_GET['preview_style']))
		{
			print '<link rel="stylesheet" href="'.$this->css_url.$_GET['preview_style'].'" type="text/css" media="screen"/>';
		}
		else if (get_option('nt_writable') != 'not_writable')
			print '<link rel="stylesheet" href="'.$this->css_url.$this->file.'" type="text/css" media="screen"/>';
		else
		{
			print '<style type="text/css">';
			print str_replace('url(', 'url('.$this->css_url, get_option('nt_css'));
			print '</style>';
		}
	}

	function add_theme_page()
	{
		add_theme_page($this->options_title, $this->options_title, 'edit_themes', $this->page, array(&$this,'options_page'));
	}
	
	function set_file($file)
	{
		update_option('nt_file',$file);
		$this->file = get_option('nt_file');
		// update files list
		$this->themes = $this->list_files($this->path,'custom_',array('custom_template.css'));
	}

	function options_page()
	{
		$this->save_form();
		if ($this->message!='') print $this->message;

		if (isset($_POST['customize']))
		{
		?>
		<div class="wrap">
			<?php $this->form(); ?>
		</div>
		<?php
		}
		else
		{
		?>
		<div class="wrap">
			<h2><a name="top"></a><?php print $this->options_title ?></h2>
			
			<form id="nf_theme" name="nf_theme" method="post">
				
			<table class="form-table">
				<tr>
					<th scope="row">
						<label for="theme"><?php _e('Choose a custom style :','customize') ?></label>
					</th>
					<td>
						<select id="theme" name="theme">
							<?php
							print '<option value="custom_template.css"';
							if ($this->file == $this->default_file) print ' selected="selected"';
							print '>'.__('Default style','customize').'</option>';
							foreach ($this->themes as $theme)
							{
								print '<option value="'.$theme.'"';
								if (isset($_POST['preview']) && $_POST['theme'] == $theme) print ' selected="selected"';
								else if (!isset($_POST['preview']) && $this->file == $theme) print ' selected="selected"';
								print '>'.$this->format_name($theme).'</option>';
							}
							?>
						</select>
					</td>
				</tr>
				<tr>
			</table>
			<?php print '<p class="submit"><input type="submit" name="preview" value="'.__('Preview this style','customize').'"/> <input type="submit" name="submit_theme" value="'.__('Choose this style','customize').'"/> <input type="submit" name="customize" value="'.__('Customize from this style &raquo;','customize').'"/></p>'; ?>
			</form>
			</fieldset>
		</div>
		<?php
		if (isset($_POST['preview']))
		{
		?>
		<div id='preview' class='wrap'>
		<h2 id="preview-post"><?php _e('Preview', 'customize'); ?></h2>
			<iframe src="<?php print get_settings('siteurl') ?>?preview_style=<?php print $_POST['theme'] ?>" width="100%" height="600" style="border:0px none"></iframe>
		</div>
		<?php
		}
		}
	}
	
	function save_form()
	{
		do_action("nt_before_save_form");
		
		if (isset($_POST['delete'])) // delete
		{
			$file = $this->path.'custom_'.str_replace('-','_',sanitize_title($_POST['save'])).'.css'; // bad !!
			if ('custom_'.$_POST['save'].'.css' != $this->default_file)
			{
				$this->file_delete($file);
				$this->set_file($this->default_file);
			}
		}
		else if (isset($_POST['theme']) && !isset($_POST['preview'])) // theme
		{
			if ($_POST['theme'] != 'custom_my_style.css') update_option('nt_css', NULL);
			$this->set_file($_POST['theme']);
		}
		else if (isset($_POST['submit'])) // css
		{
			$this->write_css($_POST);
		}
		
		do_action("nt_after_save_form");
		$this->options = $this->parse_options($this->path.$this->file);
		
		$image_uploaded = false;
		
		if ($this->options) {
			
			foreach ($this->options as $key=>$value)
			{
				if ($this->options[$key]['type'] == 'image')
				{
					$image_uploaded = true;	
					
					if (isset($_POST[$key.'_upload_submit'])) // css
					{
						$tmp_file = $_FILES[$key.'_upload']['tmp_name'];

					    if( !is_uploaded_file($tmp_file) )
					    {
					        $this->message = '<div class="error"><p>'.__('Error while uploading.','customize').'</p></div>';
					    }
						
						$name_file = $_FILES[$key.'_upload']['name'];
						$folder = $this->path.$_POST[$key.'_upload_folder'];
						
					    if( !move_uploaded_file($tmp_file, $folder.$name_file) )
					    {
					        $this->message = '<div class="error"><p>'.__('Error while copying file.','customize').'</p></div>';
					    }
					    else
					    {
					    	$this->options[$key]['value'] = $folder.$name_file;
					    	$this->message = '<div class="updated"><p>'.__('Image uploaded.','customize').'</p></div>';
					    }
					}
				}
			}
			
		}
		
		// if image uploaded, reparse options again to add image to form
		if ($image_uploaded) $this->options = $this->parse_options($this->path.$this->file);
	}

	function form()
	{
		?>
		<fieldset class="options">
		<?php print '<form enctype="multipart/form-data" '.$display.' id="nt_'.$this->options_name.'" name="nt_'.$this->options_name.'" method="post"><input type="hidden" name="nt_posted_'.$this->options_name.'" value="update" />'; ?>
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="save"><?php _e('Save file as :','customize') ?></label>
				</th>
				<td>
				<?php
				print '<input class="text" type="text" id="save" name="save" value="';
				// This is not the best, but it will be okay for now
				if (substr($this->file,0,10) == 'custom_my_') print substr($this->file,7,-4);
				//if ($this->file != $this->default_file) print substr($this->file,7,-4);
				else
				{
					//if ($this->custom_style_exists()) print uniqid('my_style_');
					//else 
					print 'my_style';
				}
				print '"/>';
				?>
				<br/><small><?php _e('Only digits, letters and _ (underscores) allowed here','customize') ?></small>
				</td>
			</tr>
		</table>
		<?php
		$this->form_rows($this->options,$this->patterns);
		print '<p class="submit"><input class="delete" type="submit" name="delete" value="'.__('Delete','customize').'"/> <input type="submit" name="submit" value="'.__('Customize Theme &raquo;','customize').'"/></p>';
		?>
		</form>
		</fieldset>
		<?php
	}
	
	function custom_style_exists()
	{
		foreach ($this->themes as $theme)
		{
			if ($theme == 'custom_my_style.css') return true;
		}
		return false;
	}
	
	function format_name($filename)
	{
		if ($filename == 'custom_template.css') return;
		return ucfirst(str_replace('_', ' ', substr($filename,7,-4)));
	}
	
	function parse_options($file)
	{
		if (get_option('nt_writable') == 'not_writable' && get_option('nt_css'))
		{
			$data = get_option('nt_css');
		}
		else
		{
			if (!file_exists($this->path.$this->default_file))
			{
				$this->message = '<div class="error"><p>'.__('Your theme does not support customization. Nevertheless, you can create a css template yourself to provide a form with customization options.','customize').'</p></div>';				
				return;
			}
			else if (!file_exists($file))
			{
				$this->message = '<div class="error"><p>'.str_replace('%file', $file, __('File %file does not exist, default file will be used.','customize')).'</p></div>';
				$file = $this->path.$this->default_file;
				$this->set_file($this->default_file);
			}
			if (file_exists($file)) // no else !
			{
				$fh = @fopen($file, 'r'); // or die("can't open file");
				if (!$fh || filesize($file)<=0) return;
				$data = fread($fh, filesize($file));
				fclose($fh);
			}
			else
			{
				$this->message = '<div class="error"><p>'.str_replace('%file', $file, __('Impossible to read file %file.','customize')).'</p></div>';
			}
		}
		
		preg_match_all('%(/\*:.*?)(.*?)(\*/.*?)(\s+)(.*?)(:)(.*?)(;.*?)%is',$data,$matches); // waouh i did it !
		
		$names = $matches[2];
		$properties = $matches[5];
		$values = $matches[7];
		
		$return_array = array();

		for ($i=0; $i<count($matches[0]); $i++)
		{
			$option_array = array();
			$option_array = $this->assign_vars($names[$i],$properties[$i],$values[$i],$this->patterns);
			$return_array[$option_array['name']] = $option_array;
		}
		
		return $return_array;
	}
	
	function assign_vars($name,$property,$value,$patterns)
	{
		$title = $name;
		$name = str_replace('-','_',sanitize_title($name));

		$return_array = array();
		$return_array['name'] = $name;
		$return_array['title'] = $title;
		$return_array['property'] = $property;
		$return_array['value'] = $value;
		
		foreach ($patterns as $key => $pattern)
		{
			preg_match('%('.$pattern['patterns'].')%is',$return_array['property'],$match);
			
			if ($match)
			{
				$return_array['type'] = $match[0];
				$return_array['type'] = $key;
				if ($return_array['type']=='image')
				{
					preg_match('/[\(](.*)[\/]+/',$return_array['value'],$match);
					$return_array['folder'] = $match[1].'/';
					$return_array['image_list'] = $this->list_files($this->path.$return_array['folder'],'',array('Thumbs.db'));
				}
			}
		}
		
		return $return_array;
	}
	
	function write_css($data)
	{
		if (isset($data['save']) && $data['save'] != '') $this->custom_file = 'custom_'.str_replace('-','_',sanitize_title($data['save'])).'.css';
		$output_file = $this->path.$this->custom_file;
		$input_file = $this->path.$this->file;

		$fh_input = @fopen($input_file, 'r'); // or die("can't open file");
		$input_data = fread($fh_input, filesize($input_file));
		fclose($fh_input);
		
		$write_data = $input_data;
		$this->data = $data;

		$patterns = "/(\*:.*?)(.*?)(\*.*?)(\s+)(.*?)(:)(.*?)(;.*?)/e"; // Don't forget e
		$write_data = preg_replace($patterns,"\$this->filter_results('\\1','\\2','\\3','\\4','\\5','\\6','\\7','\\8', \$data)", $write_data);
		
		if (!file_exists($output_file))
		{
			if (!copy($input_file, $output_file)) {}
		}

		if (!$this->file_write($output_file, $write_data))
		{
			$this->message = '<div class="updated"><p>'.__('Writing file is impossible, css stored as option.','customize').'</p></div>';
			update_option('nt_writable','not_writable');
			update_option('nt_css',$write_data);
		}
		else update_option('nt_writable','writable');
		
		$this->set_file($this->custom_file);
	}

	function filter_results($m1,$m2,$m3,$m4,$m5,$m6,$m7,$m8,$data) // ugly, hu
	{		
		$data = $this->data;
		
		$value = $m7;
		$name = $m2;

		$data_key = str_replace('-','_',sanitize_title($name));
		$replace_value = $data[$data_key];
		if ($data[$data_key.'_unit']) $replace_value .= $data[$data_key.'_unit'];
		/*
		if (strstr($m5,'image') && strstr($m7,'png')) $replace_value .= ";
			_background-image:none;
			filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(src='".$data[$data_key]."', sizingMethod='scale')";
		*/
		//print $m5;die;
	  	return $m1.$m2.$m3.$m4.$m5.$m6.$replace_value.$m8;
	}
	
	function list_files($dirpath,$filter='',$excludes='')
	{
		$return_array=array();

		if (is_dir($dirpath))
		{
		   	if ($dh = opendir($dirpath))
		   	{
				while (false !== ($file = readdir($dh)))
				{
					if (!is_dir($dirpath.$file))
					{
						$exclude_file=false;
						if ($excludes!='')
						{
							foreach ($excludes as $exclude)
							{
								if ($exclude == $file)
								{
									$exclude_file=true;
									break;
								}
							}
						}
						if ($exclude_file!=true && $filter!='' && strstr($file, $filter) != false) $return_array[$file] = $file;
						else if ($exclude_file!=true && $filter=='') $return_array[$file] = $file;
					}
				}
			    closedir($dh);
			}
		}
		else $this->message = '<div class="error"><p>'.__('It seems that the directory','customize').' "'.$dirpath.'" '.__('does not exist','customize').'</p></div>';
	    return $return_array;
	}
	
	function file_write($file, $write_data)
	{
		$is_writable = true;
		if (file_exists($file) && is_writable($file))
		{
			if (!$fh = @fopen($file, 'w+')) $is_writable = false;
			if (!@fwrite($fh, $write_data)) $is_writable = false;
			if ($fh) fclose($fh);
		}
		else $is_writable = false;
		
		return $is_writable;
	}
	
	function file_delete($file)
	{
		$fh = @fopen($file, 'w'); // or die("can't open file");
		
		if (!$fh)
		{
			$this->message = '<div class="error"><p>'.__('Could not delete the file. You must delete the file manually.','customize').'</p></div>';
			return false;	
		}
		else
		{
			fclose($fh);
			unlink($file);
			return true;
		}
	}
}

$nice_theme = new Nice_theme(basename(__FILE__),__('Customize Theme'));
?>