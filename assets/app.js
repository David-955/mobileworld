import './bootstrap.js';
import './styles/app.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

// Charger dynamiquement les pages
document.addEventListener('turbo:load', () => {
    // Gestion du bouton Retour en haut
    const btn = document.querySelector('.Btn-retourhaut');
    if (btn) {
        // Afficher/masquer le bouton en fonction du scroll
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                btn.style.display = 'block'; // Afficher le bouton après 300px de scroll
            } else {
                btn.style.display = 'none'; // Masquer le bouton sinon
            }
        });

        // Faire défiler la page vers le haut au clic sur le bouton
        btn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth' // Défilement fluide
            });
        });
    }

    // Zoomer sur l'image
    // à mettre dans les balises img que l'on veut zoomer
    const images = document.querySelectorAll('.zoomable-image');
    // trouvable sous produit.html.twig et panier
    const modal = document.getElementById('image-modal');
    const modalImg = document.getElementById('modal-image');
    
    const closeBtn = document.querySelector('.close');

    if (modal && modalImg && closeBtn) {
        images.forEach(img => {
            img.addEventListener('click', () => {
                modal.style.display = 'block';
                modalImg.src = img.src;
            });
        });

        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });

        window.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    }
});