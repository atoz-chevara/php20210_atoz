<#
    let pageObject = GetPageObject(); // PHP
#>
<?php

namespace <#= ProjectNamespace #>;

<# if (ctrlId == "grid") { #>
// Set up and run Grid object
$<#= pageObj #> = Container("<#= pageObject #>");
$<#= pageObj #>->run();
<# } else if (ctrlId == "dashboard") { #>
// Dashboard Page object
$<#= pageObject #> = $<#= pageObj #>;
<# } else { #>
// Page object
$<#= pageObject #> = &$<#= pageObj #>;
<# } #>
?>