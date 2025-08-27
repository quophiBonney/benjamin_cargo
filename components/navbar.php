<!-- Responsive scroll-aware header with hamburger menu -->
 <!-- <style>
  #google_translate_element select {
    background: transparent !important;
    border: none !important;
    font-size: 0.875rem !important; /* text-sm */
    font-family: inherit !important;
    color: #1f2937 !important; /* text-gray-800 */
    padding: 0 !important;
    outline: none !important;
    width: 100% !important;
    cursor: pointer;
  }

  /* Hide Google icon */
  .goog-te-gadget-icon {
    display: none !important;
  }

  /* Remove underline link styles */
  #google_translate_element a {
    text-decoration: none !important;
    color: inherit !important;
  }
</style> -->
<header 
  x-data="{ scrolled: false, open: false }" 
  x-init="window.addEventListener('scroll', () => scrolled = window.scrollY > 10)" 
  :class="scrolled ? 'bg-white shadow-md py-2' : 'bg-transparent py-4'" 
  class="fixed top-0 left-0 w-full z-50 transition-all duration-700 ease-in-out"
>
  <div class="px-6 lg:px-12 transition-all duration-700 ease-in-out">
    <nav 
      class="flex justify-between items-center text-gray-800 backdrop-blur-md bg-white/90 px-4 md:px-6 py-3 transition-all duration-700 ease-in-out"
      :class="scrolled ? 'rounded-none' : 'rounded-xl'">

      <!-- Logo -->
      <div>
        <a href="index.php">
          <img src="assets/logo.png" alt="Benjamin Cargo & Logistics" class="w-20 h-auto" />
        </a>
      </div>

      <!-- Desktop Menu -->
      <div class="hidden md:flex gap-6 text-sm font-medium items-center">
        <a href="index.php" class="hover:text-blue-700 transition duration-300">Home</a>
       <a href="about-us.php" class="block hover:text-blue-700 transition duration-300">About Us</a>
         <a href="contact.php" class="block hover:text-blue-700 transition duration-300">Contact</a>
       <a href="customers/login.php" target="_blank" class="bg-indigo-900 rounded text-white p-3 block transition duration-300">Portal</a>

        <!-- Language Switcher -->
       <!-- <div 
  id="google_translate_element" 
  class="relative inline-block bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm min-w-[160px]
         shadow-sm hover:shadow-md transition-all duration-300 focus-within:ring-2 focus-within:ring-blue-500"
></div> -->
      </div>

      <!-- Hamburger (Mobile) -->
      <div class="md:hidden">
        <button @click="open = !open" class="bg-indigo-900 text-white p-2 rounded focus:outline-none">
          <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none"
            viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M4 6h16M4 12h16M4 18h16" />
          </svg>
          <svg x-show="open" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none"
            viewBox="0 0 24 24" stroke="currentColor" x-cloak>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </nav>

    <!-- Mobile Menu Dropdown -->
    <div x-show="open" x-transition class="md:hidden mt-2 rounded bg-white w-full text-black px-4 py-3 space-y-3">
      <a href="index.php" class="block hover:text-blue-700 transition duration-300">Home</a>
      <a href="about-us.php" class="block hover:text-blue-700 transition duration-300">About Us</a>
       <a href="contact.php" class="block hover:text-blue-700 transition duration-300">Contact</a>
      <a href="customers/login.php" class="bg-indigo-900 rounded text-white p-3 block transition duration-300">Portal</a>

      <!-- Mobile Language Switcher -->
      <div id="google_translate_element_mobile" class="border border-gray-300 rounded-md px-2 py-1 text-sm w-full focus:outline-none focus:ring-2 focus:ring-blue-500"></div>
    </div>
  </div>
</header>

<!-- Google Translate Script -->
<script>
function googleTranslateElementInit() {
  new google.translate.TranslateElement({
    pageLanguage: 'en',
    includedLanguages: 'en,zh-CN',
    layout: google.translate.TranslateElement.InlineLayout.SIMPLE
  }, 'google_translate_element');

  // Duplicate for mobile
  new google.translate.TranslateElement({
    pageLanguage: 'en',
    includedLanguages: 'en,zh-CN',
    layout: google.translate.TranslateElement.InlineLayout.SIMPLE
  }, 'google_translate_element_mobile');
}
</script>
<script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

<!-- Scroll behavior -->
<script>
  const nav = document.querySelector('header nav');
  window.addEventListener('scroll', () => {
    if (window.scrollY > 10) {
      nav.classList.remove('mx-3', 'mt-5', 'rounded-lg');
      nav.classList.add('w-full');
    } else {
      nav.classList.add('mx-3', 'mt-5', 'rounded-lg');
      nav.classList.remove('w-full');
    }
  });
</script>

<!-- Style tweak to make Google dropdown match your design -->
<style>
  /* Remove Google default background */
  .goog-te-banner-frame.skiptranslate, .goog-te-gadget-icon { display: none !important; }
  body { top: 0px !important; }
</style>
