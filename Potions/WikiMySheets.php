<?php
/**
 * @author Marten Koedam <marten@dalines.net>
 * @package
 * @subpackage
 * @since 30-apr-2008
 * @license www.dalines.org/license
 * @copyright 2008, geopelepsis.complicated
 */
class WikiMySheets extends WikiPotion
{
	function init()
	{
		if($_SESSION['user_id']<=0)
			return $this->setError('Please Register First');
		
		require_once(CLASSES.'TableFactory.php');
		$sql = "SELECT U.USER_NAME as Owner, SU.NAME as 'Character Name', 
				SU.SHEET_TYPE as Type, SHEET_ID as View, 'edit' as Modify
			FROM TBL_SHEET_USER as SU 
			INNER JOIN TBL_USER as U
				ON SU.USER_ID = U.USER_ID
			".(($_SESSION['access']<ADMIN_ACCESSLEVEL)?" WHERE SU.USER_ID = ".$_SESSION['user_id']." ":"")."
			ORDER BY SU.NAME";
		$q = $this->formatter->db->query($sql);
		$q->execute();
		if(!$q->rows())
			return $this->setError('You currently do not have any sheets');
		
		$tf = new TableFactory($q);
		// TODO: make callback
		if($_SESSION['access']<ADMIN_ACCESSLEVEL)
			$tf->setHiddenField('Owner');
		$tf->setValueProcessor('View', "return '<a href=\"'.PATH_TO_DOCROOT.'/sheet/'.\$col.'\">'.\$col.'</a>';");
		$tf->setValueProcessor('Modify', "return '<a href=\"'.PATH_TO_DOCROOT.'/sheet/modify/'.\$row['View'].'\">'.\$col.'</a>';");
		
		$this->display = $tf->getXHTMLTable('list report');
	}
}
?>
