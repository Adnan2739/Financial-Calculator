(function(){
    const cfg = window.FinCalcConfig || {};
    const i18n = cfg.i18n || {};
    const root = document.getElementById("fin-calc-root");
    if (!root) return;

    // Apply RTL if needed
    if (cfg.isRTL) {
        root.setAttribute('dir', 'rtl');
    }

    // Parse comma-separated dropdown values from config
    function parseDropdownOptions(str) {
        if (!str) return [];
        return str.split(',').map(item => item.trim()).filter(item => item);
    }

    // Build dropdown HTML
    function buildDropdownOptions(options) {
        return options.map(opt => {
            const val = opt.trim();
            return `<option value="${val}">${val}</option>`;
        }).join('');
    }

    // Helper function to get translated text
    function __(key, fallback) {
        return i18n[key] || fallback || key;
    }

    // Initialize calculator
    function initCalculator() {
        const assetTypes = parseDropdownOptions(cfg.assetTypes || '');
        const assetModels = parseDropdownOptions(cfg.assetModels || '');
        const downPayments = parseDropdownOptions(cfg.downPaymentOptions || '');
        const tenorYears = parseDropdownOptions(cfg.tenorYearsOptions || '');
        
        root.innerHTML = `
<div class="fcalc-wrap">
  <form id="calculator-form" class="fcalc-form">
   <div class="form-col1">
    <div class="frow">
    <label>${__('typeOfAsset', 'Type of Asset')}</label>
      <select id="asset-type">
        ${buildDropdownOptions(assetTypes)}
      </select>
    </div>
    <div class="frow"><label>${__('assetModel', 'Asset Model')}</label>
      <select id="asset-model">
        ${buildDropdownOptions(assetModels)}
      </select>
    </div>
    </div>
     <div class="form-col2">
    <div class="frow"><label>${__('valueOfAsset', 'Value of Asset (Rs)')}</label><input id="asset-value" type="number" min="0" step="0.01"/></div>
    <div class="frow"><label>${__('downPayment', 'Down Payment (%)')}</label>
      <select id="down-payment-percent">
        ${buildDropdownOptions(downPayments)}
      </select>
    </div>
    </div>
     <div class="form-col3">
    <div class="frow"><label>${__('tenor', 'Tenor (Years)')}</label>
      <select id="tenor-years">
        ${buildDropdownOptions(tenorYears)}
      </select>
    </div>
    </div>
     <div class="form-col4">
    <div class="frow"><label>${__('netLease', 'Net Lease / Finance Amount (Rs)')}</label><input id="net-lease" readonly/></div>
    <div class="frow"><label>${__('installmentAmount', 'Installment Amount (Monthly) (Rs)')}</label><input id="installment-amount" readonly/></div>
    </div>
    <div class="frow button-div">
  <span class="checkbox">${document.documentElement.lang === 'ur' || document.documentElement.lang === 'ur-PK' 
      ? 'شرائط و ضوابط لاگو ہیں' 
      : 'Terms and Conditions Applied'}</span>
  <button type="button" id="calculate-btn" class="btn">${__('calculate', 'Calculate')}</button>
</div>
    </form>

  <div class="results-card">
  <div class="firt-col">
<div class="text-col">
    <h4>${__('result', 'Result')}</h4>
    <p>${__('resultDesc', 'Your calculation results are displayed below')}</p>
    </div>
    <div class="interest-col">
    <span>${__('interest', 'Interest')}</span><strong id="result-interest">--%</strong>
    </div>
    </div>
    <div class="results-grid">
    <div class="calculation-clumn">
      <div class="res-box"><span>${__('netLease', 'Net Lease / Finance Amount (Rs)')}</span><strong id="result-netlease">--</strong></div>
        <div class="res-box"><span>${__('downPaymentRs', 'Down Payment (Rs)')}</span><strong id="result-downpayment">--</strong></div>
      <div class="result-two column">
      <div class="res-box"><span>${__('tenorLabel', 'Tenor')}</span><strong id="result-tenor">--</strong></div>
      <div class="res-box"><span>${__('monthlyInstallment', 'Monthly Installment')}</span><strong id="result-installment">${__('rs', 'Rs')} --</strong></div>
      </div>
      </div>
      <div class="graph-column">
      <div class="circular-graph">
    <div class="circle">
      <svg viewBox="0 0 36 36" class="circular-chart">
        <path class="circle-bg"
              d="M18 2.0845
                 a 15.9155 15.9155 0 0 1 0 31.831
                 a 15.9155 15.9155 0 0 1 0 -31.831"/>
        <path class="circle-progress"
              stroke-dasharray="0, 100"
              d="M18 2.0845
                 a 15.9155 15.9155 0 0 1 0 31.831
                 a 15.9155 15.9155 0 0 1 0 -31.831"/>
        
      </svg>
      <text x="18" y="20.35" class="percentage">0%</text>
    </div>
    <div class="total-loan-amount"><span class="color-box paid"></span>${__('amountPaid', 'Amount Paid')}</div>
      <div class="amount-paid"><span class="color-box remaining"></span>${__('totalLoanAmount', 'Total Loan Amount')}</div>
  
  </div>
      </div>

  </div>
  <div class="btn-container">
      <button id="email-result-btn" class="btn">${__('emailResult', 'Email this Result')}</button>
      </div>

  <!-- Email Modal -->
  <div id="email-modal" class="fmodal hidden">
    <div class="fmodal-content">
      <button id="close-modal-btn" class="modal-close">&times;</button>
      <h3>${__('emailYourResults', 'Email Your Results')}</h3>
      <form id="email-form">
        <label>${__('yourName', 'Your Name')}</label> <input type="text" id="email-name" placeholder="${__('enterName', 'Enter your name')}" required />
        <label>${__('yourEmail', 'Your Email')}</label> <input type="email" id="email-address" placeholder="${__('enterEmail', 'Enter your email')}" required />
        <div class="summary-box">
          <p><strong>${__('tenorLabel', 'Tenor')}:</strong> <span id="summary-loan-term">--</span></p>
          <p><strong>${__('netLease', 'Net Lease / Finance Amount (Rs)')}:</strong> <span id="summary-netlease">--</span></p>
          <p><strong>${__('monthlyInstallment', 'Monthly Installment')}:</strong> <span id="summary-total-price">--</span></p>
        </div>
        <button type="submit" class="btn">${__('sendEmail', 'Send Email')}</button>
      </form>
      <div id="status-message" class="status-message hidden"></div>
    </div>
  </div>
</div>`;

        attachEventListeners();
    }

    function attachEventListeners() {
        const $ = (sel) => document.querySelector(sel);
        const getInterest = () => parseFloat(cfg.interestPercent) || 15;
        let lastCalc = null;

        /* -------------------------------------------------------
           VALIDATION FUNCTIONS
        --------------------------------------------------------*/
        function showError(input, message) {
            let error = input.parentElement.querySelector(".error-msg");
            if (!error) {
                error = document.createElement("div");
                error.className = "error-msg";
                error.style.color = "red";
                error.style.fontSize = "12px";
                error.style.marginTop = "3px";
                input.parentElement.appendChild(error);
            }
            error.textContent = message;
        }

        function clearErrors() {
            document.querySelectorAll(".error-msg").forEach(e => e.remove());
        }

        function validateForm() {
            clearErrors();
            let valid = true;

            const value = $('#asset-value');
            

            if (value.value.trim() === "" || parseFloat(value.value) <= 0) {
                showError(value, __('fieldRequired', 'This field is required'));
                valid = false;
            }


            return valid;
        }

        /* -------------------------------------------------------
           CALCULATION FUNCTION
        --------------------------------------------------------*/
    //     function calculate() {
    //         const value = parseFloat($('#asset-value').value) || 0;
    //         const down = parseFloat($('#down-payment-percent').value) || 0;
    //         const tenor = parseInt($('#tenor-years').value) || 1;
    //         const interest = getInterest();

    //         if (value <= 0) return null;

    //         const downAmt = (down / 100) * value;
    //         const netLease = value - downAmt;
    //         const interestAmount = (netLease * (interest / 100) * tenor);
    //         const totalPayable = netLease + interestAmount;
    //         const months = tenor * 12;
    //         const monthly = months > 0 ? totalPayable / months : 0;

    //         const formatNumber = (num) => {
    // return Number(num).toLocaleString('en-PK', {
    //     minimumFractionDigits: 2,
    //     maximumFractionDigits: 2
    // });
                
    //             if (cfg.currentLang === 'ur') {
    //                 // Convert to Urdu numerals
    //                 return convertToUrduNumerals(formatted);
    //             }
    //             return formatted;
    //         };

    //         $('#net-lease').value = formatNumber(netLease);
    //         $('#installment-amount').value = formatNumber(monthly);

    //         const formattedInterest = (interest % 1 === 0) ? interest.toFixed(0) : interest.toFixed(2);

    //         // $('#result-interest').textContent = formatNumber(parseFloat(formattedInterest)) + '%';
    //         $('#result-interest').textContent = formattedInterest + '%';
    //         $('#result-tenor').textContent = tenor + (tenor === 1 ? ' ' + __('year', 'yr') : ' ' + __('years', 'yrs'));
    //         $('#result-netlease').textContent = __('rs', 'Rs') + ' ' + formatNumber(netLease);
    //         $('#result-downpayment').textContent = __('rs', 'Rs') + ' ' + formatNumber(downAmt);
    //         $('#result-installment').textContent = __('rs', 'Rs') + ' ' + formatNumber(monthly);

    //         const percentPaid = down;
    //         const circle = document.querySelector('.circle-progress');
    //         const percentText = document.querySelector('.percentage');

    //         if (circle && percentText) {
    //             const gap = 0;
    //             circle.setAttribute('stroke-dasharray', `${percentPaid}, ${100 - percentPaid - gap}, ${gap}`);
    //             // percentText.textContent = `${formatNumber(percentPaid)}%`;
    //             percentText.textContent = `${percentPaid}%`;

    //         }

    //         lastCalc = {
    //             assetValue: value,
    //             netLease,
    //             monthlyInstallment: parseFloat(monthly.toFixed(2)),
    //             interest,
    //             months,
    //             totalPrice: parseFloat(totalPayable.toFixed(2)),
    //             tenor
    //         };
    //         emailBtn.disabled = false;
    //         emailBtn.classList.remove('disabled');
    //         return lastCalc;
    //     }

  /* -------------------------------------------------------
          NEW CALCULATION FUNCTION
        --------------------------------------------------------*/

        function calculate() {
    const value = parseFloat($('#asset-value').value) || 0;
    const down = parseFloat($('#down-payment-percent').value) || 0;
    const tenor = parseInt($('#tenor-years').value) || 1;
    
    // 1. Get the interest from config (Set this to 16 in your WP/Config settings)
    const annualInterestRate = getInterest(); 

    if (value <= 0) return null;

    const downAmt = (down / 100) * value;
    const netLease = value - downAmt;
    const months = tenor * 12;

    // 2. NEW REDUCING BALANCE MATH (EMI formula)
    // Formula: [P x r x (1+r)^n] / [(1+r)^n - 1]
    const monthlyRate = (annualInterestRate / 100) / 12;
    
    let monthly;
    if (monthlyRate === 0) {
        monthly = netLease / months;
    } else {
        monthly = netLease * monthlyRate * Math.pow(1 + monthlyRate, months) / (Math.pow(1 + monthlyRate, months) - 1);
    }

    const totalPayable = monthly * months;
    const interestAmount = totalPayable - netLease;

    // --- Formatting and UI updates remain the same ---
    const formatNumber = (num) => {
        return Number(num).toLocaleString('en-PK', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    };

    $('#net-lease').value = formatNumber(netLease);
    $('#installment-amount').value = formatNumber(monthly);

    const formattedInterest = (annualInterestRate % 1 === 0) ? annualInterestRate.toFixed(0) : annualInterestRate.toFixed(2);

    $('#result-interest').textContent = formattedInterest + '%';
    $('#result-tenor').textContent = tenor + (tenor === 1 ? ' ' + __('year', 'yr') : ' ' + __('years', 'yrs'));
    $('#result-netlease').textContent = __('rs', 'Rs') + ' ' + formatNumber(netLease);
    $('#result-downpayment').textContent = __('rs', 'Rs') + ' ' + formatNumber(downAmt);
    $('#result-installment').textContent = __('rs', 'Rs') + ' ' + formatNumber(monthly);

    // Update Graph
    const percentPaid = down;
    const circle = document.querySelector('.circle-progress');
    const percentText = document.querySelector('.percentage');
    if (circle && percentText) {
        const gap = 0;
        circle.setAttribute('stroke-dasharray', `${percentPaid}, ${100 - percentPaid - gap}, ${gap}`);
        percentText.textContent = `${percentPaid}%`;
    }

    lastCalc = {
        assetValue: value,
        netLease,
        monthlyInstallment: parseFloat(monthly.toFixed(2)),
        interest: annualInterestRate,
        months,
        totalPrice: parseFloat(totalPayable.toFixed(2)),
        tenor
    };
    emailBtn.disabled = false;
    emailBtn.classList.remove('disabled');
    return lastCalc;
}
        /* -------------------------------------------------------
           URDU NUMERAL CONVERSION
        --------------------------------------------------------*/
        // function convertToUrduNumerals(str) {
        //     const urduNumerals = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        //     return str.replace(/[0-9]/g, (digit) => urduNumerals[parseInt(digit)]);
        // }

        /* -------------------------------------------------------
           CALCULATE BUTTON WITH VALIDATION
        --------------------------------------------------------*/
        $('#calculate-btn').addEventListener('click', (e) => {
            e.preventDefault();

            if (!validateForm()) return;

            calculate();
        });

        /* -------------------------------------------------------
           EMAIL + MODAL SYSTEM
        --------------------------------------------------------*/

        const emailBtn = $('#email-result-btn');
        emailBtn.disabled = true;
        emailBtn.classList.add('disabled');

        const emailModal = $('#email-modal');
        const closeModal = $('#close-modal-btn');
        const emailForm = $('#email-form');
        const statusMessage = $('#status-message');

        // const resultInterest = $('#result-interest');
        const resultTerm = $('#result-tenor');
        const resultNetLease = $('#result-netlease');
        const resultPrice = $('#result-installment');

        emailBtn.addEventListener('click', () => {
            if (!lastCalc || !lastCalc.months) return;
            // $('#summary-interest-rate').textContent = resultInterest.textContent;
            $('#summary-loan-term').textContent = resultTerm.textContent;
            $('#summary-netlease').textContent = resultNetLease.textContent;
            $('#summary-total-price').textContent = resultPrice.textContent;
            emailModal.classList.remove('hidden');
            statusMessage.classList.add('hidden');
        });

        closeModal.addEventListener('click', () => emailModal.classList.add('hidden'));
        emailModal.addEventListener('click', (e) => { if (e.target === emailModal) emailModal.classList.add('hidden'); });

        emailForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const name = $('#email-name').value;
            const email = $('#email-address').value;

            if (!lastCalc || !lastCalc.months) return;

            const rawSummary = `
${__('valueOfAsset', 'Asset Value')}: ${__('rs', 'Rs')} ${lastCalc.assetValue}

${__('tenorLabel', 'Tenor')}: ${lastCalc.tenor} ${__('years', 'years')}
${__('monthlyInstallment', 'Monthly Installment')}: ${__('rs', 'Rs')} ${lastCalc.monthlyInstallment}
${__('totalLoanAmount', 'Total Payable')}: ${__('rs', 'Rs')} ${lastCalc.totalPrice}
            `;

            try {
                const fd = new FormData();
                fd.append('action', cfg.ajaxAction || 'fc_send_result');
                fd.append('nonce', cfg.nonce || '');
                fd.append('name', name);
                fd.append('email', email);
                fd.append('loan_amount', lastCalc.netLease);
                // fd.append('interest_rate', lastCalc.interest);
                fd.append('term_years', lastCalc.tenor);
                fd.append('term_months', lastCalc.months);
                fd.append('monthly_payment', lastCalc.monthlyInstallment);
                fd.append('total_price', lastCalc.totalPrice);
                fd.append('raw_summary', rawSummary);

                const res = await fetch(cfg.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' });
                const j = await res.json();

                if (j.success) {
                    statusMessage.textContent = j.data.message || __('emailSentSuccess', 'Email sent successfully!');
                    statusMessage.classList.remove('hidden', 'error');
                    statusMessage.classList.add('success');
                } else {
                    statusMessage.textContent = j.data?.message || __('serverError', 'Server error');
                    statusMessage.classList.remove('hidden');
                    statusMessage.classList.add('error');
                }
            } catch (err) {
                console.error('AJAX error', err);
                statusMessage.textContent = __('ajaxError', 'AJAX error - see console');
                statusMessage.classList.remove('hidden');
                statusMessage.classList.add('error');
            }

            setTimeout(() => {
                emailForm.reset();
                emailModal.classList.add('hidden');
            }, 1000);
        });
    }

    // Initialize calculator
    initCalculator();
})();