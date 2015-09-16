<?php
/**
 * @author Marten Koedam <marten@dalines.net>
 * @package
 * @subpackage
 * @since 6-jan-2008
 * @license www.dalines.org/license
 * @copyright 2008, geopelepsis.complicated
 */
class SheetgenHooks extends VoodooHooks
{
	function formattingHooks()
	{
		return array('sheetlinks'=>array(&$this,'sheetLinks'));
	}
	
	function sheetLinks($str,&$formatter)
	{
		return preg_replace_callback("/\[sheetgen:(.*)\]/siU", array(&$this,'_callbackSheet'), $str);
	}
	
	function _callbackSheet($matches)
	{
		$displayname = $toPage = $matches[1];
		if(preg_match('/ (.*)/',$matches[1],$match))
		{
			$toPage = str_replace($match[0], '', $matches[1]);
			$displayname = $match[1];
		}
		if(is_numeric($toPage))
			return '<a href="'.PATH_TO_DOCROOT.'/sheet/'.$toPage.'">'.$displayname.'</a>';
		elseif(!in_array($toPage,array('create','load')))
			return $displayname;
			
		switch($toPage)
		{
			case 'create':
				
			break;
			case 'load':
				return 'input';
			break;
		}
			
		return '<a href="'.PATH_TO_DOCROOT.'/sheet/'.$toPage.'">'.$displayname.'</a>';
	}
}
?>
