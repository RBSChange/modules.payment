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
}