    // Set up other options
    protected function setupOtherOptions()
    {
        global $Language, $Security;

        // Filter button
        $item = &$this->FilterOptions->add("savecurrentfilter");
        $item->Body = "<a class=\"ew-save-filter\" data-form=\"<#= formNameSearch #>\" href=\"#\" onclick=\"return false;\">" . $Language->phrase("SaveCurrentFilter") . "</a>";
        $item->Visible = <#= Code.bool(useBasicSearch || useExtendedBasicSearch || useAdvancedSearch) #>;
        $item = &$this->FilterOptions->add("deletefilter");
        $item->Body = "<a class=\"ew-delete-filter\" data-form=\"<#= formNameSearch #>\" href=\"#\" onclick=\"return false;\">" . $Language->phrase("DeleteFilter") . "</a>";
        $item->Visible = <#= Code.bool(useBasicSearch || useExtendedBasicSearch || useAdvancedSearch) #>;
        $this->FilterOptions->UseDropDownButton = true;
        $this->FilterOptions->UseButtonGroup = !$this->FilterOptions->UseDropDownButton;
        $this->FilterOptions->DropDownButtonPhrase = $Language->phrase("Filters");

        // Add group option item
        $item = &$this->FilterOptions->add($this->FilterOptions->GroupOptionName);
        $item->Body = "";
        $item->Visible = false;
    }

    // Set up starting group
    protected function setupStartGroup()
    {

        // Exit if no groups
        if ($this->DisplayGroups == 0) {
            return;
        }

        $startGrp = Param(Config("TABLE_START_GROUP"), "");
        $pageNo = Param("pageno", "");

        // Check for a 'start' parameter
        if ($startGrp != "") {
            $this->StartGroup = $startGrp;
            $this->setStartGroup($this->StartGroup);
        } elseif ($pageNo != "") {
            if (is_numeric($pageNo)) {
                $this->StartGroup = ($pageNo - 1) * $this->DisplayGroups + 1;
                if ($this->StartGroup <= 0) {
                    $this->StartGroup = 1;
                } elseif ($this->StartGroup >= intval(($this->TotalGroups - 1) / $this->DisplayGroups) * $this->DisplayGroups + 1) {
                    $this->StartGroup = intval(($this->TotalGroups - 1) / $this->DisplayGroups) * $this->DisplayGroups + 1;
                }
                $this->setStartGroup($this->StartGroup);
            } else {
                $this->StartGroup = $this->getStartGroup();
            }
        } else {
            $this->StartGroup = $this->getStartGroup();
        }

        // Check if correct start group counter
        if (!is_numeric($this->StartGroup) || $this->StartGroup == "") { // Avoid invalid start group counter
            $this->StartGroup = 1; // Reset start group counter
            $this->setStartGroup($this->StartGroup);
        } elseif (intval($this->StartGroup) > intval($this->TotalGroups)) { // Avoid starting group > total groups
            $this->StartGroup = intval(($this->TotalGroups - 1) / $this->DisplayGroups) * $this->DisplayGroups + 1; // Point to last page first group
            $this->setStartGroup($this->StartGroup);
        } elseif (($this->StartGroup - 1) % $this->DisplayGroups != 0) {
            $this->StartGroup = intval(($this->StartGroup - 1) / $this->DisplayGroups) * $this->DisplayGroups + 1; // Point to page boundary
            $this->setStartGroup($this->StartGroup);
        }
    }

    // Reset pager
    protected function resetPager()
    {
        // Reset start position (reset command)
        $this->StartGroup = 1;
        $this->setStartGroup($this->StartGroup);
    }

    <# if (!IsEmpty(groupPerPageList)) { #>

    // Set up number of groups displayed per page
    protected function setupDisplayGroups()
    {

        $this->DisplayGroups = <#= groupPerPage #>; // Load default
        if (Param(Config("TABLE_GROUP_PER_PAGE")) !== null) {
            $wrk = Param(Config("TABLE_GROUP_PER_PAGE"));
            if (is_numeric($wrk)) {
                $this->DisplayGroups = intval($wrk);
            } elseif (strtoupper($wrk) == "ALL") { // Display all groups
                $this->DisplayGroups = -1;
            }

            // Reset start position (reset command)
            $this->StartGroup = 1;
            $this->setStartGroup($this->StartGroup);
        } elseif ($this->getGroupPerPage() != "") {
            $this->DisplayGroups = $this->getGroupPerPage(); // Restore from session
        }
        $this->setGroupPerPage($this->DisplayGroups); // Save to session
    }

    <# } #>

    <#
    let sortFields = TABLE.TblReportType == "crosstab" ? groupFields : currentFields;
