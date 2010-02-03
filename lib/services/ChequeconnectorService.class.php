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
		$status = $order->getPaymentStatus();
		if ($status == 'PAYMENT_WAITING')
		{
			$sessionInfo = array('orderId' => $order->getPaymentId(), 
				'connectorId' => $connector->getId(), 
				'lang' => RequestContext::getInstance()->getLang(),
				'paymentAmount' => $order->getPaymentAmount(),
				'currencyCodeType' => $order->getPaymentCurrency(),
				'paymentURL' => $order->getPaymentCallbackURL());
			$this->setSessionInfo($sessionInfo);
			$url = LinkHelper::getActionUrl('payment', 'BankResponseCheque', array());
			$connector->setHTMLPayment("<p>" . f_Locale::translate("&modules.payment.frontoffice.Cheque-text;") ."</p><br /><p class=\"buttons\"><a class=\"link button\" href=\"$url\">".  f_Locale::translate('&modules.payment.frontoffice.cheque-payment;') ."</a></p>");
		}
		else
		{
			$this->setPaymentStatus($connector, $order);		
		}
	}
	
	/**
	 * @param payment_persistentdocument_paypalconnector $connector
	 * @param payment_Order $order
	 */	
	private function setPaymentStatus($connector, $order)
	{	
		$html = '<ol><li>' . f_Locale::translate('&modules.order.frontoffice.Orderlist-status;') . ' : ' . 
			order_OrderService::getInstance()->getStatusLabel($order->getPaymentStatus()) . '</li>'.
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
		$response->setDate($order->getPaymentDate());
		
		$response->setTransactionId('CHQ-' . $order->getPaymentReference());
		
		$trs = f_Locale::translate('&modules.payment.frontoffice.Cheque-recipient;', null, $lang) . ":\n" 
				. $connector->getRecipient() . "\n"
				. f_Locale::translate('&modules.payment.frontoffice.Cheque-address;', null, $lang) . ":\n"
				. $connector->getRecipientAddress();
				
		$response->setTransactionText($trs);
		$response->setDelayed();
		
		payment_ModuleService::getInstance()->logBankResponse($connector, $response);
		return $response;
	}
}