<# if (useCaptcha) { #>

    // Reset Captcha
    protected function resetCaptcha()
    {
        $sessionName = Captcha()->getSessionName();
        $_SESSION[$sessionName] = Random();
    }

<# } #>