#>

    // Get sort parameters based on sort links clicked
    protected function getSort()
    {

        if ($this->DrillDown) {
            return "<#= Quote(defaultOrderBy) #>";
        }

        $resetSort = Param("cmd") === "resetsort";
        $orderBy = Param("order", "");
        $orderType = Param("ordertype", "");

        <# if (sortType == 2) { #>
        // Check for Ctrl pressed
        $ctrl = (Param("ctrl") !== null);
        <# } #>

        // Check for a resetsort command
        if ($resetSort) {
            $this->setOrderBy("");
            $this->setStartGroup(1);
        <#
            for (let sortFld of sortFields) {
                let fldObj = "this->" + sortFld.FldParm;
        #>
            $<#= fldObj #>->setSort("");
        <#
            }
        #>

        // Check for an Order parameter
        } elseif ($orderBy != "") {
            $this->CurrentOrder = $orderBy;
            $this->CurrentOrderType = $orderType;
            <#
            for (let sortFld of sortFields) {
                let fldName = sortFld.FldName,
                fldParm = sortFld.FldParm,
                fldObj = "this->" + fldParm;
                if (sortType == 1) { // Single Column Sort
        #>
            $this->updateSort($<#= fldObj #>); // <#= fldName #>
        <#
                } else if (sortType == 2) { // Multi Column Sort
        #>
            $this->updateSort($<#= fldObj #>, $ctrl); // <#= fldName #>
    <#
                }
            }
    #>
            $sortSql = $this->sortSql();
            $this->setOrderBy($sortSql);
            $this->setStartGroup(1);
        }

    <#
        if (!IsEmpty(defaultOrderBy)) {
    #>
        // Set up default sort
        if ($this->getOrderBy() == "") {
            $useDefaultSort = true;
        <#
                let orderByFlds = defaultOrderBy.split(",");
                let fldSorts = {};
                for (let orderByFld of orderByFlds) {
                    let fldExpr = orderByFld.trim(), sort = "ASC";
                    if (fldExpr.toUpperCase().endsWith(" ASC")) {
                        sort = "ASC";
                        fldExpr = fldExpr.substr(0, fldExpr.length - 3).trim();
                    } else if (fldExpr.toUpperCase().endsWith(" DESC")) {
                        sort = "DESC";
                        fldExpr = fldExpr.substr(0, fldExpr.length - 4).trim();
                    }
                    for (let f of sortFields) {
                        let fld = FieldSqlName(f, tblDbId), fldParm = f.FldParm;
                        if (!IsBinaryField(f) && fld == fldExpr) {
                            fldSorts[fldParm] = sort;
        #>
            if ($this-><#= fldParm #>->getSort() != "") {
                $useDefaultSort = false;
            }
        <#
                            break;
                        }
                    } // Field
                } // OrderField
        #>
            if ($useDefaultSort) {
        <#
                for (let fldParm in fldSorts) {
        #>
                $this-><#= fldParm #>->setSort("<#= fldSorts[fldParm] #>");
        <#
                }
        #>
                $this->setOrderBy("<#= Quote(defaultOrderBy) #>");
            }
        }
    <#
        }
    #>

        return $this->getOrderBy();
    }

    <# if (dynamicSortCharts.length) { #>

    // Get chart sort
    protected function getChartSort()
    {

        // Check for a resetsort command
        if (Get("cmd") !== null) {
            $cmd = Get("cmd");
            if ($cmd == "resetsort") {
            <#
                let dynamicSortChartVars = dynamicSortCharts.map(c => c.ChartVar);
                for (let chartVar of dynamicSortChartVars) {
            #>
                $this-><#= chartVar #>->setSort(0);
            <#
                }
            #>
            }

        // Check for chartorder parameter
        } elseif (Get("chartorder") !== null) {
            $chartorder = Get("chartorder");
            $chartordertype = Get("chartordertype", "");
        <#
            for (let chartVar of dynamicSortChartVars) {
        #>
                if ($chartorder == "<#= chartVar #>") {
                    $this-><#= chartVar #>->setSort($chartordertype);
                }
        <#
            }
        #>
        }

        // Restore chart sort type from Session
        <#
        for (let chartVar of dynamicSortChartVars) {
    #>
            $this-><#= chartVar #>->SortType = $this-><#= chartVar #>->getSort();
            <#
        }
    #>
    }

    <# } #>

    <# if (useExtendedBasicSearch) { #>

    // Return extended filter
    protected function getExtendedFilter()
    {

        $filter = "";

        if ($this->DrillDown) {
            return "";
        }

        $restoreSession = false;
        $restoreDefault = false;
        // Reset search command
        if (Get("cmd", "") == "reset") {
            // Set default values
    <#
            for (let f of currentFields) {
                if (IsExtendedFilter(f)) {
                    let fldName = f.FldName,
                    fldParm = f.FldParm,
                    fldObj = "this->" + fldParm;
    #>
            $<#= fldObj #>->AdvancedSearch->unsetSession();
    <#
                }
            }
    #>
            $restoreDefault = true;
        } else {
            $restoreSession = !$this->SearchCommand;

    <#
        for (let f of currentFields) {
            if (IsExtendedFilter(f)) {
                let fldName = f.FldName,
                fldParm = f.FldParm,
                fldObj = "this->" + fldParm;
                if (!IsTextFilter(f)) {
    #>
            // Field <#= fldName #>
            $this->getDropDownValue($<#= fldObj #>);
    <#
                } else {
    #>
            // Field <#= fldName #>
            if ($this-><#= fldParm #>->AdvancedSearch->get()) {
            <# if (IsAutoSuggest(f)) { #>
            if (FieldDataType($<#= fldObj #>->Type) == DATATYPE_DATE) // Format default date format
            $<#= fldObj #>->AdvancedSearch->SearchValue = FormatDateTime($<#= fldObj #>->AdvancedSearch->SearchValue, 0);
            <# } #>
            }
    <#
                }
            }
        }
    #>

            if (!$this->validateForm()) {
                return $filter;
            }
        }

        // Restore session
        if ($restoreSession) {
            $restoreDefault = true;
    <#
        for (let f of currentFields) {
            if (IsExtendedFilter(f)) {
                let fldName = f.FldName,
                    fldParm = f.FldParm,
                    fldObj = "this->" + fldParm;
    #>
            if ($this-><#= fldParm #>->AdvancedSearch->issetSession()) { // Field <#= fldName #>
                $this-><#= fldParm #>->AdvancedSearch->load();
                $restoreDefault = false;
            }
    <#
        }
    }
    #>
        }

        // Restore default
        if ($restoreDefault) {
            $this->loadDefaultFilters();
        }

    <# if (ServerScriptExist("Table", "Page_FilterValidated")) { #>
        // Call page filter validated event
        $this->pageFilterValidated();
    <# } #>

        // Build SQL and save to session
    <#
    for (let f of currentFields) {
        if (IsExtendedFilter(f)) {
            let fldName = f.FldName,
                fldParm = f.FldParm,
                fldObj = "this->" + fldParm,
                dropDownType = "";
            if (!IsTextFilter(f)) {
                if (IsDateFilter(f)) {
                    dropDownType = Code.getName(fldObj, "DateFilter");
                } else {
                    dropDownType = Code.getName(fldObj, "AdvancedSearch", "SearchOperator");
                }
    #>
        $this->buildDropDownFilter($<#= fldObj #>, $filter, <#= dropDownType #>, false, true); // Field <#= fldName #>
            <#
        } else {
    #>
        $this->buildExtendedFilter($<#= fldObj #>, $filter, false, true); // Field <#= fldName #>
            <#
        }
    #>
        $<#= fldObj #>->AdvancedSearch->save();
    <#
        }
    }
    #>

	<#
		for (let f of currentFields) {
			if (IsExtendedFilter(f) && (IsDateFilter(f) || !IsTextFilter(f))) {
				let fldName = f.FldName,
					fldParm = f.FldParm,
					fldObj = "this->" + fldParm,
					valueType = "";
				if (GetFieldType(f.FldType) == 2) {
					valueType = "date";
				}
				// Enum or Set field
				if (GetFieldType(f.FldType) == 4 || f.FldTypeName == "ENUM" || f.FldTypeName == "SET") {
					let valueList = "", values = f.FldTagValues ? f.FldTagValues.split("|")
						.filter(value => !IsEmpty(value.trim()))
						.map(value => DoubleQuote(value.split(",")[0], 1)) : "";
					if (IsBooleanField(TABLE, f) && f.FldHtmlTag == "CHECKBOX") {
						valueList = values.find(value => ["1", "Y", "YES", "T", "true"].includes(RemoveQuotes(value.toUpperCase()))) || "";
					} else if (!IsEmpty(values)) {
						valueList = values.join(",");
					}
					valueList = `[${valueList}]`;
	#>
        // Field <#= fldName #>
        $<#= fldObj #>->EditValue = <#= valueList #>;
    <#
        } else {
    #>
        // Field <#= fldName #>
        LoadDropDownList($<#= fldObj #>->EditValue, $<#= fldObj #>->AdvancedSearch->SearchValue);
    <#
                }
            }
        }
    #>

        return $filter;

    }

    // Build dropdown filter
    protected function buildDropDownFilter(&$fld, &$filterClause, $fldOpr, $default = false, $saveFilter = false)
    {
        $fldVal = $default ? $fld->AdvancedSearch->SearchValueDefault : $fld->AdvancedSearch->SearchValue;
        $sql = "";
        if (is_array($fldVal)) {
            foreach ($fldVal as $val) {
                $wrk = $this->getDropDownFilter($fld, $val, $fldOpr);
                if ($wrk != "") {
                    if ($sql != "") {
                        $sql .= " OR " . $wrk;
                    } else {
                        $sql = $wrk;
                    }
                }
            }
        } else {
            $fldVal2 = $default ? $fld->AdvancedSearch->SearchValue2Default : $fld->AdvancedSearch->SearchValue2;
            $sql = $this->getDropDownFilter($fld, $fldVal, $fldOpr, $fldVal2);
        }
        if ($sql != "") {
            AddFilter($filterClause, $sql);
            if ($saveFilter) {
                $fld->CurrentFilter = $sql;
            }
        }
    }

    // Get dropdown filter
    protected function getDropDownFilter(&$fld, $fldVal, $fldOpr, $fldVal2 = "")
    {
        $fldName = $fld->Name;
        $fldExpression = $fld->Expression;
        $fldDataType = $fld->DataType;
        $isMultiple = $fld->HtmlTag == "CHECKBOX" || $fld->HtmlTag == "SELECT" && $fld->SelectMultiple;
        $fldVal = strval($fldVal);
        if ($fldOpr == "") {
            $fldOpr = "=";
        }
        $wrk = "";
        if (SameString($fldVal, Config("NULL_VALUE"))) {
            $wrk = $fldExpression . " IS NULL";
        } elseif (SameString($fldVal, Config("NOT_NULL_VALUE"))) {
            $wrk = $fldExpression . " IS NOT NULL";
        } elseif (SameString($fldVal, EMPTY_VALUE)) {
            $wrk = $fldExpression . " = ''";
        } elseif (SameString($fldVal, ALL_VALUE)) {
            $wrk = "1 = 1";
        } else {
            if ($fld->GroupSql != "") { // Use grouping SQL for search if exists
                $fldExpression = str_replace("%s", $fldExpression, $fld->GroupSql);
            }
            if (StartsString("@@", $fldVal)) {
                $wrk = $this->getCustomFilter($fld, $fldVal, $this->Dbid);
            } elseif ($isMultiple && IsMultiSearchOperator($fldOpr) && trim($fldVal) != "" && $fldVal != INIT_VALUE && ($fldDataType == DATATYPE_NUMBER || $fldDataType == DATATYPE_STRING || $fldDataType == DATATYPE_MEMO)) {
                $wrk = GetMultiSearchSql($fld, $fldOpr, trim($fldVal), $this->Dbid);
            } elseif ($fldOpr == "BETWEEN" && $fldVal != "" && $fldVal != INIT_VALUE && $fldVal2 != "" && $fldVal2 != INIT_VALUE) {
                $wrk = $fldExpression ." " . $fldOpr . " " . QuotedValue($fldVal, $fldDataType, $this->Dbid) . " AND " . QuotedValue($fldVal2, $fldDataType, $this->Dbid);
            } else {
                if ($fldVal != "" && $fldVal != INIT_VALUE) {
                    if ($fldDataType == DATATYPE_DATE && $fld->GroupSql == "" && $fldOpr != "") {
                        $wrk = GetDateFilterSql($fldExpression, $fldOpr, $fldVal, $fldDataType, $this->Dbid);
                    } else {
                        $wrk = GetFilterSql($fldOpr, $fldVal, $fldDataType, $this->Dbid);
                        if ($wrk != "") {
                            $wrk = $fldExpression . $wrk;
                        }
                    }
                }
            }
        }
        <# if (ServerScriptExist("Table", "Page_Filtering")) { #>
        // Call Page Filtering event
        if (!StartsString("@@", $fldVal)) {
            $this->pageFiltering($fld, $wrk, "dropdown", $fldOpr, $fldVal);
        }
        <# } #>
        return $wrk;
    }

    // Get custom filter
    protected function getCustomFilter(&$fld, $fldVal, $dbid = 0)
    {
        $wrk = "";
        if (is_array($fld->AdvancedFilters)) {
            foreach ($fld->AdvancedFilters as $filter) {
                if ($filter->ID == $fldVal && $filter->Enabled) {
                    $fldExpr = $fld->Expression;
                    $fn = $filter->FunctionName;
                    $wrkid = StartsString("@@", $filter->ID) ? substr($filter->ID, 2) : $filter->ID;
                    $fn = $fn != "" && !function_exists($fn) ? PROJECT_NAMESPACE . $fn : $fn;
                    if (function_exists($fn)) {
                        $wrk = $fn($fldExpr, $dbid);
                    } else {
                        $wrk = "";
                    }
                    <# if (ServerScriptExist("Table", "Page_Filtering")) { #>
                    $this->pageFiltering($fld, $wrk, "custom", $wrkid);
                    <# } #>
                    break;
                }
            }
        }
        return $wrk;
    }

    // Build extended filter
    protected function buildExtendedFilter(&$fld, &$filterClause, $default = false, $saveFilter = false)
    {
        $wrk = GetExtendedFilter($fld, $default, $this->Dbid);
        <# if (ServerScriptExist("Table", "Page_Filtering")) { #>
        if (!$default) {
            $this->pageFiltering($fld, $wrk, "extended", $fld->AdvancedSearch->SearchOperator, $fld->AdvancedSearch->SearchValue, $fld->AdvancedSearch->SearchCondition, $fld->AdvancedSearch->SearchOperator2, $fld->AdvancedSearch->SearchValue2);
        }
        <# } #>
        if ($wrk != "") {
            AddFilter($filterClause, $wrk);
            if ($saveFilter) {
                $fld->CurrentFilter = $wrk;
            }
        }
    }

    // Get drop down value from querystring
    protected function getDropDownValue(&$fld)
    {
        $ret = false;
        $parm = $fld->Param;
        if (IsPost()) {
            return false; // Skip post back
        }
        $opr = Get("z_$parm");
        if ($opr !== null) {
            $fld->AdvancedSearch->SearchOperator = $opr;
        }
        $val = Get("x_$parm");
        if ($val !== null) {
            if (is_array($val)) {
                $val = implode(Config("MULTIPLE_OPTION_SEPARATOR"), $val);
            }
            $fld->AdvancedSearch->setSearchValue($val);
            $ret = true;
        }
        $val = Get("y_$parm");
        if ($val !== null) {
            if (is_array($val)) {
                $val = implode(Config("MULTIPLE_OPTION_SEPARATOR"), $val);
            }
            $fld->AdvancedSearch->setSearchValue2($val);
            $ret = true;
        }
        return $ret;
    }

    // Dropdown filter exist
    protected function dropDownFilterExist(&$fld, $fldOpr)
    {
        $wrk = "";
        $this->buildDropDownFilter($fld, $wrk, $fldOpr);
        return ($wrk != "");
    }

    // Extended filter exist
    protected function extendedFilterExist(&$fld)
    {
        $extWrk = "";
        $this->buildExtendedFilter($fld, $extWrk);
        return ($extWrk != "");
    }

    // Validate form
    protected function validateForm()
    {
        global $Language;

        // Check if validation required
        if (!Config("SERVER_VALIDATE")) {
            return true;
        }

        <#
            for (let f of currentFields) {
                if (IsExtendedFilter(f) && IsTextFilter(f)) {
        #>
                <#= ServerValidator() #>
        <#
                }
            } // Field
        #>

        // Return validate result
        $validateForm = !$this->hasInvalidFields();

        <# if (ServerScriptExist("Table", "Form_CustomValidate")) { #>
        // Call Form_CustomValidate event
        $formCustomError = "";
        $validateForm = $validateForm && $this->formCustomValidate($formCustomError);
        if ($formCustomError != "") {
            $this->setFailureMessage($formCustomError);
        }
        <# } #>

        return $validateForm;
    }

<# } #>

<# if (useExtendedBasicSearch) { #>

    // Load default value for filters
    protected function loadDefaultFilters()
    {

        // Set up default values for extended filters
        <#
        for (let f of currentFields) {
            if (IsExtendedFilter(f)) {
                let fldName = f.FldName,
                    fldParm = f.FldParm,
                    fldObj = "this->" + fldParm;
        #>
        // Field <#= fldName #>
        $<#= fldObj #>->AdvancedSearch->loadDefault();
        <#
            }
        }
        #>
    }

<# } #>

<# if (useExtendedBasicSearch || showYearSelection) { #>

    // Show list of filters
    public function showFilterList()
    {
        global $Language;

        // Initialize
        $filterList = "";
        $captionClass = $this->isExport("email") ? "ew-filter-caption-email" : "ew-filter-caption";
        $captionSuffix = $this->isExport("email") ? ": " : "";

        <# if (showYearSelection) { // Column Year filter #>
        // Year Filter
        if (strval($this-><#= columnDateFieldParm #>->CurrentValue) != "") {
            $filterList .= "<div><span class=\"" . $captionClass . "\">" . $Language->phrase("Year") . "</span>" . $captionSuffix;
            $filterList .= "<span class=\"ew-filter-value\">" . $this-><#= columnDateFieldParm #>->CurrentValue . "</span></div>";
        }
        <# } #>

    <#
    for (let f of currentFields) {
        let fldName = f.FldName,
            fldParm = f.FldParm,
            fldObj = "this->" + fldParm;
        if (IsExtendedFilter(f)) {
    #>
        // Field <#= fldName #>
        $extWrk = "";
    <#
        if (!IsTextFilter(f)) {
            let dropDownType;
            if (IsDateFilter(f)) {
                dropDownType = Code.getName(fldObj, "DateFilter");
            } else {
                dropDownType = Code.getName(fldObj, "AdvancedSearch", "SearchOperator");
            }
    #>
        $this->buildDropDownFilter($<#= fldObj #>, $extWrk, <#= dropDownType #>);
    <#
        } else {
    #>
        $this->buildExtendedFilter($<#= fldObj #>, $extWrk);
    <#
        }
    #>
        $filter = "";
        if ($extWrk != "") {
            $filter .= "<span class=\"ew-filter-value\">$extWrk</span>";
        }
        if ($filter != "") {
            $filterList .= "<div><span class=\"" . $captionClass . "\">" . $<#= fldObj #>->caption() . "</span>" . $captionSuffix . $filter . "</div>";
        }
    <#
        }
    }
    #>

        // Show Filters
        if ($filterList != "") {
            $message = "<div id=\"ew-filter-list\" class=\"alert alert-info d-table\"><div id=\"ew-current-filters\">" .
                $Language->phrase("CurrentFilters") . "</div>" . $filterList . "</div>";
            <# if (UseCustomTemplate) { #>
            $message = "<template id=\"tp_current_filters\">" . $message . "</template>";
            <# } #>
            <# if (ServerScriptExist("Table", "Message_Showing")) { #>
            $this->messageShowing($message, "");
            <# } #>
            Write($message);
            <# if (UseCustomTemplate) { #>
        } else {
            Write("<template id=\"tp_current_filters\"></template>"); // Output dummy tag
            <# } #>
        }

    }

    // Get list of filters
    public function getFilterList()
    {
        global $UserProfile;

        // Initialize
        $filterList = "";
        $savedFilterList = "";

        <# if (hasUserTable && !IsEmpty(DB.SecUserProfileFld)) { #>
        // Load server side filters
        if (Config("SEARCH_FILTER_OPTION") == "Server" && isset($UserProfile)) {
            $savedFilterList = $UserProfile->getSearchFilters(CurrentUserName(), "<#= formNameSearch #>");
        }
        <# } #>


        <# if (showYearSelection) { // Column Year filter #>
        // Year Filter
        if (strval($this-><#= columnDateFieldParm #>->CurrentValue) != "") {
            if ($filterList != "") {
                $filterList .= ",";
            }
            $filterList .= "\"<#= columnDateFieldParm #>\":\"" . JsEncode($this-><#= columnDateFieldParm #>->CurrentValue) . "\"";
        }
        <# } #>

    <#
    for (let f of currentFields) {
        let fldName = f.FldName,
            fldParm = f.FldParm,
            fldObj = "this->" + fldParm;
        if (IsExtendedFilter(f)) {
    #>
        // Field <#= fldName #>
        $wrk = "";
    <#
            if (IsExtendedFilter(f) && !IsTextFilter(f)) {
                let dropDownType;
                if (IsDateFilter(f)) {
                    dropDownType = Code.getName(fldObj, "DateFilter");
                } else {
                    dropDownType = '""';
                }
    #>
        $wrk = ($this-><#= fldParm #>->AdvancedSearch->SearchValue != INIT_VALUE) ? $this-><#= fldParm #>->AdvancedSearch->SearchValue : "";
        if (is_array($wrk)) {
            $wrk = implode("||", $wrk);
        }
        if ($wrk != "") {
            $wrk = "\"x_<#= fldParm #>\":\"" . JsEncode($wrk) . "\"";
        }
    <#
        } else if (IsExtendedFilter(f) && IsTextFilter(f)) {
    #>
        if ($this-><#= fldParm #>->AdvancedSearch->SearchValue != "" || $this-><#= fldParm #>->AdvancedSearch->SearchValue2 != "") {
            $wrk = "\"x_<#= fldParm #>\":\"" . JsEncode($this-><#= fldParm #>->AdvancedSearch->SearchValue) . "\"," .
                "\"z_<#= fldParm #>\":\"" . JsEncode($this-><#= fldParm #>->AdvancedSearch->SearchOperator) . "\"," .
                "\"v_<#= fldParm #>\":\"" . JsEncode($this-><#= fldParm #>->AdvancedSearch->SearchCondition) . "\"," .
                "\"y_<#= fldParm #>\":\"" . JsEncode($this-><#= fldParm #>->AdvancedSearch->SearchValue2) . "\"," .
                "\"w_<#= fldParm #>\":\"" . JsEncode($this-><#= fldParm #>->AdvancedSearch->SearchOperator2) . "\"";
        }
        <#
    }
    #>
        if ($wrk != "") {
            if ($filterList != "") {
                $filterList .= ",";
            }
            $filterList .= $wrk;
        }
    <#
            }
        }
    #>

        // Return filter list in json
        if ($filterList != "") {
            $filterList = "\"data\":{" . $filterList . "}";
        }
        if ($savedFilterList != "") {
            $filterList = Concat($filterList, "\"filters\":" . $savedFilterList, ",");
        }
        return ($filterList != "") ? "{" . $filterList . "}" : "null";

    }

    <# if ((useExtendedBasicSearch || showYearSelection) && hasUserTable && !IsEmpty(DB.SecUserProfileFld)) { #>

    // Process filter list
    protected function processFilterList()
    {
        global $UserProfile;
        if (Post("ajax") == "savefilters") { // Save filter request (Ajax)
            $filters = Post("filters");
            $UserProfile->setSearchFilters(CurrentUserName(), "<#= formNameSearch #>", $filters);
            WriteJson([["success" => true]]); // Success
            return true;
        } elseif (Post("cmd") == "resetfilter") {
            $this->restoreFilterList();
        }
        return false;
    }

    <# } #>

    // Restore list of filters
    protected function restoreFilterList()
    {

        // Return if not reset filter
        if (Post("cmd", "") != "resetfilter") {
            return false;
        }

        $filter = json_decode(Post("filter", ""), true);

        return $this->setupFilterList($filter);
    }

    // Setup list of filters
    protected function setupFilterList($filter)
    {

        if (!is_array($filter)) {
            return false;
        }

    <#
        for (let f of currentFields) {
            if (IsExtendedFilter(f)) {
                let fldName = f.FldName,
                    fldParm = f.FldParm,
                    fldObj = "this->" + fldParm;
    #>
        // Field <#= fldName #>
        if (!$this-><#= fldParm #>->AdvancedSearch->getFromArray($filter)) {
            $this-><#= fldParm #>->AdvancedSearch->loadDefault(); // Clear filter
        }
        $this-><#= fldParm #>->AdvancedSearch->save();
    <#
            }
    }
        #>

        return true;
    }
    <#
        }
    #>

<# if (parmFields.length > 0) { #>

    // Return drill down filter
    protected function getDrillDownFilter()
    {
        global $Language, $SkipHeaderFooter;

        $filterList = "";
        $filter = "";

        $opt = Param("d", "");
        if ($opt == "1" || $opt == "2") {
            $mastertable = Param("s", ""); // Get source table
        <#
            for (let f of parmFields) {
                let fldParm = f.FldParm,
                    fldExpr = FieldSqlName(f, tblDbId);
        #>
                $sql = Param("<#= fldParm #>", "");
                $sql = Decrypt($sql);
                $sql = str_replace("@<#= fldParm #>", "<#= Quote(fldExpr) #>", $sql);
                if ($sql != "") {
                    if ($filter != "") {
                        $filter .= " AND ";
                    }
                    $filter .= $sql;
                    if ($sql != "1=1") {
                        $filterList .= "<div><span class=\"ew-filter-caption\">" . $this-><#= fldParm #>->caption() . "</span><span class=\"ew-filter-value\">$sql</span></div>";
                    }
                }
        <#
            } // Parm field
        #>
        }

        if ($opt == "1" || $opt == "2") {
            $this->DrillDown = true;
        }

        if ($opt == "1") {
            $this->DrillDownInPanel = true;
            $SkipHeaderFooter = true;
        }

        if ($filter != "") {
            if ($filterList == "") {
                $filterList = "<div><span class=\"ew-filter-value\">" . $Language->phrase("DrillDownAllRecords") . "</span></div>";
            }
            $this->DrillDownList = "<div id=\"ew-drilldown-filters\">" . $Language->phrase("DrillDownFilters") . "</div>" . $filterList;
        }

        return $filter;
    }

    // Show drill down filters
    public function showDrillDownList()
    {
        <# if (UseCustomTemplate) { #>
        $divclass = " class=\"d-none;\"";
        <# } else { #>
        $divclass = "";
        <# } #>
        if ($this->DrillDownList != "") {
            $message = "<div id=\"ew-drilldown-list\"" . $divclass . "><div class=\"alert alert-info\">" . $this->DrillDownList . "</div></div>";
            <# if (UseCustomTemplate) { #>
            $message .= "<template id=\"tp_current_filters\">" . $message . "</template>";
            <# } #>
            <# if (ServerScriptExist("Table", "Message_Showing")) { #>
            $this->messageShowing($message, "");
            <# } #>
            Write($message);
            <# if (UseCustomTemplate) { #>
        } else {
            Write("<template id=\"tp_current_filters\"></template>"); // Output dummy tag
            <# } #>
        }
    }

<# } #>

<# if (hasDrillDownFields) { #>

    /**
     * Get drill down SQL
     *
     * @param ReportField $fld Source field object
     * @param string $target Target field name
     * @param int $rowtype Row type
     * 0 = detail
     * 1 = group
     * 2 = page
     * 3 = grand
     * @param int $parm Filter/Column index
     * -1 = use field filter value / current/old value
     * 0 = use grouping/column field value
     * > 0 = use column index
     * @return string Drill down SQL
     */
    public function getDrillDownSql($fld, $target, $rowtype, $parm = 0)
    {
        $sql = "";
        <# if (groupFields.length > 0) { #>
        // Handle grand/page total
        if ($fld->Param == "<#= firstGroupField.FldParm #>") { // First grouping field
            if ($rowtype == ROWTOTAL_GRAND) { // Grand total
                $sql = $fld->CurrentFilter;
                if ($sql == "") {
                    $sql = "1=1"; // Show all records
                }
            } elseif ($rowtype == ROWTOTAL_PAGE && $this->PageFirstGroupFilter != "") { // Page total
                $sql = str_replace($fld->Expression, "@" . $target, "(" . $this->PageFirstGroupFilter . ")");
            }
        }
    <# } #>
        // Handle group/row/column field
        if ($parm >= 0 && $sql == "") {
            switch ($fld->Param) {
    <#
        for (let f of allFields) {
            if (IsDrillDownSource(TABLE, f)) {
                let fldName = f.FldName,
                fldParm = f.FldParm;
                if (columnField && fldName == columnField.FldName) {
                    if (columnDateFieldName != "" && ["q", "m"].includes(columnDateType)) {
                        var sqltype = (columnDateType == "q") ? "xq" : "xm";
                        if (columnDateSelect) { // Year selection (quarter/month)
    #>
                case "<#= columnFieldParm #>":
                    if (!EmptyValue($fld->AdvancedSearch->SearchValue) && !SameString($fld->AdvancedSearch->SearchValue, INIT_VALUE)) {
                        $sql = "<#= DbGroupSql("y", 0, tblDbId) #>";
                        $sql = str_replace("%s", "@" . $target, $sql) . " = " . QuotedValue($this-><#= columnDateFieldParm #>->AdvancedSearch->SearchValue, DATATYPE_NUMBER, $this->Dbid);
                    }
                    break;
    <#
                        } else { // Without year selection (quarter/month)
    #>
                case "<#= columnFieldParm #>":
                case "<#= columnDateFieldParm #>":
                    if (!EmptyValue($fld->AdvancedSearch->SearchValue) && !SameString($fld->AdvancedSearch->SearchValue, INIT_VALUE)) {
                        $sql = "<#= DbGroupSql("y", 0, tblDbId) #>";
                        $sql = str_replace("%s", "@" . $target, $sql) . " = " . QuotedValue($fld->AdvancedSearch->SearchValue, DATATYPE_NUMBER, $this->Dbid);
                    }
                    break;
    <#
                        }
                    } else if (columnDateType == "y") { // Year
    #>
                case "<#= fldParm #>":
                    if (!EmptyValue($fld->AdvancedSearch->SearchValue) && !SameString($fld->AdvancedSearch->SearchValue, INIT_VALUE)) {
                        $sql = "<#= DbGroupSql("y", 0, tblDbId) #>";
                        $sql = str_replace("%s", "@" . $target, $sql) . " = " . QuotedValue($fld->AdvancedSearch->SearchValue, DATATYPE_NUMBER, $this->Dbid);
                    }
                    break;
                        <#
                    } else { // Non date column field
    #>
                case "<#= fldParm #>":
                    if (!EmptyValue($fld->AdvancedSearch->SearchValue) && !SameString($fld->AdvancedSearch->SearchValue, INIT_VALUE)) {
                        $sql = "@" . $target . " = " . QuotedValue($fld->AdvancedSearch->SearchValue, <#= GetFieldTypeName(columnField.FldType) #>, $this->Dbid);
                    }
                    break;
    <#
                    } // End column field
                } else {
                    let isGroupField = groupFields.some(gf => gf.FldName == fldName);
                    if (isGroupField) { // Grouping field
    #>
                case "<#= fldParm #>":
                    if ($fld->GroupSql != "") {
                        $sql = str_replace("%s", "@" . $target, $fld->GroupSql) . " = " . QuotedValue($fld->CurrentValue, DATATYPE_STRING, $this->Dbid);
                        AddFilter($sql, str_replace($fld->Expression, "@" . $target, $fld->CurrentFilter));
                    } else {
                        $sql = "@" . $target . " = " . QuotedValue($fld->CurrentValue, $fld->DataType, $this->Dbid);
                    }
                    break;
    <#
                        }
                    }
                }
            }
    #>
            }
        }
        // Detail field
        if ($sql == "" && $rowtype == 0) {
            if ($fld->CurrentFilter != "") { // Use current filter
                $sql = str_replace($fld->Expression, "@" . $target, $fld->CurrentFilter);
            } elseif ($fld->CurrentValue != "") { // Use current value for detail row
                $sql = "@" . $target . "=" . QuotedValue($fld->CurrentValue, $fld->DataType, $this->Dbid);
            }
        }
        return $sql;
    }

<# } #>
