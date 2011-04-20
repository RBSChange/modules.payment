<?php
/**
 * payment_PaypalconnectorService
 * @package payment
 */
class payment_PaypalconnectorService extends payment_ConnectorService
{
	/**
	 * @var payment_PaypalconnectorService
	 */
	private static $instance;
	
	/**
	 * @return payment_PaypalconnectorService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance ( get_class () );
		}
		return self::$instance;
	}
	
	/**
	 * @return payment_persistentdocument_paypalconnector
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName ( 'modules_payment/paypalconnector' );
	}
	
	/**
	 * Create a query based on 'modules_payment/paypalconnector' model.
	 * Return document that are instance of modules_payment/paypalconnector,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery ( 'modules_payment/paypalconnector' );
	}
	
	/**
	 * Create a query based on 'modules_payment/paypalconnector' model.
	 * Only documents that are strictly instance of modules_payment/paypalconnector
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery ( 'modules_payment/paypalconnector', false );
	}
	
	/**
	 * @param payment_persistentdocument_paypalconnector $connector
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
		
		$sessionInfo = $this->getSessionInfo();
		if (!isset($sessionInfo['payerId']))
		{
			$this->connecting($connector, $order);
		}
		else
		{
			$this->payment($connector, $order);
		}	
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
	 * @param payment_persistentdocument_paypalconnector $connector
	 * @param payment_Order $order
	 */	
	private function payment($connector, $order)
	{	
		$url = LinkHelper::getActionUrl('payment', 'PayPalPayment', array());
		$connector->setHTMLPayment( "<a href=\"$url\">".  f_Locale::translate('&modules.payment.frontoffice.payment;')  ."</a>" );
	}
	
	/**
	 * @param payment_persistentdocument_paypalconnector $connector
	 * @param payment_Order $order
	 */	
	private function connecting($connector, $order)
	{
		$paymentType = "Sale";
		$paymentAmount = $order->getPaymentAmount ();
		$currencyCodeType = $order->getPaymentCurrency ();
		
		$addr = $order->getPaymentShippingAddress();
		if ($addr === null)
		{
			$errMsg = f_Locale::translate("&modules.payment.frontoffice.No-shipping-address-defined;");
			$connector->setHTMLPayment("<ul class=\"errors\"><li class=\"first last\">$errMsg</li></ol>");			
			return;
		}
		
		$name = $addr->getTitle () ? $addr->getTitle ()->getLabel () . ' ' : '';
		$shipToName = $name . $addr->getFirstname () . ' ' . $addr->getLastname ();
		$shipToStreet = $addr->getAddressLine1 ();
		$shipToStreet2 = $addr->getAddressLine2 (); //Leave it blank if there is no value
		$shipToCity = $addr->getCity ();
		$shipToState = $addr->getProvince ();
		$shipToCountryCode = strtoupper ( $addr->getCountry () ? $addr->getCountry ()->getCode () : 'FR' ); // Please refer to the PayPal country codes in the API documentation
		$shipToZip = $addr->getZipCode ();
		$phoneNum = $addr->getPhone ();
		
		$returnURL = $this->getSuccessURL ();
		$cancelURL = $this->getCancelURL ();
		$resArray = $connector->callMarkExpressCheckout ( $paymentAmount, $currencyCodeType, $paymentType, $returnURL, $cancelURL, $shipToName, $shipToStreet, $shipToCity, $shipToState, $shipToCountryCode, $shipToZip, $shipToStreet2, $phoneNum );
		
		$ack = strtoupper ( $resArray ["ACK"] );
		if ($ack == "SUCCESS")
		{
			$sessionInfo = array ('token' => urldecode ( $resArray ["TOKEN"] ), 
				'connectorId' => $connector->getId (), 
				'orderId' => $order->getPaymentId (), 
				'paymentAmount' => $paymentAmount,
				'paymentType' => $paymentType,  
				'currencyCodeType' => $currencyCodeType, 
				'lang' => RequestContext::getInstance ()->getLang (),
				'paymentURL' => $order->getPaymentCallbackURL()
			);
			
			$this->setSessionInfo($sessionInfo);
			payment_ModuleService::getInstance ()->log ( sprintf ( "PAYPAL BANKING (%s): prepare (token: '%s', amount: '%s', currency: '%s', language: '%s').", $connector->getLogLabel (), $sessionInfo ['token'], $paymentAmount, $currencyCodeType, $sessionInfo ['lang'] ) );			
			$img = f_Locale::translate("&modules.payment.frontoffice.paypal.button;", null, null, false);
			if ($img === null)
			{
				$img = 'https://www.paypal.com/fr_XC/i/btn/btn_xpressCheckout.gif';
				if (Framework::isDebugEnabled())
				{
					Framework::debug(__METHOD__ . ' DEFAULT ' . $img);
				}
			}
			else
			{
				if (Framework::isDebugEnabled())
				{
					Framework::debug(__METHOD__ . ' LOCAL ' . $img);
				}
			}
			$url = $connector->redirectToPayPal ( $sessionInfo ['token'] );
			$connector->setHTMLPayment ( "<a href=\"$url\"><img src=\"$img\" /></a>" );
		}
		else
		{
			//Display a user friendly Error on the page using any of the following error information returned by PayPal
			$ErrorCode = urldecode ( $resArray ["L_ERRORCODE0"] );
			$ErrorShortMsg = urldecode ( $resArray ["L_SHORTMESSAGE0"] );
			$ErrorLongMsg = urldecode ( $resArray ["L_LONGMESSAGE0"] );
			$ErrorSeverityCode = urldecode ( $resArray ["L_SEVERITYCODE0"] );
			$errMsg = "SetExpressCheckout API call failed. Detailed Error Message: $ErrorLongMsg Short Error Message: $ErrorShortMsg Error Code: $ErrorCode Error Severity Code: $ErrorSeverityCode";
			$connector->setHTMLPayment ( "<ul class=\"errors\"><li class=\"first last\">$errMsg</li></ol>" );
		}		
	}
	
