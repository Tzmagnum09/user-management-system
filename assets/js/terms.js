/**
 * Script for managing the terms modal
 */
document.addEventListener('DOMContentLoaded', function() {
    // Handle terms links on the page
    const termsLinks = document.querySelectorAll('a[href*="/terms"], .terms-link');
    
    termsLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Create modal if not exists
            let termsModal = document.getElementById('termsModal');
            
            if (!termsModal) {
                termsModal = document.createElement('div');
                termsModal.id = 'termsModal';
                termsModal.className = 'modal fade';
                termsModal.setAttribute('tabindex', '-1');
                termsModal.setAttribute('aria-labelledby', 'termsModalLabel');
                termsModal.setAttribute('aria-hidden', 'true');
                
                const modalDialog = document.createElement('div');
                modalDialog.className = 'modal-dialog modal-lg';
                
                termsModal.appendChild(modalDialog);
                document.body.appendChild(termsModal);
                
                // Fetch the terms content
                fetch('/terms/modal')
                    .then(response => response.text())
                    .then(html => {
                        modalDialog.innerHTML = html;
                        
                        // Initialize modal and show it
                        const modal = new bootstrap.Modal(termsModal);
                        modal.show();
                        
                        // Handle accept button
                        const acceptButton = termsModal.querySelector('#acceptTerms');
                        if (acceptButton) {
                            acceptButton.addEventListener('click', function() {
                                // Check if we're on the registration page
                                const agreeTermsCheckbox = document.querySelector('input[name="registration_form[agreeTerms]"]');
                                if (agreeTermsCheckbox) {
                                    agreeTermsCheckbox.checked = true;
                                }
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error loading terms content:', error);
                    });
            } else {
                // Modal already exists, just show it
                const modal = bootstrap.Modal.getInstance(termsModal) || new bootstrap.Modal(termsModal);
                modal.show();
            }
        });
    });
});