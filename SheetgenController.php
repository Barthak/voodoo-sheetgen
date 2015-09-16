<?php
require_once(SHEETGEN_CLASSES.'Sheet.php');
/**
 * @author Marten Koedam <marten@dalines.net>
 * @package
 * @subpackage
 * @since 9-oct-2007
 * @license www.dalines.org/license
 * @copyright 2007, Dalines Software Library
 */
class SheetgenController extends DefaultController
{
	var $dispatchers = array();
	var $voodoo = false;
	var $print_preview = false;
	
	function SheetgenController($dispatcher,$actionlist,$route=true)
	{
		$this->init();
		$route && $this->route($dispatcher,$actionlist);
	}
	
	function init()
	{
		$this->DefaultController();
		
		$this->db = $this->DBConnect();
		$this->dispatchers['sheet'] = new SheetDispatcher($this);
	}
	
	function display()
	{
		if(!$this->print_preview)
			return parent::display();

		$this->addStyleSheet('sheetgen/print.css');
		return parent::display('sheet_template',SHEETGEN_TEMPLATES);
	}
}

class SheetDispatcher
{
	var $controller;
	var $privs;
	var $conf;
	var $sheet;
	
	function SheetDispatcher(&$controller)
	{
		$this->controller =& $controller;
		$this->privs = new VoodooPrivileges($controller);
		$this->conf = VoodooIni::load('sheetgen');
		$this->sheet = new Sheet($this->controller->db);
	}
	
	function hasRights($type,$obj=false)
	{
		return $this->privs->hasRights($_SESSION['access'],$type,'sheet',$this->conf['privileges'],'',$obj);
	}
	
	function dispatch($actionlist)
	{
		if(!count($actionlist))
		{
			if($this->hasRights('view'))
			{
				$template =& VoodooTemplate::getInstance();
				$template->setDir(SHEETGEN_TEMPLATES);
				$args = array('prepath'=>PATH_TO_DOCROOT);
				
				$args['sheets'] = $this->conf['sheets'];
				
				return array('Sheet Generator',$template->parse('index',$args));
			}
			return array('Error',VoodooError::displayError('Please Register First'));
		}
		elseif(is_numeric($actionlist[0]))
		{
			return $this->viewSheet($actionlist[0]);
		}
		switch($actionlist[0])
		{
			case 'create':
				return $this->createSheet($actionlist[1]);
			break;
			case 'modify':
				if(!is_numeric($actionlist[1]))
					return array('Error', VoodooError::displayError('Incorrect Argument for Modify dispatcher'));
				return $this->modifySheet($actionlist[1]);
			break;
			case 'delete':
				if(!is_numeric($actionlist[1]))
					return array('Error', VoodooError::displayError('Incorrect Argument for Delete dispatcher'));
				return $this->deleteSheet($actionlist[1]);
			break;
			case 'convert':
				if(!$this->hasRights('convert'))
					return array('Error',VoodooError::displayError('No Permission To Convert Sheets'));
				$cv = new SheetConversion($this->controller->db, $this->conf);
				if(!$cv->validate())
					return array('Error', VoodooError::displayError('Cannot convert from previous version.'));
				return array('Converting Sheets', $cv->execute());
			break;
			case 'print':
				if(!is_numeric($actionlist[1]))
					return array('Error', VoodooError::displayError('Incorrect Argument for Print dispatcher'));
				$this->controller->print_preview = true;
				return $this->viewSheet($actionlist[1],true);
			break;
		}
		
	}
	
	function deleteSheet($id)
	{
		if(!$this->sheet->setSheet($id))
			return array('Error',VoodooError::displayError('No Sheet With ID '.$id));

		if(!$this->hasRights('delete',$this->sheet))
			return array('Error',VoodooError::displayError('No Permission To Delete Sheet'));
		
		if(isset($_GET['confirm']))
		{
			$this->sheet->delete($id);
			header('Location: '.PATH_TO_DOCROOT.'/');
			exit();
		}
		$template =& VoodooTemplate::getInstance();
		$template->setDir(SHEETGEN_TEMPLATES);
		
		$args = array('prepath' => PATH_TO_DOCROOT);
		$args['buttons'] = $template->parse('button',
							array('button'=>'Yes, delete it!',
								  'prepath'=>PATH_TO_DOCROOT,
								  'button_action'=>'/sheet/delete/'.$id.'?confirm=True'));

		$args['buttons'] .= $template->parse('button',
							array('button'=>'No, thanks',
								  'prepath'=>PATH_TO_DOCROOT,
								  'button_action'=>''));
		
		return array('Confirm Delete', $template->parse('delete',$args));
	}
	
