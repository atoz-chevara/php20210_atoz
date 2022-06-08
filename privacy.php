<## Common config #>
<#= include('shared/config-common.php') #>

<## Page class begin #>
<#= include('shared/page-class-begin.php') #>

    /**
     * Page run
     *
     * @return void
     */
    public function run()
    {
        global $ExportType, $CustomExportType, $ExportFileName, $UserProfile, $Language, $Security, $CurrentForm,
            $Breadcrumb;

<## Page run begin #>
<#= include('shared/page-run-begin.php') #>

        $Breadcrumb = new Breadcrumb("<#= homePage #>");
        $Breadcrumb->add("<#= ctrlId #>", "PrivacyPolicy", CurrentUrl(), "ew-privacy", "", true);

        $this->Heading = $Language->phrase("PrivacyPolicy");

<## Page run end #>
<#= include('shared/page-run-end.php') #>

    }
<## Page class end #>
<#= include('shared/page-class-end.php') #>
