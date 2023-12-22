function calculateBooking() {
    // define currency and price rates
    const currency = jQuery('input[name="rise_currency"]').val();
    const taxRate = parseFloat(jQuery('input[name="rise_tax_rate"]').val());
    const advancePaymentRate = parseFloat(jQuery('input[name="rise_advance_payment_rate"]').val());

    // define elements
    const rows = jQuery('tr.rise-checkout-row');
    const subTotalElement = jQuery('#rise-checkout-sub-total');
    const taxElement = jQuery('#rise-checkout-tax');
    const grandTotalElement = jQuery('#rise-checkout-grand-total');
    const advancePaymentElement = jQuery('.rise-checkout-advance-payment');

    // calculate sub total
    let subTotal = 0.00;
    for (let i = 0; i < rows.length; i++) {
        subTotal += parseFloat(jQuery(rows[i]).attr('data-price'));
    }

    // calculate tax, grand total and advance payment
    const tax = (subTotal / 100) * taxRate;
    const grandTotal = subTotal + tax;
    const advancePayment = (grandTotal / 100) * advancePaymentRate;

    // render the new prices on the table
    subTotalElement.text(currency + subTotal.toFixed(2));
    taxElement.text(currency + tax.toFixed(2));
    grandTotalElement.text(currency + grandTotal.toFixed(2));
    advancePaymentElement.text(currency + advancePayment.toFixed(2));
}

// checks if an HTML element overflows its container
function checkOverflow(el) {
    const curOverflow = el.style.overflow;

    if (!curOverflow || curOverflow === "visible")
        el.style.overflow = "hidden";

    const isOverflowing = el.clientWidth < el.scrollWidth
        || el.clientHeight < el.scrollHeight;

    el.style.overflow = curOverflow;

    return isOverflowing;
}

// sets colspans as they are defined in classnames of the elements
// i.e.: if the element has rise-colspan-full as its classname, it gets the colspan value of the th element amount
// if it has rise-colspan-1 as its classname, it gets the colspan value of the th element amount -1.
function setColspans() {
    const elements = document.querySelectorAll('[class*="rise-colspan-"]');

    elements.forEach(element => {
        const table = element.closest('table');
        const fullColspan = table.querySelectorAll('thead th:not(.d-none)').length;

        console.log(element.className);
        let colspanType = element.className.match(/rise-colspan-(\w+)/)[1];
        if (colspanType === 'full') {
            colspanType = 0;
        }
        const colspan = fullColspan - colspanType;
        element.setAttribute('colspan', colspan);
    });
}

