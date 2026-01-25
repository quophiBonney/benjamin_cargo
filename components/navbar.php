<header 
  x-data="{ scrolled: false, open: false }" 
  x-init="
    window.addEventListener('scroll', () => scrolled = window.scrollY > 10);
    $watch('open', v => { if (v) setTimeout(moveGT, 0) }); /* ensure the widget moves into the dropdown when it opens */
  " 
  :class="scrolled ? 'bg-white shadow-md py-2' : 'bg-transparent py-4'" 
  class="fixed top-0 left-0 w-full z-50 transition-all duration-700 ease-in-out"
>
  <div class="px-6 lg:px-12 transition-all duration-700 ease-in-out">
    <nav 
      class="flex justify-between items-center text-gray-800 backdrop-blur-md bg-white/90 px-4 md:px-6 py-3 transition-all duration-700 ease-in-out"
      :class="scrolled ? 'rounded-none' : 'rounded-xl'">

      <!-- Logo -->
      <div>
        <a href="">
          <img src="assets/logo.png" alt="Benjamin Cargo & Logistics" class="w-20 h-auto" />
        </a>
      </div>

      <!-- Desktop Menu -->
      <div class="hidden md:flex gap-6 text-sm font-medium items-center">
        <a href="index.php" class="hover:text-blue-700 transition duration-300">Home</a>
        <a href="about-us.php" class="block hover:text-blue-700 transition duration-300">About Us</a>
         <a href="tracker.php" class="block hover:text-blue-700 transition duration-300">Tracker</a>
        <a href="contact.php" class="block hover:text-blue-700 transition duration-300">Contact</a>
        <a href="customers/login.php" target="_blank" class="bg-indigo-900 rounded text-white p-3 block transition duration-300">Portal</a>

        <!-- Single Google Translate widget lives here on desktop -->
        <div id="gt-desktop" class="relative inline-block">
          <div 
            id="google_translate_element" 
            class="bg-white border border-gray-200 rounded-lg px-3 text-sm min-w-[160px] transition-all duration-300 focus-within:ring-2 focus-within:ring-blue-500">
          </div>
        </div>
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

      <!-- When mobile menu opens, we MOVE the same widget here -->
      <div id="gt-mobile" class="border border-gray-300 rounded-md px-2 py-1 text-sm w-full focus:outline-none focus:ring-2 focus:ring-blue-500"></div>
    </div>
  </div>
</header>
<!-- Google Translate Script -->
<script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

<!-- Optional scroll class tweaks (kept from your original) -->
<script>
   window.googleTranslateElementInit = function () {
    new google.translate.TranslateElement({
      pageLanguage: 'en',
      includedLanguages: 'en,zh-CN',
      layout: google.translate.TranslateElement.InlineLayout.SIMPLE
    }, 'google_translate_element');

    // After init, place the widget where it should be for the current viewport
    moveGT();
  };

  // Move the single widget between desktop & mobile placeholders
  function moveGT() {
    var gt = document.getElementById('google_translate_element');
    var desktopWrap = document.getElementById('gt-desktop');
    var mobileWrap = document.getElementById('gt-mobile');

    if (!gt || !desktopWrap || !mobileWrap) return;

    if (window.matchMedia('(min-width: 768px)').matches) {
      if (gt.parentElement !== desktopWrap) desktopWrap.appendChild(gt);
    } else {
      if (gt.parentElement !== mobileWrap) mobileWrap.appendChild(gt);
    }
  }

  window.addEventListener('resize', moveGT);
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
