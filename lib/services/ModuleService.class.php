<?php
/**
 * @package modules.payment
 * @method payment_ModuleService getInstance()
 */
class payment_ModuleService extends ModuleBaseService
{
	
	/**
	 * @param string $stringLine
	 */
	public function log($stringLine)
	{
		change_LoggingService::getInstance()->namedLog($stringLine, 'payment');
	}
	
	/**
	 * @param payment_persistentdocument_connector $connector
	 * @param payment_Transaction $response
	 */
	public function logBankResponse($connector, $response)
	{
		$logInfo = array();
		$logInfo[] = "date : " . $response->getDate();
		$logInfo[] = "lang : " . $response->getLang();
		$order = $response->getOrder();
		$logInfo[] = "order : " . $order->getPaymentReference();
		$logInfo[] = "orderId : " . $order->getPaymentId();
		$logInfo[] = "amout : " . $response->getAmount() . $response->getCurrency();
		$logInfo[] = "transactionId : " . $response->getTransactionId();
		$logInfo[] = "transaction : " . str_replace("\n", " ", $response->getTransactionText());	
		$this->log($connector->getLogLabel()  . ' DECODED DATA : ' . implode(', ', $logInfo));
	}
	
	/**
	 * @param integer $orderId
	 * @return payment_Order
	 */
	public function getOrderById($orderId)
	{
		if (intval($orderId) > 0)
		{
			return DocumentHelper::getDocumentInstance($orderId);
		}
		return null;
	}
	
	const PAYMENT_SESSION_NAMESPACE = 'payment';
	
	/**
	 * @return payment_Transaction
	 */
	public function getCurrentTransaction()
	{
		$storage = change_Controller::getInstance()->getInstance()->getStorage();
		$data = $storage->read('CurrentTransaction', self::PAYMENT_SESSION_NAMESPACE);	
		if (f_util_StringUtils::isNotEmpty($data))
		{
			return unserialize($data);
		}
		return null;
	}
	
	/**
	 * @param payment_Transaction $transaction
	 */
	public function setCurrentTransaction($transaction)
	{
		if ($transaction instanceof payment_Transaction) 
		{
			$data = serialize($transaction);
		}
		else
		{
			$data = null;
		}
		$storage = change_Controller::getInstance()->getInstance()->getStorage();
		$storage->write('CurrentTransaction', $data, self::PAYMENT_SESSION_NAMESPACE);
	}
	
	/**
	 * @param payment_Order $order
	 * @return payment_Transaction
	 */	
	public function getNewTransactionInstance($order)
	{
		$transaction = new payment_Transaction();
		if ($order instanceof payment_Order) 
		{
			$transaction->setOrderId($order->getPaymentId());
			$transaction->setConnectorId($order->getPaymentConnector()->getId());
			$transaction->setUserId($order->getPaymentUser());
			
			$transaction->setLang($order->getPaymentLang());
			
			$transaction->setDate($order->getPaymentDate());
			$transaction->setAmount($order->getPaymentAmount());
			$transaction->setCurrency($order->getPaymentCurrency());
			$transaction->setTransactionId($order->getPaymentTransactionId());
			$transaction->setTransactionText($order->getPaymentTransactionText());
			$transaction->setRawBankResponse($order->getPaymentResponse());
		}
		return $transaction;
	}
}