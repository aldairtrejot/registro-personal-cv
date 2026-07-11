// Function to handle errors and display them on the form
export function handleErrors(errors) {
    // Loop through each error in the 'errors' object
    for (let field in errors) {
        // Get the error element by its ID, matching the field name (e.g., #error-fieldName)
        const errorElement = document.querySelector(`#error-${field}`);

        // Get the corresponding input field by its 'name' attribute
        const inputElement = document.querySelector(`[name="${field}"]`);

        if (errorElement) {
            const message = errors[field][0]; // Get the error message from the first index of the error array
            errorElement.textContent = `! ${message}`;

            // Trigger a "shake" animation to highlight the error (visually)
            errorElement.classList.remove('shake');  // Remove any existing 'shake' class
            void errorElement.offsetWidth;  // Force a reflow to reset the animation
            errorElement.classList.add('shake'); // Add the 'shake' class to trigger the animation
        }

        // If an input field exists, add the 'input-error' class to highlight it with error styling
        if (inputElement) {
            inputElement.classList.add('input-error');
        }
    }
}
