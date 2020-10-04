(function($) {

$(document).ready(function(){
    prepareEditForm();
});

function prepareEditForm() {
    hideEditForm();
    $('button.addEvent').click(addEvent);
    $('.edit-dialog .comcal-cancel').click(hideEditForm);
    $('.edit-dialog .comcal-close').click(hideEditForm);
    $('form#editEvent').submit( submitEditForm );
    $('form#deleteEvent').submit( submitDeleteForm );
    $('a.editEvent').click(editEvent);
}

function showEditForm() {
    $('.comcal-modal-wrapper.edit-dialog').css({"z-index": 200})
    $('.comcal-modal-wrapper.edit-dialog').show(200);
    $('.comcal-modal-wrapper.edit-dialog').scrollTop(0);
    disableEditForm(false);
    hideAddEventWarning();
}

function showDeleteForm() {
    $('form#deleteEvent').show(1);
}

function hideDeleteForm() {
    $('form#deleteEvent').hide(1);
}

function addEvent() {
    $('div#editEvent h2').html('Veranstaltung eintragen');
    resetEditForm();
    showEditForm();
    hideDeleteForm();
}

function editEvent(event) {
    $('div#editEvent h2').html('Veranstaltung bearbeiten');
    event.preventDefault();
    let eventId = $(event.currentTarget).attr('eventId');
    console.log('Edit Event ' + eventId);
    fillEditForm(eventId)
    .done(function(){
        showEditForm();
        showDeleteForm();
    });
}

function fillEditForm(eventId) {
    return queryEventData(eventId, 'Raw', function(result) {
        // uncheck everything
        $('input[type=checkbox]').attr('checked', false);

        // fill in form data
        for (let key in result) {
            if (key === 'calendarName') {
                // do not override the calendarName in order to allow
                // to move an event from an unnamed calendar to this one
                continue;
            }
            let control = $('input[name='+key+'],textarea[name='+key+']');
            if (control.attr('type') === 'checkbox') {
                control.attr('checked', result[key] == 1);
            } else if (control.prop('tagName') === 'TEXTAREA') {
                control.html(result[key]);
                control.attr('value', result[key]);
            } else {
                for (i = 0; i < control.length; i++) {
                    control[i].value = result[key];
                }
            }
            if (key === 'categories') {
                for (let catIndex in result[key]) {
                    let categoryId = result[key][catIndex].categoryId;
                    let control = $('form#editEvent input[value="'+categoryId+'"]');
                    control.attr('checked', true);
                }
            }
        }
    });
}

function hideEditForm() {
    $('.comcal-modal-wrapper.edit-dialog').css({"z-index": -200})
    $('.comcal-modal-wrapper.edit-dialog').hide();
}

function disableEditForm(disabled) {
    $('form#editEvent .comcal-send').prop('disabled', disabled);
}

function resetEditForm() {
    $('form#editEvent')[0].reset();
    $('form#editEvent textarea').html('');
    $('form#editEvent textarea').attr('value', '');
    $('form#editEvent #eventId').attr('value', '');
}

function submitEditForm(event) {
    event.preventDefault();
    let form = $(this);
    disableEditForm(true);
    __submitForm(form);
}
function __submitForm(form) {
    form.ajaxSubmit({
        success: function(response) {
            console.log(response);
            confirmSuccess(response);
            location.reload();
        },
        error: function(response) {
            let text = response.responseText;
            showAddEventWarning(text);
            disableEditForm(false);
            console.error(text);
        },
    });
}

function submitDeleteForm(event) {
    event.preventDefault();
    if (confirm('Soll das Event wirklich gelöscht werden?')) {
        let form = $(this);
        __submitForm(form);
    }
}

function confirmSuccess(response) {
    alert(response);
    hideEditForm();
}

function showAddEventWarning(text) {
    let warnBox = $('#editEvent .alert-warning');
    warnBox.text(text).addClass('show');
}

function hideAddEventWarning() {
    let warnBox = $('#editEvent .alert-warning');
    warnBox.removeClass('show');
}

})(jQuery);