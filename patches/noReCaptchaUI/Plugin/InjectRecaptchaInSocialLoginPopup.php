<?php
/**
 * @category    WeltPixel
 * @package     WeltPixel_SocialLogin
 * @copyright   Copyright (c) 2018 WeltPixel
 */

namespace WeltPixel\SocialLogin\Plugin;

use Magento\Customer\Block\Account\AuthenticationPopup;
use Magento\Framework\Exception\InputException;
use Magento\Framework\ObjectManager\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;


/**
 * Class InserRecaptchaInSocialLogin
 * @package WeltPixel\SocialLogin\Plugin
 */
class InjectRecaptchaInSocialLoginPopup
{
    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param Json $serializer
     */
    public function __construct(
        Json $serializer
    ) {
        $this->serializer = $serializer;
    }

    /**
     * @param AuthenticationPopup $subject
     * @param string $result
     * @return string
     * @throws InputException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetJsLayout(AuthenticationPopup $subject, $result)
    {
        if (!interface_exists('Magento\ReCaptchaUi\Model\UiConfigResolverInterface')) {
            return $result;
        }

        $isCaptchaEnabled = ObjectManager::getInstance()->get('Magento\ReCaptchaUi\Model\IsCaptchaEnabledInterface');
        $captchaUiConfigResolver = ObjectManager::getInstance()->get('Magento\ReCaptchaUi\Model\UiConfigResolverInterface');

        $layout = $this->serializer->unserialize($result);
        $loginKey = 'customer_login';
        $crateAccountKey = 'customer_create';

        if ($isCaptchaEnabled->isCaptchaEnabledFor($loginKey)) {
            $layout['components']['ajaxLogin']['children']['recaptcha-login']['settings']
                = $captchaUiConfigResolver->get($loginKey);
        } else {
            unset($layout['components']['ajaxLogin']['children']['recaptcha-login']);
        }

        if ($isCaptchaEnabled->isCaptchaEnabledFor($crateAccountKey)) {
            $layout['components']['ajaxLogin']['children']['recaptcha-register']['settings']
                = $captchaUiConfigResolver->get($crateAccountKey);
        } else {
            unset($layout['components']['ajaxLogin']['children']['recaptcha-register']);
        }

        return $this->serializer->serialize($layout);
    }
}
