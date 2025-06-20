import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import './bootstrap.js';
import './styles/app.css';

let stripe = null;

function loadStripe() {
    if (!stripe) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://js.stripe.com/v3/ ';
            script.onload = () => {
                if (window.Stripe) {
                    stripe = window.Stripe;
                    resolve(stripe);
                } else {
                    reject(new Error('Stripe failed to load'));
                }
            };
            script.onerror = () => {
                reject(new Error('Failed to load Stripe.js'));
            };
            document.head.appendChild(script);
        });
    }
    return Promise.resolve(stripe);
}

document.addEventListener('DOMContentLoaded', () => {
    // Retour en haut
    const btn = document.querySelector('.Btn-up');
    if (btn) {
        window.addEventListener('scroll', () => {
            btn.style.display = window.scrollY > 300 ? 'block' : 'none';
        });

        btn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth',
            });
        });
    }

    // Zoom sur les images
    const images = document.querySelectorAll('.zoomable-image');
    const modal = document.getElementById('image-modal');
    const modalImg = document.getElementById('modal-image');
    const closeBtn = document.querySelector('.close');

    if (images.length > 0 && modal && modalImg && closeBtn) {
        images.forEach((img) => {
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

    // Gestion du paiement Stripe
    const checkoutButton = document.getElementById('checkout-button');

    if (checkoutButton) {
        checkoutButton.addEventListener('click', async (event) => {
            event.preventDefault(); // Empêche la soumission par défaut du formulaire

            try {
                // Désactive le bouton pendant le traitement
                checkoutButton.disabled = true;
                checkoutButton.textContent = 'Traitement en cours...';

                await loadStripe();

                const publishableKey = 'pk_test_51RPoNtQ5TDJoAfap7bXvZpvwuxfQ4y3GNz3rzjISL5PIuMobaOWqzglna1UfXQE0H5d6rC9bYpa2Rqe0inmZwqQc00SDBsckQy';

                if (!publishableKey) {
                    throw new Error('Clé publique Stripe manquante');
                }

                // Appel vers l'API Symfony pour créer une session de paiement
                const response = await fetch('/create-checkout-session', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.error || 'Erreur serveur');
                }

                const session = await response.json();

                // Redirection vers Stripe Checkout
                const stripeInstance = Stripe(publishableKey);
                const { error } = await stripeInstance.redirectToCheckout({
                    sessionId: session.id,
                });

                if (error) {
                    throw new Error(`Échec du paiement : ${error.message}`);
                }
            } catch (err) {
                console.error('Erreur lors du paiement :', err.message);
                alert(`Une erreur est survenue : ${err.message}`);
            } finally {
                // Réactive le bouton après traitement
                checkoutButton.disabled = false;
                checkoutButton.textContent = 'Payer ma commande';
            }
        });
    }
});