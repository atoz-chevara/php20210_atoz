<?php

namespace <#= ProjectNamespace #>;

/**
 * Captcha interface
 */
interface CaptchaInterface
{
    public function getHtml();
    public function getConfirmHtml();
    public function validate();
    public function getScript($formName);
}
