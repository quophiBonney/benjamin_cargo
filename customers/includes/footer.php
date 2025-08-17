 
<script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.2/dist/sweetalert2.all.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
const sidebar = document.getElementById("sidebar");
  const toggleBtn = document.getElementById("menu-toggle");

  toggleBtn.addEventListener("click", () => {
    sidebar.classList.toggle("-translate-x-full");
  });

  function toggleMenu(button) {
    const dropdown = button.nextElementSibling;
    const icon = button.querySelector("svg");
    dropdown.classList.toggle("hidden");
    icon.classList.toggle("rotate-180");
  }

  // Close all dropdowns when clicking outside
  document.addEventListener("click", function (e) {
    document.querySelectorAll("aside .relative").forEach((wrapper) => {
      const btn = wrapper.querySelector("button");
      const dropdown = wrapper.querySelector("div:not(button)");
      const icon = btn.querySelector("svg");
      if (!wrapper.contains(e.target)) {
        dropdown.classList.add("hidden");
        icon.classList.remove("rotate-180");
      }
    });
  });
  // Sidebar auto-hide for small screens
  const updateSidebar = () => {
    if (window.innerWidth < 825) {
      sidebar.classList.add("-translate-x-full");
    } else {
      sidebar.classList.remove("-translate-x-full");
    }
  };
  window.addEventListener("load", updateSidebar);
  window.addEventListener("resize", updateSidebar);
</script>