// same thing as the __() function in php
const {__, _x, _n, _nx} = wp.i18n;


// function to move an element up
jQuery.fn.moveUp = function () {
    jQuery.each(this, function () {
        jQuery(this).fadeOut(350, () => {
            jQuery(this).after(jQuery(this).prev());
            jQuery(this).fadeIn(350, () => {
                setPriorities();
            });
        });
    });
};


// function to move an element up
jQuery.fn.moveDown = function () {
    jQuery.each(this, function () {
        jQuery(this).fadeOut(350, () => {
            jQuery(this).before(jQuery(this).next());
            jQuery(this).fadeIn(350, () => {
                setPriorities();
            });
        })
    });
};


// attach event handlers on buttons (mostly used for elements that are created in js)
function setButtonBehavior() {
    const downButtons = jQuery('button[data-action="rise-plan-move-down"]');
    const upButtons = jQuery('button[data-action="rise-plan-move-up');
    const planDeleteButtons = jQuery('button[data-action="rise-plan-delete"]');
    const dateDeleteButtons = jQuery('button[data-action="rise-closed-date-delete"]');
    const bookingRoomItemDeleteButtons = jQuery('button[data-action="rise-booking-item-delete"]');
    const bookingRoomItemEditButtons = jQuery('button[data-action="rise-booking-item-edit"]');
    const noDateInput = jQuery('.rise-other-plans').find('input[data-rise-no-date]');

    jQuery.each(downButtons, (index, value) => {
        // remove the click listener so we don't add the same listener multiple times
        jQuery(value).off('click');
        jQuery(value).on('click', (e) => {
            e.preventDefault();
            const planObject = jQuery(value).closest('.rise-plan');
            planObject.moveDown();
        });
    });

    jQuery.each(upButtons, (index, value) => {
        // remove the click listener so we don't add the same listener multiple times
        jQuery(value).off('click');
        jQuery(value).on('click', (e) => {
            e.preventDefault();
            const planObject = jQuery(value).closest('.rise-plan');
            planObject.moveUp();
        });
    });

    jQuery.each(planDeleteButtons, (index, value) => {
        jQuery(value).on('click', (e) => {
            e.preventDefault();
            const inputObject = jQuery(value).closest('.rise-plan').find('input[name^="rise-plan-id"]');
            const planObject = jQuery(value).closest('.rise-plan');
            const planID = inputObject.val();
            planObject.hide(500, () => {
                planObject.remove();
                jQuery('#rise-plans-to-delete').append(`<input type="hidden" name="rise-plan-delete[]" value="${planID}">`);
                setPriorities();
            });
        });
    });

    jQuery.each(dateDeleteButtons, (index, value) => {
        jQuery(value).on('click', (e) => {
            e.preventDefault();
            const dateObject = jQuery(value).closest('.rise-closed-date');
            jQuery(dateObject).find('input[name="rise-closed-dates-action[]"]').val('delete');
            if (dateObject.hasClass('rise-closed-date-newly-added')) {
                dateObject.hide(500, () => {
                    dateObject.remove();
                    if (jQuery('#rise-closed-dates .rise-closed-date').length === 0) {
                        jQuery('#rise-dates-no-date').removeClass('d-none');
                    }
                });
            } else {
                dateObject.hide(500, () => {
                    dateObject.prependTo(jQuery('.rise-closed-dates-to-delete'));
                    if (jQuery('#rise-closed-dates .rise-closed-date').length === 0) {
                        jQuery('#rise-dates-no-date').removeClass('d-none');
                    }
                });
            }
        });
    });

    // Delete buttons in table rows in booking page
    jQuery.each(bookingRoomItemDeleteButtons, (index, value) => {
        jQuery(value).on('click', (e) => {
            e.preventDefault();
            let itemID = jQuery(value).attr('data-item-id');

            jQuery(value).closest('tr').remove();
            jQuery(`#rise_booking_items_input > .rise-booking-item[data-item-id="${itemID}"] > input[name="rise_action[]"]`).val('delete');

            // calculate subtotal, tax, grand total and advance payment again
            calculateBookingPrices();
        });
    });


    // Edit buttons in table rows in booking page
    jQuery.each(bookingRoomItemEditButtons, (index, value) => {
        // remove all event listeners here, because we run this jQuery.each everytime we create a new button so
        // we should prevent adding the same event listener multiple times.
        jQuery(value).off()
        jQuery(value).on('click', (e) => {
            e.preventDefault();
            let itemID = jQuery(value).attr('data-item-id');
            let itemInfo = jQuery(`.rise-booking-item[data-item-id="${itemID}"]`);
            let modal = jQuery('#rise_room_item_modal');

            let roomID = itemInfo.find('input[name="rise_room_id[]"]').val();
            let checkInDate = itemInfo.find('input[name="rise_checkin_date[]"]').val();
            let checkOutDate = itemInfo.find('input[name="rise_checkout_date[]"]').val();
            let quantity = itemInfo.find('input[name="rise_quantity[]"]').val();
            let numberOfPeople = itemInfo.find('input[name="rise_number_of_people[]"]').val();

            let checkInURL = moment(new Date(checkInDate)).format('YYYY-MM-DD');
            let checkOutURL = moment(new Date(checkOutDate)).format('YYYY-MM-DD');

            checkInDate = moment(new Date(checkInDate)).format('DD/MM/YYYY');
            checkOutDate = moment(new Date(checkOutDate)).format('DD/MM/YYYY');

            modal.attr('data-action', 'update');
            modal.attr('data-update-item-id', itemID);

            let select = modal.find('select#rise-modal-room');
            let dates = modal.find('input[name="rise-modal-dates"]');

            select.find(`option[value="${roomID}"]`).prop('selected', true);
            dates.val(checkInDate + ' - ' + checkOutDate);

            let restURL = rise_data.rest.endpoints.get_room_meta_box_details + '/' + roomID + '/' + checkInURL + '/' + checkOutURL;
            jQuery.ajax({
                method: 'GET',
                url: restURL,
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', rise_data.rest.nonce);
                }
            }).done(function (data) {
                jQuery('.rise-quantity-added').remove();
                jQuery('#rise-modal-box-body').append(`
                <div class="rise-modal-box-content-field rise-quantity-added">
                    <input type="number" placeholder="Quantity (available: ${data.availableAmount})" name="rise-modal-quantity" 
                            class="rise-modal-quantity" min="1" max="${data.availableAmount}" value="${quantity}">
                </div>
                <div class="rise-modal-box-content-field rise-number-of-people-added">
                    <input type="number" placeholder="Number of people" name="rise-modal-number-of-people" 
                            class="rise-modal-number-of-people" min="1" max="${data.maxNumberOfAdults}" value="${numberOfPeople}">
                </div>
                `);
                modal.toggleClass('d-flex');
            });
        });
    });


    jQuery.each(noDateInput, (index, value) => {
        jQuery(value).change(() => {
            const datesInput = jQuery(value).parents('.rise-plan-header').find('input.rise-plan-dates');
            if (jQuery(value).is(':checked')) {
                datesInput.hide();
            } else {
                datesInput.show();
            }
        })
    });
}


