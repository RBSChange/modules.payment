<?php
interface payment_Order
{
	
	/**
	 * @return integer
	 */
	function getPaymentId();
	
	/**
	 * @return string
	 */
	function getPaymentReference();
	
	/**
	 * @return users_persistentdocument_user
	 */
	function getPaymentUser();
	
	
	/**
	 * @return customer_persistentdocument_address
	 */
	function getPaymentShippingAddress();
	
	
	/**
	 * @return customer_persistentdocument_address
	 */
	function getPaymentBillingAddress();
	
	/**
	 * @return double
	 */
	function getPaymentAmount();
	
	/**
	 * @return string
	 * @example "EUR", "GBP", "CHF"
	 */
	function getPaymentCurrency();
	
	/**
	 * @return string
	 * @example "fr", "en"
	 */
	function getPaymentLang();
	
	/**
	 * @return payment_persistentdocument_connector
	 */
	function getPaymentConnector();
	
	/**
	 * @return string
	 */
	function getPaymentDate();

	/**
	 * @return string
	 */
	function getPaymentCallbackURL();
	
	/**
	 * @param string $date
	 */
	function setPaymentDate($date);
	
	/**
	 * @param string $status
	 * @example "waiting", "success", "failed"
	 */
	function setPaymentStatus($status);

	/**
	 * @param string $response
	 */
	function setPaymentResponse($response);
	
	/**
	 * @param string $transactionId
	 */
	function setPaymentTransactionId($transactionId);

	/**
	 * @param string $transactionText
	 */
	function setPaymentTransactionText($transactionText);
	
	/**
	 * @return string
	 * @example "waiting", "success", "failed"
	 */
	function getPaymentStatus();

	/**
	 * @return string
	 */
	function getPaymentResponse();
	
	/**
	 * @return string
	 */
	function getPaymentTransactionId();

	/**
	 * @param string
	 */
	function getPaymentTransactionText();	
}