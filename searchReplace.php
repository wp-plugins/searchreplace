<?php
/* 
Plugin Name: searchReplace
Description: A simple search and replace plugin. <br/>Licensed under the <a href="http://www.fsf.org/licensing/licenses/gpl.txt">GPL</a>
Version: 1.1
Author: Joost Berculo
Author URI: http://www.sargasso.nl
*/

$searchReplaceTypes = array(
	"0"=>"Disabled",
	"1"=>"Only posts and pages",
	"2"=>"Only comments",
	"3"=>"Posts, pages and comments");

function searchReplace($content, $type="post"){
	$searchReplace_options = get_option('searchReplace_options');
	if ($type=="post") {
		$types = array(1,3);
	} elseif ($type="comment") {
		$types = array(2,3);		
	} else {
		return false;
	}

	$no_regex = array();
	$regex = array();
	foreach($searchReplace_options['searchReplace'] as $pattern) {
		if (!in_array($pattern['type'],$types)) continue;
		if(isset($pattern['regEx'])) $regex[$pattern['search']] = $pattern['replace'];
		else $no_regex[$pattern['search']] = $pattern['replace'];
	}

	if (count($regex)) {
		$content = preg_replace( array_keys($regex), array_values($regex), $content);
	}
	if (count($no_regex)) {
		$content = str_replace(array_keys($no_regex), array_values($no_regex), $content);
	}
	return $content;
}

function searchReplace_option_page(){
	global $searchReplaceTypes;
	$searchReplace_options = get_option('searchReplace_options');
	if(isset($_GET['action'])&&($_GET['action']=="delete")) {
		unset($searchReplace_options['searchReplace'][$_GET['id']]);
		update_option('searchReplace_options',$searchReplace_options);
	}
	if(isset($_POST['searchReplaceAction'])&&(in_array($_POST['searchReplaceAction'],array("new","edit")))) {
		if (!isset($searchReplace_options['searchReplace'])) {
			$searchReplace_options['searchReplace'] = array();
		} 
		
		$new = array();
		if ($_POST['searchReplaceAction']=="edit") {
			$new['id'] = $_GET['id'];
		} else {
			$new['id'] = time();
		}
		$new['name'] = stripslashes($_POST['searchReplaceNameField']);
		$new['search'] = stripslashes($_POST['searchReplaceSearchField']);
		$new['replace'] = stripslashes($_POST['replaceReplaceSearchField']);
		$new['type'] = $_POST['searchReplaceType'];
		if (isset($_POST['searchReplaceRegEx'])) $new['regEx'] = $_POST['searchReplaceRegEx'];
		$add = true;
		foreach ($searchReplace_options['searchReplace'] as $option) {
			if ($option['name']== $new['name']) {
				$add = false;
				break;
			}
		}

		if ($add || ($_GET['action']=="edit")) {
			$searchReplace_options['searchReplace'][$new['id']] = $new;
			update_option('searchReplace_options',$searchReplace_options);
			echo "<div id=\"message\" class=\"updated fade\"><p>searchReplace Options Updated</p></div>\n";			
		}else {
			echo "<div id=\"error\" class=\"error fade\"><p>Update failed (name must be unique)</p></div>\n";			
		}
	}

	$allHTML = "";
	if (isset($searchReplace_options['searchReplace'])) {
		$alt = true;
		foreach($searchReplace_options['searchReplace'] as $pattern) {
			if ($alt) {
				$class="class='alternate'";
				$alt = false;
			} else {
				$class="class=''";
				$alt = true;
			}
			if(isset($pattern['regEx'])){
				$isRegex = "Yes";
			} else {
				$isRegex = "No";
			}

			$html = "<tr ".$class."><td>".$pattern['name']."</td>";
			$html .="<td>".$isRegex."</td>";
			$html .="<td>".$searchReplaceTypes[$pattern['type']]."</td>";
			$html .="<td><a href=\"".get_bloginfo( "wpurl")."/wp-admin/options-general.php?page=".array_pop(explode("/",__FILE__))."&action=delete&id=".$pattern['id']."\">delete</a> |
						 <a href=\"".get_bloginfo( "wpurl")."/wp-admin/options-general.php?page=".array_pop(explode("/",__FILE__))."&action=edit&id=".$pattern['id']."\">edit</a> </td></tr>\n";
			$allHTML .= $html;
		}
	}
	$types = "<select name = \"searchReplaceType\" id = \"searchReplaceType\">";
	if (isset($_GET['action'])&&($_GET['action']=="edit")) $type = $searchReplace_options['searchReplace'][$_GET['id']]['type'];
	else $type = -1;

	foreach	($searchReplaceTypes as $key=>$value) {
		if ($key == $type) $selected = "selected=\"selected\"";
		else $selected = "";
		$types .= "<option value = \"".$key."\" ".$selected.">".$value."</option>\n";
	}
	$types .= "</select>";
	if (isset($_GET['id']) && isset($searchReplace_options['searchReplace'][$_GET['id']]['regEx'])) $regEx = "checked=\"checked\"";
	else $regEx ="";

	if (isset($_GET['action'])&&($_GET['action']=="edit")) {
		$header = __('Edit searchReplace','Edit searchReplace');
		$hidden = '<input type="hidden" name="searchReplaceAction" value="edit"/>';
		$button = "Update searchReplace";
		$new = "(<a href=\"/wp-admin/options-general.php?page=".array_pop(explode("/",__FILE__))."\" />Add new</a>)";
	} else {
		$header = __('Add new searchReplace','Add new searchReplace');
		$hidden = '<input type="hidden" name="searchReplaceAction" value="new"/>';
		$button = "Add new searchReplace";
		$new = "";
	}
	if (isset($_GET['id'])) {
		$name = htmlspecialchars($searchReplace_options['searchReplace'][$_GET['id']]['name']);
		$search = htmlspecialchars($searchReplace_options['searchReplace'][$_GET['id']]['search']);
		$replace = $searchReplace_options['searchReplace'][$_GET['id']]['replace'];
	} else {
		$name = "";
		$search = "";
		$replace = "";
	}
			
	echo '
		<div class="wrap">
			<h2>' . __('searchReplace','searchReplace') . '</h2>
			<fieldset class="options">
				<table class="widefat" width="100%" cellpadding="5px">
					<tr>
						<th width="60%">Description</th>
						<th width="10%%">Regular Expression</th>
						<th width="20%">Type</th>
						<th width="10%">Options</th>
					</tr>
					' .$allHTML. '
				</table>
			</fieldset>
			<h3>' . $header. '</h3> '.$new.'
			<form name="searchReplace_options" method="POST">
				<p>Description:<br />
				<input size="50" type = "text" name = "searchReplaceNameField" value="'.$name.'" /></p>
				<p>Search for:<br />
				<input size="50" type = "text" name = "searchReplaceSearchField" value="'.$search.'" /></p>
				<p>Replace by:<br/>
				<textarea rows = "4" cols = "50" name = "replaceReplaceSearchField">'.$replace.'</textarea></p>
				<p>Replace type: '.$types.'</p>
				<p><input type="checkbox" name="searchReplaceRegEx" '.$regEx.'"/> Is regular expression</p>
				<p class="submit"><input type="submit" value="'.$button.'"/></p>
				'.$hidden.'
			</form>
		</div>';
}