// calculates booking subtotal, tax, grand total and advance payment prices and shows them in the table.
function calculateBookingPrices() {
    let totalPriceElements = jQuery('#rise_booking_items_input .rise_total_price');

    if (totalPriceElements.length === 0) {
        jQuery('#rise_subtotal').text("0.00");
        jQuery('#rise_tax').text("0.00");
        jQuery('#rise_grand_total').text("0.00");
        jQuery('#rise_advance_payment').text("0.00");
    }

    let subTotal = 0;
    let bookingTax = 0;
    let grandTotal = 0;
    let bookingAdvancePayment = 0;

    jQuery.each(totalPriceElements, (index, value) => {
        let tax = jQuery('input[name="rise_tax"]').val();
        let advancePayment = jQuery('input[name="rise_advance_payment"]').val();
        let currentItemTotal = jQuery(value).val().replace(',', '');

        subTotal = parseFloat(subTotal) + parseFloat(currentItemTotal);
        bookingTax = ((parseFloat(subTotal) / 100) * parseFloat(tax));
        grandTotal = parseFloat(subTotal) + parseFloat(bookingTax);
        bookingAdvancePayment = ((parseFloat(grandTotal) / 100) * parseFloat(advancePayment));

        jQuery('#rise_subtotal').text(subTotal.toFixed(2));
        jQuery('#rise_tax').text(bookingTax.toFixed(2));
        jQuery('#rise_grand_total').text(grandTotal.toFixed(2));
        jQuery('#rise_advance_payment').text(bookingAdvancePayment.toFixed(2));
    });
}


