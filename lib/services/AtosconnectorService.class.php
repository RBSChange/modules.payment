<?php
/**
 * payment_AtosconnectorService
 * @package payment
 */
class payment_AtosconnectorService extends payment_ConnectorService
{
	/**
	 * @var payment_AtosconnectorService
	 */
	private static $instance;

	/**
	 * @return payment_AtosconnectorService
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
	 * @return payment_persistentdocument_atosconnector
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_payment/atosconnector');
	}

	/**
	 * Create a query based on 'modules_payment/atosconnector' model.
	 * Return document that are instance of modules_payment/atosconnector,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_payment/atosconnector');
	}
	
	/**
	 * Create a query based on 'modules_payment/atosconnector' model.
	 * Only documents that are strictly instance of modules_payment/atosconnector
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_payment/atosconnector', false);
	}

	/**
	 * @param payment_persistentdocument_atosconnector $document
	 * @param Integer $parentNodeId Parent node ID where to save the document.
	 * @return void
	 */
	protected function postSave($document, $parentNodeId = null)
	{
		parent::postSave($document, $parentNodeId);
		$this->refreshCertificate($document);
	}

	/**
     * Currencies map : Change4 => ATOS
     */
	private $currencyMap = array(
		"EUR"	=> 978,		// Euro
		"GBP"	=> 826,		// Livre sterling
		"CHF"	=> 756		// Franc suisse
	);
	
	private $currencyPrecision = array(
		978	=> 2, // Euro
		826	=> 2, // Livre sterling
		756	=> 2 // Franc suisse
	);
	
	/**
     * Request parameter name for the returned banking data.
     *
     */
	const DATA_PARAMETER = 'DATA';

