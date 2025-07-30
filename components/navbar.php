<!-- Responsive scroll-aware header with hamburger menu -->
<header 
  x-data="{ scrolled: false, open: false }" 
  x-init="window.addEventListener('scroll', () => scrolled = window.scrollY > 10)" 
  :class="scrolled ? 'bg-white shadow-md py-2' : 'bg-transparent py-4'" 
  class="fixed top-0 left-0 w-full z-50 transition-all duration-700 ease-in-out"
>
  <div class="px-6 lg:px-16 transition-all duration-700 ease-in-out">
    <nav 
      class="flex justify-between items-center text-gray-800 backdrop-blur-md bg-white/90 px-4 md:px-6 py-3 transition-all duration-700 ease-in-out"
      :class="scrolled ? 'rounded-none' : 'rounded-xl'"
    >
      <!-- Logo -->
      <div>
       <a href="index.php">
         <img src="assets/benjamin_cargo_logo.png" alt="Benjamin Cargo & Logistics" class="w-20 h-auto" />
</a>
      </div>

      <!-- Desktop Menu -->
      <div class="hidden md:flex gap-6 text-sm font-medium">
        <a href="index.php" class="hover:text-blue-700 transition duration-300">Home</a>
        <a href="tracking.php" class="hover:text-blue-700 transition duration-300">Logistics Query</a>
        <a href="#" class="hover:text-blue-700 transition duration-300">I Need Cargo</a>
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
      <a href="tracking.php" class="block hover:text-blue-700 transition duration-300">Logistics Query</a>
      <a href="#" class="block hover:text-blue-700 transition duration-300">I Need Cargo</a>
    </div>
  </div>
</header>




<!-- JavaScript to handle scroll behavior -->


<!-- Add margin to push content below fixed header -->


<script>
  const nav = document.getElementById('mainNav');

  window.addEventListener('scroll', () => {
    if (window.scrollY > 10) {
      nav.classList.remove('mx-4', 'md:mx-10', 'mt-5', 'rounded-lg');
      nav.classList.add('w-full', 'shadow-md');
    } else {
      nav.classList.add('mx-4', 'md:mx-10', 'mt-5', 'rounded-lg');
      nav.classList.remove('w-full');
    }
  });
</script>
