window.addEventListener('load', () => {

    // Set select datepicker on display none on change
    let selectPeriod = document.getElementById('period_reporting_period');
    let divAnd = document.getElementById('between-date');
    let inputBegin = document.getElementById('period_reporting_begin');
    let inputEnd = document.getElementById('period_reporting_end');

    let setDisplayValue = (elements, value) => {
        elements.forEach(element => {
            element.style.display = value
        });
    };

    if (selectPeriod !== null) {

        let arrayElementsForDisplay = [inputBegin, inputEnd, divAnd];

        let conditionsHideDateInput = (valueSelect) => {

            if (valueSelect === 'custom_range') {
                setDisplayValue(arrayElementsForDisplay, "block");
                inputBegin.required = true;
            } else {
                setDisplayValue(arrayElementsForDisplay, "none");
            }
        };

        conditionsHideDateInput(selectPeriod.value);

        selectPeriod.addEventListener('change', (e) => {
            inputBegin.required = false;
            inputEnd.required = false;
            conditionsHideDateInput(e.target.value);

        });
    }
    // --- End datepicker display //

    // set Checked By Default For 'closed_on' choice
    let inputClosedOn = document.getElementById('period_reporting_choice_0');
    let inputCreatedOn = document.getElementById('period_reporting_choice_1');
    if (inputClosedOn !== null) {
        if (inputClosedOn.checked === false && inputCreatedOn.checked === false) {
            inputClosedOn.checked = true;
        }
    }
    // --- End Checked By Default --- //

    // Scroll reporting-infos element to View
    if (document.getElementById('reporting-infos') !== null) {
        document.getElementById('reporting-infos').scrollIntoView({behavior: 'smooth', block: 'center'});
    }
    // --- End scroll ---//

    // Move div-link-to-searchList element 'afterend' searchSaved element
    let eltLinkToSearchList = document.getElementById('div-link-to-searchList');
    let eltSearchSaved = document.getElementById('searchSaved');
    let eltPeriodsSavedLink = document.getElementById('periods-saved-link');

    if (eltSearchSaved !== null && eltLinkToSearchList !== null) {
        eltPeriodsSavedLink.innerText = 'Tous les périodes de recherche sauvegardées';
        eltSearchSaved.insertAdjacentElement('afterend', eltLinkToSearchList);
    }
    // --- End move --- //

    // Set reporting-action link display none
    if (document.getElementById('actions') !== null) {
        document.getElementById('reporting-action').style.display = "none";
    }
    // --- End link display --- //

    // Place a loader when page in loading
    let formElt = document.querySelector('form');

    if (formElt !== null) {

        let eltLoader = document.getElementById('loader');
        let eltLoading = document.getElementById('loading');

        formElt.addEventListener('submit', (event) => {
            if (event.submitter.id !== "period_reporting_export") {
                eltLoader.style.display = "block";
            } else {
                eltLoading.style.display = "block";
            }
        });

        let buttons = [
            document.getElementById('period_reporting_export'),
            document.getElementById('period_reporting_report'),
            document.getElementById('period_reporting_saveSearch')
        ];

        formElt.addEventListener('mouseover', () => {
            if (eltLoader.style.display === "block" || eltLoading.style.display === "block") {
                buttons.forEach(element => {
                    element.disabled = true;
                });
                eltPeriodsSavedLink.style.color = "grey";
                eltPeriodsSavedLink.removeAttribute('href');
            }
        });

        let buttonLoading = document.getElementById('loading-button');
        if (buttonLoading !== null) {
            buttonLoading.addEventListener('click', () => {
                window.location.reload();
            })
        }
    }
    // --- End loader --- //

    // Show chart with or without priority and show or not each platform
    let inputPriorityOrder = document.getElementById('priority-order');

    if (inputPriorityOrder !== null) {

        let eltsWithPrio = document.querySelectorAll('.withPrio');
        let eltsWithoutPrio = document.querySelectorAll('.withoutPrio');
        let eltsClassChartsPlateforme = document.querySelectorAll('.charts-plateforme');
        let eltsChartsArrowsMiddle = document.querySelectorAll('.charts-arrows-middle');
        let eltsChartsArrowsTop = document.querySelectorAll('.charts-arrows-top');
        let divFirstChart = document.getElementById('first-chart');
        let divReportingPriority = document.getElementById('reporting-priority-order');
        let divCheckbox = document.getElementById('reporting-checkbox');
        let divArrowsMiddle = document.getElementById('div-arrows-middle');
        let divChartsArrowTop = document.getElementById('div-charts-arrows-top')
        let buttonChartUp = document.getElementById('bt-chart-arrow-up');
        let buttonChartDown = document.getElementById('bt-chart-arrow-down');
        let linkChartArrowUp = document.getElementById('link-chart-arrow-up');
        let linkchartArrowDown = document.getElementById('link-chart-arrow-down');
        let linkTopArrowDown = document.getElementById('link-top-arrow-down');
        let linkTopArrowUp = document.getElementById('link-top-arrow-up');

        let arrayPlateforme = ['console', 'infra', 'lmt', 'mediabuying', 'nativious',
            'performance', 'publishing', 'rgpd', 'wavecy', 'wordpress'];
        let eltsPlateforme = [];
        let allPlatformChartsInput = [];
        for (let elt of arrayPlateforme) {
            eltsPlateforme.push(document.getElementById('reporting-charts-' + elt));
            allPlatformChartsInput.push(document.getElementById('input-charts-' + elt));
        }


        let setOpacityValue = (elements, value) => {
            elements.forEach(element => {
                element.style.visibility = value
            });
        };

        setTimeout(() => {
            setOpacityValue(eltsWithoutPrio, "visible");
            setOpacityValue(eltsClassChartsPlateforme, "visible");
            setDisplayValue(eltsWithoutPrio, "none");
            setDisplayValue(eltsPlateforme, "none");

        }, 10);

        let checkInputUnchecked = (element) => {
            return !element.checked;
        };

        // Set charts of each platform on display none or block on change
        for (let elt of arrayPlateforme) {
            let eltPf = 'elt' + elt;
            let eltInput = 'input' + elt;
            eltInput = document.getElementById('input-charts-' + elt);
            eltPf = document.getElementById('reporting-charts-' + elt);

            eltInput.addEventListener('change', (event) => {

                buttonChartUp.style.display = "none";
                setDisplayValue(eltsChartsArrowsMiddle, "block");

                if (event.target.checked) {
                    eltPf.style.display = "block";
                    eltPf.insertAdjacentElement('beforebegin', divCheckbox);
                    eltPf.insertAdjacentElement('beforebegin', divReportingPriority);
                    eltPf.insertAdjacentElement('beforebegin', divArrowsMiddle);
                    divCheckbox.scrollIntoView();
                } else {
                    eltPf.insertAdjacentElement('beforebegin', divCheckbox);
                    eltPf.insertAdjacentElement('beforebegin', divReportingPriority);
                    eltPf.insertAdjacentElement('beforebegin', divArrowsMiddle);
                    divCheckbox.scrollIntoView();
                    eltPf.style.display = "none";
                }

                if (allPlatformChartsInput.every(checkInputUnchecked)) {
                    divFirstChart.insertAdjacentElement('beforebegin', divReportingPriority);
                    setDisplayValue(eltsChartsArrowsMiddle, "none");
                    setDisplayValue(eltsChartsArrowsTop, "none");
                    buttonChartUp.style.display = "block";
                }
            });
        }
        // --- End charts with or without Priority --- //

        // Set charts with or without Priority on display none or block on change
        inputPriorityOrder.addEventListener('change', (event) => {
            if (event.target.checked) {
                setDisplayValue(eltsWithPrio, "block");
                setDisplayValue(eltsWithoutPrio, "none");
            } else {
                setDisplayValue(eltsWithPrio, "none");
                setDisplayValue(eltsWithoutPrio, "block");
            }
        });
        // --- End charts with or without Priority --- //

        // Set buttons and links on display none or block on click
        buttonChartDown.addEventListener('click', () => {
            window.scrollTo(0, document.body.scrollHeight);
            buttonChartUp.style.display = "block";
        });
        buttonChartUp.addEventListener('click', () => {
            document.body.scrollTop = 0;
            document.documentElement.scrollTop = 0;
            setDisplayValue(eltsChartsArrowsTop, "block");
            setDisplayValue(eltsChartsArrowsMiddle, "none");
            divFirstChart.insertAdjacentElement('beforebegin', divReportingPriority);
            divChartsArrowTop.style.display = "none";
        });

        linkChartArrowUp.addEventListener('click', () => {
            divFirstChart.insertAdjacentElement('beforebegin', divReportingPriority);
            setDisplayValue(eltsChartsArrowsTop, "block");
            setDisplayValue(eltsChartsArrowsMiddle, "none");
        });

        linkchartArrowDown.addEventListener('click', () => {
            buttonChartUp.style.display = "block";
            setDisplayValue(eltsChartsArrowsTop, "none");
            document.getElementById('div-charts-arrows-middle').style.display = "none";
        });

        linkTopArrowDown.addEventListener('click', () => {
            divCheckbox.insertAdjacentElement('afterend', divReportingPriority);
            setDisplayValue(eltsChartsArrowsTop, "none");

            if (allPlatformChartsInput.every(checkInputUnchecked)) {
                setDisplayValue(eltsChartsArrowsMiddle, "none");
            } else {
                setDisplayValue(eltsChartsArrowsMiddle, "block");
            }
        });

        linkTopArrowUp.addEventListener('click', () => {
            divChartsArrowTop.style.display = "none";
        });
        // --- End buttons and links --- //
    }
    // --- End chart --- //
});