<section class="relative w-full overflow-hidden"
  x-data="{ 
    tab: 'groupage',
    showResult: false,
    loadingPort: '',
    dischargePort: '',
    weight: '',
    length: '',
    width: '',
    height: '',
    volume: 0,
    airAmount: 0,
    seaAmount: 0
  }">

  <!-- Hero Section -->
  <div class="bg-orange-500 bg-[url('/assets/map-bg.png')] bg-cover bg-center bg-no-repeat text-white py-20 px-6 md:px-16">
    <div class="grid grid-cols-1 md:grid-cols-2 items-center gap-10 max-w-7xl mx-auto mt-24 md:mt-0">
      <div>
        <h1 class="text-2xl md:text-5xl font-bold uppercase mb-4">Benjamin Cargo & Logistics</h1>
        <p class="text-white/90 text-base max-w-xl">
          Fast, reliable cargo shipping and logistics. Choose your port, fill in the cargo info, and get your transport fee instantly.
        </p>
      </div>
      <div class="flex justify-center">
        <img src="assets/finally.png" alt="Cargo Image" class="w-full max-w-sm" />
      </div>
    </div>
  </div>

  <!-- Floating Card -->
  <div class="relative -mt-24 z-10 px-6 md:px-10">
    <div class="bg-white rounded-lg shadow-2xl p-6 md:p-8 max-w-6xl mx-auto">

      <!-- Tab Navigation -->
      <div class="flex flex-wrap gap-6 border-b border-gray-200 mb-6 text-sm font-medium">
        <template x-for="option in [
          { id: 'groupage', name: 'Shipping groupage container' },
          { id: 'air', name: 'Air Express' },
          { id: 'full', name: 'Shipping full container' },
          { id: 'express', name: 'Express' },
          { id: 'fba', name: 'FBA head stroke' }
        ]" :key="option.id">
          <button 
            @click="tab = option.id" 
            :class="tab === option.id ? 'border-blue-600 text-blue-600 border-b-2' : 'text-gray-500 hover:text-blue-500'" 
            class="pb-2 transition-all duration-500"
            x-text="option.name"
          ></button>
        </template>
      </div>

      <!-- Groupage Form -->
      <div x-show="tab === 'groupage'" x-transition>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">

          <!-- Loading Port -->
          <div>
            <label class="text-sm text-gray-700 font-medium mb-1 block">Port of loading</label>
            <select x-model="loadingPort" class="w-full p-2 border border-gray-300 rounded focus:ring-blue-500">
              <option disabled selected value="">Select Port</option>
              <option>Foshan warehouse</option>
              <option>Shenzhen warehouse</option>
              <option>Guangzhou warehouse</option>
            </select>
          </div>

          <!-- Discharge Port -->
          <div>
            <label class="text-sm text-gray-700 font-medium mb-1 block">Port of discharge</label>
            <select x-model="dischargePort" class="w-full p-2 border border-gray-300 rounded focus:ring-blue-500">
              <option disabled selected value="">Select Destination</option>
              <option>Accra</option>
              <option>Tema</option>
              <option>Kumasi</option>
              <option>Takoradi</option>
            </select>
          </div>

          <!-- Weight -->
          <div>
            <label class="text-sm text-gray-700 font-medium mb-1 block">* Weight (kg)</label>
            <input type="number" x-model="weight" placeholder="e.g., 45" class="w-full p-2 border border-gray-300 rounded focus:ring-blue-500" />
          </div>

          <!-- Length -->
          <div>
            <label class="text-sm text-gray-700 font-medium mb-1 block">* Length (cm)</label>
            <input type="number" x-model="length" placeholder="e.g., 100" class="w-full p-2 border border-gray-300 rounded focus:ring-blue-500" />
          </div>

          <!-- Width -->
          <div>
            <label class="text-sm text-gray-700 font-medium mb-1 block">* Width (cm)</label>
            <input type="number" x-model="width" placeholder="e.g., 50" class="w-full p-2 border border-gray-300 rounded focus:ring-blue-500" />
          </div>

          <!-- Height -->
          <div>
            <label class="text-sm text-gray-700 font-medium mb-1 block">* Height (cm)</label>
            <input type="number" x-model="height" placeholder="e.g., 40" class="w-full p-2 border border-gray-300 rounded focus:ring-blue-500" />
          </div>

          <!-- Button -->
          <div class="flex items-end col-span-full md:col-span-2 lg:col-span-1">
            <button 
              @click="
                if (weight && length && width && height && loadingPort && dischargePort) {
                  const vol = (parseFloat(length) * parseFloat(width) * parseFloat(height)) / 1000000;
                  const chargeableWeightAir = Math.max(parseFloat(weight), vol * 200);
                  const chargeableWeightSea = Math.max(parseFloat(weight), vol * 333);

                  volume = vol.toFixed(2);
                  airAmount = (chargeableWeightAir * 7).toFixed(2);
                  seaAmount = (chargeableWeightSea * 5).toFixed(2);

                  showResult = true;
                } else {
                  showResult = false;
                  alert('Please fill in all fields correctly!');
                }
              "
              class="w-full bg-blue-700 hover:bg-blue-700 text-white font-semibold text-sm py-2 rounded transition duration-300"
            >
              🔍 Query Transport Fee
            </button>
          </div>
        </div>

        <!-- Result Display -->
        <div x-show="showResult" x-transition class="mt-10">
          <h2 class="text-lg font-semibold text-gray-700 mb-4">Shipping Quote Details</h2>
          <div class="overflow-auto">
            <table class="w-full text-sm text-gray-600 border border-gray-300 rounded-lg overflow-hidden">
              <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                <tr>
                  <th class="px-4 py-2">Port of Loading</th>
                  <th class="px-4 py-2">Port of Discharge</th>
                  <th class="px-4 py-2">Weight (kg)</th>
                  <th class="px-4 py-2">Volume (m³)</th>
                  <th class="px-4 py-2 text-blue-700">Air Freight (USD)</th>
                  <th class="px-4 py-2 text-green-700">Sea Freight (USD)</th>
                </tr>
              </thead>
              <tbody>
                <tr class="bg-white border-t">
                  <td class="px-4 py-2" x-text="loadingPort"></td>
                  <td class="px-4 py-2" x-text="dischargePort"></td>
                  <td class="px-4 py-2" x-text="weight"></td>
                  <td class="px-4 py-2" x-text="volume"></td>
                  <td class="px-4 py-2 font-semibold text-blue-700">$<span x-text="airAmount"></span></td>
                  <td class="px-4 py-2 font-semibold text-green-700">$<span x-text="seaAmount"></span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>

      <!-- Other Tabs -->
      <div x-show="tab !== 'groupage'" x-transition class="text-gray-500 text-sm mt-6">Other tab content goes here...</div>

    </div>
  </div>
</section>
