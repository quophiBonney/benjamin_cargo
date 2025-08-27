<!-- Hero Background -->
<div class="w-full trial-bg h-screen flex flex-col justify-center">
  <div class="px-6 md:px-14 mt-24 md:mt-0 text-white">
    <h1 class="text-2xl md:text-5xl font-bold uppercase mb-4 mt-5">Benjamin Cargo Logistics</h1>
    <p class="text-sm md:text-md lg:text-lg lg:max-w-xl">
    Benjamin Cargo Logistics in a Glance
Benjamin Cargo Logistics is a trusted non-vessel operating common carrier (NVOCC) specializing in deep-sea Roll-on Roll-off (RoRo) and container shipping services. Our operations focus on key destinations in West & Middle East Africa, offering reliable and efficient transport solutions.
    </p>
  </div>
</div>
<section 
 class="relative w-full overflow-hidden" 
  x-data="{ 
    mode: '',
    location: '',
    goodsType: '',
    length: '',
    width: '',
    height: '',
    weight: '',
    pieces: '',
    cbm: 0,
    seaCost: 0,
    airCost: 0,
     rate: 0,
    showResult: false,
    showModal: false
}">
  <div class="mt-5 z-10 px-6 md:px-16 p-10">
    <div class="bg-white rounded-lg shadow-xl p-4 md:p-8 mx-auto">
      <!-- Inputs -->
      <div class="flex flex-wrap gap-4">

        <!-- Mode -->
        <div class="flex-1 min-w-[200px]">
          <label class="block text-sm font-medium">Shipping Mode</label>
          <select x-model="mode" class="w-full p-2 border rounded">
            <option value="">Select Mode</option>
            <option value="Sea">Sea</option>
            <option value="Air-Normal">Air (Normal)</option>
            <option value="Air-Express">Air (Express)</option>
          </select>
        </div>

        <!-- Destination -->
        <div class="flex-1 min-w-[150px]">
          <label class="block text-sm font-medium">Destination</label>
          <select x-model="location" class="w-full p-2 border rounded">
            <option value="">Select</option>
            <option value="Accra">Accra</option>
            <option value="Kumasi">Kumasi</option>
          </select>
        </div>

        <!-- Goods Type -->
        <div class="flex-1 min-w-[150px]">
          <label class="block text-sm font-medium">Goods Type</label>
          <select x-model="goodsType" class="w-full p-2 border rounded">
            <option value="">Select</option>
            <template x-if="mode==='Sea'">
              <optgroup label="Sea Freight">
                <option value="normal">Normal Goods</option>
                <option value="special">Special Goods</option>
                <option value="battery">Battery Goods</option>
              </optgroup>
            </template>
            <template x-if="mode==='Air-Express'">
              <optgroup label="Air Express">
                <option value="normal">Normal Goods</option>
                <option value="special">Special Goods</option>
                <option value="phone">Mobile Phone</option>
                <option value="tablet">Tablet</option>
                <option value="laptop">Laptop</option>
                <option value="battery">Pure Battery</option>
              </optgroup>
            </template>
            <template x-if="mode==='Air-Normal'">
              <optgroup label="Air Normal">
                <option value="normal">Normal Goods</option>
                <option value="special">Special Goods</option>
                <option value="battery">Battery Goods</option>
              </optgroup>
            </template>
          </select>
        </div>

        <!-- Dimensions (Sea) -->
        <template x-if="mode==='Sea'">
          <div class="flex flex-wrap gap-4 w-full">
            <div class="flex-1 min-w-[100px]">
              <label class="block text-sm font-medium">Length (cm)</label>
              <input type="number" x-model="length" class="w-full p-2 border rounded" placeholder="e.g., 100">
            </div>
            <div class="flex-1 min-w-[100px]">
              <label class="block text-sm font-medium">Width (cm)</label>
              <input type="number" x-model="width" class="w-full p-2 border rounded" placeholder="e.g., 80">
            </div>
            <div class="flex-1 min-w-[100px]">
              <label class="block text-sm font-medium">Height (cm)</label>
              <input type="number" x-model="height" class="w-full p-2 border rounded" placeholder="e.g., 45">
            </div>
          </div>
        </template>

        <!-- Weight (Air per kg) -->
        <template x-if="(mode==='Air-Normal' || mode==='Air-Express') && (goodsType==='normal' || goodsType==='special' || goodsType==='battery')">
          <div class="flex-1 min-w-[100px]">
            <label class="block text-sm font-medium">Weight (kg)</label>
            <input type="number" x-model="weight" class="w-full p-2 border rounded">
          </div>
        </template>

        <!-- Pieces (Air per unit items) -->
        <template x-if="mode==='Air-Express' && (goodsType==='phone' || goodsType==='tablet' || goodsType==='laptop')">
          <div class="flex-1 min-w-[100px]">
            <label class="block text-sm font-medium">Pieces</label>
            <input type="number" x-model="pieces" class="w-full p-2 border rounded">
          </div>
        </template>

        <!-- Button -->
       <!-- Button -->
