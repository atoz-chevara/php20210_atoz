<## Common config #>
<#= include('shared/config-common.php') #>

<#
    let customPage = TABLE.TblName,
        isPhp = customPage.trim().toLowerCase().endsWith(".php"),
        includeFiles = TABLE.IncludeFiles && isPhp;

    global.tblVar = TABLE.TblVar;
#>

<# if (includeFiles) { #>

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

        // Set up Breadcrumb
        $this->setupBreadcrumb();

<## Page run end #>
<#= include('shared/page-run-end.php') #>

    }

    // Set up Breadcrumb
    protected function setupBreadcrumb()
    {
        global $Breadcrumb, $Language;
        $Breadcrumb = new Breadcrumb("<#= homePage #>");
        $Breadcrumb->add("<#= ctrlId #>", "<#= tblVar #>", CurrentUrl(), "", "<#= tblVar #>", true);
        $this->Heading = $Language->TablePhrase("<#= tblVar #>", "TblCaption");
    }

<## Common server events #>
<#= include('shared/server-events.php') #>

<## Page class end #>
<#= include('shared/page-class-end.php') #>

<# } #>
