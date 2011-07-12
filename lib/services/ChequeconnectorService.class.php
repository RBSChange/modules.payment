<?php
/**
 * payment_ChequeconnectorService
 * @package payment
 */
class payment_ChequeconnectorService extends payment_ConnectorService
{
	/**
	 * @var payment_ChequeconnectorService
	 */
	private static $instance;

	/**
	 * @return payment_ChequeconnectorService
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
		return $this->pp->createQuery('modules_payment/chequeconnector');
	}
	
	/**
	 * Create a query based on 'modules_payment/chequeconnector' model.
	 * Only documents that are strictly instance of modules_payment/chequeconnector
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_payment/chequeconnector', false);
	}
	
	/**
	 * @param payment_persistentdocument_chequeconnector $connector
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
		
		$acceptUrl = LinkHelper::getActionUrl('payment', 'BankResponseCheque', array('accept' => true));
		$cancelUrl = LinkHelper::getActionUrl('payment', 'BankResponseCheque', array('cancel' => true));
		$connector->setHTMLPayment("<p>" . f_Locale::translate("&modules.payment.frontoffice.Cheque-text;") .
			"</p><br /><p class=\"buttons\">" .
			"<a class=\"button\" href=\"$acceptUrl\">".  f_Locale::translate('&modules.payment.frontoffice.cheque-payment;') ."</a> ".
			"<a class=\"button\" href=\"$cancelUrl\">".  f_Locale::translate('&modules.payment.frontoffice.cancel;') ."</a>".
			"</p>");
	}
	
	/**
	 * @param payment_persistentdocument_paypalconnector $connector
	 * @param payment_Order $order
	 */	
	private function setPaymentStatus($connector, $order)
	{	
		$html = '<ol class="messages"><li>' . f_Locale::translate('&modules.order.frontoffice.Orderlist-status;') . ' : ' . 
			 f_Locale::translate('&modules.payment.frontoffice.status.'. ucfirst($order->getPaymentStatus())  .';') . '</li>'.
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
		//$response->setDate(date_Calendar::getInstance()->toString());		

		
		if ($parameters['status'] == 'waiting')
		{
			$response->setDelayed();
			$response->setTransactionId('CHQ-' . $order->getPaymentReference());
		
			$trs = f_Locale::translate('&modules.payment.frontoffice.Cheque-recipient;', null, $lang) . ":\n" 
				. $connector->getRecipient() . "\n"
				. f_Locale::translate('&modules.payment.frontoffice.Cheque-address;', null, $lang) . ":\n"
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