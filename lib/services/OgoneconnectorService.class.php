<?php
/**
 * payment_OgoneconnectorService
 * @package modules.payment
 */
class payment_OgoneconnectorService extends payment_ConnectorService
{
	
	/**
	 * @var payment_OgoneconnectorService
	 */
	private static $instance;
	
	/**
	 * @return payment_OgoneconnectorService
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
	 * @return payment_persistentdocument_ogoneconnector
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_payment/ogoneconnector');
	}
	
	/**
	 * Create a query based on 'modules_payment/ogoneconnector' model.
	 * Return document that are instance of modules_payment/ogoneconnector,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp
			->createQuery('modules_payment/ogoneconnector');
	}
	
	/**
	 * Create a query based on 'modules_payment/ogoneconnector' model.
	 * Only documents that are strictly instance of modules_payment/ogoneconnector
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp
			->createQuery('modules_payment/ogoneconnector', false);
	}
	
	/**
	 * Return null if $id is not a payment_persistentdocument_ogoneconnector
	 * @param integer $id
	 * @return payment_persistentdocument_ogoneconnector
	 */
	public function getConnectorById($id)
	{
		try 
		{
			$document = DocumentHelper::getDocumentInstance($id);
			if ($document instanceof payment_persistentdocument_ogoneconnector)
			{
				return $document;
			}
		}
		catch (Exception $e)
		{
			Framework::exception($e);
		}
		return null;
	}
	
	
	/**
	 * @param payment_persistentdocument_ogoneconnector $connector
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
		
		//Set session Info for callback
		$sessionInfo = array('orderId' => $order->getPaymentId(), 
				'connectorId' => $connector->getId(), 
				'lang' => RequestContext::getInstance()->getLang(), 
				'paymentAmount' => $order->getPaymentAmount(), 
				'currencyCodeType' => $order->getPaymentCurrency(), 
				'paymentURL' => $order->getPaymentCallbackURL());
		$this->setSessionInfo($sessionInfo);
		
		$params = array();
		$params['PSPID'] = $connector->getPspid();
		$params['ORDERID'] = $order->getPaymentReference();
		$params['AMOUNT'] = strval(round($order->getPaymentAmount() * 100));
		$params['CURRENCY'] = $order->getPaymentCurrency();
		$params['LANGUAGE'] = strtolower($order->getPaymentLang()) . '_' . strtoupper(
				$order->getPaymentLang());
		$params['COMPLUS'] = $order->getPaymentId() . ' ' . $connector->getId();
		
		$params['USERID'] = strval($order->getPaymentUser()->getId());
		
		$address = $order->getPaymentBillingAddress();
		if ($address)
		{
			$params['CN'] = $address->getLastname() . ' ' . $address->getFirstname();
			$params['EMAIL'] = $address->getEmail();
			$ownerAddress = $address->getAddressLine1();
			if ($address->getAddressLine2())
			{
				$ownerAddress .= ' ' . $address->getAddressLine2();
			}
			if ($address->getAddressLine3())
			{
				$ownerAddress .= ' ' . $address->getAddressLine3();
			}
			$params['OWNERADDRESS'] = $ownerAddress;
			$params['OWNERZIP'] = $address->getZipCode();
			$params['OWNERZIP'] = $address->getZipCode();
			$params['OWNERTOWN'] = $address->getCity();
			$country = $address->getCountry();
			if ($country)
			{
				$params['OWNERCTY'] = $country->getLabel();
			}
		}
		
		
		$params['accepturl'] = $this->getSuccessURL($connector);
		$params['declineurl'] = $this->getCancelURL($connector);
		$params['backurl'] = $params['declineurl'];
		
		if ($connector->getOperation())
		{
			$params['operation'] = $connector->getOperation();
		}
		
		$this->completeRequestParams($params, $connector, $order);
		
		$params['SHASign'] = $this->getSignIn($params, $connector->getShaInKey());
		
		$html = array();
		$html[] = '<form action="' . $connector->getBankServerUrl() . '" method="post" id="PaymentRequest">';
		foreach ($params as $name => $value)
		{
			$html[] = '<input type="hidden" name="' . $name . '" value="' . $value . '" />';
		}
		$html[] = '<input type="submit" name="bouton" value="' . f_Locale::translate(
				'&modules.payment.document.ogoneconnector.payment-cb;') . '" />';
		$html[] = '</form>';
		
		$connector->setHTMLPayment(implode("", $html));
	}
	
	/**
	 * @param array $params
	 * @param payment_persistentdocument_ogoneconnector $connector
	 * @param payment_Order $order
	 */	
	protected function completeRequestParams(&$params, $connector, $order)
	{
		//Override this	
	}
	
