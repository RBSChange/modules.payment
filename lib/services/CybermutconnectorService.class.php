<?php
define("CMCIC_CTLHMAC", "V1.04.sha1.php--[CtlHmac%s%s]-%s");
define("CMCIC_CTLHMACSTR", "CtlHmac%s%s");
define("CMCIC_CGI2_RECEIPT", "version=2\ncdr=%s");
define("CMCIC_CGI2_MACOK", "0");
define("CMCIC_CGI2_MACNOTOK", "1\n");
define("CMCIC_CGI2_FIELDS", "%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*");
define("CMCIC_CGI1_FIELDS", "%s*%s*%s%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s");
define("CMCIC_URLPAIEMENT", "paiement.cgi");
define("CMCIC_VERSION", "3.0");

/**
 * payment_CybermutconnectorService
 * @package payment
 */
class payment_CybermutconnectorService extends payment_ConnectorService
{
	/**
	 * @var payment_CybermutconnectorService
	 */
	private static $instance;

	/**
	 * @return payment_CybermutconnectorService
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
	 * @return payment_persistentdocument_cybermutconnector
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_payment/cybermutconnector');
	}

	/**
	 * Create a query based on 'modules_payment/cybermutconnector' model.
	 * Return document that are instance of modules_payment/cybermutconnector,
	 * including potential children.
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_payment/cybermutconnector');
	}

	/**
	 * Create a query based on 'modules_payment/cybermutconnector' model.
	 * Only documents that are strictly instance of modules_payment/cybermutconnector
	 * (not children) will be retrieved
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createStrictQuery()
	{
		return $this->pp->createQuery('modules_payment/cybermutconnector', false);
	}

	/**
	 * @param payment_persistentdocument_cybermutconnector $connector
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

		//Generate Bank Form Information
		$sReference = $order->getPaymentReference();
		$sMontant = $order->getPaymentAmount();

		$sDevise = $order->getPaymentCurrency();

		$sTexteLibre = $order->getPaymentId() . ' ' . $connector->getId();

		$sDate = date("d/m/Y:H:i:s");
		$sLangue = strtoupper(RequestContext::getInstance()->getLang());
		$sEmail = $order->getPaymentUser()->getEmail();

		$sNbrEch = "";
		$sDateEcheance1 = "";
		$sMontantEcheance1 = "";
		$sDateEcheance2 = "";
		$sMontantEcheance2 = "";
		$sDateEcheance3 = "";
		$sMontantEcheance3 = "";
		$sDateEcheance4 = "";
		$sMontantEcheance4 = "";
		$sOptions = "";

		$oTpe = new CMCIC_Tpe($sLangue, $connector);
		$oHmac = new CMCIC_Hmac($oTpe);

		// Control String for support
		$CtlHmac = sprintf(CMCIC_CTLHMAC, $oTpe->sVersion, $oTpe->sNumero, $oHmac->computeHmac(sprintf(CMCIC_CTLHMACSTR, $oTpe->sVersion, $oTpe->sNumero)));
		payment_ModuleService::getInstance()->log('CYBERMUT BANKING CtlHmac : ' . $CtlHmac);

		$PHP1_FIELDS = sprintf(CMCIC_CGI1_FIELDS, $oTpe->sNumero, $sDate, $sMontant, $sDevise, $sReference, $sTexteLibre, $oTpe->sVersion, $oTpe->sLangue, $oTpe->sCodeSociete, $sEmail, $sNbrEch, $sDateEcheance1, $sMontantEcheance1, $sDateEcheance2, $sMontantEcheance2, $sDateEcheance3, $sMontantEcheance3, $sDateEcheance4, $sMontantEcheance4, $sOptions);

		// MAC computation
		$sMAC = $oHmac->computeHmac($PHP1_FIELDS);

		payment_ModuleService::getInstance()->log('CYBERMUT BANKING RAW CODE : ' . $PHP1_FIELDS);
		payment_ModuleService::getInstance()->log('CYBERMUT BANKING MAC : ' . $sMAC);

		$returnContext = "?orderId=" . $order->getPaymentId() . "&connectorId=" . $connector->getId() . "&lang=" . RequestContext::getInstance()->getLang();

		$html = array();
		$html[] = '<form action="' . $oTpe->sUrlPaiement . '" method="post" id="PaymentRequest">';
		$html[] = '<input type="hidden" name="version"             id="version"        value="' . $oTpe->sVersion . '" />';
		$html[] = '<input type="hidden" name="TPE"                 id="TPE"            value="' . $oTpe->sNumero . '" />';
		$html[] = '<input type="hidden" name="date"                id="date"           value="' . $sDate . '" />';
		$html[] = '<input type="hidden" name="montant"             id="montant"        value="' . $sMontant . $sDevise . '" />';
		$html[] = '<input type="hidden" name="reference"           id="reference"      value="' . $sReference . '" />';
		$html[] = '<input type="hidden" name="MAC"                 id="MAC"            value="' . $sMAC . '" />';
		$html[] = '<input type="hidden" name="url_retour"          id="url_retour"     value="' . $this->getCancelURL() . $returnContext . '" />';
		$html[] = '<input type="hidden" name="url_retour_ok"       id="url_retour_ok"  value="' . $this->getSuccessURL() . $returnContext . '" />';
		$html[] = '<input type="hidden" name="url_retour_err"      id="url_retour_err" value="' . $this->getCancelURL() . $returnContext . '" />';
		$html[] = '<input type="hidden" name="lgue"                id="lgue"           value="' . $oTpe->sLangue . '" />';
		$html[] = '<input type="hidden" name="societe"             id="societe"        value="' . $oTpe->sCodeSociete . '" />';
		$html[] = '<input type="hidden" name="texte-libre"         id="texte-libre"    value="' . $sTexteLibre . '" />';
		$html[] = '<input type="hidden" name="mail"                id="mail"           value="' . $sEmail . '" />';
		$html[] = '<input type="hidden" name="nbrech"              id="nbrech"         value="' . $sNbrEch . '" />';
		$html[] = '<input type="hidden" name="dateech1"            id="dateech1"       value="' . $sDateEcheance1 . '" />';
		$html[] = '<input type="hidden" name="montantech1"         id="montantech1"    value="' . $sMontantEcheance1 . '" />';
		$html[] = '<input type="hidden" name="dateech2"            id="dateech2"       value="' . $sDateEcheance2 . '" />';
		$html[] = '<input type="hidden" name="montantech2"         id="montantech2"    value="' . $sMontantEcheance2 . '" />';
		$html[] = '<input type="hidden" name="dateech3"            id="dateech3"       value="' . $sDateEcheance3 . '" />';
		$html[] = '<input type="hidden" name="montantech3"         id="montantech3"    value="' . $sMontantEcheance3 . '" />';
		$html[] = '<input type="hidden" name="dateech4"            id="dateech4"       value="' . $sDateEcheance4 . '" />';
		$html[] = '<input type="hidden" name="montantech4"         id="montantech4"    value="' . $sMontantEcheance4 . '" />';
		$html[] = '<input type="submit" name="bouton"              id="bouton"         value="' . f_Locale::translate('&modules.payment.document.cybermutconnector.payment-cb;') . '" />';
		$html[] = '</form>';
		$connector->setHTMLPayment(implode("", $html));
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
	public function getSuccessURL()
	{
		return "http://" . $this->getServer() . "/payment/cybermutResponse.php";
	}

	/**
	 * @return String
	 */
	public function getCancelURL()
	{
		return "http://" . $this->getServer() . "/payment/cybermutCancel.php";
	}