	/**
	 * 
	 * @param array $resArray
	 * @return payment_Transaction
	 */
	public function getBankResponse($resArray)
	{
		if (!isset($resArray["ACK"]) || !isset($resArray["connectorId"]) || !isset($resArray["orderId"]))
		{
			throw new Exception("PAYPAL BANKING FAILED : INVALID DATA");
		}

		$response = new payment_Transaction();
		$response->setRawBankResponse(serialize($resArray));
		payment_ModuleService::getInstance()->log('PAYPAL BANKING DATA : ' . str_replace("\n", " ", var_export($resArray, true)));
		$order = DocumentHelper::getDocumentInstance($resArray["orderId"]);
		$connector = DocumentHelper::getDocumentInstance($resArray["connectorId"]);
		$response->setOrderId($order->getId());
		$response->setConnectorId($connector->getId());
		
		$response->setLang($resArray['lang']);
		$ack = strtoupper($resArray["ACK"]);
		if($ack != "SUCCESS" )
		{
			$ErrorCode = urldecode($resArray["L_ERRORCODE0"]);
			$ErrorShortMsg = urldecode($resArray["L_SHORTMESSAGE0"]);
			$ErrorLongMsg = urldecode($resArray["L_LONGMESSAGE0"]);
			$ErrorSeverityCode = urldecode($resArray["L_SEVERITYCODE0"]);
			
			$msg = "GetExpressCheckoutDetails API call failed. ";
			$msg .= "Detailed Error Message: " . $ErrorLongMsg;
			$msg .= "Short Error Message: " . $ErrorShortMsg;
			$msg .= "Error Code: " . $ErrorCode;
			$msg .= "Error Severity Code: " . $ErrorSeverityCode;
			
			$response->setFailed();
			$response->setTransactionId('ERROR-'. $ErrorCode);
			if (Framework::inDevelopmentMode())
			{
				$response->setTransactionText('Paiement échoué.' . $msg);
			}
			else
			{
				$response->setTransactionText('Paiement échoué.');
			}
			
			payment_ModuleService::getInstance()->logBankResponse($connector, $response);
			return $response;			
		}

		/*
		'********************************************************************************************************************
		'
		' THE PARTNER SHOULD SAVE THE KEY TRANSACTION RELATED INFORMATION LIKE 
		'                    transactionId & orderTime 
		'  IN THEIR OWN  DATABASE
		' AND THE REST OF THE INFORMATION CAN BE USED TO UNDERSTAND THE STATUS OF THE PAYMENT 
		'
		'********************************************************************************************************************
		*/

		$transactionId		= $resArray["TRANSACTIONID"]; // ' Unique transaction ID of the payment. Note:  If the PaymentAction of the request was Authorization or Order, this value is your AuthorizationID for use with the Authorization & Capture APIs. 
		$transactionType 	= $resArray["TRANSACTIONTYPE"]; //' The type of transaction Possible values: l  cart l  express-checkout 
		$paymentType		= $resArray["PAYMENTTYPE"];  //' Indicates whether the payment is instant or delayed. Possible values: l  none l  echeck l  instant 
		$orderTime 			= $resArray["ORDERTIME"];  //' Time/date stamp of payment
		$amt				= $resArray["AMT"];  //' The final amount charged, including any shipping and taxes from your Merchant Profile.
		$currencyCode		= $resArray["CURRENCYCODE"];  //' A three-character currency code for one of the currencies listed in PayPay-Supported Transactional Currencies. Default: USD. 
		$feeAmt				= $resArray["FEEAMT"];  //' PayPal fee amount charged for the transaction
		$settleAmt			= $resArray["SETTLEAMT"];  //' Amount deposited in your PayPal account after a currency conversion.
		$taxAmt				= $resArray["TAXAMT"];  //' Tax charged on the transaction.
		$exchangeRate		= $resArray["EXCHANGERATE"];  //' Exchange rate if a currency conversion occurred. Relevant only if your are billing in their non-primary currency. If the customer chooses to pay with a currency other than the non-primary currency, the conversion occurs in the customer’s account.
		
		/*
		' Status of the payment: 
				'Completed: The payment has been completed, and the funds have been added successfully to your account balance.
				'Pending: The payment is pending. See the PendingReason element for more information. 
		*/
		
		$paymentStatus	= $resArray["PAYMENTSTATUS"]; 

		/*
		'The reason the payment is pending:
		'  none: No pending reason 
		'  address: The payment is pending because your customer did not include a confirmed shipping address and your Payment Receiving Preferences is set such that you want to manually accept or deny each of these payments. To change your preference, go to the Preferences section of your Profile. 
		'  echeck: The payment is pending because it was made by an eCheck that has not yet cleared. 
		'  intl: The payment is pending because you hold a non-U.S. account and do not have a withdrawal mechanism. You must manually accept or deny this payment from your Account Overview. 		
		'  multi-currency: You do not have a balance in the currency sent, and you do not have your Payment Receiving Preferences set to automatically convert and accept this payment. You must manually accept or deny this payment. 
		'  verify: The payment is pending because you are not yet verified. You must verify your account before you can accept this payment. 
		'  other: The payment is pending for a reason other than those listed above. For more information, contact PayPal customer service. 
		*/
		
		$pendingReason	= $resArray["PENDINGREASON"];  

		/*
		'The reason for a reversal if TransactionType is reversal:
		'  none: No reason code 
		'  chargeback: A reversal has occurred on this transaction due to a chargeback by your customer. 
		'  guarantee: A reversal has occurred on this transaction due to your customer triggering a money-back guarantee. 
		'  buyer-complaint: A reversal has occurred on this transaction due to a complaint about the transaction from your customer. 
		'  refund: A reversal has occurred on this transaction because you have given the customer a refund. 
		'  other: A reversal has occurred on this transaction due to a reason not listed above. 
		*/
		
		$reasonCode		= $resArray["REASONCODE"];  

		$response->setTransactionId($transactionId);
		if ($paymentStatus == 'Pending')
		{
			//2009-12-18T15:02:46Z'
			$date = preg_replace('/^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2}:\d{2})Z$/', '$1 $2', $orderTime);
			$response->setDate($date);
			$response->setDelayed();
			$response->setTransactionText(f_Locale::translate('&modules.payment.frontoffice.paypal.Payment-waiting;', array('transaction' => $transactionId)));
		}
		else
		{
			$date = preg_replace('/^(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2}:\d{2})Z$/', '$1 $2', $orderTime);
			$response->setDate($date);
			$response->setAccepted();
			$response->setTransactionText(f_Locale::translate('&modules.payment.frontoffice.paypal.Payment-success;', array('transaction' => $transactionId)));
		}
		
		payment_ModuleService::getInstance()->logBankResponse($connector, $response);
		return $response;
	}
	
	/**
	 * @return String
	 */
	public function getSuccessURL()
	{
		return "http://" . $this->getServer () . "/payment/paypalConfirm.php";
	}
	
	/**
	 * @return String
	 */
	public function getCancelURL()
	{
		return "http://" . $this->getServer () . "/payment/paypalCancel.php";
	}
	
	/**
	 * @return String
	 */
	private function getServer()
	{
		$currentWebsite = website_WebsiteModuleService::getInstance ()->getCurrentWebsite ();
		return $currentWebsite->getDomain ();
	}
	
	/**
	 * Parse order paymentResponse
	 * @param payment_Order $order
	 * @return array associative array<String, String>
	 */
	public function parsePaymentResponse($order)
	{
		$result = array();
		
		if (!$order->getPaymentResponse())
		{
			return $result;
		}
		$matches = unserialize($order->getPaymentResponse());
		foreach ($matches as $name => $value) 
		{
			switch ($name) 
			{
				case 'token':
				case 'connectorId':
				case 'orderId':
				case 'paymentAmount':	
				case 'currencyCodeType':
				case 'lang':
				case 'paymentURL':	
				break;
				
				default:
					$result[$name] = $value;
					break;
			}
		}
		return $result;		
	}
}