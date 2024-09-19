//Todo: I think there is a need of customizable place for fees
var calcGoldPrices = metal_price_calc.goldPrices;
var calcUnitRates = metal_price_calc.unit_rates;
var calcFees = metal_price_calc.fees;
var calcConversionRates = metal_price_calc.conversionRates;


function calcForm() {
    var amount = 0;
    var weight = parseFloat(document.getElementById('txtWeight').value);

    // if no weight is entered, set amount to 0
    if (isNaN(weight)) {
        document.getElementById('txtAmount').value = 0;
        return;
    }

    var symbol = document.getElementById('lstSymbol').value;
    var request = calcGoldPrices[symbol];
    amount = parseFloat(request.ask);
    // convert the amount to float
    // amount = parseFloat(amount);

    var customFees = parseFloat(calcFees.custom_fees);
    amount = amount + customFees;

    var unit = document.getElementById('lstWeightUnit').value;
    amount = amount * calcUnitRates[unit] * weight;
  
    var currency = document.getElementById('lstCurrency').value;
    amount = amount * calcConversionRates[currency];


    document.getElementById('txtAmount').value = amount.toFixed(2);
    // add currency after the amount
    document.getElementById('txtAmount').value += ' ' + currency;
}
