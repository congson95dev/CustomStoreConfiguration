<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Gssi\AccessRequest\Controller\Index;

use Magento\Contact\Model\ConfigInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Gssi\AccessRequest\Helper\GetConfigAccessRequestData;

class Post extends \Magento\Contact\Controller\Index
{
    /**
     * @var Context
     */
    private $context;


    /**
     * @var LoggerInterface
     */
    private $logger;

    protected $transportBuilder;

    protected $storeManager;

    protected $getConfigAccessRequestData;

    /**
     * @param Context $context
     * @param ConfigInterface $contactsConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        ConfigInterface $contactsConfig,
        LoggerInterface $logger = null,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        GetConfigAccessRequestData $getConfigAccessRequestData
    ) {
        parent::__construct($context, $contactsConfig);
        $this->context = $context;
        $this->logger = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->getConfigAccessRequestData = $getConfigAccessRequestData;
    }

    /**
     * Post user question
     *
     * @return Redirect
     */
    public function execute()
    {
        $isEnable = $this->getConfigAccessRequestData->isEnable('module_enable_field');

        if(!$isEnable){
            $this->messageManager->addErrorMessage(
                __('This module is not enabled.')
            );
            return $this->resultRedirectFactory->create()->setPath('access/index');
        }

        if (!$this->isPostRequest()) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }
        try {
            $this->sendEmail($this->validatedParams());
            $this->messageManager->addSuccessMessage(
                __('Thanks for sending us with your access request. We\'ll respond to you very soon.')
            );
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addErrorMessage(
                __('An error occurred while processing your form. Please try again later.')
            );
        }
        return $this->resultRedirectFactory->create()->setPath('access/index');
    }

    /**
     * @param array $post Post data from contact form
     * @return void
     */
    private function sendEmail($post)
    {
        $send_to =  $this->getConfigAccessRequestData->getEmailOptions('email_send_to');
        $sender = $this->getConfigAccessRequestData->getEmailOptions('email_sender');
        $email_template = $this->getConfigAccessRequestData->getEmailOptions('email_template');

        $templateOptions = array('area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $this->storeManager->getStore()->getId());
        $templateVars = array(
                    'store' => $this->storeManager->getStore(),
                    'name' => $post['name'],
                    'email' => $post['email'],
                    'telephone' => $post['telephone'],
                    'company' => $post['company'],
                    'head_office_address' => $post['head_office_address'],
                    'company_size' => $post['company_size'],
                    'comment' => $post['comment']
                );
        $from = $sender;
        $to = $send_to;
        $transport = $this->transportBuilder->setTemplateIdentifier($email_template)
                        ->setTemplateOptions($templateOptions)
                        ->setTemplateVars($templateVars)
                        ->setFrom($from)
                        ->addTo($to)
                        ->setReplyTo($post['email'], $post['name'])
                        ->getTransport();
        $transport->sendMessage();
    
        
    }

    /**
     * @return bool
     */
    private function isPostRequest()
    {
        /** @var Request $request */
        $request = $this->getRequest();
        return !empty($request->getPostValue());
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function validatedParams()
    {
        $request = $this->getRequest();
        if (trim($request->getParam('name')) === '') {
            throw new LocalizedException(__('Name is missing'));
        }
        if (trim($request->getParam('comment')) === '') {
            throw new LocalizedException(__('Comment is missing'));
        }
        if (false === \strpos($request->getParam('email'), '@')) {
            throw new LocalizedException(__('Invalid email address'));
        }
        if (trim($request->getParam('hideit')) !== '') {
            throw new \Exception();
        }

        return $request->getParams();
    }
}
