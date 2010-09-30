<?php
/**
 * payment_patch_0304
 * @package modules.payment
 */
class payment_patch_0304 extends patch_BasePatch
{
 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->log('Compile documents...');
		$this->execChangeCommand('compile-documents', array());
		
		$this->log('Update database...');
		$filePath = f_util_FileUtils::buildChangeBuildPath('modules','payment','dataobject','m_payment_doc_connector_payment_ogoneconnector.mysql.sql');
		$this->executeSQLFilePath($filePath);
		$this->execChangeCommand('compile-db-schema', array());
		
		$this->log('Compile htaccess, locales, editors-config...');
		$this->execChangeCommand('compile-htaccess', array());
		$this->execChangeCommand('compile-locales', array('payment'));
		$this->execChangeCommand('compile-editors-config', array());
		
		$this->log('Add lists...');
		$this->executeLocalXmlScript('ogone.xml');
	}

	
	protected function executeSQLFilePath($filePath)
	{
		$sql = file_get_contents($filePath);
		foreach(explode(";",$sql) as $query)
		{
			$query = trim($query);
			if (empty($query))
			{
				continue;
			}
			try
			{
				$this->executeSQLQuery($query);
			}
			catch (Exception $e)
			{
				$this->logError($e->getMessage());
			}
		}
	}
		
	/**
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'payment';
	}

	/**
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0304';
	}
}