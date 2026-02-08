
function loadData() {
    // Call our helper function that hides all error messages by default.
    hideAllErrorMessages();

    // If you had other initialization code, you can place it here too.
}

// Finds all elements with id="error-id" and hides them.
function hideAllErrorMessages() {
    // querySelectorAll returns a list of all elements with id="error-id".
    let errorElements = document.querySelectorAll('#error-id');

    // Loop through each element and hide it.
    errorElements.forEach(function(el) {
        el.style.display = 'none';
    });
}

/*
 If in the future you want to show specific errors upon validation,
 you can create a function like:

 function showError(element) {
     element.style.display = 'inline';  // or 'block'
 }

 Then in some validation logic, call showError(...) for the relevant span.
*/