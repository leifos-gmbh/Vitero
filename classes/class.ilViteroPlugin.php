<?php

include_once("./Services/Repository/classes/class.ilRepositoryObjectPlugin.php");

/**
 * Vitero repository object plugin
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 * @version $Id: class.ilViteroPlugin.php 39365 2013-01-21 15:55:26Z smeyer $
 *
 */
class ilViteroPlugin extends ilRepositoryObjectPlugin
{
	private static $instance = null;

	const CTYPE = 'Services';
	const CNAME = 'Repository';
	const SLOT_ID = 'robj';
	const PNAME = 'Vitero';
	
	
	/**
	 * Init vitero
	 */
	protected function init()
	{
		$this->initAutoLoad();
	}
	
	/**
	 * Get singelton instance
	 * @global ilPluginAdmin $ilPluginAdmin
	 * @return ilViteroPlugin
	 */
	public static function getInstance()
	{
		global $ilPluginAdmin;
		
		if(self::$instance)
		{
			return self::$instance;
		}
		include_once './Services/Component/classes/class.ilPluginAdmin.php';
		return self::$instance = ilPluginAdmin::getPluginObject(
			self::CTYPE,
			self::CNAME,
			self::SLOT_ID,
			self::PNAME
		);
	}

	public function getPluginName()
	{
		return "Vitero";
	}
	
	
	/**
	 * Init auto loader
	 * @return void
	 */
	protected function initAutoLoad()
	{
		spl_autoload_register(
			array($this,'autoLoad')
		);
	}

	/**
	 * Auto load implementation
	 *
	 * @param string class name
	 */
	private final function autoLoad($a_classname)
	{
		$class_file = $this->getClassesDirectory().'/class.'.$a_classname.'.php';
		@include_once($class_file);
	}

	/**
	 * drop database tables and delete ilSetting entrys
	 */
	protected function uninstallCustom()
	{
		global $ilDB;

		if ($ilDB->tableExists('rep_robj_xvit_data'))
		{
			$ilDB->dropTable('rep_robj_xvit_data');
		}

		if ($ilDB->tableExists('rep_robj_xvit_excl'))
		{
			$ilDB->dropTable('rep_robj_xvit_excl');
		}

		if ($ilDB->tableExists('rep_robj_xvit_locked'))
		{
			$ilDB->dropTable('rep_robj_xvit_locked');
		}

		if ($ilDB->tableExists('rep_robj_xvit_smap'))
		{
			$ilDB->dropTable('rep_robj_xvit_smap');
		}

		if ($ilDB->tableExists('rep_robj_xvit_umap'))
		{
			$ilDB->dropTable('rep_robj_xvit_umap');
		}

		$settings = new ilSetting('vitero_config');
		$settings->deleteAll();
	}
	
}
?>