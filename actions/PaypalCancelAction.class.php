<?php
class payment_PaypalCancelAction extends f_action_BaseAction
{
	/**
	 * @see f_action_BaseAction::_execute()
	 *
	 * @param Context $context
	 * @param Request $request
	 */
	protected function _execute($context, $request)
	{	
 		$remoteAddr = $_SERVER['REMOTE_ADDR'];
        $requestUri = $_SERVER['REQUEST_URI'];
        $ms = payment_ModuleService::getInstance();	
		$ms->log("BANKING CANCEL PAYPAL from [".$remoteAddr." : ".$requestUri."]");
		$connectorService = payment_PaypalconnectorService::getInstance(); 
		$sessionInfo = $connectorService->getSessionInfo();
		try
		{
			if (isset($sessionInfo['paymentURL']))
			{
				if ($request->getParameter('token') == $sessionInfo['token'])
				{
					$url = $sessionInfo['paymentURL'];
					$response = new payment_Transaction();
					$response->setRawBankResponse(serialize($sessionInfo));
					$order = DocumentHelper::getDocumentInstance($sessionInfo["orderId"]);
					RequestContext::getInstance()->setLang($order->getLang());
					$connector = DocumentHelper::getDocumentInstance($sessionInfo["connectorId"]);
					$response->setOrderId($order->getId());
					$response->setConnectorId($connector->getId());
					$response->setLang($order->getLang());
					$response->setFailed();
					$response->setTransactionText(LocaleService::getInstance()->transFO('m.payment.frontoffice.cancel-transaction'));
					$response->setTransactionId('CANCEL-'. $order->getId());
					$connectorService->setPaymentResult($response, $order);
				}
			}
			else
			{
				$currentWebsite = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
				$url = $currentWebsite->getUrlForLang(RequestContext::getInstance()->getLang());
			}
		}
		catch (Exception $e)
		{
			$currentWebsite = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
			$url = $currentWebsite->getUrlForLang(RequestContext::getInstance()->getLang());
		}
		
		$context->getController()->redirectToUrl($url);
		return VIEW::NONE;
	}

	/**
	 * @return Integer
	 */
	public function getRequestMethods()
	{
		return Request::POST | Request::GET;
	}

	/**
	 * @return Boolean
	 */
	public final function isSecure()
	{
		return false;
	}
}