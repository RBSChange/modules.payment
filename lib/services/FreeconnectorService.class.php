<?php
/**
 * payment_FreeconnectorService
 * @package modules.payment
 */
class payment_FreeconnectorService extends payment_ConnectorService
{
	/**
	 * @var payment_FreeconnectorService
	 */
	private static $instance;

	/**
	 * @return payment_FreeconnectorService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}

	/**
	 * @return payment_persistentdocument_freeconnector
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_payment/freeconnector');
	}

	/**
	 * Create a query based on 'modules_payment/freeconnector' model.
	 * Return document that are instance of modules_payment/freeconnector,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_payment/freeconnector');
	}
	
	/**
	 * Create a query based on 'modules_payment/freeconnector' model.
	 * Only documents that are strictly instance of modules_payment/freeconnector
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_payment/freeconnector', false);
	}

	/**
	 * @param payment_persistentdocument_freeconnector $connector
	 * @param payment_Order $order
	 */
	public function setPaymentInfo($connector, $order)
	{
		$transactionId = $order->getPaymentTransactionId();
		if ($transactionId != null)
		{
			$this->setPaymentStatus($connector, $order);
			return;
		}
		
		$sessionInfo = array('orderId' => $order->getPaymentId(), 
			'connectorId' => $connector->getId(), 
			'lang' => RequestContext::getInstance()->getLang(),
			'paymentAmount' => $order->getPaymentAmount(),
			'currencyCodeType' => $order->getPaymentCurrency(),
			'paymentURL' => $order->getPaymentCallbackURL());
		$this->setSessionInfo($sessionInfo);
		
		$acceptUrl = LinkHelper::getActionUrl('payment', 'ResponseFree', array('accept' => true));
		$cancelUrl = LinkHelper::getActionUrl('payment', 'ResponseFree', array('cancel' => true));
		$ls = LocaleService::getInstance();
		$formaters =  array('ucf', 'html');
		$connector->setHTMLPayment("<p>" . $ls->transFO('m.payment.frontoffice.free-text', $formaters) .
			"</p><br /><p class=\"buttons\">" .
			"<a class=\"link button\" href=\"$acceptUrl\">". $ls->transFO('m.payment.frontoffice.free-payment', $formaters) ."</a> ".
			"<a class=\"link button\" href=\"$cancelUrl\">". $ls->transFO('m.payment.frontoffice.cancel', $formaters) ."</a>".
			"</p>");
	}
	
	/**
	 * @param payment_persistentdocument_paypalconnector $connector
	 * @param payment_Order $order
	 */	
	private function setPaymentStatus($connector, $order)
	{	
		$ls = LocaleService::getInstance();
		$html = '<ol><li>' .$ls->transFO('m.order.frontoffice.Orderlist-status', array('ucf', 'html', 'lab')) . ' ' .
			 $ls->transFO('m.payment.frontoffice.free-status-'. $order->getPaymentStatus(), array('ucf', 'html')) . '</li>'.
			'<li>' . f_util_HtmlUtils::nlTobr($order->getPaymentTransactionText()) .'</li></ol>';
		$connector->setHTMLPayment($html);
	}
	
	
	/**
	 * @param array $parameters
	 * @return payment_Transaction
	 */
	public function getBankResponse($parameters)
	{	
		$response = new payment_Transaction();
		$response->setRawBankResponse(serialize($parameters));
		payment_ModuleService::getInstance()->log('FREE DATA : ' . str_replace("\n", " ", var_export($parameters, true)));
		
		$orderId = $parameters['orderId'];
		$response->setOrderId($orderId);
		
		$connectorId = $parameters['connectorId'];
		$response->setConnectorId($connectorId);
		$connector = $response->getConnector();
		
		$lang = $parameters['lang'];
		$response->setLang($parameters['lang']);
		
	
		$order = $response->getOrder();
		$response->setAmount($order->getPaymentAmount());
		$response->setCurrency($order->getPaymentCurrency());
		if ($order->getPaymentAmount() > 0)
		{
			throw new Exception("FREE PAYMENT: INVALID AMOUNT");
		}
		
		if ($parameters['status'] == 'success')
		{
			if ($connector->getAutoValidation())
			{
				$response->setDate(date_Calendar::getInstance()->toString());	
				$response->setAccepted();
			}
			else
			{
				$response->setDelayed();
			}
			$response->setTransactionId('FREE-' . $order->getPaymentReference());
			$response->setTransactionText($connector->getDescription());
		}
		else
		{
			$response->setTransactionId('CANCEL-' . $order->getPaymentReference());
			$response->setFailed();
			$response->setTransactionText('Canceled');
		}
		
		payment_ModuleService::getInstance()->logBankResponse($connector, $response);
		return $response;
	}	

