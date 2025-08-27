<div class="px-6 md:px-14 grid grid-cols-1 md:grid-cols-2 gap-6 mt-16">
    <div>
        <img src="./assets/virtual-assistant.png" alt="" class="h-full w-full"/>
</div>
<div>
    <div class="text-center">
        <h3 class="text-2xl font-bold uppercase">Get Intouch With Us</h3>
        <p>We are excited to talk to you and also get you satisfied</p>
</div>
    <form class="space-y-4" id="contactForm">
        <div>
            <label for="name" class="text-sm text-gray-700 font-medium mb-1 block">Name</label>
            <input type="text" id="fullName" name="fullName" placeholder="Your Name" class="w-full p-3 border border-gray-300 rounded focus:ring-blue-500" />
</div>
 <div>
            <label for="email" class="text-sm text-gray-700 font-medium mb-1 block">Email</label>
            <input type="text" id="email" name="email" placeholder="you@gmail.com" class="w-full p-3 border border-gray-300 rounded focus:ring-blue-500" />
</div>
 <div>
            <label for="contact" class="text-sm text-gray-700 font-medium mb-1 block">Phone Number</label>
            <input type="number" id="phoneNumber" name="phoneNumber" placeholder="05XXXXXXXX" class="w-full p-3 border border-gray-300 rounded focus:ring-blue-500" />
</div>
 <div>
            <label for="message" class="text-sm text-gray-700 font-medium mb-1 block">Message</label>
            <textarea id="message" name="message" placeholder="How may we help your?" class="w-full p-3 border border-gray-300 rounded focus:ring-blue-500" rows="4"></textarea>
</div>
    <div>
        <input type="submit" value="Send Message" class="w-full bg-blue-900 text-white p-3 rounded hover:bg-blue-800 transition duration-300 cursor-pointer" id="submitBtn"/>
</div>
</form>
</div>
</div>
<script>
      document.getElementById('contactForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const submitBtn = document.getElementById('submitBtn');
  submitBtn.disabled = true;

  const formData = new FormData(this);
   console.log(formData)
  try {
    const response = await fetch('./customers/customer-functions/insert-prospects.php', {
      method: 'POST',
      body: formData
    });

    let result;
    const contentType = response.headers.get("Content-Type");

    if (!response.ok) {
      // Try to extract the body if it’s still JSON error
      try {
        result = await response.json();
      } catch {
        throw new Error('Server error, invalid response');
      }
      throw new Error(result.errors ? result.errors.join('<br>') : 'Server error');
    }

    // Check if the response is JSON
    if (contentType && contentType.includes("application/json")) {
      result = await response.json();
    } else {
      throw new Error('Invalid response format');
    }

    if (result.success) {
      Swal.fire({
        icon: 'success',
        text: 'Thank you for contacting Benjamin Cargo Logistics, our team will contact shortly!',
        timer: 2000,
        showConfirmButton: false
      });
      this.reset();
       document.querySelector('[x-data]').__x.$data.showModal = false;
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Validation Error',
        html: (result.errors || ['Unknown error occurred.']).map(e => `<div>${e}</div>`).join('')
      });
      return;
    }

  } catch (err) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      html: err.message
    });
  } finally {
    submitBtn.disabled = false;
  }
});
</script>
