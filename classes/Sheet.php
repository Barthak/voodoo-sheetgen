<?php
/**
 * @author Marten Koedam <marten@dalines.net>
 * @package
 * @subpackage
 * @since 9-okt-2007
 * @license www.dalines.org/license
 * @copyright 2004-2007, geopelepsis.complicated
 */
define( "MODE_EDIT", 1 );
define( "MODE_VIEW", 2 );

class sheet
{
	var $db; 					// Obejct. Database.
								
	var $id; 					// Integer. Sheet Type Id.
	var $name; 					// String. Sheet Type Name ( eg. Vampire: the Requiem ).
	var $code;					// String. Sheet Type Code ( eg. vampire_the_requiem ).
								
	var $vars;					// Array. Variables.
								
	var $mode = MODE_EDIT;		// Integer. Modes ( MODE_EDIT, MODE_VIEW ).
	
	var $passwd = "";			// String. MD5 Password.
	var $email = "";			// String. Email.
	var $datetime = 0;			// Integer. Date( "U" ).
	var $hits = 0;				// Integer. Hits of the sheet. Deprecated.
	
	/**
	*	Contructor. Sets the database.
	*	
	*	@access public
	**/
	function Sheet( $db )
	{
		$this->db = $db;
	}
	
	/**
	*	Builds the variables for $this->code ini file
	*	
	*	@access public
	*	@return Array $vars
	**/
	function buildVars($temp)
	{
		$vars = array();
		foreach( $temp as $key => $var )
		{
			if( isset( $var["type"] ))
				$vars[$key] = $this->{"_".$var["type"]."Display"}( $var );
			else
			{
				$tmp_settings = 0;
				foreach( $var as $key1 => $name )
				{
					if( $key1 == "settings" )
						$tmp_settings = $name;
					else
					{
						$splitted = split( "_", $key1 );
						$vars[$splitted[0]] = $this->{"_make".$name}( $key1, $key, $tmp_settings );
					}
				}
			}
		}
		return $vars;
	}
	
	/**
	*	Inits the general display settings ( style, sheet-image )
	*	
	*	@access protected
	*	@param Array $vars
	*	@return string
	**/
	function _generalDisplay( $vars )
	{
		return "";
	}
	
	/**
	*	Gets the var from the var array if it isset.
	*	
	*	@access protected
	*	@param string $varName
	*	@return string
	**/
	function _getVar( $varName )
	{
		if( isset( $this->vars[$varName] ))
			return $this->vars[$varName];
		return "";
	}
	
	/**
	*	Makes the string settings output.
	*	
	*	@access protected
	*	@param string $title
	*	@param string $section deprecated
	*	@param string $settings
	*	@return string $output
	**/
	function _makestring( $title, $section, $settings )
	{
		$splitted = split( "_", $title );
		$settings = split( "_", $settings );
		$output = "<td><b>".$splitted[0].":</b></td>";
		if( $this->mode == MODE_EDIT )
			$output .= "<td align=\"right\"><input type=\"text\" value=\"".$this->_getVar( "value_".$splitted[1] )."\" size=\"".$settings[1]."\" class=\"input2\" name=\"value_".$splitted[1]."\">";
		else
			$output .= "<td align=\"left\" width=\"17%\">".$this->_getVar( "value_".$splitted[1] );
		return $output."</td>";
	}
	