// ajax request to getPrices endpoint
function getPrices(restURL, startTime, endTime) {
    let url = restURL + '/' + jQuery('input[name=rise_room_pricing_plans]').val() + '/' + startTime + '/' + endTime;
    return jQuery.ajax({
        method: 'GET',
        url: url,
        beforeSend: function (xhr) {
            xhr.setRequestHeader('X-WP-Nonce', rise_data.rest.nonce);
        }
    });
}


// ajax request to getClosedDates endpoint
function getClosedDates(roomID, startTime, endTime) {
    const url = rise_data.rest.endpoints.get_closed_dates + '/' + roomID + '/' + startTime + '/' + endTime;
    return jQuery.ajax({
        method: 'GET',
        url: url,
        beforeSend: function (xhr) {
            xhr.setRequestHeader('X-WP-Nonce', rise_data.rest.nonce);
        }
    });
}


// ajax request to getPrice endpoint
function getPrice(restURL, date) {
    let url = restURL + '/' + jQuery('input[name=rise_room]').val() + '/' + date;
    return jQuery.ajax({
        method: 'GET',
        url: url,
        beforeSend: function (xhr) {
            xhr.setRequestHeader('X-WP-Nonce', rise_data.rest.nonce);
        }
    });
}


function setPriorities() {
    let priority = 1;
    const plans = jQuery('.rise-other-plans > .rise-plan');
    jQuery.each(plans, (index, value) => {
        jQuery(value).find('[data-rise-priority]').val(priority);
        const ratesInput = jQuery(value).find('input[data-rise-rate-id]');
        const ratesLabel = jQuery(value).find('label[data-rise-rate-id]');

        const noDateInput = jQuery(value).find('input[data-rise-no-date]');
        const noDateLabel = jQuery(value).find('label[data-rise-no-date]');

        jQuery.each(ratesInput, (index, value) => {
            const rateID = jQuery(value).attr('data-rise-rate-id');
            jQuery(value).val(`${priority}-${rateID}`);
            jQuery(value).attr('id', `rise-rate-${priority}-${rateID}`);
        });

        jQuery.each(ratesLabel, (index, value) => {
            const rateID = jQuery(value).attr('data-rise-rate-id');
            jQuery(value).attr('for', `rise-rate-${priority}-${rateID}`);
        });

        jQuery.each(noDateInput, (index, value) => {
            jQuery(value).val(priority);
            jQuery(value).attr('id', `rise-no-date-${priority}`);
        });

        jQuery.each(noDateLabel, (index, value) => {
            jQuery(value).attr('for', `rise-no-date-${priority}`);
        });

        priority++;
    });
}


jQuery('#rise-add-new-plan').on('click', (e) => {
    e.preventDefault();
    jQuery('#rise-plan-to-copy').clone()
        .prependTo('.rise-other-plans')
        .removeClass('d-none')
        .removeAttr('id')
        .addClass('rise-plan-newly-added');
    jQuery('.rise-plan-dates').daterangepicker({
        autoApply: true,
        locale: {
            format: 'DD/MM/YYYY'
        }
    });
    setPriorities();
    setButtonBehavior();
});


jQuery('#rise_rooms').on('change', function () {
    let selectedValue = this.selectedOptions[0].value;

    let e = window.location.href;
    e = e.replace(/[&]?rise_room_id=[0-9]+/, ""), 0 != this.value && (e += "&rise_room_id=" + selectedValue), window.location.href = e
});


// removes rise-active class from all settings tab buttons.
function removeRiseActiveFromSettings() {
    jQuery.each(jQuery('.rise-settings-tabs button'), (index, value) => {
        jQuery(value).removeClass('rise-active');
    })
}


// removes rise-active class from all payments tab buttons.
function removeRiseActiveFromPayments() {
    jQuery.each(jQuery('.rise-payments-tabs button'), (index, value) => {
        jQuery(value).removeClass('rise-active');
    })
}


// hides all settings tabs
function hideSettingsTabs() {
    jQuery.each(jQuery('.rise-setting'), (index, value) => {
        jQuery(value).hide();
    })
}


