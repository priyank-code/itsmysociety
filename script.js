// Typing effect
const typingText =
  "Welcome to our digital society management platform. Streamline your society operations with our comprehensive features including notice updates, maintenance tracking, and complaint management. Experience seamless communication between society members and administrators for better community living.";
const typingElement = document.querySelector(".typing-effect");
const cursorElement = document.querySelector(".typing-cursor");
let index = 0;
let isDeleting = false;
const typingDelay = 70; // Delay between each character typing
const deletingDelay = 30; // Delay between each character deletion
const pauseDelay = 2000; // Delay before starting to delete/type

function typeEffect() {
  // Check if element exists
  if (!typingElement) return;

  if (!isDeleting) {
    // Typing
    if (index < typingText.length) {
      typingElement.innerHTML = typingText.substring(0, index + 1);
      index++;
      setTimeout(typeEffect, typingDelay);
    } else {
      // Pause before starting to delete
      isDeleting = true;
      setTimeout(typeEffect, pauseDelay);
    }
  } else {
    // Deleting
    if (index > 0) {
      typingElement.innerHTML = typingText.substring(0, index - 1);
      index--;
      setTimeout(typeEffect, deletingDelay);
    } else {
      // Reset to start typing again
      isDeleting = false;
      setTimeout(typeEffect, pauseDelay);
    }
  }
}

// Start the typing effect
typeEffect();
// Function to check if the counter section is in the viewport
function isInViewport(element) {
  const rect = element.getBoundingClientRect();
  return rect.top >= 0 && rect.bottom <= window.innerHeight;
}

// Counter animation function
function animateCounter(counter) {
  const target = parseInt(counter.getAttribute("data-target"));
  let count = 0;

  const increment = target / 375;
  const interval = setInterval(() => {
    count += increment;
    if (count >= target) {
      count = target;
      clearInterval(interval);
    }
    counter.textContent = Math.floor(count);
  }, 15);
}

// On scroll, check if the counter is in the viewport
window.addEventListener("scroll", () => {
  const counters = document.querySelectorAll(".counter");
  counters.forEach((counter) => {
    if (isInViewport(counter) && !counter.classList.contains("animated")) {
      animateCounter(counter);
      counter.classList.add("animated"); // To make sure it only animates once
    }
  });
});

// Close mobile navbar when clicking on nav links
document.addEventListener("DOMContentLoaded", function () {
  const navLinks = document.querySelectorAll(".nav-link");
  const offcanvasMenu = document.getElementById("offcanvasNavbar2");
  const registerModal = document.getElementById("exampleModal");

  navLinks.forEach((link) => {
    link.addEventListener("click", () => {
      const bsOffcanvas = bootstrap.Offcanvas.getInstance(offcanvasMenu);
      if (bsOffcanvas && !link.hasAttribute("data-bs-toggle")) {
        bsOffcanvas.hide();
      }
    });
  });
});

// Password visibility toggle for all password fields
function setupPasswordToggles() {
  const togglePassword = document.getElementById("togglePassword");
  const toggleSignupPassword = document.getElementById("toggleSignupPassword");
  const toggleAdminPassword = document.getElementById("toggleAdminPassword");
  const password = document.getElementById("inputPassword1");
  const signupPassword = document.getElementById("password");
  const adminPassword = document.getElementById("adminPassword");

  function setupToggle(toggleBtn, passwordInput) {
    if (toggleBtn && passwordInput) {
      toggleBtn.addEventListener("click", () => {
        const type =
          passwordInput.getAttribute("type") === "password"
            ? "text"
            : "password";
        passwordInput.setAttribute("type", type);
        toggleBtn.querySelector("i").classList.toggle("bi-eye");
        toggleBtn.querySelector("i").classList.toggle("bi-eye-slash");
      });
    }
  }

  setupToggle(togglePassword, password);
  setupToggle(toggleSignupPassword, signupPassword);
  setupToggle(toggleAdminPassword, adminPassword);
}

// Form Validation
function setupFormValidation() {
  const forms = document.querySelectorAll("form");

  forms.forEach((form) => {
    form.addEventListener("submit", (event) => {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add("was-validated");
    });
  });
}

// Initialize all features when the document is loaded
document.addEventListener("DOMContentLoaded", function () {
  setupPasswordToggles();
  setupFormValidation();
});
