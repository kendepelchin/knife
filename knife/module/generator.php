<?php

/**
 * This source file is a part of the Knife CLI Tool for Fork CMS.
 * More information can be found on http://www.fork-cms.com
 *
 * @author Jelmer Snoeck <jelmer.snoeck@netlash.com>
 */
class KnifeModuleGenerator extends KnifeBaseGenerator
{
	/**
	 * The module name.
	 *
	 * @var	string
	 */
	private $moduleName;

	/**
	 * The actions
	 *
	 * @var array
	 */
	private $moduleActions;

	/**
	 * This starts the generator.
	 */
	public function init()
	{
		// name given?
		if(!isset($this->arg[0])) throw new Exception('Please specify a module name');

		// clean the name
		$this->moduleName = $this->cleanString($this->arg[0]);

		// create the module
		$return = $this->createModule();

		// error handling
		if(!$return) $this->errorHandler(__CLASS__, 'createModule');
		else $this->successHandler('The module "' . ucfirst($this->moduleName) . '" is created.');
	}

	/**
	 * Creates the actions
	 *
	 * @param	string $actions		Thea ctions to create.
	 */
	private function createActions($actions)
	{
		// get the position and actions
		$explode = explode('=', $actions);

		// we have actions to create
		if(!empty($explode))
		{
			// frontend action
			if(strtolower($explode[0]) == 'f' || strtolower($explode[0]) == 'b')
			{
				// create action data
				$actionData = array();
				array_push($actionData, $this->arg[0]);
				array_push($actionData, $actions);

				// create a new action
				$action = new KnifeActionGenerator($actionData);
			}
		}
	}

	/**
	 * Create the directories
	 */
	private function createDirs()
	{
		// the backend
		$backendDirs = array(
			'main' => BACKENDPATH . 'modules/' . $this->buildDirName($this->moduleName),
			'sub' => array(
				'actions', 'js',
				'engine' => array('cronjobs'),
				'installer' => array('data'),
				'layout' => array('templates')
			)
		);

		// make the backend directories
		$this->makeDirs($backendDirs);

		// the frontend
		$frontendDirs = array(
			'main' => FRONTENDPATH . 'modules/' . $this->buildDirName($this->moduleName),
			'sub' => array(
				'actions', 'engine',
				'layout' => array('templates'),
				'js'
			)
		);

		// make the frontend directories
		$this->makeDirs($frontendDirs);
	}

	/**
	 * Create the files
	 */
	private function createFiles()
	{
		/*
		 * Backend files
		 */
		$backendPath = BACKENDPATH . 'modules/' . $this->buildDirName($this->moduleName) . '/';

		// model file
		$modelInput = $this->replaceFileInfo(CLIPATH . 'knife/module/base/backend/model.php');
		$this->makeFile($backendPath . 'engine/model.php', $modelInput);

		// config file
		$configInput = $this->replaceFileInfo(CLIPATH . 'knife/module/base/backend/config.php');
		$this->makeFile($backendPath . 'config.php', $configInput);

		// info
		$infoInput = $this->replaceFileInfo(CLIPATH . 'knife/module/base/backend/info.xml');
		$this->makeFile($backendPath . 'info.xml', $infoInput);

		if(VERSIONCODE >= 3)
		{
			$installInput = $this->replaceFileInfo(CLIPATH . 'knife/module/base/backend/installer.php');
			$this->makeFile($backendPath . 'installer/installer.php', $installInput);
		}
		else
		{
			$installInput = $this->replaceFileInfo(CLIPATH . 'knife/module/base/backend/installer.php');
			$this->makeFile($backendPath . 'installer/install.php', $installInput);
		}

		// locale
		$localeInput = $this->replaceFileInfo(CLIPATH . 'knife/module/base/backend/locale.xml');
		$this->makeFile($backendPath . 'installer/data/locale.xml', $localeInput);

		// install sql file
		$this->makeFile($backendPath . 'installer/data/install.sql');

		// javascript
		$jsInput = $this->replaceFileInfo(CLIPATH . 'knife/module/base/backend/javascript.js');
		$this->makeFile($backendPath . 'js/' . strtolower($this->moduleName) . '.js', $jsInput);

		/*
		 * Frontend files
		 */
		$frontendPath = FRONTENDPATH . 'modules/' . $this->buildDirName($this->moduleName) . '/';

		// model
		$modelInput = $this->replaceFileInfo(CLIPATH . 'knife/module/base/frontend/model.php');
		$this->makeFile($frontendPath . 'engine/model.php', $modelInput);

		// config
		$configInput = $this->replaceFileInfo(CLIPATH . 'knife/module/base/frontend/config.php');
		$this->makeFile($frontendPath . 'config.php', $configInput);

		// javascript
		$jsInput = $this->replaceFileInfo(CLIPATH . 'knife/module/base/frontend/javascript.js');
		$this->makeFile($frontendPath . 'js/' . strtolower($this->moduleName) . '.js', $jsInput);
	}

	/**
	 * This action creates a module. This will not overwrite an existing module.
	 *
	 * The data needed for this action: 'modulename'
	 * The optional data for this action: f=frontendaction1,frontendaction2 b=backendaction1,backendaction2
	 *
	 * Example: ft module blog f=detail,category b=add,edit
	 * This will create the module 'blog' with the frontendactions detail and category, and the backend actions add and edit.
	 */
	protected function createModule()
	{
		// module already exists
		if(is_dir(FRONTENDPATH . 'modules/' . strtolower($this->moduleName)) || is_dir(BACKENDPATH . 'modules/' . strtolower($this->moduleName))) return false;

		// insert into the database
		if(!$this->databaseInfo()) return false;

		// create the directories
		$this->createDirs();

		// create the files
		$this->createFiles();

		// define the module
		define('MODULE', $this->moduleName);

		// build the index files
		$this->createActions('f=index');
		$this->createActions('b=index');

		// there are more arguments given
		if(isset($this->arg[1])) $this->createActions($this->arg[1]);
		if(isset($this->arg[2])) $this->createActions($this->arg[2]);

		// return
		return true;
	}