	/**
	*	Makes the string settings output without a title.
	*	
	*	@access protected
	*	@param string $title
	*	@param string $section deprecated
	*	@param string $settings
	*	@return string $output
	**/
	function _makestringnoname( $title, $section, $settings )
	{
		$style = "";
		$splitted = split( "_", $title );
		$settings = split( "_", $settings );
		if( isset( $settings[2] ))
			$style = " class=\"td_".$settings[2]."\"";
		$output = "";
		if( $this->mode == MODE_EDIT )
			$output .= "<td".$style." colspan=\"".$settings[0]."\" align=\"center\"><input type=\"text\" class=\"input2\" size=\"".$settings[1]."\" value=\"".$this->_getVar( "value_".$splitted[1] )."\" name=\"value_".$splitted[1]."\">";
		else
			$output .= "<td".$style." colspan=\"".$settings[0]."\" align=\"left\">".$this->_getVar( "value_".$splitted[1] );
		return $output .= "</td>";
	}
	function _makecheck( $title, $section, $settings )
	{
		return $this->_makedots( $title, $section, $settings, 'checkbox');
	}
	/**
	*	Makes the dots output in either a radio button or checkbox.
	*	
	*	@access private
	*	@param string $title
	*	@param string $section deprecated
	*	@param string $settings
	*	@param string $type default 'radio'
	*	@return string $output
	**/
	function _makedots( $title, $section, $settings, $type = "radio" )
	{
		$click = ($type == 'radio') ? ' ondblclick="resetDot(this);" ' : '';
		$style = "";
		$splitted = split( "_", $title );
		$settings = split( "_", $settings );
		if( isset( $settings[2] ))
			$style = " class=\"td_".$settings[2]."\"";
		$content = "<td".$style."><b>".$splitted[0]."</b></td>
			<td".$style." nowrap align=\"right\">";
		$tmp_var = $this->_getVar( "value_".$splitted[1] );
		for( $i = 1; $i <= $settings[1]; $i++ )
		{
			$checked = "";
			if( $tmp_var == $i )
				$checked = "checked";
			elseif( $i <= $settings[0] )
				$checked = "checked";
			if( $this->mode == MODE_EDIT )
				$content .= "<input ".$checked.$click." type=\"".$type."\" name=\"value_".$splitted[1]."\" value=\"".$i."\">";
			else
			{
				$imgType = ( $type == "radio" ) ? "" : "_cb";
				if( $tmp_var >= $i )
					$checked = "checked";
				$content .= "<img src=\"".PATH_TO_DOCROOT."/images/sheetgen/".$this->code.$imgType."_".$checked.".gif\" hspace=\"1\">";
			}
		}
		$content .= "</td>";
		return $content;
	}
	
	/**
	*	Makes the dots output with a customizable title
	*	
	*	@access private
	*	@param string $title
	*	@param string $section deprecated
	*	@param string $settings
	*	@param string $type default 'radio'
	*	@return string $output
	**/
	function _makedotsinput( $title, $section, $settings, $type = "radio" )
	{
		$click = ($type == 'radio') ? ' ondblclick="resetDot(this);" ' : '';
		$style = "";
		$size = 20;
		$splitted = split( "_", $title );
		$settings = split( "_", $settings );
		if( isset( $settings[2] ) && $settings[2] )
			$style = " class=\"td_".$settings[2]."\"";
		if( isset($settings[3]))
			$size = $settings[3];
		$content = "<td".$style.">";
		if( $this->mode == MODE_EDIT )
			$content .= "<input type=\"text\" class=\"input2\" size=\"".$size."\" value=\"".$this->_getVar( "value_str_".$splitted[1] )."\" name=\"value_str_".$splitted[1]."\">";
		else
			$content .= $this->_getVar( "value_str_".$splitted[1] );
		$content .= "</td>
			<td".$style." nowrap align=\"right\">";
		$tmp_var = $this->_getVar( "value_".$splitted[1] );
		for( $i = 1; $i <= $settings[1]; $i++ )
		{
			$checked = "";
			if( $tmp_var == $i )
				$checked = "checked";
			elseif( $i <= $settings[0] )
				$checked = "checked";
			if( $this->mode == MODE_EDIT )
				$content .= "<input ".$checked.$click." type=\"".$type."\" name=\"value_".$splitted[1]."\" value=\"".$i."\">";
			else
			{
				$imgType = ( $type == "radio" ) ? "" : "_cb";
				if( $tmp_var >= $i )
					$checked = "checked";
				$content .= "<img src=\"".PATH_TO_DOCROOT."/images/sheetgen/".$this->code.$imgType."_".$checked.".gif\" hspace=\"1\">";
			}
		}
		$content .= "</td>";
		return $content;
	}
	