// hides all payments tabs
function hidePaymentsTabs() {
    jQuery.each(jQuery('.rise-payment'), (index, value) => {
        jQuery(value).hide();
    })
}


// switch to the tab given in parameters
function switchSettingsTab(tab) {
    hideSettingsTabs();
    switch (tab) {
        case 'general':
            jQuery('button#rise-tab-general').addClass('rise-active');
            jQuery('#rise-settings-general').show();
            break;
        case 'hotel_info':
            jQuery('button#rise-tab-hotel-info').addClass('rise-active');
            jQuery('#rise-settings-hotel-info').show();
            break;
        case 'payments':
            jQuery('button#rise-tab-payments').addClass('rise-active');
            jQuery('#rise-settings-payments').show();
            break;
        case 'mail':
            jQuery('button#rise-tab-mail').addClass('rise-active');
            jQuery('#rise-settings-mail').show();
            break;
    }
}


function switchPaymentsTab(tab) {
    hidePaymentsTabs();
    switch (tab) {
        case 'offline':
            jQuery('button#rise-tab-offline').addClass('rise-active');
            jQuery('#rise-payments-offline').show();
            break;
        case 'arrival':
            jQuery('button#rise-tab-arrival').addClass('rise-active');
            jQuery('#rise-payments-arrival').show();
            break;
        case 'paypal':
            jQuery('button#rise-tab-paypal').addClass('rise-active');
            jQuery('#rise-payments-paypal').show();
            break;
        case 'stripe':
            jQuery('button#rise-tab-stripe').addClass('rise-active');
            jQuery('#rise-payments-stripe').show();
            break;
        case 'iyzico':
            jQuery('button#rise-tab-iyzico').addClass('rise-active');
            jQuery('#rise-payments-iyzico').show();
            break;
    }
}


// converts daterangepicker dates into js date objects
function getDateObject(date) {
    let dateArray = {
        'year': parseInt(date.split('/')[2]),
        'month': parseInt(date.split('/')[1]) - 1,
        'day': parseInt(date.split('/')[0])
    };

    return new Date(dateArray['year'], dateArray['month'], dateArray['day']);
}


// reset all fields in modal form
function resetModalForm() {
    jQuery('.rise-quantity-added').remove();
    jQuery('.rise-number-of-people-added').remove();
    jQuery('.rise-modal-box-rates').html('');
    jQuery.each(jQuery('#rise-modal-room option:selected'), function () {
        jQuery(this).prop('selected', false);
    });
    jQuery('#rise-select-default-option').prop('selected', true);
    jQuery('input[name="rise-modal-dates"]').val('');
}


// initialize close rooms page
function initializeCloseRooms() {
    // initialize daterangepicker
    jQuery('input.rise-closed-dates').daterangepicker({
        autoApply: true,
        locale: {
            format: 'DD/MM/YYYY'
        }
    });

    // initialize calendar
    const roomID = jQuery('input[name=rise_room_close_rooms]').val();
    if (roomID) {
        let closedDates;
        const calendarEl = document.getElementById('rise-calendar-close-rooms');
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            datesSet: function (info) {
                const startDate = moment(info.start).format('YYYY-MM-DD');
                const endDate = moment(info.end).format('YYYY-MM-DD');

                getClosedDates(roomID, startDate, endDate).then(function (response) {
                    closedDates = response;
                });
            },
            dayCellDidMount: function (info) {
                setTimeout(function () {
                    let currentDay = moment(info.date).format('YYYY-MM-DD');
                    if (closedDates && closedDates.includes(currentDay)) {
                        jQuery(info.el)
                            .find('.fc-daygrid-day-frame')
                            .find('.fc-daygrid-day-events')
                            .find('.fc-daygrid-day-bottom')
                            .append(__('CLOSED', 'rise-hotel-booking'));
                    }
                }, 1500);
            }
        });
        calendar.render();
    }

    // handle add new closed date button
    jQuery('#rise-add-new-closed-date').on('click', function (e) {
        e.preventDefault();

        jQuery('.rise-dates-no-date').addClass('d-none');

        jQuery('#rise-closed-date-to-copy').clone()
            .prependTo('div.rise-closed-dates')
            .removeClass('d-none')
            .removeAttr('id')
            .addClass('rise-closed-date-newly-added');
        jQuery('input.rise-closed-dates').daterangepicker({
            autoApply: true,
            locale: {
                format: 'DD/MM/YYYY'
            }
        });
        setButtonBehavior();
    });
}


