<# if (groupFields.length > 0) { #>
<?php

    }

    <#
    for (let i = groupFields.length - 1; i >= 0; i--) {
        groupIndex = i;
        let grpFld = groupFields[i];
        if (grpFld.FldGroupByShowSummary) { // Show group summary
    #>
?>

<?php if (<#= totalGrps #> > 0) { ?>

<# if (ctrlId == "crosstab") { #>

<## Group footer rows (crosstab) #>
<#= include('./crosstab-group-footer-rows.php') #>

<# } else if (ctrlId == "summary") { #>

    <# if (showSummaryView) { #>

<## Group footer rows (compact) #>
<#= include('./compact-group-footer-rows.html') #>

    <# } else { #>

<## Group footer rows (summary) #>
<#= include('./summary-group-footer-rows.html') #>

    <# } #>

<# } #>

<?php } ?>

<?php
    <#
        } // End grpFld.FldGroupByShowSummary
        if (i > 0) {
    #>
    } // End group level <#= i #>
    <#
        }
    } // End for i
    #>

?>
<# } #>
