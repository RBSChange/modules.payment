<?php
/**
 * payment_ConnectorService
 * @package payment
 */
class payment_ConnectorService extends f_persistentdocument_DocumentService
{
	/**
	 * @var payment_ConnectorService
	 */
	private static $instance;

	/**
	 * @return payment_ConnectorService
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
	 * @return payment_persistentdocument_connector
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_payment/connector');
	}

	/**
	 * Create a query based on 'modules_payment/connector' model.
	 * Return document that are instance of modules_payment/connector,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_payment/connector');
	}
	
	/**
	 * @return payment_persistentdocument_connector[]
	 */
	public function getConnectors()
	{
		return $this->createQuery()->add(Restrictions::published())->find();
	}


	/**
	 * this method is call before save the duplicate document.
	 * If this method not override in the document service, the document isn't duplicable.
	 * An IllegalOperationException is so launched.
	 *
	 * @param f_persistentdocument_PersistentDocument $newDocument
	 * @param f_persistentdocument_PersistentDocument $originalDocument
	 * @param Integer $parentNodeId
	 *
	 * @throws IllegalOperationException
	 */
	protected function preDuplicate($newDocument, $originalDocument, $parentNodeId)
	{
		throw new IllegalOperationException('This document cannot be duplicated.');
	}
	
	/**
	 * @param payment_Order $payment
	 */
	public function initializePayment($payment)
	{
		if (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__ . $payment->getPaymentId());
		}
	}
	
	/**
	 * @param payment_persistentdocument_connector $connector
	 * @param payment_Order $order
	 */
	public function setPaymentInfo($connector, $order)
	{
		throw new Exception('Not implemented function');		
	}
	
	/**
	 * @param payment_persistentdocument_freeconnector $connector
	 * @param payment_Order $order
	 */
	protected function setPaymentStatus($connector, $order)
	{
		$ls = LocaleService::getInstance();
		$template = TemplateLoader::getInstance()->setMimeContentType('html')
			->setPackageName('modules_payment')
			->setDirectory('templates')
			->load('Payment-Inc-PaymentStatus-Default');

		$template->setAttribute('connector', $connector);
		$template->setAttribute('order', $order);
		$template->setAttribute('status', $ls->transFO('m.payment.frontoffice.status.' . $order->getPaymentStatus(), array('ucf')));
		$template->setAttribute('transactionText', f_util_HtmlUtils::nlTobr($order->getPaymentTransactionText()));
		$connector->setHTMLPayment($template->execute(true));
	}

	/**
	 * @param payment_persistentdocument_connector $connector
	 * @return string
	 */
	public function getSelectionAsHtml($connector)
	{
		$template = TemplateLoader::getInstance()->setMimeContentType('html')
			->setPackageName('modules_payment')
			->setDirectory('templates')
			->load('Payment-Inc-Selection-' . $connector->getTemplateViewName());
		$template->setAttribute('connector', $connector);
		return $template->execute(true);
	}
		
	/**
	 * @param payment_Transaction $response
	 * @param payment_Order $order
	 */
	public function setPaymentResult($response, $order)
	{
		$order->setPaymentTransactionId($response->getTransactionId());
		$order->setPaymentTransactionText($response->getTransactionText());
		$order->setPaymentResponse($response->getRawBankResponse());
		if ($response->isAccepted())
		{
			$order->setPaymentDate($response->getDate());
			$order->setPaymentStatus('success');
		}
		else if ($order->getPaymentStatus() != 'success')
		{  
			if ($response->isFailed())
			{
				$order->setPaymentStatus('failed');
			}
			else 
			{
				$order->setPaymentStatus('waiting');
			}
		}
	}
	
	/**
	 * Parse order paymentResponse
	 * @param payment_Order $order
	 * @return array associative array<String, String>
	 */
	public function parsePaymentResponse($order)
	{
		return array();
	}
	
	/**
	 * @param array $info
	 */
	public function setSessionInfo($info)
	{
		$session = Controller::getInstance()->getContext()->getUser();
		$session->setAttribute('connector', $info, 'payment');
	}
	
	/**
	 * @return array
	 */
	public function getSessionInfo()
	{
		$session = Controller::getInstance()->getContext()->getUser();
		if ($session->hasAttribute('connector', 'payment'))
		{
			return $session->getAttribute('connector', 'payment');	
		}
		return array();
	}
	
	/**
	 * Return null if $id is not a payment_Order
	 * @param integer $id
	 * @return payment_Order
	 */
	public function getPaymentOrderById($id)
	{
		try 
		{
			$order = DocumentHelper::getDocumentInstance($id);
			if ($order instanceof payment_Order)
			{
				return $order;
			}
		}
		catch (Exception $e)
		{
			Framework::exception($e);
		}
		return null;
	}
	
	/**
	 * @param order_persistentdocument_order $order
	 * @param order_CartInfo $cartInfo
	 */
	public function setOrderAddress($order, $cartInfo)
	{
		$billingAddress = $order->getBillingAddress();
		if ($billingAddress === null)		
		{
			$billingAddress = customer_AddressService::getInstance()->getNewDocumentInstance();
			$order->setBillingAddress($billingAddress);
		}
		
		if ($cartInfo->getAddressInfo()->useSameAddressForBilling)
		{
			$cartInfo->getAddressInfo()->exportShippingAddress($billingAddress);
		}
		else
		{
			$cartInfo->getAddressInfo()->exportBillingAddress($billingAddress);
		}
		$billingAddress->setPublicationstatus('FILED');
		$billingAddress->save();
		$cartInfo->setBillingAddressId($billingAddress->getId());
	}
	
	/**
	 * @param payment_persistentdocument_connector $document
	 * @param string $errorMessage
	 */
	public function canBeFiled($document, &$errorMessage)
	{
		$ms = ModuleService::getInstance();
		if ($ms->moduleExists('catalog'))
		{
			$sfs = catalog_PaymentfilterService::getInstance();
			$query = $sfs->createQuery()->add(Restrictions::eq('connector', $document))->setProjection(Projections::rowCount('count'));
			if (f_util_ArrayUtils::firstElement($query->findColumn('count')) > 0)
			{
				$errorMessage = LocaleService::getInstance()->transBO('m.payment.bo.general.used-in-filters');
				return false;
			}
		}
		return true;
	}
}