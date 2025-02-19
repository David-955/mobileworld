import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

// Avec symfony les pages sont chargés dynamiquement car j'ai utilisé des routes, il faut donc utiliser l'événement turbo:load pour que le code soit exécuté à chaque chargement de page.
// turbo:load est un événement spécifique à Turbo qui se déclenche chaque fois qu'une nouvelle page est chargée dynamiquement.
document.addEventListener('turbo:load', () => {
    const btn = document.querySelector('.Btn-retourhaut');

    if (btn) {
        btn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Optionnel : Afficher/masquer le bouton en fonction du scroll
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                btn.style.display = 'flex';
            } else {
                btn.style.display = 'none';
            }
        });
    }
});