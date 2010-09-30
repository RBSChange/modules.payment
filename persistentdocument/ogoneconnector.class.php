<?php
/**
 * Class where to put your custom methods for document payment_persistentdocument_ogoneconnector
 * @package modules.payment.persistentdocument
 */
class payment_persistentdocument_ogoneconnector extends payment_persistentdocument_ogoneconnectorbase 
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

	
	public function getTemplateViewName()
	{
		return 'Ogone';
	}
	
	/**
	 * @return String
	 */
	public function getListenerURL()
	{
		return "http://" . Framework::getUIDefaultHost() . "/payment/ogoneListener.php";
	}
}