	/**
	 * Written from the check_HMAC function provided in the PHP Kit.
	 *
	 * @param Request $request
	 * @return payment_Transaction
	 */
	public function getBankResponse($parameters)
	{
		$response = new payment_Transaction();
		$textLibre = $parameters['texte-libre'];
		$contextIds = explode(' ', $textLibre);
		if (count($contextIds) != 2)
		{
			return $response;
		}

		$orderId = $contextIds[0];
		$response->setOrderId($orderId);
		$order = $response->getOrder();

		$connector = DocumentHelper::getDocumentInstance($contextIds[1]);
		$oTpe = new CMCIC_Tpe("FR", $connector);
		$oHmac = new CMCIC_Hmac($oTpe);

		// Message Authentication
		$numauto = isset($parameters['numauto']) ? $parameters['numauto'] : null;
		$cgi2_fields = sprintf(CMCIC_CGI2_FIELDS, $oTpe->sNumero, $parameters["date"], $parameters['montant'], $parameters['reference'], $parameters['texte-libre'], $oTpe->sVersion, $parameters['code-retour'], $parameters['cvx'], $parameters['vld'], $parameters['brand'], $parameters['status3ds'], $numauto, $parameters['motifrefus'], $parameters['originecb'], $parameters['bincb'], $parameters['hpancb'], $parameters['ipclient'], $parameters['originetr'], $parameters['veres'], $parameters['pares']);
		$response->setRawBankResponse($cgi2_fields);
		if ($oHmac->computeHmac($cgi2_fields) == strtolower($parameters['MAC']))
		{
			//$parameters["date"] -> JJ/MM/AAAA_a_HH:MM:SS
			$date = preg_replace('/^(\d{2})\/(\d{2})\/(\d{4})_a_(\d{2}:\d{2}:\d{2})$/', '$3-$2-$1 $4', $parameters["date"]);

			$amount = $parameters['montant'];
			$mnt_lth = strlen($amount) - 3;
			if ($mnt_lth > 0)
			{
				$currency = substr($amount, $mnt_lth, 3);
				$amount = substr($amount, 0, $mnt_lth);
			}
			else
			{
				$currency = "EUR";
			}
			$response->setAmount($amount);
			$response->setCurrency($currency);
			$code_retour = $parameters['code-retour'];
			$response->setTransactionId(strtoupper($code_retour) . "-" . $order->getPaymentId());
			switch ($code_retour)
			{
				case "Annulation" :
					$response->setFailed();
					$response->setTransactionText(f_Locale::translate('&modules.payment.document.cybermutconnector.Transaction-failed;'));
					break;
				case "payetest" :
					$response->setAccepted();
					$response->setTransactionText(f_Locale::translate('&modules.payment.document.cybermutconnector.Transaction-accepted-test;'));
					$response->setDate(date_Converter::convertDateToGMT($date));
					break;
				case "paiement" :
					$response->setAccepted();
					$response->setTransactionText(f_Locale::translate('&modules.payment.document.cybermutconnector.Transaction-accepted;'));
					$response->setDate(date_Converter::convertDateToGMT($date));
					break;

				/*** ONLY FOR MULTIPART PAYMENT ***/
				case "paiement_pf2" :
				case "paiement_pf3" :
				case "paiement_pf4" :
					break;

				case "Annulation_pf2" :
				case "Annulation_pf3" :
				case "Annulation_pf4" :
					break;
			}
		}
		payment_ModuleService::getInstance()->logBankResponse($connector, $response);
		return $response;
	}

