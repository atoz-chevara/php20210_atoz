<## Common config #>
<#= include('shared/config-common.php') #>

<## Common table config #>
<#= include('shared/config-table.php') #>

<## Page class begin #>
<#= include('shared/page-class-begin.php') #>

<## Captcha variables #>
<#= include('shared/captcha-var.php') #>

    public $IsModal = false;
    public $OldPassword;
    public $NewPassword;
    public $ConfirmPassword;

<#
    let passwordField = GetFieldObject(secTable, PROJ.SecPasswdFld),
        checkPasswordStrength = passwordField.FldCheckPasswordStrength;
#>

    /**
     * Page run
     *
     * @return void
     */
    public function run()
    {
        global $ExportType, $CustomExportType, $ExportFileName, $UserProfile, $Language, $Security, $CurrentForm,
            $UserTable, $Breadcrumb, $SkipHeaderFooter;

        $this->OffsetColumnClass = ""; // Override user table

        // Create Password fields object (used by validation only)
        $this->OldPassword = new DbField("<#= secTblVar #>", "<#= Quote(secTable.TblName) #>", "opwd", "opwd", "opwd", "", 202, 255, 0, false, "", false, false, false);
        $this->OldPassword->EditAttrs->appendClass("<#= inputClass #>");
        $this->NewPassword = new DbField("<#= secTblVar #>", "<#= Quote(secTable.TblName) #>", "npwd", "npwd", "npwd", "", 202, 255, 0, false, "", false, false, false);
        $this->NewPassword->EditAttrs->appendClass("<#= inputClass #>");
<# if (checkPasswordStrength) { #>
        $this->NewPassword->EditAttrs->appendClass("ew-password-strength");
<# } #>
        $this->ConfirmPassword = new DbField("<#= secTblVar #>", "<#= Quote(secTable.TblName) #>", "cpwd", "cpwd", "cpwd", "", 202, 255, 0, false, "", false, false, false);
        $this->ConfirmPassword->EditAttrs->appendClass("<#= inputClass #>");
        if (Config("ENCRYPTED_PASSWORD")) {
            $this->OldPassword->Raw = true;
            $this->NewPassword->Raw = true;
            $this->ConfirmPassword->Raw = true;
        }

<## Page run begin #>
<#= include('shared/page-run-begin.php') #>

        // Check modal
        if ($this->IsModal) {
            $SkipHeaderFooter = true;
        }

        $Breadcrumb = new Breadcrumb("<#= homePage #>");
        $Breadcrumb->add("<#= ctrlId #>", "ChangePasswordPage", CurrentUrl(), "", "", true);
        $this->Heading = $Language->phrase("ChangePasswordPage");

        $postBack = IsPost();
        $validate = true;
        if ($postBack) {
            $this->OldPassword->setFormValue(Post($this->OldPassword->FieldVar));
            $this->NewPassword->setFormValue(Post($this->NewPassword->FieldVar));
            $this->ConfirmPassword->setFormValue(Post($this->ConfirmPassword->FieldVar));
            $validate = $this->validateForm();
        }

<## Captcha script #>
<#= include('shared/captcha-script.php') #>

        $pwdUpdated = false;

        if ($postBack && $validate) {

            // Setup variables
            $userName = $Security->currentUserName();
            if (IsPasswordReset())
                $userName = Session(SESSION_USER_PROFILE_USER_NAME);
<# if (checkPasswordExpiry) { #>
            if (IsPasswordExpired())
                $userName = Session(SESSION_USER_PROFILE_USER_NAME);
<# } #>

            $filter = GetUserFilter(Config("LOGIN_USERNAME_FIELD_NAME"),  $userName);

            // Set up filter (WHERE Clause)
            $this->CurrentFilter = $filter;
            $sql = $this->getCurrentSql();

            if ($rsold = Conn($UserTable->Dbid)->fetchAssoc($sql)) {
                if (IsPasswordReset() || ComparePassword(GetUserInfo(Config("LOGIN_PASSWORD_FIELD_NAME"), $rsold), $this->OldPassword->CurrentValue)) {
                    $validPwd = true;
    <# if (ServerScriptExist("Other", "User_ChangePassword")) { #>
                    if (!IsPasswordReset()) {
                        $validPwd = $this->userChangePassword($rsold, $userName, $this->OldPassword->CurrentValue, $this->NewPassword->CurrentValue);
                    }
    <# } #>
                    if ($validPwd) {
                        $rsnew = [Config("LOGIN_PASSWORD_FIELD_NAME") => $this->NewPassword->CurrentValue]; // Change Password
    <# if (PROJ.SecChangeEmail) { #>
                        $emailAddress = GetUserInfo(Config("USER_EMAIL_FIELD_NAME"), $rsold);
    <# } #>
                        $validPwd = $this->update($rsnew);
                        if ($validPwd)
                            $pwdUpdated = true;
                    } else {
                        $this->setFailureMessage($Language->phrase("InvalidNewPassword"));
                    }

                } else {
                    $this->setFailureMessage($Language->phrase("InvalidPassword"));
                }
            }

        }

        if ($pwdUpdated) {
<# if (PROJ.SecChangeEmail) { #>
            if (@$emailAddress != "") {
                // Load Email Content
                $email = new Email();
                $email->load(Config("EMAIL_CHANGE_PASSWORD_TEMPLATE"));
                $email->replaceSender(Config("SENDER_EMAIL")); // Replace Sender
                $email->replaceRecipient($emailAddress); // Replace Recipient
    <# if (ServerScriptExist("Other", "Email_Sending")) { #>
                $args = [];
                $args["rs"] = &$rsnew;
                $emailSent = false;
                if ($this->emailSending($email, $args))
                    $emailSent = $email->send();
    <# } else { #>
                $emailSent = $email->send();
    <# } #>
                // Send email failed
                if (!$emailSent) {
                    $this->setFailureMessage($email->SendErrDescription);
                }
            }
<# } #>

            if ($this->getSuccessMessage() == "") {
                $this->setSuccessMessage($Language->phrase("PasswordChanged")); // Set up success message
            }

            if (IsPasswordReset()) {
                $_SESSION[SESSION_STATUS] = "";
                $_SESSION[SESSION_USER_PROFILE_USER_NAME] = "";
            }

<# if (checkPasswordExpiry) { #>
            // Update user profile and login again
            global $UserProfile;
            $UserProfile->loadProfileFromDatabase($userName);
            $UserProfile->setValue(Config("USER_PROFILE_LAST_PASSWORD_CHANGED_DATE"), StdCurrentDate());
            $UserProfile->saveProfileToDatabase($userName);

            if (IsPasswordExpired()) {
                $_SESSION[SESSION_USER_PROFILE_PASSWORD] = $this->NewPassword->CurrentValue;
                $_SESSION[SESSION_STATUS] = "loggingin";
                $this->terminate("<#= loginPage #>"); // Return to login page
                return;
            }
<# } #>

            $this->terminate("<#= indexPage #>"); // Return to default page
            return;

        }

<## Page run end #>
<#= include('shared/page-run-end.php') #>

    }

    // Validate form
    protected function validateForm()
    {
        global $Language;

        // Check if validation required
        if (!Config("SERVER_VALIDATE")) {
            return true;
        }

        $valid = true;
        if (!IsPasswordReset() && EmptyValue($this->OldPassword->CurrentValue)) {
            $this->OldPassword->addErrorMessage($Language->phrase("EnterOldPassword"));
            $valid = false;
        }
        if (EmptyValue($this->NewPassword->CurrentValue)) {
            $this->NewPassword->addErrorMessage($Language->phrase("EnterNewPassword"));
            $valid = false;
        }
        if (!$this->NewPassword->Raw && Config("REMOVE_XSS") && CheckPassword($this->NewPassword->CurrentValue)) {
            $this->NewPassword->addErrorMessage($Language->phrase("InvalidPasswordChars"));
        }
        if ($this->NewPassword->CurrentValue != $this->ConfirmPassword->CurrentValue) {
            $this->ConfirmPassword->addErrorMessage($Language->phrase("MismatchPassword"));
        }

    <# if (ServerScriptExist("Other", "Form_CustomValidate")) { #>
        // Call Form CustomValidate event
        $formCustomError = "";
        $valid = $valid && $this->formCustomValidate($formCustomError);
        if ($formCustomError != "") {
            $this->setFailureMessage($formCustomError);
        }
    <# } #>

        return $valid;

    }

<## Common server events #>
<#= include('shared/server-events.php') #>

    <#= GetServerScript("Other", "Email_Sending") #>
    <#= GetServerScript("Other", "Form_CustomValidate") #>
    <#= GetServerScript("Other", "User_ChangePassword") #>
<## Page class end #>
<#= include('shared/page-class-end.php') #>
