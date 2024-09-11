jQuery(document).ready(function($) {

    function currency_converter(amount , currency) {
        
        // Define the conversion rates
        const conversionRates = {
            'AED': 3.67,
            'SAR': 3.75,
            'USD': 1
        };

        // Convert the amount based on the to_currency three decimal places
        // return amount * conversionRates[currency];
        return (amount * conversionRates[currency]).toFixed(3);
    }

    // Function to fetch some text from the backend and return it

    function updateMetalPrices(callback) {
        // Make an AJAX request

        $.ajax({
            url: ajax_object.ajax_url, // URL to admin-ajax.php
            type: 'POST',
            data: {
                action: 'mpt_metal_price_updater_action', // The custom action name
                security: ajax_object.ajax_nonce, // Security nonce
                some_data: 'example_data' // Any data you want to send
            },
            success: function(response) {
                //  log the json response
                console.log('response:', response);
                // update metal prices
                updateTextContent(response);
            },
            error: function(error) {
                console.log('AJAX error:', error);
                // updateTextContent(error);
            }
        });


    }

    // Function to update the text content every 5 seconds
    function updateTextContent(response) {

        
        var downSrc = `<svg fill="#ff0000"  version="1.1" id="arrow-down-src" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512.002 512.002" >
                <g>
                <g>
                    <path d="M498.837,65.628c-7.957-3.328-17.152-1.472-23.253,4.629L256,289.841L36.416,70.257
                    c-6.101-6.101-15.275-7.936-23.253-4.629C5.184,68.913,0,76.721,0,85.34v106.667c0,5.675,2.24,11.093,6.251,15.083
                    l234.667,234.667c4.16,4.16,9.621,6.251,15.083,6.251c5.462,0,10.923-2.091,15.083-6.251L505.751,207.09
                    c4.011-3.989,6.251-9.408,6.251-15.083V85.34C512,76.721,506.816,68.913,498.837,65.628z"/>
                </g>
                </g>
            </svg>`;
        var upSrc = `<svg fill="#00ff00" version="1.1" id="arrow-up-src" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512.001 512.001" xml:space="preserve">
                        <g>
                            <g>
                                <path d="M505.749,304.918L271.083,70.251c-8.341-8.341-21.824-8.341-30.165,0L6.251,304.918C2.24,308.907,0,314.326,0,320.001
                                    v106.667c0,8.619,5.184,16.427,13.163,19.712c7.979,3.307,17.152,1.472,23.253-4.629L256,222.166L475.584,441.75
                                    c4.075,4.075,9.536,6.251,15.083,6.251c2.752,0,5.525-0.512,8.171-1.621c7.979-3.285,13.163-11.093,13.163-19.712V320.001
                                    C512,314.326,509.76,308.907,505.749,304.918z"/>
                            </g>
                        </g>
                    </svg>`;


        // iterate over all items with class mpt-metal-content
        $('.mpt-metal-content').each(function(index, element) {
            // get the currency of the element
            var currency = $(element).attr('mpt-currency');
            // get the code of the element
            var metal = $(element).attr('mpt-metal');
            // get the request of the element
            var request = $(element).attr('mpt-request');
            

            // if request is bid_time or name, fill the element with the data from the response
            if (request == 'bid_time' || request == 'name'){
                $(element).text(response[metal][request]);
                return;

            }
            // if request is ask or bid, update the amount and currency
            if (request == 'ask' || request == 'bid') {
                // extract old ask or bid price
                var oldPrice = $(element).find('.amount').text();
                // extract new ask or bid price
                var newPrice = currency_converter(response[metal][request], currency);
                // calculate the difference between the old and new price
                var diff = newPrice - oldPrice;
                // update the amount and currency of the element
                $(element).find('.amount').text(newPrice);
                $(element).find('.currency').text(currency);
                // if the difference is negative, replace the svg with down arrow and add the down class
                if (diff < 0) {
                    $(element).find('svg').replaceWith(downSrc);
                    $(element).removeClass('metal-price-up');
                    $(element).addClass('metal-price-down');
                } else if (diff > 0) {
                    // if the difference is positive, replace the svg with up arrow and add the up class
                    $(element).find('svg').replaceWith(upSrc);
                    $(element).removeClass('metal-price-down');
                    $(element).addClass('metal-price-up');
                }
            }



        });
    }


    // Initial update
    updateMetalPrices();
    // get interval from the backend
    var interval = ajax_object.interval;
    // update metal prices 
    setInterval(updateMetalPrices, interval * 1000);


});
