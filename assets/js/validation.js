/**
 * Form validation script for Luna Chatbot
 */

document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            let hasError = false;
            
            // Get all required inputs
            const requiredInputs = form.querySelectorAll('input[required], textarea[required], select[required]');
            
            // Clear previous error messages
            const errorMessages = form.querySelectorAll('.error-message');
            errorMessages.forEach(msg => {
                msg.remove();
            });
            
            // Validate each required field
            requiredInputs.forEach(input => {
                if (!input.value.trim()) {
                    hasError = true;
                    
                    // Add error styling
                    input.classList.add('input-error');
                    
                    // Add error message
                    const errorMessage = document.createElement('div');
                    errorMessage.className = 'error-message';
                    errorMessage.textContent = 'This field is required';
                    
                    // Insert error message after input
                    input.parentNode.insertBefore(errorMessage, input.nextSibling);
                } else {
                    input.classList.remove('input-error');
                }
            });
            
            // Validate number inputs
            const numberInputs = form.querySelectorAll('input[type="number"]');
            numberInputs.forEach(input => {
                if (input.value.trim()) {
                    const min = parseFloat(input.getAttribute('min'));
                    const max = parseFloat(input.getAttribute('max'));
                    const value = parseFloat(input.value);
                    
                    if (min !== null && value < min) {
                        hasError = true;
                        input.classList.add('input-error');
                        const errorMessage = document.createElement('div');
                        errorMessage.className = 'error-message';
                        errorMessage.textContent = `Value must be at least ${min}`;
                        input.parentNode.insertBefore(errorMessage, input.nextSibling);
                    } else if (max !== null && value > max) {
                        hasError = true;
                        input.classList.add('input-error');
                        const errorMessage = document.createElement('div');
                        errorMessage.className = 'error-message';
                        errorMessage.textContent = `Value must not exceed ${max}`;
                        input.parentNode.insertBefore(errorMessage, input.nextSibling);
                    }
                }
            });
            
            // Prevent form submission if there are errors
            if (hasError) {
                event.preventDefault();
                
                // Scroll to first error
                const firstError = form.querySelector('.input-error');
                if (firstError) {
                    firstError.focus();
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
        
        // Clear error styling on input
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('input-error');
                
                // Remove error message
                const errorMessage = this.nextElementSibling;
                if (errorMessage && errorMessage.classList.contains('error-message')) {
                    errorMessage.remove();
                }
            });
        });
    });
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(function() {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s ease';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });
});