	/**
	*	Makes the dots output without a name
	*	
	*	@access private
	*	@param string $title
	*	@param string $section deprecated
	*	@param string $settings
	*	@param string $type default 'radio'
	*	@param boolean $vertical default 'false'
	*	@return string $output
	**/
	function _makedotsnoname( $title, $section, $settings, $type = "radio", $vertical = false )
	{
		$click = ($type == 'radio') ? ' ondblclick="resetDot(this);" ' : '';
		$style = "";
		$content = "";
		$splitted = split( "_", $title );
		$settings = split( "_", $settings );
		if( isset( $settings[2] ))
			$style = " class=\"td_".$settings[2]."\"";
		if( ! $vertical )
			$content .= "<td".$style." nowrap colspan=\"2\" align=\"center\">";
		$tmp_var = $this->_getVar( "value_".$splitted[1] );
		for( $i = 1; $i <= $settings[1]; $i++ )
		{
			if( $vertical )
			{
				$content .= "\n<tr><td style=\"padding: 0px 0px 0px 50px;\">".(($settings[1] - $i)+1)."</td>
					<td style=\"padding: 0px 50px 0px 0px;\" nowrap colspan=\"2\" align=\"right\">";
			}
			$checked = "";
			if( $type == "checkbox" )
				$tmp_var = $this->_getVar( "value_".$splitted[1]."_".$i );
			if( $tmp_var == $i )
				$checked = "checked";
			elseif( $i <= $settings[0] )
				$checked = "checked";
			
			if( $this->mode == MODE_EDIT )
			{
				$content .= "<input ".$checked.$click." type=\"".$type."\" name=\"value_".$splitted[1].
					(( $type == "checkbox" ) ? "_".$i : "" )."\" value=\"".$i."\">";
			}
			else
			{
				if( $type == "radio" )
				{
					if( ! $vertical && $tmp_var >= $i )
						$checked = "checked";
					elseif( $vertical && $tmp_var <= $i )
						$checked = "checked";
					else
						$checked = "";
					$content .= "<img src=\"".PATH_TO_DOCROOT."/images/sheetgen/".$this->code."_".$checked.".gif\" hspace=\"1\">";
				}
				else
				{
					if( ! $vertical && $tmp_var >= $i )
						$checked = "checked";
					elseif( $vertical && $tmp_var < $i )
						$checked = "checked";
					else
						$checked = "";
					$content .= "<img src=\"".PATH_TO_DOCROOT."/images/sheetgen/".$this->code."_cb_".$checked.".gif\" hspace=\"1\">";
				}
			}
			if( $vertical )
				$content .= "</td></tr>";
		}
		if( ! $vertical )
			$content .= "</td>";
		return $content;
	}
	
	/**
	*	Makes the checkbox output without a title.
	*	
	*	@access protected
	*	@param string $title
	*	@param string $section deprecated
	*	@param string $settings
	*	@return string $this->_makedotsnoname();
	**/
	function _makechecknoname( $title, $section, $settings )
	{
		return $this->_makedotsnoname( $title, $section, $settings, "checkbox" );
	}
	
	/**
	*	Makes the dots output without a title vertically.
	*	
	*	@access protected
	*	@param string $title
	*	@param string $section deprecated
	*	@param string $settings
	*	@return string $this->_makedotsnoname();
	**/
	function _makedotsvertical( $title, $section, $settings )
	{
		return $this->_makedotsnoname( $title, $section, $settings, "radio", true );
	}
	
	function _maketextarea($title, $section, $settings)
	{
		$style = "";
		$splitted = split( "_", $title );
		$settings = split( "_", $settings );
		if( isset( $settings[2] ))
			$style = " class=\"td_".$settings[2]."\"";
		$output = "";
		if( $this->mode == MODE_EDIT )
			$output .= "<td".$style." colspan=\"".$settings[0]."\" align=\"center\"><textarea rows=\"".$settings[1]."\" cols=\"".$settings[2]."\" name=\"value_".$splitted[1]."\">".$this->_getVar( "value_".$splitted[1] )."</textarea>";
		else
			$output .= "<td".$style." colspan=\"".$settings[0]."\" align=\"left\"><div class=\"t_area\" style=\"width: ".$settings[2]."em; height: ".($settings[1]*1.5)."em;\">".nl2br($this->_getVar( "value_".$splitted[1] )).'</div>';
		return $output .= "</td>";
	}
	
	/**
	 * Set the sheet
	 *	
	 * @access public
	 * @param integer $sheet_value_id
	 * @return boolean
	 */
	function setSheet($sheet_id)
	{
		$sql = "SELECT SHEET_TYPE, USER_ID FROM TBL_SHEET_USER ";
		$sql .= "WHERE SHEET_ID = ??";
		$ex = $this->db->query( $sql );
		$ex->bind_values( array($sheet_id));
		if(!$ex->execute())
			return false;
		if(!$ex->rows())
			return false;
		$row = $ex->fetch();
		$this->id = $sheet_id;
		$this->type = $row->SHEET_TYPE;
		$this->user = new User($this->db,$row->USER_ID);
		return true;
	}
	
	/**
	*	Verifies the submitted password with the password from the database.
	*	
	*	@access public
	*	@param string $passwd
	*	@return boolean
	**/
	function verify( $passwd )
	{
		if( md5( $passwd ) == $this->passwd )
			return true;
		return false;
	}
	
	/**
	*	Loads the sheet for $sheet_value_id
	*	
	*	@access public
	*	@param integer $sheet_value_id
	*	@return boolean
	**/
	function loadSheet($sheet_id)
	{
		$ex = $this->getSheetValues($sheet_id);
		while( $row = $ex->fetch())
		{
			$id = $row->{"VALUE_ID"};
			$int = $row->{"VALUE_INT"};
			$str = $row->{"VALUE_STRING"};
			if( $int == -1000000 )
			{
				$name = "value_".$id;
				$val = explode( ",", $str );
				foreach( $val as $i )
					$this->vars[$name."_".$i] = $i;
			}
			elseif( ! empty( $int ) && ! empty( $str ))
			{
				$this->vars["value_".$id] = $int;
				$this->vars["value_str_".$id] = $str;
			}
			elseif( ! empty( $str ))
				$this->vars["value_".$id] = $str;
			else
				$this->vars["value_".$id] = $int;
		}
		return true;
	}
	
