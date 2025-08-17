
<aside id="sidebar" class="fixed inset-y-0 left-0 w-64 bg-gradient-to-b from-gray-900 via-gray-800 to-gray-700 text-white z-40 transform transition-transform duration-300 ease-in-out md:translate-x-0 -translate-x-full">
  <div class="flex items-center justify-center p-6 border-b border-gray-700">
    <h1 class="text-xl md:text-2xl font-bold">Benjamin Cargo</h1>
  </div>

  <nav class="px-6 py-4 space-y-4">
    <a href="" class="w-full flex items-center gap-3 hover:bg-gray-700 px-3 py-2 text-sm hover:bg-gray-100"> <i class="fa-solid fa-home"></i> Home</a>
   <div>
      <form method="POST" action="includes/logout.php">
      <button type="submit" class="w-full flex items-center gap-3 hover:bg-gray-700 px-3 py-2 rounded transition">
        <i class="fa-solid fa-right-from-bracket"></i> Sign Out
      </button>
    </form>
  </div>
  </nav>
</aside>

<script>
  function toggleMenu(button) {
    const content = button.nextElementSibling;
    content.classList.toggle('hidden');
  }
</script>
