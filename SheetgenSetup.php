<?php
/**
 * @author Marten Koedam <marten@dalines.net>
 * @package SheetgenController
 * @subpackage SheetgenSetup
 * @since 12-okt-2007
 * @license www.dalines.org/license
 * @copyright 2007, geopelepsis.complicated
 */
class SheetgenSetup extends VoodooDefaultSetup
{
	/**
	 * 
	 */
	function verify()
	{
		// If any table exists, we shouldnt execute the setup
		$sql = "DESC TBL_SHEET_VALUES";
		$q = $this->db->query($sql);
		$q->execute(true);
		return (!(bool)$q->rows());
	}
	/**
	 * 
	 */
	function createTables()
	{
		$tables = array();
		$tables[] = "CREATE TABLE `TBL_SHEET_VALUES` (
			  `VALUE_ID` int(11) NOT NULL,
			  `SHEET_ID` int(11) NOT NULL,
			  `VALUE_INT` int(11) NOT NULL default '0',
			  `VALUE_STRING` text,
			  PRIMARY KEY  (`VALUE_ID`,`SHEET_ID`)
			)";
			
		$tables[] = "CREATE TABLE `TBL_SHEET_USER` (
			  `SHEET_ID` int(11) NOT NULL AUTO_INCREMENT,
			  `SHEET_TYPE` varchar(6) NOT NULL,
			  `NAME` varchar(64) NOT NULL,
			  `USER_ID` int(11) NOT NULL,
			  PRIMARY KEY  (`SHEET_ID`)
			)";
		$this->execute($tables);
	}
}
?>