<div class="min-w-[150px]">
  <button @click="
    if(location && mode && goodsType){
       seaCost = 0; airCost = 0; cbm = 0;

      // SEA CALCULATION
      if(mode==='Sea'){
        let rawCbm = (length/100 * width/100 * height/100);
        if(rawCbm < 0.1) rawCbm = 0.1; // minimum CBM
        cbm = rawCbm; // keep numeric

        rate = 0;
        if(goodsType==='normal') rate = 240;
        if(goodsType==='special') rate = 250;
        if(goodsType==='battery') rate = 270;

        seaCost = (rawCbm * rate).toFixed(2);
      }

      // AIR NORMAL
      if(mode==='Air-Normal'){
        rate = 0;
        if(goodsType==='normal') rate = 20;
        if(goodsType==='special') rate = 22;
        if(goodsType==='battery') rate = 50; 
        airCost = (weight * rate).toFixed(2);
      }

      // AIR EXPRESS
      if(mode==='Air-Express'){
        rate = 0;
        if(goodsType==='normal') rate = 20;
        if(goodsType==='special') rate = 22;
        if(goodsType==='battery') rate = 50;
        if(goodsType==='phone') rate = 25;
        if(goodsType==='tablet') rate = 30;
        if(goodsType==='laptop') rate = 50;

        if(goodsType==='phone' || goodsType==='tablet' || goodsType==='laptop'){
          airCost = (pieces * rate).toFixed(2);
        } else {
          airCost = (weight * rate).toFixed(2);
        }
      }

      showResult = true;
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Missing Information',
        text: 'Please fill all required fields before getting a quote.'
      });
    }
  " 
  class="bg-blue-900 text-white p-2 rounded w-full lg:mt-5" id="getQuoteBtn">Get Quote</button>
</div>

        </div>


      <!-- Results Table -->
      <div x-show="showResult" class="mt-8">
       <div class="flex justify-between mb-3">
          <h2 class="font-semibold text-lg mb-4">Freight Quote</h2>
    <button @click="showModal = true" class="bg-blue-900 text-white p-1 rounded px-4">Get Started</button>
       </div>
        <div class="overflow-auto">
          <table class="w-full text-sm text-gray-600 border border-gray-300 rounded-lg overflow-hidden text-center">
           <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
  <tr>
    <th class="px-4 py-2">Mode</th>
    <th class="px-4 py-2">Destination</th>
    <th class="px-4 py-2">Goods</th>
    <th class="px-4 py-2">CBM</th>
    <th class="px-4 py-2">Weight/Pieces</th>
    <th class="px-4 py-2">Rate</th> <!-- ✅ new column -->
    <th class="px-4 py-2">Cost (USD)</th>
  </tr>
</thead>
<tbody>
  <tr class="bg-white border-t">
    <td class="px-4 py-2 font-medium text-blue-600" x-text="mode"></td>
    <td class="px-4 py-2" x-text="location"></td>
    <td class="px-4 py-2 capitalize" x-text="goodsType"></td>
    <td class="px-4 py-2" x-text="cbm ? cbm : '-'"></td>
    <td class="px-4 py-2" x-text="(weight ? weight+' kg' : (pieces ? pieces+' pcs' : '-'))"></td>
    <td class="px-4 py-2" x-text="'$'+rate"></td> <!-- ✅ show rate -->
    <td class="px-4 py-2 font-bold text-green-600">$<span x-text="seaCost || airCost"></span></td>
  </tr>
</tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
  <div x-show="showModal" x-cloak
     class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 backdrop-blur-md px-2">
  <div class="bg-white rounded-lg shadow-xl w-full max-w-xl p-6 mb-5 mt-5">
    <h2 class="text-xl font-semibold">Get Started</h2>
<p class="mb-4 text-sm">Please fill out the form below. Our team will contact you shortly to finalize the details and provide further assistance.</p>
    <form class="space-y-4" id="contactForm">
      <div>
        <label class="block text-sm font-medium">Full Name</label>
        <input type="text" class="w-full border p-2 rounded" placeholder="John Doe" name="fullName">
      </div>
      <div>
        <label class="block text-sm font-medium">Email</label>
        <input type="email" class="w-full border p-2 rounded" placeholder="me@gmail.com" name="email">
      </div>
      <div>
        <label class="block text-sm font-medium">Phone Number</label>
        <input type="number" class="w-full border p-2 rounded" placeholder="02XXXXXXXX" name="phoneNumber">
      </div>
      <div>
        <label class="block text-sm font-medium">Message</label>
        <textarea class="w-full border p-2 rounded" rows="3" placeholder="Your message" name="message"></textarea>
      </div>
      <div class="flex justify-end gap-2">
        <button type="button" @click="showModal = false" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
        <button type="submit" class="px-4 py-2 bg-blue-900 text-white rounded" id="submitBtn">Submit</button>
      </div>
    </form>
  </div>
</div>
</section>


<script>
  let disclaimerShown = false; // ✅ flag to track if disclaimer has been shown

  document.getElementById('getQuoteBtn').addEventListener('click', function(e) {
    if (!disclaimerShown) {
      e.preventDefault(); // stop Alpine calc from running immediately
      Swal.fire({
        text: 'An estimated quote is designed to give you a clear idea of the cost based on the information you provide. The final price may be adjusted once we complete a full review.',
        icon: 'info',
        confirmButtonText: 'Got it!'
      }).then(() => {
        disclaimerShown = true; 
        // ✅ Trigger the original Alpine click again
        e.target.click();
      });
    }
  });

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

