<div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-0 mt-16">
    <div>
        <h4 class="text-2xl font-bold uppercase text-center">Accra Warehouse</h4>
           <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3970.6133784068547!2d-0.1005465250143571!3d5.6239561943570795!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xfdf850017d85093%3A0x30e226580e882ab4!2sBenjamin%20Cargo%20Logistics%20Warehouse!5e0!3m2!1sen!2sgh!4v1755536206081!5m2!1sen!2sgh" class="w-full h-full" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
</div>
 <div>
     <h4 class="text-2xl font-bold uppercase text-center">Kumasi Warehouse</h4>
           <iframe src="https://www.google.com/maps/embed?pb=!1m27!1m12!1m3!1d126791.90440814647!2d-1.7408533430589679!3d6.739693793999543!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!4m12!3e6!4m4!1s0xfdb970064f4e69d%3A0xcad3f7a1d1933feb!3m2!1d6.739700699999999!2d-1.6584514!4m5!1s0xfdb970064f4e69d%3A0xcad3f7a1d1933feb!2sP8QR%2BVJM%2C%20Abrepo%20Rd%2C%20Kumasi!3m2!1d6.739700699999999!2d-1.6584514!5e0!3m2!1sen!2sgh!4v1757081522677!5m2!1sen!2sgh" class="h-full w-full" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
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