	function viewSheet($id,$print=false)
	{
		if(!$this->sheet->setSheet($id))
			return array('Error',VoodooError::displayError('No Sheet With ID '.$id));
		
		$type = $this->sheet->type;
		if(!$this->hasRights('view',$this->sheet))
			return array('Error',VoodooError::displayError('No Permission To View Sheet'));
		
		$template =& VoodooTemplate::getInstance();
		$template->setDir(SHEETGEN_TEMPLATES);

		$this->controller->addStyleSheet('sheetgen/sheet_'.$type.'.css');
		$vars = parse_ini_file(SHEETGEN_CONF.'sheet_'.$type.'.ini',true);

		$this->sheet->mode = MODE_VIEW;
		$this->sheet->code = $print?'print':$type;
		$this->sheet->loadSheet($id);
		$args = $this->sheet->buildVars($vars);
		$args['prepath'] = PATH_TO_DOCROOT;

		if(isset($_GET['message']))
			$args['message'] = VoodooError::displayError(sprintf('Succesfully Saved. The Sheet ID is: %s', $id));

		$args['buttons'] = '';
		if($this->hasRights('modify',$this->sheet))
			$args['buttons'] .= $template->parse('button',
							array('button'=>'Edit',
								  'prepath'=>PATH_TO_DOCROOT,
								  'button_action'=>'/sheet/modify/'.$id));
		if($this->hasRights('delete',$this->sheet))
			$args['buttons'] .= ' '.$template->parse('button',
							array('button'=>'Delete',
								  'prepath'=>PATH_TO_DOCROOT,
								  'button_action'=>'/sheet/delete/'.$id));
		$args['buttons'] .= ' '.$template->parse('button',
							array('button'=>'Print',
								  'prepath'=>PATH_TO_DOCROOT,
								  'button_action'=>'/sheet/print/'.$id.''));

		if($print)
			$args['buttons'] = '';
		return array($this->conf['sheets'][$type],$template->parse('sheet_'.$type,$args));
	}
	
	function modifySheet($id)
	{
		if(!$this->sheet->setSheet($id))
			return array('Error',VoodooError::displayError('No Sheet With ID '.$id));
		
		$type = $this->sheet->type;
		if(!$this->hasRights('modify',$this->sheet))
			return array('Error',VoodooError::displayError('No Permission To Modify Sheet'));

		$template =& VoodooTemplate::getInstance();
		$template->setDir(SHEETGEN_TEMPLATES);
	
		$this->controller->addStyleSheet('sheetgen/sheet_'.$type.'.css');
		$this->controller->script = '<script type="text/javascript" src="'.PATH_TO_DOCROOT.'/scripts/sheetgen/sheetgen.js"></script>';
		
		$vars = parse_ini_file(SHEETGEN_CONF.'sheet_'.$type.'.ini',true);

		if(isset($_POST['sheet']))
		{
			$this->sheet->deleteValues($id);
			$name = $_POST['value_'.$vars['main_settings']['name_field']];
			$this->sheet->saveSheet($type, $name, $id);
			header('Location: '.PATH_TO_DOCROOT.'/sheet/'.$id.'?message=true');
			exit();
		}
		$this->sheet->loadSheet($id);
		$args = $this->sheet->buildVars($vars);
		$args['prepath'] = PATH_TO_DOCROOT;
		$args['type_or_id'] = $id;
		$args['sheetaction'] = 'modify';
		$args['buttons'] = $template->parse('submit', array('button'=>'Update'));
		
		return array($this->conf['sheets'][$type],$template->parse('sheet_'.$type,$args));
	}
	
	function createSheet($type)
	{
		if(!$this->hasRights('create', false))
			return array('Error',VoodooError::displayError('No Permission To Create Sheet'));

		$template =& VoodooTemplate::getInstance();
		$template->setDir(SHEETGEN_TEMPLATES);
	
		$this->controller->addStyleSheet('sheetgen/sheet_'.$type.'.css');
		$this->controller->script = '<script type="text/javascript" src="'.PATH_TO_DOCROOT.'/scripts/sheetgen/sheetgen.js"></script>';
		
		$vars = parse_ini_file(SHEETGEN_CONF.'sheet_'.$type.'.ini',true);

		if(isset($_POST['sheet']))
		{
			$name = $_POST['value_'.$vars['main_settings']['name_field']];
			$id = $this->sheet->saveSheet($type, $name);
			header('Location: '.PATH_TO_DOCROOT.'/sheet/'.$id.'?message=true');
			exit();
		}
		else
			$args = $this->sheet->buildVars($vars);
		$args['prepath'] = PATH_TO_DOCROOT;
		$args['type_or_id'] = $type;
		$args['sheetaction'] = 'create';
		$args['buttons'] = $template->parse('submit', array('button'=>'Save'));

		return array($this->conf['sheets'][$type],$template->parse('sheet_'.$type,$args));
	}
}
/**
 * Used for conversion of Sheet from version 1.4.x to the Voodoo version
 */
