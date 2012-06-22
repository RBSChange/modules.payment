<?php
class commands_ApplyAtosPolicy extends c_ChangescriptCommand
{
	/**
	 * @return string
	 */
	function getUsage()
	{
		return "";
	}
	
	/**
	 * @return string
	 */
	function getDescription()
	{
		return "";
	}
	
	/**
	 * @return boolean default false
	 */
	function isHidden()
	{
		return true;
	}

	/**
	 * @see c_ChangescriptCommand::getEvents()
	 */
	public function getEvents()
	{
		return array(
			array('target' => 'apply-webapp-policy'),
		);
	}
	
	/**
	 * @param string[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Apply Atos Binaries Policy ==");

		$files = array(PROJECT_HOME."/bin/request", PROJECT_HOME."/bin/response");
		foreach ($files as $file)
		{
			f_util_FileUtils::chmod($file, "755");
		}
	}
}