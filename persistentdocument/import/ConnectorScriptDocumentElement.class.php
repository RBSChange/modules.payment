<?php
/**
 * payment_ConnectorScriptDocumentElement
 * @package modules.payment.persistentdocument.import
 */
class payment_ConnectorScriptDocumentElement extends import_ScriptDocumentElement
{
    /**
     * @return payment_persistentdocument_connector
     */
    protected function initPersistentDocument()
    {
    	return payment_ConnectorService::getInstance()->getNewDocumentInstance();
    }
    
    /**
	 * @return f_persistentdocument_PersistentDocumentModel
	 */
	protected function getDocumentModel()
	{
		return f_persistentdocument_PersistentDocumentModel::getInstanceFromDocumentModelName('modules_payment/connector');
	}
}