class SheetConversion
{
	function SheetConversion($db, $conf)
	{
		$this->db = $db;
				require_once(CLASSES.'Database.php');
		$settings = $conf['conversion_db'];
		$connstring = $settings['driver'].":".$settings['server'].":".$settings['name'];
		$this->original_db = new Database($connstring,$settings['user'],$settings['password'],true);
		$this->sheet_conv = $conf['sheet_conversion'];
	}
	
	function execute()
	{
		$dry_run = true;
		if(isset($_GET['dry_run'])&&($_GET['dry_run']==0))
			$dry_run = false;

		$sql = "SELECT USER_EMAIL as email, USER_PASSWORD as passwd, 
				SHEET_VALUE_ID as sheet_id, SHEET_ID as type
			FROM TBL_SHEET_USER 
			ORDER BY SHEET_VALUE_ID";
		$q = $this->original_db->query($sql);
		$q->execute();
		$rv = '';
		
		$users = array();
		$failures = 0;
		$total = array();

		$user = new User($this->db);		
		while($r = $q->fetch())
		{
			$total[$r->sheet_id] = array($r->email,$this->sheet_conv[$r->type]);
			$rv .= sprintf('Converting Sheet <strong>%s</strong>... <ul>', $r->sheet_id);
			if(!$user->setUserByName($r->email))
			{
				$rv .= sprintf('<li>new user: %s</li>', $r->email);
				if(isset($users[$r->email]))
				{
					$rv .= '<li>user already in list to be created</li>';
					if($r->passwd != $users[$r->email])
					{
						$rv .= '<li>[<span class="rejected">failed</span>] = passwords dont match</li>';
						$failures++;
					}
					else
						$rv .= '<li>[<span class="blue_text">success</span>] = passwords match</li>';
				}
				else
					$users[$r->email] = $r->passwd;
			}
			else
			{
				$rv .= '<li>user exists...</li>';
				if($user->password != $r->passwd)
				{
					$rv .= '<li>[<span class="rejected">failed</span>] = passwords dont match</li>';
					$failures++;
				}
				else
					$rv .= '<li>[<span class="blue_text">success</span>] = passwords match</li>';
			}
			$rv .= '</ul><br />';
		}
		$rv .= sprintf('<br />Total Failures <span class="rejected">%s</span> '. 
			'out of <span class="blue_text">%s</span><br /><br />', $failures, count($total));
		if($dry_run)
		{
			$template =& VoodooTemplate::getInstance();
			$template->setDir(SHEETGEN_TEMPLATES);

			$rv .= $template->parse('button',
							array('button'=>'CONVERT ALL NON-FAILURES!',
								  'prepath'=>PATH_TO_DOCROOT,
								  'button_action'=>'/sheet/convert?dry_run=0'));
		}
		else
		{
			$insert = "INSERT INTO TBL_SHEET_VALUES 
				(VALUE_ID, SHEET_ID, VALUE_INT, VALUE_STRING) VALUES ";
			foreach($total as $id => $args)
			{
				list($user_name, $type) = $args;
				$user = new User($this->db);
				if(!$user->setUserByName($user_name))
				{
					$user = new User($this->db, array('name'=>$user_name,'password'=>$users[$user_name],'email'=>$user_name));
					$user->accesslevel = 30;
					$user->insert();
				}
				$res = $this->convert_sheet($insert, $id, $type, $user);
				$insert = $res;
			}
			$insert = substr($insert,0,-1);
			$q = $this->db->query($insert);
			$q->execute();
		}
		return $rv;
	}
	
	function convert_sheet($insert, $id, $type, $user)
	{
		$sql = "SELECT VALUE_ID, SHEET_VALUE_ID as SHEET_ID, VALUE_INT, VALUE_STRING
			FROM TBL_SHEET_VALUES WHERE SHEET_VALUE_ID = ??";
		$q = $this->original_db->query($sql);
		$q->bind_values($id);
		$q->execute();
		if(!$q->rows())
			return $insert;
		$charname = '';
		while($r = $q->fetch())
		{
			if($r->VALUE_ID == 1)
				$charname = $r->VALUE_STRING;
			$insert .= sprintf("(%s,%s,%s,'%s'),", $r->VALUE_ID, $r->SHEET_ID, $r->VALUE_INT, mysql_escape_string($r->VALUE_STRING));
		}
		$ins = "INSERT INTO TBL_SHEET_USER (SHEET_ID, USER_ID, SHEET_TYPE, NAME) VALUES(??,??,??,??)";
		$q = $this->db->query($ins);
		$q->bind_values($id, $user->id, $type, $charname);
		$q->execute();
		
		return $insert;
	}
	function validate()
	{
		$sql = "SELECT COUNT(USER_ID) as cnt FROM TBL_SHEET_USER";
		$q = $this->db->query($sql);
		$q->execute();
		$r = $q->fetch();
		if($r->cnt>0)
			return false;
		return true;
	}
}

?>