	/**

	 * @param array $parameters
	 * @return payment_Transaction
	 */
	public function getBankResponse($parameters)
	{
		if (!isset($parameters['SHASIGN']))
		{
			Framework::info(__METHOD__ . ' no SHASIGN bank information in request.');
			return null;
		}
		if (!isset($parameters['COMPLUS']))
		{
			Framework::info(__METHOD__ . ' no COMPLUS bank information in request.');
			return null;
		}
		
		$data = explode(' ', $parameters['COMPLUS']);
		if (count($data) != 2 || !is_numeric($data[0]) || !is_numeric($data[1]))
		{
			$msg = __METHOD__ . ' Invalid COMPLUS information in request :' . $parameters['COMPLUS'];
			payment_ModuleService::getInstance()->log($msg);
			Framework::warn($msg);
			return null;
		}
		
		$connector = $this->getConnectorById($data[1]);
		$order = $this->getPaymentOrderById($data[0]);
		if ($connector === null || $order === null)
		{
			$msg = __METHOD__ . ' Invalid payment_Order or ogoneconnector information in request :' . $parameters['COMPLUS'];
			payment_ModuleService::getInstance()->log($msg);
			Framework::warn($msg);
			return null;
		}
		
		$signOut =  $this->getSignOut($parameters, $connector->getShaOutKey());
		if (strtoupper($parameters['SHASIGN']) != $signOut )
		{
			$msg = __METHOD__ . ' Invalid SHASIGN :' . $parameters['SHASIGN'];
			payment_ModuleService::getInstance()->log($msg);
			Framework::warn($msg);
			return null;
		}
		
		$response = new payment_Transaction();
		$response->setRawBankResponse(serialize($parameters));
		$response->setOrderId($order->getPaymentId());		
		$response->setAmount($parameters['amount']);
		$response->setCurrency($parameters['currency']);
		
		$status = $parameters['STATUS'];
		$acceptance = $parameters['ACCEPTANCE'];
		$payID = $parameters['PAYID'];
		$response->setTransactionId($status . "-" . $payID . "-" . $acceptance);
		switch ($status)
		{
			case "5": //Authorized
			case "9": //Payment requested
				$response->setAccepted();
				$response->setTransactionText(f_Locale::translate('&modules.payment.document.ogoneconnector.Transaction-accepted;'));
				$response->setDate(date_Calendar::getInstance());
				break;
			case "51": //Authorization waiting	
			case "91": //Payment processing
			case "52": //Authorization not known		
			case "91": //Payment uncertain	
				$response->setDelayed();
				$response->setTransactionText(f_Locale::translate('&modules.payment.document.ogoneconnector.Transaction-delayed;'));
				break;
			default:
				$response->setFailed();
				$response->setTransactionText(f_Locale::translate('&modules.payment.document.ogoneconnector.Transaction-failed;'));
				break;
		}
		
		payment_ModuleService::getInstance()->logBankResponse($connector, $response);
		return $response;
	}

	/**
	 * @param payment_Transaction $bankResponse
	 * @param payment_Order $order
	 */
	public function setListenerPaymentResult($bankResponse, $order)
	{
		if ($order->getPaymentStatus() !== 'success')
		{
			if ($bankResponse->isFailed() && $order->getPaymentStatus() !== 'failed')
			{
				$this->setPaymentResult($bankResponse, $order);
			}
			else if ($bankResponse->isAccepted() && $order->getPaymentStatus() !== 'success')
			{
				$this->setPaymentResult($bankResponse, $order);
			}
			else if ($bankResponse->isDelayed() && $order->getPaymentStatus() !== 'waiting')
			{
				$this->setPaymentResult($bankResponse, $order);
			}
		}
	}
	
