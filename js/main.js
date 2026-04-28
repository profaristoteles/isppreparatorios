// JS Principal para interatividade pública

document.addEventListener("DOMContentLoaded", () => {
    // Efeitos simples ou scripts de validação de formulário
    console.log("ISP Preparatórios carregado.");

    // Mobile Menu Toggle
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const navLinks = document.querySelector('.nav-links');
    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });
    }
});
