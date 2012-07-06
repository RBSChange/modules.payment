<?php
/**
 * @package modules.payment
 * @method payment_ChequeconnectorService getInstance()
 */
class payment_ChequeconnectorService extends payment_ConnectorService
{
	/**
	 * @return payment_persistentdocument_chequeconnector
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_payment/chequeconnector');
	}

	/**
	 * Create a query based on 'modules_payment/chequeconnector' model.
	 * Return document that are instance of modules_payment/chequeconnector,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_payment/chequeconnector');
	}
	
	/**
	 * Create a query based on 'modules_payment/chequeconnector' model.
	 * Only documents that are strictly instance of modules_payment/chequeconnector
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->getPersistentProvider()->createQuery('modules_payment/chequeconnector', false);
	}
	
	/**
	 * @param payment_persistentdocument_chequeconnector $connector
	 * @param payment_Order $order
	 * @throws Exception
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
		
		$acceptUrl = LinkHelper::getActionUrl('payment', 'BankResponseCheque', array('accept' => true));
		$cancelUrl = LinkHelper::getActionUrl('payment', 'BankResponseCheque', array('cancel' => true));
		$template = change_TemplateLoader::getNewInstance()->setExtension('html')
			->load('modules', 'payment', 'templates', 'Payment-Inc-PaymentForm-' . $connector->getTemplateViewName());
		if ($template === null)
		{
			throw new Exception('Template not found: Payment-Inc-PaymentForm-' . $connector->getTemplateViewName());
		}
		$template->setAttribute('connector', $connector);
		$template->setAttribute('order', $order);
		$template->setAttribute('acceptUrl', $acceptUrl);
		$template->setAttribute('cancelUrl', $cancelUrl);
		$connector->setHTMLPayment($template->execute(true));
	}
	
	/**
	 * @param array $parameters
	 * @return payment_Transaction
	 */
	public function getBankResponse($parameters)
	{	
		$response = new payment_Transaction();
		$response->setRawBankResponse(serialize($parameters));
		payment_ModuleService::getInstance()->log('CHEQUE BANKING DATA : ' . str_replace("\n", " ", var_export($parameters, true)));
		
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
		
		if ($parameters['status'] == 'waiting')
		{
			$response->setDelayed();
			$response->setTransactionId('CHQ-' . $order->getPaymentReference());
		
			$ls = LocaleService::getInstance();
			$trs = $ls->formatKey($lang, 'm.payment.frontoffice.cheque-recipient', array('ucf', 'lab')) . ' ' 
				. $connector->getRecipient() . "\n"
				. $ls->formatKey($lang, 'm.payment.frontoffice.cheque-address', array('ucf', 'lab')) . ' '
				. $connector->getRecipientAddress();
			$response->setTransactionText($trs);	
		}
		else
		{
			$response->setTransactionId('CHQ-' . $order->getPaymentReference());
			$response->setFailed();
			$response->setTransactionText('Canceled');
		}
		
		payment_ModuleService::getInstance()->logBankResponse($connector, $response);
		return $response;
	}
}