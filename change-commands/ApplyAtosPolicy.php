<?php
/**
 * Should be in website module
 */
class commands_ApplyAtosPolicy extends commands_AbstractChangeCommand
{
	/**
	 * @return String
	 */
	function getUsage()
	{
		return "";
	}
	
	/**
	 * @return String
	 */
	function getDescription()
	{
		return "";
	}
	
	/**
	 * @return Boolean default false
	 */
	function isHidden()
	{
		return true;
	}

	/**
	 * @param String[] $params
	 * @param array<String, String> $options where the option array key is the option name, the potential option value or true
	 * @see c_ChangescriptCommand::parseArgs($args)
	 */
	function _execute($params, $options)
	{
		$this->message("== Apply Atos Binaries Policy ==");

		$files = array(WEBEDIT_HOME."/bin/request", WEBEDIT_HOME."/bin/response");
		foreach ($files as $file)
		{
			f_util_FileUtils::chmod($file, "755");
		}
	}
}