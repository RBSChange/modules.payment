<?php
class payment_BankResponseOgoneAction extends f_action_BaseAction
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
		$ms->log("BANKING OGONE from [".$remoteAddr." : ".$requestUri."] BEGIN");	
		$url = null;
		try
		{
			$this->getTransactionManager()->beginTransaction();		
			$parameters = $request->getParameters();
			$connectorService = payment_OgoneconnectorService::getInstance();
			$bankResponse = $connectorService->getBankResponse($parameters);
			if ($bankResponse)
			{
				$order = $bankResponse->getOrder();
				$connectorService->setPaymentResult($bankResponse, $order);
			}
			
			$sessionInfo = $connectorService->getSessionInfo();
			if (isset($sessionInfo['paymentURL']))
			{
				$url = $sessionInfo['paymentURL'];
				$connectorService->setSessionInfo(array());
			}
			elseif ($bankResponse)
			{
				$order = $bankResponse->getOrder();
				if ($order instanceof order_persistentdocument_bill)
				{
					$doc = $order->getOrder();
					if ($doc !== null && $doc->getCustomer() == customer_CustomerService::getInstance()->getCurrentCustomer())
					{
						$url = LinkHelper::getDocumentUrlForWebsite($doc, $doc->getWebsite(), $doc->getLang());
					}
				}
			}
	
			if ($url === null)
			{
				$currentWebsite = website_WebsiteModuleService::getInstance()->getCurrentWebsite();
				$url = $currentWebsite->getUrlForLang(RequestContext::getInstance()->getLang());
			}
			
			$ms->log("BANKING OGONE from [".$remoteAddr." : ".$requestUri."] END AND REDIRECT : " . $url);
			$this->getTransactionManager()->commit();
	
		}
		catch(Exception $e)
		{
			$ms->log("BANKING OGONE from [".$remoteAddr." : ".$requestUri."] FAILED : " . $e->getMessage());
			$this->getTransactionManager()->rollBack($e);
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