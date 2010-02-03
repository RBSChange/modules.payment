<?php
class payment_patch_0300 extends patch_BasePatch
{
	
	//  by default, isCodePatch() returns false.
	//  decomment the following if your patch modify code instead of the database structure or content.
	/**
	 * Returns true if the patch modify code that is versionned.
	 * If your patch modify code that is versionned AND database structure or content,
	 * you must split it into two different patches.
	 * @return Boolean true if the patch modify code that is versionned.
	 */
	//	public function isCodePatch()
	//	{
	//		return true;
	//	}
	

	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		// Remove the following line and implement the patch here.
		parent::execute();
		
		$this->log('Update database structure...');
		$this->executeSQLQuery("CREATE TABLE IF NOT EXISTS `m_payment_doc_connector` (
	  `document_id` int(11) NOT NULL default '0',
	  `document_model` varchar(50) NOT NULL default '',
	  `document_label` varchar(255),
	  `document_author` varchar(50),
	  `document_authorid` int(11),
	  `document_creationdate` datetime,
	  `document_modificationdate` datetime,
	  `document_publicationstatus` ENUM('DRAFT', 'CORRECTION', 'ACTIVE', 'PUBLICATED', 'DEACTIVATED', 'FILED', 'DEPRECATED', 'TRASH', 'WORKFLOW') NULL DEFAULT NULL,
	  `document_lang` varchar(2),
	  `document_modelversion` varchar(20),
	  `document_version` int(11),
	  `document_startpublicationdate` datetime,
	  `document_endpublicationdate` datetime,
	  `document_metas` text,
	  `code` varchar(25),
	  `merchantid` varchar(255),
	  `tpecertifcontent` text,
	  `tpeparmcomcontent` text,
	  `recipient` varchar(255),
	  `recipientaddress` text,
	  `bankserverurl` varchar(255),
	  `tpekey` varchar(255),
	  `tpepassphrase` varchar(255),
	  `tpecompanycode` varchar(255),
		PRIMARY KEY  (`document_id`)) TYPE=InnoDB CHARACTER SET utf8 COLLATE utf8_bin;");
		
		$this->executeSQLQuery("INSERT INTO `m_payment_doc_connector` 
	(`document_id`, `document_model`, `document_label`, `document_author`, `document_authorid`, `document_creationdate`, `document_modificationdate`, `document_publicationstatus`, `document_lang`, `document_modelversion`, `document_version`, `document_startpublicationdate`, `document_endpublicationdate`, `document_metas`, 
	`code`, `merchantid`, `tpecertifcontent`, `tpeparmcomcontent`, `recipient`, `recipientaddress`, 
	`bankserverurl` , `tpekey` , `tpepassphrase` , `tpecompanycode`)
	SELECT 
	 `document_id`, `document_model`, `document_label`, `document_author`, `document_authorid`, `document_creationdate`, `document_modificationdate`, `document_publicationstatus`, `document_lang`, `document_modelversion`, `document_version`, `document_startpublicationdate`, `document_endpublicationdate`, `document_metas`,
	`codereference`, `merchantid`, `tpecertifcontent`, `tpeparmcomcontent`, `recipient`, `recipientaddress`, 
	`bankserverurl`, `tpekey`, `tpepassphrase`, `tpecompanycode`
	FROM `m_synchro_doc_article` 
	WHERE `document_model` IN ('modules_order/chequebillingmode', 'modules_order/atosbillingmode', 'modules_order/cybermutbillingmode')");
		
		// Update Model name.
		$this->executeSQLQuery("UPDATE m_payment_doc_connector SET document_model = 'modules_payment/chequeconnector' WHERE document_model = 'modules_order/chequebillingmode';");
		$this->executeSQLQuery("UPDATE f_document SET document_model = 'modules_payment/chequeconnector' WHERE document_model = 'modules_order/chequebillingmode';");
		$this->executeSQLQuery("UPDATE f_relation SET document_model_id1 = 'modules_payment/chequeconnector' WHERE document_model_id1 = 'modules_order/chequebillingmode';");
		$this->executeSQLQuery("UPDATE f_relation SET document_model_id2 = 'modules_payment/chequeconnector' WHERE document_model_id2 = 'modules_order/chequebillingmode';");
		
		// Update Model name.
		$this->executeSQLQuery("UPDATE m_payment_doc_connector SET document_model = 'modules_payment/atosconnector' WHERE document_model = 'modules_order/atosbillingmode';");
		$this->executeSQLQuery("UPDATE f_document SET document_model = 'modules_payment/atosconnector' WHERE document_model = 'modules_order/atosbillingmode';");
		$this->executeSQLQuery("UPDATE f_relation SET document_model_id1 = 'modules_payment/atosconnector' WHERE document_model_id1 = 'modules_order/atosbillingmode';");
		$this->executeSQLQuery("UPDATE f_relation SET document_model_id2 = 'modules_payment/atosconnector' WHERE document_model_id2 = 'modules_order/atosbillingmode';");
		
		// Update Model name.
		$this->executeSQLQuery("UPDATE m_payment_doc_connector SET document_model = 'modules_payment/atosconnector' WHERE document_model = 'modules_order/atosbillingmode';");
		$this->executeSQLQuery("UPDATE f_document SET document_model = 'modules_payment/atosconnector' WHERE document_model = 'modules_order/atosbillingmode';");
		$this->executeSQLQuery("UPDATE f_relation SET document_model_id1 = 'modules_payment/atosconnector' WHERE document_model_id1 = 'modules_order/atosbillingmode';");
		$this->executeSQLQuery("UPDATE f_relation SET document_model_id2 = 'modules_payment/atosconnector' WHERE document_model_id2 = 'modules_order/atosbillingmode';");
		
		// Update Model name.
		$this->executeSQLQuery("UPDATE m_payment_doc_connector SET document_model = 'modules_payment/cybermutconnector' WHERE document_model = 'modules_order/cybermutbillingmode';");
		$this->executeSQLQuery("UPDATE f_document SET document_model = 'modules_payment/cybermutconnector' WHERE document_model = 'modules_order/cybermutbillingmode';");
		$this->executeSQLQuery("UPDATE f_relation SET document_model_id1 = 'modules_payment/cybermutconnector' WHERE document_model_id1 = 'modules_order/cybermutbillingmode';");
		$this->executeSQLQuery("UPDATE f_relation SET document_model_id2 = 'modules_payment/cybermutconnector' WHERE document_model_id2 = 'modules_order/cybermutbillingmode';");
		
		$ts = TreeService::getInstance();
		$newRootId = ModuleService::getInstance()->getRootFolderId('payment');
		$rootNode = DocumentHelper::getDocumentInstance($newRootId);
		
		$connectors = payment_ConnectorService::getInstance()->createQuery()->find();
		$this->log('Move existing connector to payment tree... (' . count($connectors) . ' connectors to move)');
		
		foreach ($connectors as $connector)
		{
			// Move the document.
			$treeNode = $ts->getInstanceByDocument($connector);
			if ($treeNode !== null)
			{
				$ts->deleteNode($treeNode);
			}
			$ts->newLastChildForNode($ts->getInstanceByDocument($rootNode), $connector->getId());
		}
	}
	
	/**
	 * Returns the name of the module the patch belongs to.
	 *
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'payment';
	}
	
	/**
	 * Returns the number of the current patch.
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0300';
	}

}