	/**
	*	Get all the values for $sheet_value_id
	*	
	*	@access public (?)
	*	@param integer $sheet_value_id
	*	@return Object $ex
	**/
	function getSheetValues( $sheet_id )
	{
		$sql = "SELECT VALUE_INT, VALUE_STRING, VALUE_ID FROM TBL_SHEET_VALUES ";
		$sql .= "WHERE SHEET_ID = ??";
		$ex = $this->db->query( $sql );
		$ex->bind_values( array( $sheet_id ));
		$ex->execute();
		return $ex;
	}
	
	/**
	*	Save the sheet. If the option $sheet_save_id is supplied, save values for that id.
	*	
	*	@access public
	*	@param integer $sheet_save_id
	*	@return integer/boolean
	**/
	function saveSheet($type, $character_name, $sheet_id=0)
	{
		if($sheet_id == 0)
		{
			if( ! $sheet_id = $this->_addSheet($type, $character_name))
				return false;
		}
		else
			$this->_updateSheet($sheet_id, $character_name);
		$vars = $_POST;
		$saveArray = array();
		foreach( $vars as $index => $value )
		{
			if( substr( $index, 0, 5 ) == "value" && $value !== "" )
			{
				$sub = split( "_", $index );
				if( sizeof( $sub ) == 3 )
				{
					if( $sub[1] == "str" )
						$saveArray[$sub[2]]["string"] = strip_tags($value);
					else
						$saveArray[$sub[1]]["int"][] = $value;
				}
				else
				{
					if( is_numeric( $value ))
						$saveArray[$sub[1]]["int"] = $value;
					else
						$saveArray[$sub[1]]["string"] = strip_tags($value);
				}
			}
		}
		$query = array();
		foreach( $saveArray as $saveId => $value )
		{
			$int = ( isset( $value["int"] ) && ! is_array( $value["int"] )) ? $value["int"] : "";
			$str = isset( $value["string"] ) ? $value["string"] : "";
			if( isset( $value["int"] ) && is_array( $value["int"] ))
			{
				$int = -1000000;
				$str = implode( ",", $value["int"] );
			}
			$query[] = "( '".$saveId."', '".$sheet_id."', '".$int."', '".addslashes( stripslashes( $str ))."' )";
		}
		
		$sql = "INSERT INTO TBL_SHEET_VALUES ( VALUE_ID, SHEET_ID, VALUE_INT, VALUE_STRING ) VALUES ";
		$sql .= implode( ", ", $query );
		$ex = $this->db->query( $sql );
		if( $ex->execute())
			return $sheet_id;
		return false;
	}
	
	/**
	*	Deletes all the values for the $sheetValueId
	*	
	*	@access public
	*	@param integer $sheetValueId
	*	@return boolean
	**/
	function deleteValues($sheet_id)
	{
		$sql = "DELETE FROM TBL_SHEET_VALUES WHERE SHEET_ID = ??";
		$ex = $this->db->query( $sql );
		$ex->bind_values( array($sheet_id));
		return $ex->execute();
	}
	
	function delete($id=null)
	{
		$id || $id = $this->id;
		$this->deleteValues($id);
		$sql = "DELETE FROM TBL_SHEET_USER WHERE SHEET_ID = ??";
		$q =  $this->db->query($sql);
		$q->bind_values($id);
		return $q->execute();
	}
	
	/**
	*	Adds the sheet for the user
	*	
	*	@access protected
	*	@return integer
	**/
	function _addSheet($type, $character_name)
	{
		$sql = "INSERT INTO TBL_SHEET_USER ( USER_ID, SHEET_TYPE, NAME ) ";
		$sql .= "VALUES (??, ??, ??)";
		$ex = $this->db->query( $sql );
		$ex->bind_values(array($_SESSION['user_id'], $type, $character_name));
		if( $ex->execute())
			return $ex->lastid();
		return false;
	}
	
	function _updateSheet($id, $character_name)
	{
		$sql = "UPDATE TBL_SHEET_USER SET NAME = ?? ";
		$sql .= "WHERE SHEET_ID = ??";
		$ex = $this->db->query( $sql );
		$ex->bind_values(array($character_name, $id));
		if( $ex->execute())
			return true;
		return false;
	}
}
?>