	/**
	 * @param payment_persistentdocument_freeconnector $document
	 * @param Integer $parentNodeId Parent node ID where to save the document (optionnal => can be null !).
	 * @return void
	 */
//	protected function preSave($document, $parentNodeId)
//	{
//		parent::preSave($document, $parentNodeId);
//
//	}

	/**
	 * @param payment_persistentdocument_freeconnector $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function preInsert($document, $parentNodeId)
//	{
//		parent::preInsert($document, $parentNodeId);
//	}

	/**
	 * @param payment_persistentdocument_freeconnector $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postInsert($document, $parentNodeId)
//	{
//		parent::postInsert($document, $parentNodeId);
//	}

	/**
	 * @param payment_persistentdocument_freeconnector $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function preUpdate($document, $parentNodeId)
//	{
//		parent::preUpdate($document, $parentNodeId);
//	}

	/**
	 * @param payment_persistentdocument_freeconnector $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postUpdate($document, $parentNodeId)
//	{
//		parent::postUpdate($document, $parentNodeId);
//	}

	/**
	 * @param payment_persistentdocument_freeconnector $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
//	protected function postSave($document, $parentNodeId)
//	{
//		parent::postSave($document, $parentNodeId);
//	}

	/**
	 * @param payment_persistentdocument_freeconnector $document
	 * @return void
	 */
//	protected function preDelete($document)
//	{
//		parent::preDelete($document);
//	}

	/**
	 * @param payment_persistentdocument_freeconnector $document
	 * @return void
	 */
//	protected function preDeleteLocalized($document)
//	{
//		parent::preDeleteLocalized($document);
//	}

	/**
	 * @param payment_persistentdocument_freeconnector $document
	 * @return void
	 */
//	protected function postDelete($document)
//	{
//		parent::postDelete($document);
//	}

	/**
	 * @param payment_persistentdocument_freeconnector $document
	 * @return void
	 */
//	protected function postDeleteLocalized($document)
//	{
//		parent::postDeleteLocalized($document);
//	}

	/**
	 * @param payment_persistentdocument_freeconnector $document
	 * @return boolean true if the document is publishable, false if it is not.
	 */
//	public function isPublishable($document)
//	{
//		$result = parent::isPublishable($document);
//		return $result;
//	}


	/**
	 * Methode à surcharger pour effectuer des post traitement apres le changement de status du document
	 * utiliser $document->getPublicationstatus() pour retrouver le nouveau status du document.
	 * @param payment_persistentdocument_freeconnector $document
	 * @param String $oldPublicationStatus
	 * @param array<"cause" => String, "modifiedPropertyNames" => array, "oldPropertyValues" => array> $params
	 * @return void
	 */
//	protected function publicationStatusChanged($document, $oldPublicationStatus, $params)
//	{
//		parent::publicationStatusChanged($document, $oldPublicationStatus, $params);
//	}

	/**
	 * Correction document is available via $args['correction'].
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Array<String=>mixed> $args
	 */
//	protected function onCorrectionActivated($document, $args)
//	{
//		parent::onCorrectionActivated($document, $args);
//	}

	/**
	 * @param payment_persistentdocument_freeconnector $document
	 * @param String $tag
	 * @return void
	 */
//	public function tagAdded($document, $tag)
//	{
//		parent::tagAdded($document, $tag);
//	}

	/**
	 * @param payment_persistentdocument_freeconnector $document
	 * @param String $tag
	 * @return void
	 */
//	public function tagRemoved($document, $tag)
//	{
//		parent::tagRemoved($document, $tag);
//	}

