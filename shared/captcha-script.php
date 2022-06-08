<#
    if (extCaptcha && extCaptcha.Enabled && useCaptcha) {
        if (["add", "register"].includes(ctrlId)) {
#>
        // CAPTCHA checking
        if ($this->isShow() || $this->isCopy()) {
            $this->resetCaptcha();
        } elseif (IsPost()) {
            $CurrentForm->Index = -1;
            $captcha = Captcha();
            $captcha->Response = $CurrentForm->getValue($captcha->ResponseField);
            if (!$captcha->validate()) { // CAPTCHA unmatched
                if ($captcha->getFailureMessage() == "") {
                    $captcha->setDefaultFailureMessage();
                }
                $this->CurrentAction = "show"; // Reset action, do not insert
                $this->EventCancelled = true; // Event cancelled
                $this->restoreFormValues(); // Restore form values
            } else {
                if ($this->CurrentAction == "add")
                    $this->resetCaptcha();
            }
        }
<#
        } else if (ctrlId == "edit") {
#>
        // CAPTCHA checking
        if ($this->isShow()) {
            $this->resetCaptcha();
        } elseif (IsPost()) {
            $CurrentForm->Index = -1;
            $captcha = Captcha();
            $captcha->Response = $CurrentForm->getValue($captcha->ResponseField);
            if (!$captcha->validate()) { // CAPTCHA unmatched
                if ($captcha->getFailureMessage() == "") {
                    $captcha->setDefaultFailureMessage();
                }
                $this->CurrentAction = ""; // Reset action, do not update
                $this->EventCancelled = true; // Event cancelled
                $this->restoreFormValues(); // Restore form values
            } else {
                if ($this->CurrentAction == "update")
                    $this->resetCaptcha();
            }
        }
<#
        } else if (ctrlId == "login") {
#>
            // CAPTCHA checking
            if (IsPost()) {
                $captcha = Captcha();
                $captcha->Response = Post($captcha->ResponseField);
                if (!$captcha->validate()) { // CAPTCHA unmatched
                    if ($captcha->getFailureMessage() == "") {
                        $captcha->setDefaultFailureMessage();
                    }
                    $validate = false;
                }
            }
            if (!$validate) {
                $this->resetCaptcha();
            }
<#
        } else if (ctrlId == "reset_password") {
#>
        // CAPTCHA checking
        if (IsPost()) {
            $captcha = Captcha();
            $captcha->Response = Post($captcha->ResponseField);
            if (!$captcha->validate()) { // CAPTCHA unmatched
                if ($captcha->getFailureMessage() == "") {
                    $captcha->setDefaultFailureMessage();
                }
                $validEmail = false;
                $action = "";
            }
        }
        if (!$validEmail) {
            $this->resetCaptcha();
        }
<#
        } else if (ctrlId == "change_password") {
#>
        // CAPTCHA checking
        if (IsPost()) {
            $captcha = Captcha();
            $captcha->Response = Post($captcha->ResponseField);
            if (!$captcha->validate()) { // CAPTCHA unmatched
                if ($captcha->getFailureMessage() == "") {
                    $captcha->setDefaultFailureMessage();
                }
                $validate = false;
            }
        }
        if (!$validate) {
            $this->resetCaptcha();
        }
<#
        }
    }
#>
