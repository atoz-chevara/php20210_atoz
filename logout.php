<## Common config #>
<#= include('shared/config-common.php') #>

<## Default table #>
<# if (hasUserTable) { #>
<#= include('shared/config-table.php') #>
<# } #>

<## Page class begin #>
<#= include('shared/page-class-begin.php') #>

    /**
     * Page run
     *
     * @return void
     */
    public function run()
    {
        global $ExportType, $CustomExportType, $ExportFileName, $UserProfile, $Language, $Security, $CurrentForm;

<## Page run begin #>
<#= include('shared/page-run-begin.php') #>

        $validate = true;

        $username = $Security->currentUserName();

        <# if (ServerScriptExist("Other", "User_LoggingOut")) { #>
        // Call User LoggingOut event
        $validate = $this->userLoggingOut($username);
        <# } else { #>
        $validate = true;
        <# } #>

        if (!$validate) {

            $lastUrl = $Security->lastUrl();
            if ($lastUrl == "") {
                $lastUrl = "<#= indexPage #>";
            }
            $this->terminate($lastUrl); // Go to last accessed URL
            return;

        } else {

            if (ReadCookie("AutoLogin") == "") // Not autologin
                WriteCookie("Username", ""); // Clear user name cookie

            WriteCookie("Password", ""); // Clear password cookie
            WriteCookie("LastUrl", ""); // Clear last URL

<# if (checkConcurrentUser) { #>

            // Clear Session ID
            $UserProfile->removeUser($username, session_id());

<# } #>

            <# if (PROJ.SecLogInOutAuditTrail) { #>
            $this->writeAuditTrailOnLogout();
            <# } #>

            <# if (ServerScriptExist("Other", "User_LoggedOut")) { #>
            // Call User LoggedOut event
            $this->userLoggedOut($username);
            <# } #>

            // Clean upload temp folder
            CleanUploadTempPaths(session_id());

            // Unset all of the Session variables
            $_SESSION = [];

            // If session expired, show expired message
            if (Get("expired") == "1") {
                Container("flash")->addMessage("failure", $Language->phrase("SessionExpired"));
            }
            <# if (PROJ.UsePersonalData) {#>
            if (Get("deleted") == "1") {
                Container("flash")->addMessage("success", $Language->phrase("PersonalDataDeleteSuccess"));
            }
            <# } #>
            session_write_close();

            // Delete the Session cookie and kill the Session
            if (\Delight\Cookie\Cookie::exists(session_name())) {
                $cookie = new \Delight\Cookie\Cookie(session_name());
                $cookie->setSameSiteRestriction(Config("COOKIE_SAMESITE"));
                $cookie->setHttpOnly(Config("COOKIE_HTTP_ONLY"));
                $cookie->setSecureOnly(Config("COOKIE_SAMESITE") == "None" || IsHttps() && Config("COOKIE_SECURE"));
                $cookie->delete();
            }

            // Go to login page
            $this->terminate("<#= loginPage #>");
            return;
        }

<## Page run end #>
<#= include('shared/page-run-end.php') #>

    }

<# if (PROJ.SecLogInOutAuditTrail) { #>

    // Write audit trail on logout
    protected function writeAuditTrailOnLogout()
    {
        global $Language;
        <# if (useUserIdForAuditTrail) { #>
        $usr = CurrentUserID();
        <# } else { #>
        $usr = CurrentUserName();
        <# } #>
        WriteAuditLog($usr, $Language->phrase("AuditTrailLogout"), CurrentUserIP(), "", "", "", "");
    }

<# } #>

<## Common server events #>
<#= include('shared/server-events.php') #>

    <#= GetServerScript("Other", "User_LoggingOut") #>
    <#= GetServerScript("Other", "User_LoggedOut") #>
<## Page class end #>
<#= include('shared/page-class-end.php') #>
