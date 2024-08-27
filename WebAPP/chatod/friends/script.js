let currentSectionId = 'section1'; // Initialize with the default section

function showSection(sectionId) {
    const currentSection = document.getElementById(currentSectionId);
    const targetSection = document.getElementById(sectionId);

    // If trying to switch to the same section, do nothing
    if (currentSectionId === sectionId) return;

    // Determine the direction based on section IDs
    const sections = Array.from(document.querySelectorAll('.section-content'));
    const currentIndex = sections.indexOf(currentSection);
    const targetIndex = sections.indexOf(targetSection);

    if (currentIndex < targetIndex) {
        // Moving forward
        currentSection.style.transform = 'translateX(-100%)';
        targetSection.style.transform = 'translateX(100%)';
    } else {
        // Moving backward
        currentSection.style.transform = 'translateX(100%)';
        targetSection.style.transform = 'translateX(-100%)';
    }

    // Apply classes after a brief delay to ensure transition
    setTimeout(() => {
        currentSection.classList.remove('active');
        currentSection.classList.add('inactive');

        targetSection.classList.remove('inactive');
        targetSection.classList.add('active');
        targetSection.style.transform = 'translateX(0)'; // Ensure the target section is visible

        currentSectionId = sectionId; // Update the current section ID
    }, 10); // Small delay for smooth transition
}

// Automatically show Section 1 when the page loads
window.onload = function() {
    showSection('section1');
};