	/**
	 * @param payment_persistentdocument_paypalconnector $connector
	 * @return String
	 */
	protected function getServer($connector = null)
	{
		$currentWebsite = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
		return $currentWebsite->getDomain();
	}
	
	/**
	 * @param payment_persistentdocument_paypalconnector $connector
	 * @return String
	 */
	protected function getSuccessURL($connector = null)
	{
		$protocol =  ($connector !== null && $connector->getUseHTTPS()) ? "https://" : "http://";
		return $protocol . $this->getServer($connector) . "/payment/ogoneConfirm.php";
	}

	/**
	 * @param payment_persistentdocument_paypalconnector $connector
	 * @return String
	 */
	protected function getCancelURL($connector = null)
	{
		$protocol =  ($connector !== null && $connector->getUseHTTPS()) ? "https://" : "http://";
		return $protocol . $this->getServer($connector) . "/payment/ogoneCancel.php";
	}
	
	private function getSignIn($params, $shaKey)
	{
		$datas = $this->filterSignParams($params);
		$strs = array();
		foreach ($datas as $name => $value)
		{
			$strs[] = strtoupper($name) . "=" . $value;
		}
		$strs[] = '';
		$str = implode($shaKey, $strs);
		Framework::info(__METHOD__ . ':' . $str);
		return strtoupper(sha1($str, false));
	}
	
	private function getSignOut($params, $shaKey)
	{
		$datas = $this->filterSignParams($params, false);
		$strs = array();
		foreach ($datas as $name => $value)
		{
			$strs[] = strtoupper($name) . "=" . $value;
		}
		$strs[] = '';
		$str = implode($shaKey, $strs);
		Framework::info(__METHOD__ . ':' . $str);
		return strtoupper(sha1($str, false));
	}
	
