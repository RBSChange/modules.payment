<?php
/**
 * payment_AtosconnectorScriptDocumentElement
 * @package modules.payment.persistentdocument.import
 */
class payment_AtosconnectorScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return payment_persistentdocument_atosconnector
     */
    protected function initPersistentDocument()
    {
    	return payment_AtosconnectorService::getInstance()->getNewDocumentInstance();
    }
	
	/**
	 * @see import_ScriptDocumentElement::getDocumentProperties()
	 *
	 * @return array
	 */
	protected function getDocumentProperties()
	{
		$properties = parent::getDocumentProperties();
		if (isset($properties['tpeCertifContent']))
		{
			$properties['tpeCertifContent'] = str_replace('\n ', "\n", $properties['tpeCertifContent']);
		}
		if (isset($properties['tpeParmcomContent']))
		{
			$properties['tpeParmcomContent'] = str_replace('\n ', "\n", $properties['tpeParmcomContent']);
		}
		return $properties;
	}
 
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_payment/atosconnector');
	}
}