	/**
	 * @param payment_persistentdocument_freeconnector $fromDocument
	 * @param f_persistentdocument_PersistentDocument $toDocument
	 * @param String $tag
	 * @return void
	 */
//	public function tagMovedFrom($fromDocument, $toDocument, $tag)
//	{
//		parent::tagMovedFrom($fromDocument, $toDocument, $tag);
//	}

	/**
	 * @param f_persistentdocument_PersistentDocument $fromDocument
	 * @param payment_persistentdocument_freeconnector $toDocument
	 * @param String $tag
	 * @return void
	 */
//	public function tagMovedTo($fromDocument, $toDocument, $tag)
//	{
//		parent::tagMovedTo($fromDocument, $toDocument, $tag);
//	}

	/**
	 * Called before the moveToOperation starts. The method is executed INSIDE a
	 * transaction.
	 *
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param Integer $destId
	 */
//	protected function onMoveToStart($document, $destId)
//	{
//		parent::onMoveToStart($document, $destId);
//	}

	/**
	 * @param payment_persistentdocument_freeconnector $document
	 * @param Integer $destId
	 * @return void
	 */
//	protected function onDocumentMoved($document, $destId)
//	{
//		parent::onDocumentMoved($document, $destId);
//	}

	/**
	 * this method is call before saving the duplicate document.
	 * If this method not override in the document service, the document isn't duplicable.
	 * An IllegalOperationException is so launched.
	 *
	 * @param payment_persistentdocument_freeconnector $newDocument
	 * @param payment_persistentdocument_freeconnector $originalDocument
	 * @param Integer $parentNodeId
	 *
	 * @throws IllegalOperationException
	 */
//	protected function preDuplicate($newDocument, $originalDocument, $parentNodeId)
//	{
//		throw new IllegalOperationException('This document cannot be duplicated.');
//	}

	/**
	 * this method is call after saving the duplicate document.
	 * $newDocument has an id affected.
	 * Traitment of the children of $originalDocument.
	 *
	 * @param payment_persistentdocument_freeconnector $newDocument
	 * @param payment_persistentdocument_freeconnector $originalDocument
	 * @param Integer $parentNodeId
	 *
	 * @throws IllegalOperationException
	 */
//	protected function postDuplicate($newDocument, $originalDocument, $parentNodeId)
//	{
//	}

	/**
	 * Returns the URL of the document if has no URL Rewriting rule.
	 *
	 * @param payment_persistentdocument_freeconnector $document
	 * @param string $lang
	 * @param array $parameters
	 * @return string
	 */
//	public function generateUrl($document, $lang, $parameters)
//	{
//	}

	/**
	 * Filter the parameters used to generate the document url.
	 *
	 * @param f_persistentdocument_PersistentDocument $document
	 * @param string $lang
	 * @param array $parameters may be an empty array
	 */
//	public function filterDocumentUrlParams($document, $lang, $parameters)
//	{
//		$parameters = parent::filterDocumentUrlParams($document, $lang, $parameters)
//		return $parameters;
//	}

	/**
	 * @param payment_persistentdocument_freeconnector $document
	 * @return integer | null
	 */
//	public function getWebsiteId($document)
//	{
//		return parent::getWebsiteId($document);
//	}

	/**
	 * @param payment_persistentdocument_freeconnector $document
	 * @return website_persistentdocument_page | null
	 */
//	public function getDisplayPage($document)
//	{
//		return parent::getDisplayPage($document);
//	}

	/**
	 * @param payment_persistentdocument_freeconnector $document
	 * @param string $forModuleName
	 * @param array $allowedSections
	 * @return array
	 */
//	public function getResume($document, $forModuleName, $allowedSections = null)
//	{
//		$resume = parent::getResume($document, $forModuleName, $allowedSections);
//		return $resume;
//	}

	/**
	 * @param payment_persistentdocument_freeconnector $document
	 * @param string $bockName
	 * @return array with entries 'module' and 'template'. 
	 */
//	public function getSolrserachResultItemTemplate($document, $bockName)
//	{
//		return array('module' => 'payment', 'template' => 'Payment-Inc-FreeconnectorResultDetail');
//	}
}