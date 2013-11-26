<?php
class payment_BankCancelCybermutAction extends f_action_BaseAction
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
		$ms->log("BANKING CANCEL CYBERMUT from [".$remoteAddr." : ".$requestUri."] BEGIN");

		try
		{
			$this->getTransactionManager()->beginTransaction();
			$connectorService = payment_CybermutconnectorService::getInstance();
			$sessionInfo = $connectorService->getSessionInfo();
			if (count($sessionInfo) == 0)
			{
				throw new Exception('Session expired');
			}

					$params = array();
			$params['orderId'] = $request->getParameter('orderId');
			$params['connectorId'] = $request->getParameter('connectorId');
			$params['lang'] = $request->getParameter('lang');
			$params['status'] = 'Annulation';

			$bankResponse = $connectorService->getCallbackResponse($params);
			RequestContext::getInstance()->setLang($bankResponse->getLang());

			$order = $bankResponse->getOrder();

			//En production le listener ce charge de compléter la commande
			if (Framework::inDevelopmentMode())
			{
				$connectorService->setPaymentResult($bankResponse, $order);
			}
			elseif ($order->getPaymentStatus() === 'initiated')
			{
				$ms->log("BANKING CYBERMUT from [" . $remoteAddr . " : " . $requestUri . "] UPDATE STATUS: FAILED");
				$connectorService->setPaymentResult($bankResponse, $order);
			}

			$url = $sessionInfo['paymentURL'];
			$connectorService->setSessionInfo(array());
			$ms->log("BANKING CANCEL CYBERMUT from [".$remoteAddr." : ".$requestUri."] END AND REDIRECT : " . $url);

			$this->getTransactionManager()->commit();
		}
		catch(Exception $e)
		{
			$ms->log("BANKING CANCEL CYBERMUT from [".$remoteAddr." : ".$requestUri."] FAILED : " . $e->getMessage());
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