	private function filterSignParams($params, $filterIn = true)
	{
		if ($filterIn)
		{
				$keyWords = array("ACCEPTURL", "ADDMATCH", "ADDRMATCH", "AIAIRNAME", "AIAIRTAX", "AIBOOKIND*XX*", 
				"AICARRIER*XX*", "AICHDET", "AICLASS*XX*", "AICONJTI", "AIDESTCITY*XX*", 
				"AIDESTCITYL*XX*", "AIEXTRAPASNAME*XX*", "AIEYCD", "AIFLDATE*XX*", "AIFLNUM*XX*", 
				"AIIRST", "AIORCITY*XX*", "AIORCITYL*XX*", "AIPASNAME", "AISTOPOV*XX*", 
				"AITIDATE", "AITINUM", "AITYPCH", "AIVATAMNT", "AIVATAPPL", "ALIAS", 
				"ALIASOPERATION", "ALIASUSAGE", "ALLOWCORRECTION", "AMOUNT", "AMOUNT*XX*", 
				"AMOUNTHTVA", "AMOUNTTVA", "BACKURL", "BGCOLOR", "BRAND", "BRANDVISUAL", 
				"BUTTONBGCOLOR", "BUTTONTXTCOLOR", "CANCELURL", "CARDNO", "CATALOGURL", 
				"CAVV_3D", "CAVVALGORITHM_3D", "CERTID", "CHECK_AAV", "CIVILITY", "CN", "COM", 
				"COMPLUS", "COSTCENTER", "COSTCODE", "CREDITCODE", "CUID", "CURRENCY", "CVC", 
				"DATA", "DATATYPE", "DATEIN", "DATEOUT", "DECLINEURL", "DISCOUNTRATE", "ECI", 
				"ECOM_BILLTO_POSTAL_CITY", "ECOM_BILLTO_POSTAL_COUNTRYCODE", 
				"ECOM_BILLTO_POSTAL_NAME_FIRST", "ECOM_BILLTO_POSTAL_NAME_LAST", 
				"ECOM_BILLTO_POSTAL_POSTALCODE", "ECOM_BILLTO_POSTAL_STREET_LINE1", 
				"ECOM_BILLTO_POSTAL_STREET_LINE2", "ECOM_BILLTO_POSTAL_STREET_NUMBER", 
				"ECOM_CONSUMERID", "ECOM_CONSUMERORDERID", "ECOM_CONSUMERUSERALIAS", 
				"ECOM_PAYMENT_CARD_EXPDATE_MONTH", "ECOM_PAYMENT_CARD_EXPDATE_YEAR", 
				"ECOM_PAYMENT_CARD_NAME", "ECOM_PAYMENT_CARD_VERIFICATION", 
				"ECOM_SHIPTO_COMPANY", "ECOM_SHIPTO_DOB", "ECOM_SHIPTO_ONLINE_EMAIL", 
				"ECOM_SHIPTO_POSTAL_CITY", "ECOM_SHIPTO_POSTAL_COUNTRYCODE", 
				"ECOM_SHIPTO_POSTAL_NAME_FIRST", "ECOM_SHIPTO_POSTAL_NAME_LAST", 
				"ECOM_SHIPTO_POSTAL_POSTALCODE", "ECOM_SHIPTO_POSTAL_STREET_LINE1", 
				"ECOM_SHIPTO_POSTAL_STREET_LINE2", "ECOM_SHIPTO_POSTAL_STREET_NUMBER", 
				"ECOM_SHIPTO_TELECOM_FAX_NUMBER", "ECOM_SHIPTO_TELECOM_PHONE_NUMBER", 
				"ECOM_SHIPTO_TVA", "ED", "EMAIL", "EXCEPTIONURL", "EXCLPMLIST", 
				"EXECUTIONDATE*XX*", "FIRSTCALL", "FLAG3D", "FONTTYPE", "FORCECODE1", 
				"FORCECODE2", "FORCECODEHASH", "FORCEPROCESS", "FORCETP", "GENERIC_BL", 
				"GIROPAY_ACCOUNT_NUMBER", "GIROPAY_BLZ", "GIROPAY_OWNER_NAME", "GLOBORDERID", 
				"GUID", "HDFONTTYPE", "HDTBLBGCOLOR", "HDTBLTXTCOLOR", "HEIGHTFRAME", "HOMEURL", 
				"HTTP_ACCEPT", "HTTP_USER_AGENT", "INCLUDE_BIN", "INCLUDE_COUNTRIES", "INVDATE", 
				"INVDISCOUNT", "INVLEVEL", "INVORDERID", "ISSUERID", "ITEMCATEGORY*XX*", 
				"ITEMDISCOUNT*XX*", "ITEMID*XX*", "ITEMNAME*XX*", "ITEMPRICE*XX*", 
				"ITEMQUANT*XX*", "ITEMUNITOFMEASURE*XX*", "ITEMVATCODE*XX*", "LANGUAGE", 
				"LEVEL1AUTHCPC", "LIDEXCL*XX*", "LIMITCLIENTSCRIPTUSAGE", "LINE_REF", "LIST_BIN", 
				"LIST_COUNTRIES", "LOGO", "MERCHANTID", "MODE", "MTIME", "MVER", "NETAMOUNT", 
				"OPERATION", "ORDERID", "ORIG", "OR_INVORDERID", "OR_ORDERID", "OWNERADDRESS", 
				"OWNERADDRESS2", "OWNERCTY", "OWNERTELNO", "OWNERTOWN", "OWNERZIP", "PAIDAMOUNT", 
				"PARAMPLUS", "PARAMVAR", "PAYID", "PAYMETHOD", "PM", "PMLIST", 
				"PMLISTPMLISTTYPE", "PMLISTTYPE", "PMLISTTYPEPMLIST", "PMTYPE", "POPUP", "POST", 
				"PSPID", "PSWD", "REF", "REFER", "REFID", "REFKIND", "REF_CUSTOMERID", 
				"REF_CUSTOMERREF", "REMOTE_ADDR", "REQGENFIELDS", "RTIMEOUT", 
				"RTIMEOUTREQUESTEDTIMEOUT", "SCORINGCLIENT", "SETT_BATCH", "SID", "STATUS_3D", 
				"SUBSCRIPTION_ID", "SUB_AM", "SUB_AMOUNT", "SUB_COM", "SUB_COMMENT", "SUB_CUR", 
				"SUB_ENDDATE", "SUB_ORDERID", "SUB_PERIOD_MOMENT", "SUB_PERIOD_MOMENT_M", 
				"SUB_PERIOD_MOMENT_WW", "SUB_PERIOD_NUMBER", "SUB_PERIOD_NUMBER_D", 
				"SUB_PERIOD_NUMBER_M", "SUB_PERIOD_NUMBER_WW", "SUB_PERIOD_UNIT", 
				"SUB_STARTDATE", "SUB_STATUS", "TAAL", "TAXINCLUDED*XX*", "TBLBGCOLOR", 
				"TBLTXTCOLOR", "TID", "TITLE", "TOTALAMOUNT", "TP", "TRACK2", "TXTBADDR2", 
				"TXTCOLOR", "TXTOKEN", "TXTOKENTXTOKENPAYPAL", "TYPE_COUNTRY", 
				"UCAF_AUTHENTICATION_DATA", "UCAF_PAYMENT_CARD_CVC2", 
				"UCAF_PAYMENT_CARD_EXPDATE_MONTH", "UCAF_PAYMENT_CARD_EXPDATE_YEAR", 
				"UCAF_PAYMENT_CARD_NUMBER", "USERID", "USERTYPE", "VERSION", "WBTU_MSISDN", 
				"WBTU_ORDERID", "WEIGHTUNIT", "WIN3DS", "WITHROOT");
		}
		else
		{
				// List of parameters used in SHASIGN: https://www2.payment-services.ingenico.com/ogone/support/~/media/kdb/integration%20guides/sha-out_params.ashx?la=fr
				$keyWords = array("AAVADDRESS", "AAVCHECK", "AAVMAIL", "AAVNAME", "AAVPHONE", "AAVZIP",
				"ACCEPTANCE", "ALIAS", "AMOUNT", "BIC", "BIN", "BRAND", "CARDNO", "CCCTY", "CN",
				"COLLECTOR_BIC", "COLLECTOR_IBAN", "COMPLUS", "CREATION_STATUS", "CREDITDEBIT",
				"CURRENCY", "CVCCHECK", "DCC_COMMPERCENTAGE", "DCC_CONVAMOUNT", "DCC_CONVCCY",
				"DCC_EXCHRATE", "DCC_EXCHRATESOURCE", "DCC_EXCHRATETS", "DCC_INDICATOR",
				"DCC_MARGINPERCENTAGE", "DCC_VALIDHOURS", "DEVICEID", "DIGESTCARDNO", "ECI", "ED",
				"EMAIL", "ENCCARDNO", "FXAMOUNT", "FXCURRENCY", "IP", "IPCTY", "MANDATEID", "MOBILEMODE",
				"NBREMAILUSAGE", "NBRIPUSAGE", "NBRIPUSAGE_ALLTX", "NBRUSAGE", "NCERROR", "ORDERID", "PAYID",
				"PAYMENT_REFERENCE", "PM", "SCO_CATEGORY", "SCORING", "SEQUENCETYPE", "SIGNDATE", "STATUS",
				"SUBBRAND", "SUBSCRIPTION_ID", "TRXDATE", "VC", "PAYIDSUB");
		}
		$result = array();
		foreach ($params as $name => $value) 
		{
			if (is_string($value) && $value !== '' && in_array(strtoupper($name), $keyWords))
			{
				$result[strtoupper($name)] = $value;
			}
		}
		ksort($result, SORT_STRING);
		return $result;
	}
}