// initialize settings page
function initializeSettingsPage() {
    // set tabs to default
    switchSettingsTab('general');
    switchPaymentsTab('offline');

    // main settings tabs
    const generalButton = jQuery('button#rise-tab-general');
    const hotelInfoButton = jQuery('button#rise-tab-hotel-info');
    const paymentsButton = jQuery('button#rise-tab-payments');
    const mailButton = jQuery('button#rise-tab-mail');

    generalButton.on('click', () => {
        removeRiseActiveFromSettings();
        switchSettingsTab('general');
    });

    hotelInfoButton.on('click', () => {
        removeRiseActiveFromSettings();
        switchSettingsTab('hotel_info');
    });

    paymentsButton.on('click', () => {
        removeRiseActiveFromSettings();
        switchSettingsTab('payments');
    });

    mailButton.on('click', () => {
        removeRiseActiveFromSettings();
        switchSettingsTab('mail');
    });

    // payment settings tabs
    const offlineButton = jQuery('button#rise-tab-offline');
    const arrivalButton = jQuery('button#rise-tab-arrival');
    const paypalButton = jQuery('button#rise-tab-paypal');
    const stripeButton = jQuery('button#rise-tab-stripe');
    const iyzicoButton = jQuery('button#rise-tab-iyzico');

    offlineButton.on('click', (e) => {
        e.preventDefault();
        removeRiseActiveFromPayments();
        switchPaymentsTab('offline');
    });

    arrivalButton.on('click', (e) => {
        e.preventDefault();
        removeRiseActiveFromPayments();
        switchPaymentsTab('arrival');
    });

    paypalButton.on('click', (e) => {
        e.preventDefault();
        removeRiseActiveFromPayments();
        switchPaymentsTab('paypal');
    });

    stripeButton.on('click', (e) => {
        e.preventDefault();
        removeRiseActiveFromPayments();
        switchPaymentsTab('stripe');
    });

    iyzicoButton.on('click', (e) => {
        e.preventDefault();
        removeRiseActiveFromPayments();
        switchPaymentsTab('iyzico');
    });

    // add new notification email address
    jQuery('#rise-settings-add-email').on('click', function (e) {
        e.preventDefault();

    })
}

function getNumberOfNights(startDate, endDate) {
    const start = moment(startDate);
    const end = moment(endDate);
    const duration = moment.duration(end.diff(start));
    return duration.asDays();
}

