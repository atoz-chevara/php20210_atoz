<## Common config #>
<#= include('shared/config-common.php') #>

<## Common table config #>
<#= include('shared/config-table.php') #>

<## Common report config #>
<#= include('shared/config-report.php') #>

<## Page class begin #>
<#= include('shared/page-class-begin.php') #>

<#
	// Calculate grid class name
	let cnt = TABLE.DashboardItems.length,
		arItemClassName = new Array(cnt);
	if (TABLE.TblDashboardType == "horizontal") {
		let col = Math.floor(12/cnt), rmdr = 12 % cnt;
		arItemClassName.fill("col-sm-" + col);
		if (rmdr > 0)
			arItemClassName[cnt - 1] = "col-sm-" + (col + rmdr);
	} else {
		arItemClassName.fill("col-sm-12");
	}
#>

	public $DashboardType = "<#= TABLE.TblDashboardType #>";
	public $ItemClassNames = <#= JSON.stringify(arItemClassName) #>;

	/**
	 * Page run
	 *
	 * @return void
	 */
	public function run()
	{
		global $ExportType, $ExportFileName, $Language, $Security, $UserProfile, $CustomExportType;

<## Page run begin #>
<#= include('shared/page-run-begin.php') #>

		// Set up Breadcrumb
		$this->setupBreadcrumb();

<## Page run end #>
<#= include('shared/page-run-end.php') #>

	}

<## Shared functions #>
<#= include('shared/shared-functions.php') #>
<#= include('shared/report-shared-functions.php') #>

<## Common server events #>
<#= include('shared/server-events.php') #>
<## Page class end #>
<#= include('shared/page-class-end.php') #>
