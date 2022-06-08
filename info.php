<## Common config #>
<#= include('shared/config-common.php') #>

<## Common table config #>
<#= include('shared/config-table.php') #>

<## Common report config #>
<# if (TABLE.TblType == "REPORT") { #>
<#= include('shared/config-report.php') #>
<# } #>

<## Common table class #>
<#= include('shared/table-class.php') #>