jQuery(document).ready(() => {
    const today = moment(new Date()).format('DD/MM/YYYY');

    jQuery('.rise-plan-dates').daterangepicker({
        autoApply: true,
        locale: {
            format: 'DD/MM/YYYY'
        }
    });

    const modalDates = jQuery('.rise-modal-dates');
    modalDates.daterangepicker({
        autoApply: true,
        minDate: today,
        locale: {
            format: 'DD/MM/YYYY'
        }
    });

    modalDates.on('apply.daterangepicker', function (ev, picker) {
        const startDate = picker.startDate.format('MM/DD/YYYY');
        const endDate = picker.endDate.format('MM/DD/YYYY');

        if (startDate === endDate) {
            alert('Please pick at least one night.');
            date.focus();
        }
    });

    const activityLogRestURL = rise_data.rest.endpoints.get_activity_log;
    jQuery('#rise-activity-log-table').DataTable({
            "order": [[3, "desc"]],
            "serverSide": true,
            "processing": true,
            "ajax": {
                "url": activityLogRestURL,
                "type": "GET",
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', rise_data.rest.nonce);
                },
            },
        }
    );

    setButtonBehavior();
    setPriorities();

    const roomID = jQuery('input[name=rise_room_pricing_plans]').val();
    const currencySymbol = jQuery('input[name=rise_currency_symbol]').val();
    if (roomID) {
        let prices;
        const calendarEl = document.getElementById('rise-calendar-pricing-plans');
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            datesSet: function (info) {
                const startDate = moment(info.start).format('YYYY-MM-DD');
                const endDate = moment(info.end).format('YYYY-MM-DD');
                const restURL = rise_data.rest.endpoints.get_prices;

                getPrices(restURL, startDate, endDate).then(function (response) {
                    prices = response;
                });
            },
            dayCellDidMount: function (info) {
                setTimeout(function () {
                    let currentDay = moment(info.date).format('YYYY-MM-DD');
                    if (prices && prices[currentDay]) {
                        jQuery(info.el)
                            .find('.fc-daygrid-day-frame')
                            .find('.fc-daygrid-day-events')
                            .find('.fc-daygrid-day-bottom')
                            .append(currencySymbol + prices[currentDay]);
                    }
                }, 1500);
            }
        });
        calendar.render();
    }


    // settings page
    initializeSettingsPage();


    // coupon page
    const utilizationDates = jQuery('#rise_coupon_utilization_dates');
    const reservationDates = jQuery('#rise_coupon_reservation_dates');
    const utilizationSameAsReservation = jQuery('#rise_coupon_utilization_same_as_reservation')

    utilizationDates.daterangepicker({
        autoApply: true,
        locale: {
            format: 'DD/MM/YYYY'
        }
    });

    reservationDates.daterangepicker({
        autoApply: true,
        locale: {
            format: 'DD/MM/YYYY'
        }
    });

    utilizationDates.on('change', function (e) {
        if (utilizationSameAsReservation.is(':checked')) {
            reservationDates.val(jQuery(this).val());
        }
    });

    reservationDates.on('change', function (e) {
        if (utilizationSameAsReservation.is(':checked')) {
            utilizationDates.val(jQuery(this).val());
        }
    });

    utilizationSameAsReservation.on('change', function (e) {
        if (utilizationSameAsReservation.is(':checked')) {
            utilizationDates.val(reservationDates.val());
        }
    });


    // bookings page
    // initialize select2 on country select
    jQuery('#rise_customer_country').select2({
        width: '100%'
    });


    jQuery('button.rise_field_edit').on('click', function (e) {
        e.preventDefault();
        let action = jQuery(this).attr('data-action');
        let status = jQuery(this).attr('data-status');

        switch (action) {
            case 'toggle-details':
                switch (status) {
                    case 'show':
                        jQuery(this).parents('.rise_details_field').find('#rise_details_edit').css('display', 'flex');
                        jQuery(this).parents('.rise_details_field').find('#rise_details_show').css('display', 'none');
                        jQuery(this).attr('data-status', 'edit');
                        break;
                    case 'edit':
                        jQuery(this).parents('.rise_details_field').find('#rise_details_edit').css('display', 'none');
                        jQuery(this).parents('.rise_details_field').find('#rise_details_show').css('display', 'flex');
                        jQuery(this).attr('data-status', 'show');
                        break;
                }
                break;
            case 'toggle-notes':
                switch (status) {
                    case 'show':
                        jQuery(this).parents('.rise_details_field').find('#rise_notes_edit').css('display', 'flex');
                        jQuery(this).parents('.rise_details_field').find('#rise_notes_show').css('display', 'none');
                        jQuery(this).attr('data-status', 'edit');
                        break;
                    case 'edit':
                        jQuery(this).parents('.rise_details_field').find('#rise_notes_edit').css('display', 'none');
                        jQuery(this).parents('.rise_details_field').find('#rise_notes_show').css('display', 'flex');
                        jQuery(this).attr('data-status', 'show');
                        break;
                }
                break;
        }

    });


    // add room item button handler
    jQuery('button#rise-add-room-item').on('click', function (e) {
        e.preventDefault();
        let modal = jQuery('#rise_room_item_modal');
        modal.attr('data-action', 'add');
        modal.toggleClass('d-flex');
    });


    // close room item modal button handler
    jQuery('button#rise_close_modal').on('click', function (e) {
        e.preventDefault();
        jQuery('#rise_room_item_modal').toggleClass('d-flex');
        resetModalForm();
    });


    // check room availability modal button handler
    jQuery('button#rise_check_modal').on('click', async function (e) {
        e.preventDefault();
        let dates = jQuery('input[name="rise-modal-dates"]').val();

        let checkIn = dates.split(' - ')[0];
        let checkOut = dates.split(' - ')[1];

        checkIn = moment(getDateObject(checkIn)).format('YYYY-MM-DD');
        checkOut = moment(getDateObject(checkOut)).format('YYYY-MM-DD');
        let room = jQuery('select#rise-modal-room').val();

        if (checkIn === checkOut) {
            alert(__("Check-in and check-out dates can't be the same.", 'rise-hotel-booking'));
        } else if (dates === '') {
            alert(__("You must select check-in and check-out dates..", 'rise-hotel-booking'));
        } else if (room === null) {
            alert(__("You must select a room.", 'rise-hotel-booking'));
        } else {
            let restURL = rise_data.rest.endpoints.get_room_meta_box_details + '/' + room + '/' + checkIn + '/' + checkOut;
            const detailsData = await jQuery.ajax({
                method: 'GET',
                url: restURL,
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', rise_data.rest.nonce);
                }
            });
            jQuery('.rise-quantity-added').remove();
            jQuery('.rise-number-of-people-added').remove();
            jQuery('#rise-modal-box-body').append(`
                    <div class="rise-modal-box-content-field rise-quantity-added">
                        <input type="number" placeholder="Quantity (available: ${detailsData.availableAmount})" name="rise-modal-quantity" 
                                class="rise-modal-quantity" min="0" max="${detailsData.availableAmount}">
                    </div>
                    <div class="rise-modal-box-content-field rise-number-of-people-added">
                        <input type="number" placeholder="Number of people (maximum: ${detailsData.maxNumberOfAdults})" name="rise-modal-number-of-people" 
                                class="rise-modal-number-of-people" min="1" max="${detailsData.maxNumberOfAdults}">
                    </div>
                `);

            restURL = rise_data.rest.endpoints.get_rates_for_dates + '/' + checkIn + '/' + checkOut + '/' + room;
            const plans = await jQuery.ajax({
                method: 'GET',
                url: restURL,
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', rise_data.rest.nonce);
                }
            });
            const currency = jQuery('input[name="rise_currency"]').val();
            const ratesEl = jQuery('.rise-modal-box-rates');
            ratesEl.text('');
            plans.forEach((plan, index) => {
                let rates = plan.rates.map((rate) => {
                    return '<span>' + rate.name + '</span>'
                }).join('');
                if (!rates) {
                    rates = '<span>' + __('Regular', 'rise-hotel-booking') + '</span>';
                }

                ratesEl.append(`
                    <div class="rise-modal-box-plan">
                        <input type="radio" name="rise-modal-plan" id="rise-modal-plan-${index}" value="${plan.plan_id}" required>
                        <label for="rise-modal-plan-${index}">
                            ${rates}
                            <span class="rise-modal-plan-price">${currency}${parseFloat(parseFloat(plan.price) * parseInt(getNumberOfNights(checkIn, checkOut))).toFixed(2)}</span>
                        </label>
                    </div>
                `);
            });
        }
    });

    let bookingItemID = parseInt(jQuery('.rise-booking-item:last-child').attr('data-item-id')) + 1;

    // add room item modal button handler
    jQuery('button#rise_add_modal').on('click', function (e) {
        e.preventDefault();

        let dates = jQuery('.rise-modal-dates').val();

        let checkIn = dates.split(' - ')[0];
        let checkOut = dates.split(' - ')[1];

        checkIn = moment(getDateObject(checkIn)).format('YYYY-MM-DD');
        checkOut = moment(getDateObject(checkOut)).format('YYYY-MM-DD');
        let room = jQuery('select#rise-modal-room').val();
        let quantity = jQuery('input[name="rise-modal-quantity"]').val();
        let numberOfPeople = jQuery('input[name="rise-modal-number-of-people"]').val();
        let planID = jQuery('input[name="rise-modal-plan"]:checked').val();

        if (checkIn === checkOut) {
            alert(__("Check-in and check-out dates can't be the same.", 'rise-hotel-booking'))
        } else if (dates === '') {
            alert(__("You must select check-in and check-out dates..", 'rise-hotel-booking'));
        } else if (room === null) {
            alert(__("You must select a room.", 'rise-hotel-booking'));
        } else if (quantity === '') {
            alert(__("You must enter a quantity.", 'rise-hotel-booking'));
        } else {
            let restURL = rise_data.rest.endpoints.get_room_information + '/' + room + '/' + checkIn + '/' + checkOut + '/' + quantity + '/' + planID;

            jQuery.ajax({
                method: 'GET',
                url: restURL,
                beforeSend: function (xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', rise_data.rest.nonce);
                }
            }).done(function (data) {
                let modal = jQuery('#rise_room_item_modal');
                let tableAddedRooms = jQuery('tbody#added_rooms');

                switch (modal.attr('data-action')) {
                    case 'add':
                        let tableHTML = `
                                <tr>
                                <td>${data['item']}</td>
                                <td class="text-center">${data['dates']}</td>
                                <td class="text-center">${data['night']}</td>
                                <td class="text-center">${quantity}</td>
                                <td class="text-center rise-booking-items-table-rates">${data['rates']}</td>
                                <td class="text-center">${data['currency'] + data['total']}</td>
                                <td class="text-center">
                                <button class="btn btn-primary" data-action="rise-booking-item-edit"
                                    data-item-id="${bookingItemID}">
                                        <span class="dashicons dashicons-edit"></span>
                                </button>
                                <button class="btn btn-danger" data-action="rise-booking-item-delete" data-item-id="${bookingItemID}">
                                <span class="dashicons dashicons-trash"></span>
                                </button>
                                </td>
                                </tr>
                                `;

                        let inputHTML = `
                                <div class="rise-booking-item" data-item-id="${bookingItemID}">
                                    <input type="hidden" name="rise_action[]" value="add">
                                    <input type="hidden" name="rise_plan_id[]" value="${planID}">
                                    <input type="hidden" name="rise_item_id[]" value="null">
                                    <input type="hidden" name="rise_room_id[]" value="${room}">
                                    <input type="hidden" name="rise_checkin_date[]" value="${checkIn}">
                                    <input type="hidden" name="rise_checkout_date[]" value="${checkOut}">
                                    <input type="hidden" name="rise_quantity[]" value="${quantity}">
                                    <input type="hidden" name="rise_number_of_people[]" value="${numberOfPeople}">
                                    <input type="hidden" name="rise_total_price[]" class="rise_total_price" value="${data['total']}">
                                </div>
                                `;

                        tableAddedRooms.append(tableHTML);
                        jQuery('#rise_booking_items_input').append(inputHTML);
                        break;
                    case 'update':
                        let item = jQuery(`.rise-booking-item[data-item-id="${modal.attr('data-update-item-id')}"]`);
                        item.find('input[name="rise_action[]"]').val('update');
                        item.find('input[name="rise_room_id[]"]').val(room);
                        item.find('input[name="rise_checkin_date[]"]').val(checkIn);
                        item.find('input[name="rise_checkout_date[]"]').val(checkOut);
                        item.find('input[name="rise_quantity[]"]').val(quantity);
                        item.find('input[name="rise_number_of_people[]"]').val(numberOfPeople);
                        item.find('input[name="rise_total_price[]"]').val(data['total']);

                        let tableRow = jQuery(`button[data-item-id="${modal.attr('data-update-item-id')}"]`).parents('tr');

                        let newTableRow = `
                                <tr>
                                <td>${data['item']}</td>
                                <td class="text-center">${data['dates']}</td>
                                <td class="text-center">${data['night']}</td>
                                <td class="text-center">${quantity}</td>
                                <td class="text-center">${data['currency'] + data['total']}</td>
                                <td class="text-center">
                                <button class="btn btn-primary" data-action="rise-booking-item-edit"
                                    data-item-id="${modal.attr('data-update-item-id')}">
                                        <span class="dashicons dashicons-edit"></span>
                                </button>
                                <button class="btn btn-danger" data-action="rise-booking-item-delete" data-item-id="${modal.attr('data-update-item-id')}">
                                <span class="dashicons dashicons-trash"></span>
                                </button>
                                </td>
                                </tr>
                                `;

                        tableRow.replaceWith(newTableRow);
                        break;
                }

                modal.attr('data-action', 'new');
                modal.attr('data-update-item-id', 'null');

                modal.toggleClass('d-flex');
                calculateBookingPrices();
                resetModalForm();
                setButtonBehavior();
                bookingItemID++;
            });
        }
    });

    calculateBookingPrices();

    initializeCloseRooms();
});
