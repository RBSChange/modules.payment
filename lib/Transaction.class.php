<?php
class payment_Transaction
{
	const ACCEPTED  = 'A';
	const FAILED    = 'F';
	const DELAYED   = 'D';

	private $date;
	private $amount;
	private $currency;

	private $orderId;
	private $lang;
	private $connectorId;
	private $userId;
	
	
	private $transactionId;
	private $transactionText;
	private $status;
	private $rawBankResponse;
	
	/**
	 * @return integer
	 */
	public function getUserId()
	{
		return $this->userId;
	}
	
	/**
	 * @param integer $userId
	 */
	public function setUserId($userId)
	{
		$this->userId = $userId;
	}
	
	/**
	 * @return users_persistentdocument_user
	 */
	public function getUser()
	{
		return DocumentHelper::getDocumentInstance($this->userId);
	}	
	/**
	 * @return integer
	 */
	public function getConnectorId()
	{
		return $this->connectorId;
	}
	
	/**
	 * @param integer $connectorId
	 */
	public function setConnectorId($connectorId)
	{
		$this->connectorId = $connectorId;
	}
	
	/**
	 * @return payment_persistentdocument_connector
	 */
	public function getConnector()
	{
		return DocumentHelper::getDocumentInstance($this->connectorId);
	}	
	
	/**
	 * @return string
	 */
	public function getTransactionId()
	{
		return $this->transactionId;
	}
	
	/**
	 * @return string
	 */
	public function getTransactionText()
	{
		return $this->transactionText;
	}
	
	/**
	 * @param string $transactionId
	 */
	public function setTransactionId($transactionId)
	{
		$this->transactionId = $transactionId;
	}
	
	/**
	 * @param string $transactionText
	 */
	public function setTransactionText($transactionText)
	{
		$this->transactionText = $transactionText;
	}
		
	/**
	 * @return string
	 */
	public function getLang()
	{
		return $this->lang;
	}
	
	/**
	 * @param string $lang
	 */
	public function setLang($lang)
	{
		$this->lang = $lang;
	}
	/**
	 * @return string
	 */
	public function getOrderId()
	{
		return $this->orderId;
	}
	
	/**
	 * @param string $orderId
	 */
	public function setOrderId($orderId)
	{
		$this->orderId = $orderId;
	}
	
	/**
	 * @return payment_Order
	 */
	public function getOrder()
	{
		return DocumentHelper::getDocumentInstance($this->orderId);
	}
		
	/**
	 * @param String $value
	 * @return payment_Transaction
	 */
	public function setDate($value)
	{
		$this->date = $value;
		return $this;
	}

	/**
	 * @return String
	 */
	public function getDate()
	{
		return $this->date;
	}

	/**
	 * @param String $value
	 * @return payment_Transaction
	 */
	public function setAmount($value)
	{
		$this->amount = $value;
		return $this;
	}

	/**
	 * @return String
	 */
	public function getAmount()
	{
		return $this->amount;
	}

	/**
	 * @param String $value
	 * @return payment_Transaction
	 */
	public function setCurrency($value)
	{
		$this->currency = $value;
		return $this;
	}

	/**
	 * @return String
	 */
	public function getCurrency()
	{
		return $this->currency;
	}

	/**
	 * @param String $value
	 * @return payment_Transaction
	 */
	public function setRawBankResponse($value)
	{
		$this->rawBankResponse = $value;
		return $this;
	}

	/**
	 * @return String
	 */
	public function getRawBankResponse()
	{
		return $this->rawBankResponse;
	}

	/**
	 * @return payment_Transaction
	 */
	public function setAccepted()
	{
		$this->status = self::ACCEPTED;
		return $this;
	}

	/**
	 * @return Boolean
	 */
	public function isAccepted()
	{
		return self::ACCEPTED == $this->status;
	}

	/**
	 * @return payment_Transaction
	 */
	public function setFailed()
	{
		$this->status = self::FAILED;
		return $this;
	}

	/**
	 * @return Boolean
	 */
	public function isFailed()
	{
		return self::FAILED == $this->status;
	}
	
	public function setDelayed()
	{
		$this->status = self::DELAYED;
	}
	
	/**
	 * @return Boolean
	 */
	public function isDelayed()
	{
		return self::DELAYED == $this->status;
	}
}