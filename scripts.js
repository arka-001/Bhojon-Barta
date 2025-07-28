document.addEventListener('DOMContentLoaded', () => {
    const elements = document.querySelectorAll('.single-restaurant, .single-dish, .category-item');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target); // Stop observing once animated
            }
        });
    }, {
        threshold: 0.1 // Trigger when 10% of the element is visible
    });

    elements.forEach(element => {
        observer.observe(element);
    });
});