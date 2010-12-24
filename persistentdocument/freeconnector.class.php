<?php
/**
 * Class where to put your custom methods for document payment_persistentdocument_freeconnector
 * @package modules.payment.persistentdocument
 */
class payment_persistentdocument_freeconnector extends payment_persistentdocument_freeconnectorbase 
{
	/**
	 * @param string $moduleName
	 * @param string $treeType
	 * @param array<string, string> $nodeAttributes
	 */
//	protected function addTreeAttributes($moduleName, $treeType, &$nodeAttributes)
//	{
//	}
	
	/**
	 * @param string $actionType
	 * @param array $formProperties
	 */
//	public function addFormProperties($propertiesNames, &$formProperties)
//	{	
//	}

	/**
	 * @return string
	 */	
	public function getTemplateViewName()
	{
		return 'Free';
	}
}