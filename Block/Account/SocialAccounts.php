<?php
/**
 * @category    WeltPixel
 * @package     WeltPixel_SocialLogin
 * @copyright   Copyright (c) 2018 WeltPixel
 */

namespace WeltPixel\SocialLogin\Block\Account;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Response\Http;
use Magento\Framework\View\Element\Template;
use WeltPixel\SocialLogin\Model\SocialloginFactory;

/**
 * Class SocialAccounts
 * @package WeltPixel\SocialLogin\Block\Account
 */
class SocialAccounts extends \Magento\Framework\View\Element\Template
{
    /**
     * @var Session
     */
    protected $customerSession;
    /**
     * @var SocialloginFactory
     */
    protected $socialloginFactory;
    /**
     * @var Http
     */
    protected $response;

    /**
     * @var AccountManagementInterface
     */
    protected $customerAccountManagement;

    /**
     * @var \Magento\Framework\Math\Random
     */
    protected $random;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    public function __construct(
        Template\Context $context,
        Session $customerSession,
        SocialloginFactory $socialloginFactory,
        Http $response,
        AccountManagementInterface $customerAccountManagement,
        CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Math\Random $random,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->customerSession = $customerSession;
        $this->socialloginFactory = $socialloginFactory;
        $this->response = $response;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->customerRepository = $customerRepository;
        $this->random = $random;
    }

    /**
     * @return bool
     */
    public function getSocialAccounts()
    {
        $customer = $this->getCustomer();
        if (!$customer->getId()) {
            $this->response->setRedirect('customer/account/');
        }
        $socialAccountsCollection = $this->socialloginFactory->create()->getUsersByCustomerId($customer->getId());
        if ($socialAccountsCollection->getSize() < 1) {
            return false;
        }

        return $socialAccountsCollection;
    }

    /**
     * @return mixed
     */
    public function getCustomer()
    {
        return $this->customerSession->getCustomer();
    }

    /**
     * @param $userId
     * @return string
     */
    public function getUnlinkUrl($userId)
    {
        return $this->getUrl('sociallogin/account/unlink/', ['id' => $userId]);
    }

    /**
     * @return bool
     */
    public function isRequired()
    {
        $customer  = $this->getCustomer();
        $rpToken = $customer->getRpToken();
        $regenerateToken = false;
        $resetPasswordTokenIsValid = false;

        if ($rpToken) {
            try {
                $resetPasswordTokenIsValid = $this->customerAccountManagement->validateResetPasswordLinkToken((int)$customer->getId(), $rpToken);
            } catch (\Exception $e) {
                $resetPasswordTokenIsValid = true;
                $regenerateToken = true;
            }
        }

        if ($regenerateToken) {
            $newPasswordToken = $this->random->getUniqueHash();
            $customerApiModel = $this->customerRepository->getById($customer->getId());
            $this->customerAccountManagement->changeResetPasswordLinkToken($customerApiModel, $newPasswordToken);
        }

        if (!$customer->getPasswordHash() && $resetPasswordTokenIsValid) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return string
     */
    public function getResetPasswordUrl()
    {
        $params = [];
        $customer  = $this->getCustomer();
        $customerId = $customer->getId();
        $params['id'] = $customerId;
        $params['token'] = $customer->getRpToken();
        try {
            $url = $this->getUrl('customer/account/createPassword/', $params);
        } catch (\Exception $e) {
            $newPasswordToken = $this->random->getUniqueHash();
            $customerApiModel = $this->customerRepository->getById($customer->getId());
            $this->customerAccountManagement->changeResetPasswordLinkToken($customerApiModel, $newPasswordToken);

            $params['token'] = $newPasswordToken;
            $url = $this->getUrl('customer/account/createPassword/', $params);
        }

        return $url;
    }
}