	/**
	 * @param array $parameters
	 * @return payment_Transaction
	 */
	public function getCallbackResponse($parameters)
	{
		$response = new payment_Transaction();
		$response->setRawBankResponse(serialize($parameters));
		payment_ModuleService::getInstance()->log('CYBERMUT BANKING DATA : ' . str_replace("\n", " ", var_export($parameters, true)));

		$orderId = $parameters['orderId'];
		$response->setOrderId($orderId);
		$order = $response->getOrder();

		$transactionId = $order->getPaymentTransactionId();
		if ($transactionId != null)
		{
			return null;
		}

		$connectorId = $parameters['connectorId'];
		$response->setConnectorId($connectorId);
		$connector = $response->getConnector();

		$lang = $parameters['lang'];
		$response->setLang($lang);

		$response->setAmount($order->getPaymentAmount());
		$response->setCurrency($order->getPaymentCurrency());
		$response->setDate($order->getPaymentDate());

		switch ($parameters['status'])
		{
			case "Annulation" :
				$response->setTransactionId('CANCEL-' . $order->getPaymentReference());
				$response->setFailed();
				$response->setTransactionText(f_Locale::translate('&modules.payment.document.cybermutconnector.Transaction-failed;'));
				break;
			case "payetest" :
				$response->setTransactionId('PAYETEST-' . $order->getPaymentReference());
				$response->setAccepted();
				$response->setTransactionText(f_Locale::translate('&modules.payment.document.cybermutconnector.Transaction-accepted-test;'));
				break;
			case "paiement" :
				$response->setTransactionId('DELAY-' . $order->getPaymentReference());
				$response->setDelayed();
				$response->setTransactionText(f_Locale::translate('&modules.payment.document.cybermutconnector.Transaction-delayed;'));
				break;
		}

		payment_ModuleService::getInstance()->logBankResponse($connector, $response);
		return $response;
	}
}

/*****************************************************************************
 *
 * Classe / Class : CMCIC_Tpe
 *
 *****************************************************************************/

class CMCIC_Tpe
{

	public $sVersion; // Version du TPE - TPE Version (Ex : 3.0)
	public $sNumero; // Numero du TPE - TPE Number (Ex : 1234567)
	public $sCodeSociete; // Code Societe - Company code (Ex : companyname)
	public $sLangue; // Langue - Language (Ex : FR, DE, EN, ..)
	public $sUrlOK; // Url de retour OK - Return URL OK
	public $sUrlKO; // Url de retour KO - Return URL KO
	public $sUrlPaiement; // Url du serveur de paiement - Payment Server URL (Ex : https://paiement.creditmutuel.fr/paiement.cgi)


	private $_sCle; // La clé - The Key


