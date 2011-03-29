<?php
/**
* @package ReIMG Image Resizer
* @copyright (c) 2011 DavidIQ.com
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

//Don't load hook if not installed.
if (!isset($config['reimg_version']))
{
	return;
}

/**
 * A hook that is used to change the behavior of phpBB just before the templates
 * are displayed.
 * @param	phpbb_hook	$hook	the phpBB hook object
 * @return	void
 */
function reimg_template_hook(&$hook)
{
	global $template, $config, $phpEx, $phpbb_root_path, $user;

	$page_name = substr($user->page['page_name'], 0, strpos($user->page['page_name'], '.'));

	if (in_array($page_name, array('memberlist', 'posting', 'ucp', 'viewtopic')))
	{
		define('LOAD_REIMG', true);
	}

	//This will prevent further loading of this hook.  If you need this hook loaded on a page other than the ones above either add to
	//the array above or add a define('LOAD_REIMG', true) to your page.
	if (!defined('LOAD_REIMG'))
	{
		return;
	}

	if (!function_exists('reimg_get_config'))
	{
		include($phpbb_root_path . 'includes/functions_reimg.' . $phpEx);
	}

	$user->add_lang('mods/reimg');

	//Standard template variables
	$template->assign_vars(array(
		'S_REIMG'					=> reimg_get_config('reimg_enabled', false),
		'REIMG_MAX_WIDTH'			=> reimg_get_config('reimg_max_width'),
		'REIMG_MAX_HEIGHT'			=> reimg_get_config('reimg_max_height'),
		'REIMG_REL_WIDTH'			=> reimg_get_config('reimg_rel_width'),
		'S_REIMG_SWAP_PORTRAIT'		=> reimg_get_config('reimg_swap_portrait', 0),
		'REIMG_LOADING_IMG_SRC'		=> $user->img('icon_reimg_loading', '', false, '', 'src'),
		'REIMG_LOADING_IMG_WIDTH'	=> $user->img('icon_reimg_loading', '', false, '', 'width'),
		'REIMG_LOADING_IMG_HEIGHT'	=> $user->img('icon_reimg_loading', '', false, '', 'height'),
		'S_REIMG_BUTTON'			=> (substr(reimg_get_config('reimg_link'), 0, 6) == 'button') ? 1 : 0,
		'S_REIMG_LINK'				=> (substr(reimg_get_config('reimg_link'), -4) == 'link') ? 1 : 0,
		'S_REIMG_ZOOM'				=> (substr(reimg_get_config('reimg_zoom'), 0, 8) == '_litebox') ? '_litebox' : reimg_get_config('reimg_zoom'),
		'REIMG_ZOOM_IN_IMG_SRC'		=> $user->img('icon_reimg_zoom_in', '', false, '', 'src'),
		'REIMG_ZOOM_IN_IMG_WIDTH'	=> $user->img('icon_reimg_zoom_in', '', false, '', 'width'),
		'REIMG_ZOOM_IN_IMG_HEIGHT'	=> $user->img('icon_reimg_zoom_in', '', false, '', 'height'),
		'S_REIMG_LITEBOX'			=> ((substr(reimg_get_config('reimg_zoom'), 0, 8) == '_litebox' || reimg_get_config('reimg_zoom') == '_highslide') && (reimg_get_config('reimg_max_width') || reimg_get_config('reimg_max_height') || reimg_get_config('reimg_rel_width'))) ? reimg_get_config('reimg_zoom') : '',
		'REIMG_ZOOM_OUT_IMG_SRC'	=> $user->img('icon_reimg_zoom_out', '', false, '', 'src'),
		'REIMG_ZOOM_OUT_IMG_WIDTH'	=> $user->img('icon_reimg_zoom_out', '', false, '', 'width'),
		'REIMG_ZOOM_OUT_IMG_HEIGHT'	=> $user->img('icon_reimg_zoom_out', '', false, '', 'height'),
		'REIMG_PROPERTIES'			=> reimg_properties(),
		'REIMG_AJAX_URL'			=> generate_board_url() . "/reimg/reimg_ajax.$phpEx",
	));

	//Now we need to handle some pages
	switch ($page_name)
	{
		case 'memberlist':
			if (request_var('mode', '') == 'viewprofile' && reimg_get_config('reimg_ignore_sig_img', false) == false)
			{
				if (isset($template->_tpldata['.'][0]['SIGNATURE']))
				{
					$template->assign_var('SIGNATURE', insert_reimg_properties($template->_tpldata['.'][0]['SIGNATURE']));
				}
			}
		break;

		case 'posting':
			if (!empty($template->_tpldata['topic_review_row']))
			{
				foreach ($template->_tpldata['topic_review_row'] as $row => $data)
				{
					// Alter the array
					$template->alter_block_array('topic_review_row', array(
						'MESSAGE' 	=> insert_reimg_properties($data['MESSAGE']),
					), $row, 'change');
				}
			}

			if (isset($template->_tpldata['.'][0]['PREVIEW_MESSAGE']))
			{
				$template->assign_var('PREVIEW_MESSAGE', insert_reimg_properties($template->_tpldata['.'][0]['PREVIEW_MESSAGE']));
			}

			if (isset($template->_tpldata['.'][0]['PREVIEW_SIGNATURE']))
			{
				$template->assign_var('PREVIEW_SIGNATURE', insert_reimg_properties($template->_tpldata['.'][0]['PREVIEW_SIGNATURE']));
			}
		break;

		case 'ucp':
			if (request_var('mode', '') == 'signature' && reimg_get_config('reimg_ignore_sig_img', false) == false)
			{
				if (isset($template->_tpldata['.'][0]['SIGNATURE_PREVIEW']))
				{
					$template->assign_var('SIGNATURE_PREVIEW', insert_reimg_properties($template->_tpldata['.'][0]['SIGNATURE_PREVIEW']));
				}
			}

			$prefix = '';

			if (isset($template->_tpldata['.'][0]['PREVIEW_MESSAGE']))
			{
				$prefix = 'PREVIEW_';
			}

			if (isset($template->_tpldata['.'][0][$prefix . 'MESSAGE']))
			{
				$template->assign_var($prefix . 'MESSAGE', insert_reimg_properties($template->_tpldata['.'][0][$prefix . 'MESSAGE']));
			}

			if (isset($template->_tpldata['.'][0][$prefix . 'SIGNATURE']))
			{
				$template->assign_var($prefix . 'SIGNATURE', insert_reimg_properties($template->_tpldata['.'][0][$prefix . 'SIGNATURE']));
			}

			if (!empty($template->_tpldata['history_row']))
			{
				foreach ($template->_tpldata['history_row'] as $row => $data)
				{
					// Alter the array
					$template->alter_block_array('history_row', array(
						'MESSAGE' 	=> insert_reimg_properties($data['MESSAGE']),
					), $row, 'change');
				}
			}
		break;

		case 'viewtopic':
			if (!empty($template->_tpldata['postrow']))
			{
				foreach ($template->_tpldata['postrow'] as $row => $data)
				{
					// Alter the array
					$template->alter_block_array('postrow', array(
						'MESSAGE' 	=> insert_reimg_properties($data['MESSAGE']),
						'SIGNATURE'	=> (reimg_get_config('reimg_ignore_sig_img', false) ? $data['SIGNATURE'] : insert_reimg_properties($data['SIGNATURE'])),
					), $row, 'change');
				}
			}
		break;
	}
}

// Register
$phpbb_hook->register(array('template', 'display'), 'reimg_template_hook');