<?php
<# if (groupFields.length > 0) { #>
while (<#= grpCount #> <= count($<#= pageObj #>->GroupRecords) && <#= grpCount #> <= <#= displayGrps #>) {
<# } else { #>
while (<#= recCnt #> < count($<#= pageObj #>->DetailRecords) && <#= recCnt #> < <#= displayGrps #>) {
<# } #>
?>
