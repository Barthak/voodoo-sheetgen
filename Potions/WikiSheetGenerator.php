<?php
/**
 * @author Marten Koedam <marten@dalines.net>
 * @package
 * @subpackage
 * @since 30-apr-2008
 * @license www.dalines.org/license
 * @copyright 2008, geopelepsis.complicated
 */
class WikiSheetGenerator extends WikiPotion
{
	function init()
	{
		$t = VoodooTemplate::getInstance();
		$old = $t->getDir();
		$t->setDir(SHEETGEN_TEMPLATES);
		$args = array('prepath'=>PATH_TO_DOCROOT);
		
		$conf = VoodooIni::load('sheetgen');
		$args['sheets'] = $conf['sheets'];
		
		$this->display = $t->parse('index',$args);
		$t->template_dir = $old;
	}
}
?>
