        // Set LoginStatus / Page_Rendering / Page_Render
        if (!IsApi() && !$this->isTerminated()) {

        <# if (isExtendPageClass) { #>
            // Pass table and field properties to client side
            $this->toClientVar(["tableCaption"], ["caption", "Visible", "Required", "IsInvalid", "Raw"]);
        <# } #>

        <# if (isSecurityEnabled) { #>
            // Setup login status
            SetupLoginStatus();
        <# } #>

            // Pass login status to client side
            SetClientVar("login", LoginStatus());

<# if (ctrlId != "error") { #>

            // Global Page Rendering event (in userfn*.php)
            Page_Rendering();

            // Page Render event
            if (method_exists($this, "pageRender")) {
                $this->pageRender();
            }

<# } #>

        }