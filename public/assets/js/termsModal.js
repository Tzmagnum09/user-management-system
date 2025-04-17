// Script pour gérer le modal des conditions d'utilisation
document.addEventListener('DOMContentLoaded', function() {
    // Sélectionnez tous les liens vers les conditions d'utilisation
    const termsLinks = document.querySelectorAll('.terms-link');
    
    // Pour chaque lien, ajoutez un écouteur d'événement
    termsLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Vérifiez si le modal existe déjà
            let termsModal = document.getElementById('termsModal');
            
            if (!termsModal) {
                // Si le modal n'existe pas, créez-le
                fetch(this.getAttribute('href'))
                    .then(response => response.text())
                    .then(html => {
                        // Parser le HTML pour extraire le contenu des conditions
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const termsContent = doc.querySelector('.card-body').innerHTML;
                        
                        // Créer le modal
                        const modalHTML = `
                            <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                    <div class="modal-content">
                                        <div class="modal-header" style="background: linear-gradient(135deg, #8e44ad, #3498db); color: white;">
                                            <h5 class="modal-title" id="termsModalLabel">Conditions d'utilisation</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1);"></button>
                                        </div>
                                        <div class="modal-body">
                                            ${termsContent}
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                            <button type="button" class="btn btn-gradient accept-terms">J'accepte</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        // Ajouter le modal au document
                        document.body.insertAdjacentHTML('beforeend', modalHTML);
                        
                        // Récupérer le modal nouvellement créé
                        termsModal = document.getElementById('termsModal');
                        
                        // Événement pour le bouton "J'accepte"
                        const acceptButton = termsModal.querySelector('.accept-terms');
                        if (acceptButton) {
                            acceptButton.addEventListener('click', function() {
                                // Cocher la case des conditions si elle existe
                                const agreeTermsCheckbox = document.querySelector('input[name="registration_form[agreeTerms]"]');
                                if (agreeTermsCheckbox) {
                                    agreeTermsCheckbox.checked = true;
                                }
                                // Fermer le modal
                                const modalInstance = bootstrap.Modal.getInstance(termsModal);
                                modalInstance.hide();
                            });
                        }
                        
                        // Initialiser et ouvrir le modal
                        const modal = new bootstrap.Modal(termsModal);
                        modal.show();
                    })
                    .catch(error => {
                        console.error('Erreur lors du chargement des conditions d\'utilisation:', error);
                    });
            } else {
                // Si le modal existe déjà, ouvrez-le simplement
                const modal = new bootstrap.Modal(termsModal);
                modal.show();
            }
        });
    });
});