	/**
	 * Constructeur / Constructor
	 * @param string $sLangue
	 * @param payment_persistentdocument_cybermutconnector $connector
	 */
	function __construct($sLangue = "FR", $connector)
	{

		// contrôle de l'existence des constantes de param�trages.
		//$aRequiredConstants = array('CMCIC_CLE', 'CMCIC_VERSION', 'CMCIC_TPE', 'CMCIC_CODESOCIETE');
		//$this->_checkTpeParams($aRequiredConstants);


		$this->sVersion = CMCIC_VERSION;
		$this->_sCle = $connector->getTpeKey();
		$this->sNumero = $connector->getMerchantId();
		$this->sUrlPaiement = 'https://' . $connector->getBankServerUrl() . '/' . CMCIC_URLPAIEMENT;

		$this->sCodeSociete = $connector->getTpeCompanyCode();
		$this->sLangue = $sLangue;

		$this->sUrlOK = "";
		$this->sUrlKO = "";
	}

	// ----------------------------------------------------------------------------
	//
	// Fonction / Function : getCle
	//
	// Renvoie la clé du TPE / return the TPE Key
	//
	// ----------------------------------------------------------------------------


	public function getCle()
	{

		return $this->_sCle;
	}

	// ----------------------------------------------------------------------------
	//
	// Fonction / Function : _checkTpeParams
	//
	// Contrôle l'existence des constantes d'initialisation du TPE
	// Check for the initialising constants of the TPE
	//
	// ----------------------------------------------------------------------------


	private function _checkTpeParams($aConstants)
	{

		for($i = 0; $i < count($aConstants); $i ++)
			if (! defined($aConstants[$i]))
				die("Erreur paramètre " . $aConstants[$i] . " indéfini");
	}

}

/*****************************************************************************
 *
 * Classe / Class : CMCIC_Hmac
 *
 *****************************************************************************/

class CMCIC_Hmac
{

	private $_sUsableKey; // La clé du TPE en format opérationnel / The usable TPE key


	// ----------------------------------------------------------------------------
	//
	// Constructeur / Constructor
	//
	// ----------------------------------------------------------------------------


	function __construct($oTpe)
	{

		$this->_sUsableKey = $this->_getUsableKey($oTpe);
	}

	// ----------------------------------------------------------------------------
	//
	// Fonction / Function : _getUsableKey
	//
	// Renvoie la clé dans un format utilisable par la certification hmac
	// Return the key to be used in the hmac function
	//
	// ----------------------------------------------------------------------------


	private function _getUsableKey($oTpe)
	{

		$hexStrKey = substr($oTpe->getCle(), 0, 38);
		$hexFinal = "" . substr($oTpe->getCle(), 38, 2) . "00";

		$cca0 = ord($hexFinal);

		if ($cca0 > 70 && $cca0 < 97)
			$hexStrKey .= chr($cca0 - 23) . substr($hexFinal, 1, 1);
		else
		{
			if (substr($hexFinal, 1, 1) == "M")
				$hexStrKey .= substr($hexFinal, 0, 1) . "0";
			else
				$hexStrKey .= substr($hexFinal, 0, 2);
		}
		return pack("H*", $hexStrKey);
	}

	// ----------------------------------------------------------------------------
	//
	// Fonction / Function : computeHmac
	//
	// Renvoie le sceau HMAC d'une chaine de données
	// Return the HMAC for a data string
	//
	// ----------------------------------------------------------------------------


	public function computeHmac($sData)
	{

		return strtolower(hash_hmac("sha1", $sData, $this->_sUsableKey));

	// If you don't have PHP 5 >= 5.1.2 and PECL hash >= 1.1
	// you may use the hmac_sha1 function defined below
	//return strtolower($this->hmac_sha1($this->_sUsableKey, $sData));
	}

	// ----------------------------------------------------------------------------
	//
	// Fonction / Function : hmac_sha1
	//
	// RFC 2104 HMAC implementation for PHP >= 4.3.0 - Creates a SHA1 HMAC.
	// Eliminates the need to install mhash to compute a HMAC
	// Adjusted from the md5 version by Lance Rushing .
	//
	// ----------------------------------------------------------------------------


	public function hmac_sha1($key, $data)
	{

		$length = 64; // block length for SHA1
		if (strlen($key) > $length)
		{
			$key = pack("H*", sha1($key));
		}
		$key = str_pad($key, $length, chr(0x00));
		$ipad = str_pad('', $length, chr(0x36));
		$opad = str_pad('', $length, chr(0x5c));
		$k_ipad = $key ^ $ipad;
		$k_opad = $key ^ $opad;

		return sha1($k_opad . pack("H*", sha1($k_ipad . $data)));
	}

}
