<?php
class payment_BankListenerCybermutAction extends f_action_BaseAction
{

	/**
	 * The response we must give to the bank server should be:
	 *
	 * Pragma: no-cache
	 * Content-type: text/plain
	 * Version: 1
	 * OK
	 *
	 * or:
	 *
	 * Pragma: no-cache
	 * Content-type: text/plain
	 * Version: 1
	 * Document falsifie
	 *
	 * @param Context $context
	 * @param WebRequest $request
	 */
	public function _execute($context, $request)
	{
		$remoteAddr = $_SERVER['REMOTE_ADDR'];
		$requestUri = $_SERVER['REQUEST_URI'];
		$ms = payment_ModuleService::getInstance();
		$ms->log("BANKING CYBERMUT LISTENER from [".$remoteAddr." : ".$requestUri."] BEGIN");

		try
		{
			$this->getTransactionManager()->beginTransaction();

			$connectorService = payment_CybermutconnectorService::getInstance();
			$bankResponse = $connectorService->getBankResponse($request->getParameters());
			if ($bankResponse->getTransactionId() == null)
			{
				throw new Exception("CYBERMUT BANKING FAILED : BAD MAC CHECKING");
			}
			$order = $bankResponse->getOrder();

			$taskId = $order->getMeta('taskId');
			if($taskId)
			{
				$task = DocumentHelper::getDocumentInstance($taskId);
				if(!$task->isPublished())
				{
					Framework::error(__METHOD__.": payment task $taskId already executed");
					$ms->log("BANKING CYBERMUT TASK : payment task $taskId already executed");
					$task = null;
				}

				if($task->getIsrunning())
				{
					Framework::error(__METHOD__.": payment task $taskId is running");
					$ms->log("BANKING CYBERMUT TASK : payment task $taskId is running");
					$task = null;
				}
			}

			if($task)
			{
				if($bankResponse->isAccepted())
				{
					// la validation de paiement se fera 5 minutes plus tard pour ne pas être en conflit avec le traitement
					// sur la page de retour de la banque (payment_BankResponseCybermutAction)
					$date = date_Calendar::getInstance()->add(date_Calendar::MINUTE, 5);
					$task->setUniqueExecutiondate($date);
				}
				$task->setParameters(serialize(array('bankResponse' => $bankResponse)));
				f_persistentdocument_PersistentProvider::getInstance()->updateDocument($task);
				$ms->log("BANKING CYBERMUT TASK : update payment task $taskId");
			}
			else
			{
				if($bankResponse->isAccepted())
				{
					// la validation de paiement se fera 5 minutes plus tard pour ne pas être en conflit avec le traitement
					// sur la page de retour de la banque (payment_BankResponseCybermutAction)
					$date = date_Calendar::getInstance()->add(date_Calendar::MINUTE, 5);
				}
				else
				{
					// selon la FAQ dans la documentation technique, l'internaute dispose de 45 minutes pour saisir le numéro CB
					// https://www.cmcicpaiement.fr/fr/installation/telechargements/index.html
					$date = date_Calendar::getInstance()->add(date_Calendar::MINUTE, 60);
				}

				$task = task_PlannedtaskService::getInstance()->getNewDocumentInstance();
				$task->setSystemtaskclassname('payment_CybermutPaymentResult');
				$task->setLabel(__METHOD__);
				$task->setUniqueExecutiondate($date);
				$task->setParameters(serialize(array('bankResponse' => $bankResponse)));
				$task->save();
				$order->setMeta('taskId', $task->getId());
				$order->saveMeta();
				$ms->log("BANKING CYBERMUT TASK : create payment task $taskId");
			}

			if (f_util_StringUtils::isEmpty($order->getPaymentStatus()) || $order->getPaymentStatus() === 'initiated')
			{
				$order->setPaymentStatus('waiting');
			}

			$ms->log("BANKING CYBERMUT LISTENER from [".$remoteAddr." : ".$requestUri."] END");
			$this->getTransactionManager()->commit();
		}
		catch(Exception $e)
		{
			$ms->log("BANKING CYBERMUT LISTENER from [".$remoteAddr." : ".$requestUri."] FAILED : " . $e->getMessage());
			$this->getTransactionManager()->rollBack($e);

			header("Pragma: no-cache");
			header("Content-type: text/plain");
			$receipt = CMCIC_CGI2_MACNOTOK.$bankResponse->getRawBankResponse();
			printf (CMCIC_CGI2_RECEIPT, $receipt);
			exit();
		}

		ob_get_clean();
		header("Pragma: no-cache");
		header("Content-type: text/plain");
		$receipt = CMCIC_CGI2_MACOK;
		printf (CMCIC_CGI2_RECEIPT, $receipt);
		exit();
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