	/**
	 * Create the database info
	 */
	private function databaseInfo()
	{
		// database instance
		$db = Knife::getDB(true);

		try
		{
			/*
			 * Insert module
			 */
			$parameters = array();
			$parameters['name'] = strtolower($this->moduleName);
			if(VERSIONCODE >= 3) $parameters['installed_on'] = gmdate('Y-m-d H:i:s');
			$db->insert('modules', $parameters);

			// group module rights
			$parameters = array();
			$parameters['group_id'] = 1;
			$parameters['module'] = strtolower($this->moduleName);
			$db->insert('groups_rights_modules', $parameters);

			/*
			 * Insert index action
			 */
			$parameters['action'] = 'index';
			$parameters['level'] = 7;
			$db->insert('groups_rights_actions', $parameters);
		}
		// houston, we have a problem.
		catch(Exception $e)
		{
			if(DEV_MODE) throw $e;
			else throw new Exception('Something went wrong while inserting the data into the database.');
		}

		// return
		return true;
	}

	/**
	 * Replaces all the info in a file
	 *
	 * @return	string
	 * @param	string $file		The file to replace the info from
	 */
	private function replaceFileInfo($file)
	{
		// replace
		$fileInput = $this->readFile($file);
		$fileInput = str_replace('classname', $this->buildName($this->moduleName), $fileInput);
		$fileInput = str_replace('subname', strtolower($this->buildName($this->moduleName)), $fileInput);
		$fileInput = str_replace('authorname', AUTHOR, $fileInput);

		// return
		return $fileInput;
	}

	/**
	 * Shows info about the modules.
	 */
	public function showAll()
	{
		// get the database instance
		$db = Knife::getDB();

		// all the modules
		$modules = array();

		// get all module directories
		$backendDirs = scandir(BACKENDPATH . 'modules/');
		$frontendDirs = scandir(FRONTENDPATH . 'modules/');
		$allDirs = array_merge($backendDirs, $frontendDirs);

		// loop the backend dirs
		foreach($allDirs as $key => $dir)
		{
			// if it is a file
			if(!is_dir(BACKENDPATH . 'modules/' . $dir) || $dir === '.' || $dir === '..' || $dir === '.svn' || array_key_exists($dir, $modules)) continue;

			// check if the module is active
			$active = $db->getVar('SELECT m.active
									FROM modules AS m
									WHERE m.name = ?',
									(string) $dir);

			// set the message if it is not installed
			$active = (empty($active)) ? 'N' : $active;

			// @todo make check if tables are set
			// @todo make check if locale is installed
			// @todo make check for the files

			// put it into the modules
			$modules[$dir] = $active;
		}

		// header
		$output = "--------------------------\n";
		$output.= "|      MODULE     |ACTIVE|\n";
		$output.= "--------------------------\n";

		// go trough the modules
		foreach($modules as $module => $active)
		{
			// get the length of the modulename
			$strLength = 17 - strlen($module);
			$strFirst = ceil($strLength / 2);

			$output.= '|';

			// input
			for($i = 0; $i < $strFirst; $i++) $output.= ' ';

			// add the module
			$output.= ($active == 'N') ? "\033[31m" : "";
			$output.= strtoupper($module);
			$output.= "\033[37m";

			// add more space
			for($i = 0; $i < ($strLength - $strFirst); $i++) $output.= ' ';

			// add state
			$output.= "|  ";
			$output.= ($active == 'N') ? "\033[31m" : "";
			$output.= $active;
			$output.= "\033[37m";
			$output.= "   |\n";
		}

		// add the end
		$output.= "--------------------------\n";

		// print it
		echo $output;
		exit;
	}

	/**
	 * Shows info about a specific module
	 *
	 * @param	string $module		The module to show info from.
	 */
	public function showInfo($module)
	{
		// the database instance
		$db = Knife::getDB();

		// get the actions
		$moduleDbActions = $db->getRecords('SELECT a.action
											FROM groups_rights_actions AS a
											WHERE a.module = ?',
											array((string) $module));

		// get the extras
		$extrasTable = (VERSIONCODE <= 3) ? 'pages_extras' : 'modules_extras';
		$moduleExtras = $db->getRecords('SELECT e.type, e.label, e.action
											FROM ' . $extrasTable . ' AS e
											WHERE e.module = ?',
											array((string) $module));

		if(VERSIONCODE >= 3)
		{
			// the installation date
			$moduleInstalled = $db->getVar('SELECT m.installed_on
												FROM modules AS m
												WHERE m.name = ?',
												array((string) $module));
			$output = 'Installed on: ' . $moduleInstalled . "\n\n";
		}
		else
		{
			// the description
			$moduleDescription = $db->getVar('SELECT m.description
												FROM modules AS m
												WHERE m.name = ?',
												array((string) $module));
			$output = $moduleDescription . "\n\n";
		}

		if(!empty($moduleDbActions))
		{
			$output.= "Installed actions:\n";
			foreach($moduleDbActions as $action) $output.= '  ' . $action['action'] . "\n";
		}
		if(!empty($moduleExtras))
		{
			$output.= "\nInstalled extras:\n";
			foreach($moduleExtras as $extra) $output.= '  ' . $extra['type'] . ': ' . $extra['label'] . " (" . $extra['action'] . ")\n";
		}

		// print it
		echo $output;
		exit;
	}
}