jQuery(document).ready(() => {
    // initialize select2 on checkout country select
    jQuery('#rise-checkout-country').select2();

    const today = moment(new Date()).format('DD/MM/YYYY');
    const dates = [jQuery('#secondary #rise_dates'), jQuery('#rise_dates')];
    dates.forEach((date) => {
        date.daterangepicker({
            autoApply: true,
            minDate: today,
            locale: {
                format: 'DD/MM/YYYY'
            }
        });

        // check if there is at least one night selected on date picker
        date.on('apply.daterangepicker', function (ev, picker) {
            const startDate = picker.startDate.format('MM/DD/YYYY');
            const endDate = picker.endDate.format('MM/DD/YYYY');

            if (startDate === endDate) {
                alert('Please pick at least one night.');
                date.focus();
            }
        });

    });


    jQuery('#rise_search_btn').on('click', function (e) {
        // check if there is at least one night selected on date picker
        const [startDate, endDate] = dates.val().split(' - ');
        if (startDate === endDate) {
            e.preventDefault();
            alert('Please pick at least one night.');
        }

        // check if number of people is minimum 1
        const numberOfPeopleEl = jQuery('#rise_number_of_people');
        const numberOfPeople = parseInt(numberOfPeopleEl.val());
        if (numberOfPeople <= 0) {
            numberOfPeopleEl.val(1);
        }
    });


    // Room book button on room search results page
    jQuery('button[data-action="rise_book"]').on('click', function (e) {
        const arrivalDate = jQuery('input[name="rise_result_arrival_date"]');
        const departureDate = jQuery('input[name="rise_result_departure_date"]');
        const roomID = jQuery('input[name="rise_result_room_id"]');
        const planID = jQuery('input[name="rise_result_plan_id"]');

        const arrivalDateFormatted = moment(arrivalDate.val(), 'DD/MM/YYYY').format('YYYY-MM-DD');
        const departureDateFormatted = moment(departureDate.val(), 'DD/MM/YYYY').format('YYYY-MM-DD');
        const selectedRoomID = jQuery(this).parents('tr').attr('data-room-id');
        const selectedPlanID = jQuery(this).attr('data-plan-id');

        arrivalDate.val(arrivalDateFormatted);
        departureDate.val(departureDateFormatted);
        roomID.val(selectedRoomID);
        planID.val(selectedPlanID);
    });


    // Existing customer search by mail section, apply button on checkout page
    jQuery('button[data-action="rise-apply-existing-email"]').on('click', function (e) {
        e.preventDefault();
        let email = jQuery('#rise-existing-customer-email').val();
        let restURL = rise_data.rest.endpoints.get_customer_details_by_email + '/' + email;

        if (email === '') {
            alert('Please fill the email field.');
            return;
        }

        // show spinner
        const button = jQuery(this);
        button.find('.rise-spinner').removeClass('d-none');
        jQuery('button, input, textarea, select').prop('disabled', true);

        jQuery.ajax({
            method: 'GET',
            url: restURL,
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', rise_data.rest.nonce);
            },
            statusCode: {
                400: function (responseObject, textStatus, jqXHR) {
                    alert(responseObject['responseJSON']['message']);
                    button.find('.rise-spinner').addClass('d-none');
                    jQuery('button, input, textarea, select').prop('disabled', false);
                }
            }
        }).done(function (data) {
            jQuery(`#rise-checkout-title > option[value="${data['rise_customer_name_prefix']}"]`).prop('selected', true);
            jQuery('#rise-checkout-first-name').val(data['rise_customer_first_name']);
            jQuery('#rise-checkout-last-name').val(data['rise_customer_last_name']);
            jQuery('#rise-checkout-address').val(data['rise_customer_address']);
            jQuery('#rise-checkout-city').val(data['rise_customer_city']);
            jQuery('#rise-checkout-state').val(data['rise_customer_state']);
            jQuery('#rise-checkout-postal-code').val(data['rise_customer_postal_code']);
            jQuery(`#rise-checkout-country > option[value="${data['rise_customer_country']}"]`).prop('selected', true);
            jQuery('#rise-checkout-phone').val(data['rise_customer_phone']);
            jQuery('#rise-checkout-email').val(data['rise_customer_email']);

            button.find('.rise-spinner').addClass('d-none');
            jQuery('button, input, textarea, select').prop('disabled', false);
        });
    });


    // Delete button on each table row on checkout page
    jQuery('button[data-rise-checkout-delete]').on('click', function (e) {
        e.preventDefault();

        let restURL = rise_data.rest.endpoints.delete_room_from_session + '/' + jQuery(this).attr('data-temporary-id');
        jQuery.ajax({
            method: 'GET',
            url: restURL,
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', rise_data.rest.nonce);
            }
        }).done(function (data) {
            if (data) {
                location.assign(location.href);
            }
        });
    });


    // Apply coupon button on checkout page
    jQuery('button[data-action="rise-checkout-apply-coupon"]').on('click', async function (e) {
        e.preventDefault();

        const rooms = jQuery('div.rise_checkout_room');
        const couponCode = jQuery('input[name="rise-checkout-coupon-user-input"]').val();
        const couponAlert = jQuery('.rise-checkout-coupon-alert');
        let discountedRoomCount = 0;

        for (let i = 0; i < rooms.length; i++) {
            const room = jQuery(rooms[i]);
            const planID = room.find('input[name="rise_result_plan_id[]"]').val();
            const roomID = room.find('input[name="rise_result_room_id[]"]').val();
            const checkInDate = room.find('input[name="rise_result_arrival_date[]"]').val()
            const checkOutDate = room.find('input[name="rise_result_departure_date[]"]').val()

            const restURL = rise_data.rest.endpoints.check_coupon_availability + '/' + couponCode + '/' + checkInDate + '/' + checkOutDate + '/' + roomID + '/' + planID;

            const button = jQuery(this);

            button.find('.rise-spinner').removeClass('d-none');
            jQuery('button, input, textarea, select').prop('disabled', true);

            await jQuery.ajax({
                method: 'GET',
                url: restURL,
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', rise_data.rest.nonce);
                },
                statusCode: {
                    400: function (responseObject, textStatus, jqXHR) {
                        couponAlert.text(responseObject['responseJSON']['message']);
                        couponAlert.attr('data-rise-coupon-result', responseObject['responseJSON']['type']);
                        couponAlert.removeClass('d-none');

                        button.find('.rise-spinner').addClass('d-none');
                        jQuery('button, input, textarea, select').prop('disabled', false);
                    }
                }
            }).done(function (discountedPrice) {
                // define the elements
                const regularPriceElement = jQuery(`tr[data-room-id=${roomID}][data-plan-id=${planID}] .rise-regular-price`);
                const discountedPriceElement = jQuery(`tr[data-room-id=${roomID}][data-plan-id=${planID}] .rise-discounted-price`);
                const tableRow = jQuery(`tr[data-room-id=${roomID}][data-plan-id=${planID}]`);
                const currency = jQuery('input[name="rise_currency"]').val();

                // get the current price from the table row data
                const currentPrice = tableRow.attr('data-original-price');

                if (discountedPrice !== false) {
                    discountedPrice = discountedPrice * tableRow.attr('data-quantity');
                    // show the discounted price to the user
                    regularPriceElement.addClass('rise-old-price');
                    discountedPriceElement.text(`${currency}${discountedPrice.toFixed(2)}`);
                    tableRow.attr('data-price', discountedPrice)
                    discountedRoomCount++;
                } else {
                    regularPriceElement.removeClass('rise-old-price');
                    discountedPriceElement.text("");
                    tableRow.attr('data-price', currentPrice)
                }

                button.find('.rise-spinner').addClass('d-none');
                jQuery('button, input, textarea, select').prop('disabled', false);
                couponAlert.attr('data-rise-coupon-result', 'success');
            });
        }

        if (discountedRoomCount === 0) {
            couponAlert.text('Coupon is not applicable.');
            couponAlert.attr('data-rise-coupon-result', 'not-available');
            couponAlert.removeClass('d-none');
        } else {
            jQuery('.rise-checkout-applied-coupon').text(couponCode);
            jQuery('.rise-checkout-applied-coupon-field').removeClass('d-none');
            couponAlert.addClass('d-none');
        }

        await calculateBooking();
        jQuery(this).attr('data-applied', couponCode);
    });

    // Remove coupon button on checkout page
    jQuery('a#rise-checkout-remove-coupon').on('click', function (e) {
        e.preventDefault();

        jQuery.ajax({
            method: 'GET',
            url: rise_data.rest.endpoints.remove_coupon,
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', rise_data.rest.nonce);
            }
        }).done(function (response) {
            const couponAlert = jQuery('#rise-checkout-coupon-alert');
            if (response === true) {
                // define row elements
                const rows = jQuery('tr.rise-checkout-row');

                // revert the price of all rows to their original price
                for (let i = 0; i < rows.length; i++) {
                    const currentRow = jQuery(rows[i]);
                    currentRow.attr('data-price', currentRow.attr('data-original-price'));
                }

                // remove old price class from every price div
                jQuery('[data-rise-regular-price]').removeClass('rise-old-price');

                // empty all the discounted price divs
                jQuery('[data-rise-discounted-price]').text('');

                // empty coupon input
                jQuery('input[name="rise-checkout-coupon-user-input"]').attr('value', '');

                //empty data-applied attribute of apply coupon button
                jQuery('button[data-action="rise-checkout-apply-coupon"]').attr('data-applied', '');

                // hide 'coupon applied' message
                jQuery('#rise-checkout-applied-coupon-field').addClass('d-none');

                calculateBooking();
            } else {
                couponAlert.text('An error occurred while removing the coupon.');
                couponAlert.removeClass('d-none');
            }
        });

    });

    // Show passport/id number input when iyzico payment method is selected
    jQuery('input[name="rise-checkout-payment-method"]').on('change', function (e) {
        const selectedMethod = e.target.value;
        const idNumberEl = jQuery('[data-rise-passport-id]');
        const idNumberInputEl = jQuery(idNumberEl).find('input#rise-checkout-passport-id');

        if (selectedMethod === 'iyzico') {
            idNumberEl.removeClass('d-none');
            idNumberInputEl.attr('required', '');
            return;
        }

        idNumberEl.addClass('d-none');
        idNumberInputEl.removeAttr('required');
    });
});

const windowLoadResize = () => {
    const checkoutTable = jQuery('#rise-checkout-table');
    const nightRow = jQuery('.rise-checkout-night');

    if (checkoutTable[0]) {
    // check if table overflow is visible, hide night rows if it is
        if (checkOverflow(checkoutTable[0])) {
            nightRow.addClass('d-none');
        } else {
            nightRow.removeClass('d-none');
        }
        setColspans();
    }
}

window.addEventListener('resize', windowLoadResize);
window.addEventListener('load', windowLoadResize);