	/**
     * Response codes (see descriptions above).
     *
     * an implementation specyfing these parameters accorded to its bank
     * (sogenactif, cyberplus... would extend Atos...)
     **/
	const RESPONSE_CODE_ACCEPTED = 0;
	const RESPONSE_CODE_ASK_FOR_AUTHORIZATION = 2;
	const RESPONSE_CODE_INVALID_MERCHANT = 3;
	const RESPONSE_CODE_REFUSED = 5;
	const RESPONSE_CODE_INVALID_TRANSACTION = 12;
	const RESPONSE_CODE_INVALID_AMOUNT = 13;
	const RESPONSE_CODE_CANCEL = 17;
	const RESPONSE_CODE_ERROR_FORMAT = 30;
	const RESPONSE_CODE_SECURITY = 63;
	const RESPONSE_CODE_OVERFLOW = 75;
	const RESPONSE_CODE_UNAVAILABLE = 90;
	
	
	/**
	 * @param payment_persistentdocument_atosconnector $connector
	 * @param payment_Order $order
	 */
	public function setPaymentInfo($connector, $order)
	{
		$status = $order->getPaymentStatus();
		if ($status != 'PAYMENT_WAITING')
		{		
			$this->setPaymentStatus($connector, $order);
			return;
		}
		
		
		if (!$this->hasCertificate($connector))
		{
			throw new Exception('Invalid config file for connector ' . $connector->__toString());
		}
		
		//Set session Info for callback
		$sessionInfo = array('orderId' => $order->getPaymentId(), 
				'connectorId' => $connector->getId(), 
				'lang' => RequestContext::getInstance()->getLang(),
				'paymentAmount' => $order->getPaymentAmount(),
				'currencyCodeType' => $order->getPaymentCurrency(),
				'paymentURL' => $order->getPaymentCallbackURL());
		$this->setSessionInfo($sessionInfo);
			
		//Generate Bank Form Information
		$params = array();
		// Mandatory things
		$params['merchant_id'] = $connector->getMerchantId();
		$currencyCode = $order->getPaymentCurrency();
		
		if (!isset($this->currencyMap[$currencyCode]))
		{
			throw new Exception("Atos currency code is not defined (change currency: $currencyCode)");
		}
		
		$params['currency_code'] = $this->currencyMap[$currencyCode];
		$numberOfZeroToAdd = $this->currencyPrecision[$params['currency_code']];
		
		$amount = strval($order->getPaymentAmount());
		$pos = strpos($amount, ".");
		if ($pos !== false)
		{
			$numberOfZeroToAdd = $numberOfZeroToAdd - strlen(substr($amount, $pos + 1, strlen($amount)));
			if ($numberOfZeroToAdd < 0)
			{
				$numberOfZeroToAdd = 0;
			}
			$amount = substr_replace($amount, '', $pos, 1);
		}
		$amount = $amount . str_repeat("0", $numberOfZeroToAdd);		
		$params['amount'] = $amount;
		
		$params['normal_return_url'] = $this->getSuccessURL();
		$params['cancel_return_url'] = $this->getCancelURL();
		$params['automatic_response_url'] = $this->getListenerURL();
		$params['language'] = RequestContext::getInstance()->getLang();
		$params['pathfile'] = $this->getPathFile();
		
		// Optional things
		$params['order_id'] = $order->getPaymentReference();
		$params['return_context'] = $order->getPaymentId() . ',' . $connector->getId();	
			
		$user = $order->getPaymentUser();
		if ($user)
		{
			$params['customer_id'] = $user->getId();
			$params['customer_email'] = $user->getEmail();
		}
		// Following parameters are optionnal, and should be defined in the config/atos/parcom file
		// Top centered logo
		// $params['advert'] = '';
		// Left side logo
		// $params['logo_id'] = '';
		// Right side logo
		// $params['logo_id2'] = '';
		// background image
		// $params['background_id'] = '';
		// background color
		// $params['bgcolor'] = '';

		$path_bin = f_util_FileUtils::buildWebappPath('bin', 'request');
		
		foreach ($params as $param => $value)
		{
			$path_bin .= ' '. $param .'='. $value;
		}
		
		$result = exec($path_bin);
		payment_ModuleService::getInstance()->log(
			sprintf("ATOS BANKING (%s): prepare (bin: '%s', transaction: '%s', amount: '%s', currency: '%s', language: '%s').",
				$connector->getLogLabel(), $path_bin, $params['order_id'], $params['amount'], $params['currency_code'], $params['language']
				)
			);

		/**
    	 * Sortie de la fonction : $result=!code!error!buffer!
    	 *  - code =  0	: La fonction génère une page html contenue dans la variable buffer
    	 *  - code = -1 : La fonction retourne un message d'erreur dans la variable error
    	 * On separe les differents champs et on les met dans un tableau
    	 */
		$resultArray = explode ('!', $result);
		$code = isset($resultArray[1]) ? $resultArray[1] : '';
		$error = isset($resultArray[2]) ? $resultArray[2] : '';
		$message = isset($resultArray[3]) ? $resultArray[3] : '';
		if (($code == '') && ($error == ''))
		{
			throw new Exception("Executable request not found : $path_bin");
		}
		if ($code != 0)
		{
			throw new Exception("Error message from $path_bin : " . var_export($error, true));
		}
		
		$connector->setHTMLPayment($message);	
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
	 * Return the valid transaction_id to the format requested by Atos server
	 * @param order_persistentdocument_order $order
	 * @return Integer
	 * @throws Exception
	 */
	private function getCurrencyCode($order)
	{
		if (!isset($this->currencyMap[$order->getCurrencyCode()]))
		{
			throw new order_ValidationException("Atos currency code is not defined (change currency: ".$order->getCurrencyCode().")");
		}
		return $this->currencyMap[$order->getCurrencyCode()];
	}
	/**
	 * @return String
	 */
	private function getPathFile()
	{
		return f_util_FileUtils::buildWebeditPath('build', 'atos', 'pathfile');
	}
	
	/**
	 * @param String $merchantId
	 * @return String
	 */
	private function getParmcomFile($merchantId)
	{
		return f_util_FileUtils::buildWebeditPath('build', 'atos', 'parmcom.'.$merchantId);
	}
	
	/**
	 * @param String $merchantId
	 * @return String
	 */
	private function getCertifFile($merchantId)
	{
		return f_util_FileUtils::buildWebeditPath('build', 'atos', 'certif.fr.'.$merchantId);
	}
	
	/**
	 * @return String
	 */
	private function getServer()
	{
		$currentWebsite = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
		return $currentWebsite->getDomain();
	}
	
	/**
	 * @return String
	 */
	private function getSuccessURL()
	{
		return "http://" . $this->getServer() . "/payment/atosResponse.php";
	}

	/**
	 * @return String
	 */
	private function getCancelURL()
	{
		return "http://" . $this->getServer() . "/payment/atosResponse.php";
	}

	/**
	 * @return String
	 */
	private function getListenerURL()
	{
		return "http://" . $this->getServer() . "/payment/atosListenerResponse.php";
	}
	
	/**
	 * @param payment_persistentdocument_atosconnector $atosConnector
	 */		
	private function hasCertificate($atosConnector)
	{
		if (!file_exists($this->getPathFile())) {return false;}
		$merchantId = $atosConnector->getMerchantId();
		if (!file_exists($this->getParmcomFile($merchantId))) {return false;}
		if (!file_exists($this->getCertifFile($merchantId))) {return false;}
		return true;
	}	
	
	/**
	 * @param payment_persistentdocument_atosconnector $atosConnector
	 */		
	private function refreshCertificate($atosConnector)
	{
		$pathfile = $this->getPathFile();
		$template = FileResolver::getInstance()->setPackageName('modules_payment')->setDirectory('config/atos')
			->getPath('pathfile.tpl');
		$content = f_util_FileUtils::read($template);
		$content = str_replace('{WEBEDIT_HOME}', WEBEDIT_HOME, $content);	
		f_util_FileUtils::writeAndCreateContainer($pathfile, $content, f_util_FileUtils::OVERRIDE);
		
		$merchantId = $atosConnector->getMerchantId();
		$certifFile = $this->getCertifFile($merchantId);
		f_util_FileUtils::writeAndCreateContainer($certifFile, $atosConnector->getTpeCertifContent(), f_util_FileUtils::OVERRIDE);
		
		$parmcomFile = $this->getParmcomFile($merchantId); 
		f_util_FileUtils::writeAndCreateContainer($parmcomFile, $atosConnector->getTpeParmcomContent(), f_util_FileUtils::OVERRIDE);
	}
	
	/**
	 * @param string $data
	 * @return payment_Transaction
	 */
	public function getBankResponse($data)
	{	
		if ($data == null)
		{
			throw new Exception("ATOS BANKING from FAILED : REQUEST DATA NOT FOUND");
		}

		$response = new payment_Transaction();
		$params = array('message' => $data, 'pathfile' => $this->getPathFile());		
		$path_bin = f_util_FileUtils::buildWebappPath('bin', 'response');	
		foreach ($params as $param => $value)
		{
			$path_bin .= ' '. $param .'='. $value;
		}
		$result = exec($path_bin);	
		$response->setRawBankResponse($result);
		payment_ModuleService::getInstance()->log('ATOS BANKING DATA : ' . $result);
		
		$resultArray = explode('!', $result);
		$code = $resultArray[1];
		$error = $resultArray[2];
		if ((($code == "") && ($error == "")) || ($code != 0))
		{
			throw new Exception("ATOS BANKING FAILED : BAD REQUEST : " . $error . " DATA :[$data]");
		}
		
		$response->setLang($resultArray[25]);
		$contextArray = explode(',', $resultArray[21]);
		try 
		{
			if (count($contextArray) !== 2)
			{
				throw new Exception("ATOS BANKING FAILED : BAD CONTEXT : " . $resultArray[21]);
			}
			$order = DocumentHelper::getDocumentInstance($contextArray[0]);
			$connector = DocumentHelper::getDocumentInstance($contextArray[1]);
		}
		catch (Exception $e)
		{
			throw new Exception("ATOS BANKING FAILED : BAD CONTEXT OBJECT: " . $e->getMessage());
		}
		$response->setOrderId($order->getId());
		$currency = intval($resultArray[14]);
		foreach ($this->currencyMap as $code => $key) 
		{
			if ($key == $currency) 
			{
				$response->setCurrency($code);
				break;
			}
		}
		$prec = $this->currencyPrecision[$currency];
		$amount = floatval(preg_replace('/^(\d*)(\d{'.$prec.'})$/', '\1.\2', $resultArray[5]));			
		$response->setAmount($amount);
			
		// Is the payment OK or not ?
		if ($resultArray[11] == self::RESPONSE_CODE_ACCEPTED)
		{
			$response->setAccepted();
			$response->setTransactionId($resultArray[6]);
			$response->setTransactionText('Paiement par carte "'.$resultArray[7] .'" effectué avec succés.');
			$date = preg_replace('/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', '\1-\2-\3 \4:\5:\6', $resultArray[8]);
			$response->setDate($date);
		} 
		else 
		{
			$response->setFailed();
			$response->setTransactionId('ERROR-'. $resultArray[11]);
			$response->setTransactionText('Paiement échoué.');			
		}
			
		payment_ModuleService::getInstance()->logBankResponse($connector, $response);
		return $response;
	}
}