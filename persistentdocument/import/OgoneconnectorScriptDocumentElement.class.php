<?php
/**
 * payment_OgoneconnectorScriptDocumentElement
 * @package modules.payment.persistentdocument.import
 */
class payment_OgoneconnectorScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return payment_persistentdocument_ogoneconnector
     */
    protected function initPersistentDocument()
    {
    	return payment_OgoneconnectorService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_payment/ogoneconnector');
	}
}