function searchReplace_add_option_page() {
	add_options_page('searchReplace','searchReplace',9,basename(__FILE__),'searchReplace_option_page');
}

function searchReplace_install(){
	$searchReplaceInit = 'a:2:{s:7:"version";d:0.1;s:13:"searchReplace";a:18:{i:1273596852;a:6:{s:2:"id";s:10:"1273596852";s:4:"name";s:15:"BBcode bold [b]";s:6:"search";s:18:"#\[b](.+?)\[/b]#is";s:7:"replace";s:19:"<strong>$1</strong>";s:4:"type";s:1:"2";s:5:"regEx";s:2:"on";}i:1273597721;a:6:{s:2:"id";s:10:"1273597721";s:4:"name";s:17:"BBcode italic [i]";s:6:"search";s:18:"#\[i](.+?)\[/i]#is";s:7:"replace";s:11:"<em>$1</em>";s:4:"type";s:1:"2";s:5:"regEx";s:2:"on";}i:1273641075;a:6:{s:2:"id";s:10:"1273641075";s:4:"name";s:21:"BBcode blockquote [q]";s:6:"search";s:18:"#\[q](.+?)\[/q]#is";s:7:"replace";s:27:"<blockquote>$1</blockquote>";s:4:"type";s:1:"2";s:5:"regEx";s:2:"on";}i:1273641143;a:6:{s:2:"id";i:1273641143;s:4:"name";s:20:"BBcode underline [u]";s:6:"search";s:18:"#\[u](.+?)\[/u]#is";s:7:"replace";s:49:"<span style="text-decoration:underline">$1</span>";s:4:"type";s:1:"2";s:5:"regEx";s:0:"";}i:1273641180;a:6:{s:2:"id";s:10:"1273641180";s:4:"name";s:24:"BBcode strikethrough [s]";s:6:"search";s:18:"#\[s](.+?)\[/s]#is";s:7:"replace";s:52:"<span style="text-decoration:line-through">$1</span>";s:4:"type";s:1:"2";s:5:"regEx";s:2:"on";}i:1273641253;a:6:{s:2:"id";i:1273641253;s:4:"name";s:25:"BBcode text color [color]";s:6:"search";s:44:"!\[color=(#?[A-Za-z0-9]+?)](.+?)\[/color]!is";s:7:"replace";s:32:"<span style="color:$1">$2</span>";s:4:"type";s:1:"2";s:5:"regEx";s:0:"";}i:1273641298;a:6:{s:2:"id";i:1273641298;s:4:"name";s:18:"BBcode image [img]";s:6:"search";s:22:"#\[img](.+?)\[/img]#is";s:7:"replace";s:32:"<img src="$1" alt="" title="" />";s:4:"type";s:1:"2";s:5:"regEx";s:0:"";}i:1273641344;a:6:{s:2:"id";i:1273641344;s:4:"name";s:18:"BBcode size [size]";s:6:"search";s:34:"#\[size=([0-9]+?)](.+?)\[/size]#is";s:7:"replace";s:38:"<span style="font-size:$1px">$2</span>";s:4:"type";s:1:"2";s:5:"regEx";s:0:"";}i:1273641388;a:6:{s:2:"id";i:1273641388;s:4:"name";s:18:"BBcode font [font]";s:6:"search";s:44:"#\[font=([A-Za-z0-9 ;\-]+?)](.+?)\[/font]#is";s:7:"replace";s:38:"<span style="font-family:$1">$2</span>";s:4:"type";s:1:"2";s:5:"regEx";s:0:"";}i:1273641454;a:6:{s:2:"id";i:1273641454;s:4:"name";s:18:"BBcode code [code]";s:6:"search";s:24:"#\[code](.+?)\[/code]#is";s:7:"replace";s:13:"<pre>$1</pre>";s:4:"type";s:1:"2";s:5:"regEx";s:0:"";}i:1273641571;a:6:{s:2:"id";i:1273641571;s:4:"name";s:27:"BBcode blockquote 2 [quote]";s:6:"search";s:26:"#\[quote](.+?)\[/quote]#is";s:7:"replace";s:27:"<blockquote>$1</blockquote>";s:4:"type";s:1:"2";s:5:"regEx";s:0:"";}i:1273641662;a:6:{s:2:"id";i:1273641662;s:4:"name";s:30:"BBcode ordered list 1 [list=1]";s:6:"search";s:26:"#\[list=1](.+?)\[/list]#is";s:7:"replace";s:20:"<ol type="1">$1</ol>";s:4:"type";s:1:"2";s:5:"regEx";s:0:"";}i:1273641687;a:6:{s:2:"id";i:1273641687;s:4:"name";s:30:"BBcode ordered list 1 [list=a]";s:6:"search";s:26:"#\[list=a](.+?)\[/list]#is";s:7:"replace";s:20:"<ol type="a">$1</ol>";s:4:"type";s:1:"2";s:5:"regEx";s:0:"";}i:1273641769;a:6:{s:2:"id";i:1273641769;s:4:"name";s:20:"BBcode list item [*]";s:6:"search";s:16:"#\[\*](.+?)\n#is";s:7:"replace";s:11:"<li>$1</li>";s:4:"type";s:1:"2";s:5:"regEx";s:0:"";}i:1273641842;a:6:{s:2:"id";i:1273641842;s:4:"name";s:28:"BBcode background color [bg]";s:6:"search";s:38:"!\[bg=(#?[A-Za-z0-9]+?)](.+?)\[/bg]!is";s:7:"replace";s:43:"<span style="background-color:$1">$2</span>";s:4:"type";s:1:"2";s:5:"regEx";s:0:"";}i:1273641894;a:6:{s:2:"id";i:1273641894;s:4:"name";s:31:"BBcode simple url replace [url]";s:6:"search";s:22:"#\[url](.+?)\[/url]#is";s:7:"replace";s:35:"<a href="$1" target="_blank">$1</a>";s:4:"type";s:1:"2";s:5:"regEx";s:0:"";}i:1273641933;a:6:{s:2:"id";i:1273641933;s:4:"name";s:50:"BBcode advanced url replace [url] (with link name)";s:6:"search";s:28:"#\[url=(.+?)](.+?)\[/url]#is";s:7:"replace";s:35:"<a href="$1" target="_blank">$2</a>";s:4:"type";s:1:"2";s:5:"regEx";s:0:"";}i:1273642128;a:6:{s:2:"id";s:10:"1273642128";s:4:"name";s:47:"Youtube [youtube]youtube_id[/youtube] (425x350)";s:6:"search";s:30:"#\[youtube](.+?)\[/youtube]#is";s:7:"replace";s:211:"<div class="sryoutube"><object type="application/x-shockwave-flash" style="width: 425px; height: 350px;" data="http://www.youtube.com/v/$1"><param name="movie" value="http://www.youtube.com/v/$1"></object></div>";s:4:"type";s:1:"1";s:5:"regEx";s:2:"on";}}}';
	$searchReplace_options = get_option('searchReplace_options');
	if(!$searchReplace_options){
		add_option('searchReplace_options');
		$searchReplace_options = unserialize($searchReplaceInit);
		update_option('searchReplace_options', $searchReplace_options);
	} else {
		if(!isset($searchReplace_options['version'])){
			$searchReplace_options['version'] = 0.1;
			update_option('searchReplace_options', $searchReplace_options);
		}
	}
}

function searchReplace_uninstall(){
	delete_option('searchReplace_options');
}

function searchReplaceContent($content) {
	return searchReplace($content);
}

function searchReplaceComment($content) {
	return searchReplace($content, "comment");
}

register_activation_hook(__FILE__, 'searchReplace_install');
register_uninstall_hook(__FILE__, 'searchReplace_uninstall');
add_action('admin_menu', 'searchReplace_add_option_page');
add_action('the_content', 'searchReplaceContent');
add_action('comment_text', 'searchReplaceComment');
?>
