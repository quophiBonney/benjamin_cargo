<!-- Hero Background -->
<div class="w-full trial-bg h-screen flex flex-col justify-center">
  <div class="px-6 md:px-16 mt-24 md:mt-0 text-white">
    <h1 class="text-3xl md:text-5xl font-bold uppercase mb-4">Benjamin Cargo & Logistics</h1>
    <p class="text-sm md:text-md lg:text-lg lg:max-w-xl">
     Fast, reliable cargo shipping and logistics. Choose your port, fill the cargo info, and get your transport fee instantly. Lorem ipsum dolor sit amet consectetur adipisicing elit. Hic quidem voluptatum eveniet asperiores dolores laboriosam, qui veniam et, maxime numquam aliquam ullam necessitatibus deleniti placeat? Dolores perspiciatis accusamus vel laborum omnis sunt debitis possimus dolorem itaque dignissimos reiciendis, recusandae placeat.
    </p>
  </div>
</div>

<!-- Form Card Floating Under -->
<section class="relative w-full overflow-hidden" x-data="{ 
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
  <div class="mt-5 z-10 px-6 md:px-16 p-10">
    <div class="bg-white rounded-lg shadow-2xl p-6 md:p-8 mx-auto">

      <!-- Tabs -->
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

      <!-- Groupage Tab -->
      <div x-show="tab === 'groupage'" x-transition:enter="transition ease-out duration-700" x-transition:leave="transition ease-in duration-500">
        <div class="flex flex-wrap gap-4 items-end">
          <div class="flex-1 min-w-[190px]">
            <label class="text-sm text-gray-700 font-medium mb-1 block">Port of loading</label>
            <select x-model="loadingPort" class="w-full p-2 border border-gray-300 rounded focus:ring-blue-500">
              <option disabled selected value="">Select Port</option>
              <option>Foshan warehouse</option>
              <option>Shenzhen warehouse</option>
              <option>Guangzhou warehouse</option>
            </select>
          </div>

          <div class="flex-1 min-w-[200px]">
            <label class="text-sm text-gray-700 font-medium mb-1 block">Port of discharge</label>
            <select x-model="dischargePort" class="w-full p-2 border border-gray-300 rounded focus:ring-blue-500">
              <option disabled selected value="">Select Destination</option>
              <option>Accra</option>
              <option>Tema</option>
              <option>Lagos</option>
            </select>
          </div>

          <div class="flex-1 min-w-[70px]">
            <label class="text-sm text-gray-700 font-medium mb-1 block">* Weight (kg)</label>
            <input type="number" x-model="weight" placeholder="e.g., 45" class="w-full p-2 border border-gray-300 rounded focus:ring-blue-500" />
          </div>

          <div class="flex-1 min-w-[70px]">
            <label class="text-sm text-gray-700 font-medium mb-1 block">Length (cm)</label>
            <input type="number" x-model="length" placeholder="L" class="w-full p-2 border border-gray-300 rounded focus:ring-blue-500" />
          </div>

          <div class="flex-1 min-w-[70px]">
            <label class="text-sm text-gray-700 font-medium mb-1 block">Width (cm)</label>
            <input type="number" x-model="width" placeholder="W" class="w-full p-2 border border-gray-300 rounded focus:ring-blue-500" />
          </div>

          <div class="flex-1 min-w-[70px]">
            <label class="text-sm text-gray-700 font-medium mb-1 block">Height (cm)</label>
            <input type="number" x-model="height" placeholder="H" class="w-full p-2 border border-gray-300 rounded focus:ring-blue-500" />
          </div>

          <div class="flex-1 min-w-[180px]">
            <button 
              @click="
                if (weight && length && width && height && loadingPort && dischargePort) {
                  const vol = (parseFloat(length) * parseFloat(width) * parseFloat(height)) / 1000000;
                  const chargeableWeight = Math.max(parseFloat(weight), vol * 200);

                  volume = vol.toFixed(2);
                  airAmount = (chargeableWeight * 5).toFixed(2);
                  seaAmount = (vol * 100).toFixed(2);
                  showResult = true;
                } else {
                  showResult = false;
                  alert('Please fill all fields correctly!');
                }
              "
              class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm py-2 rounded transition duration-300"
            >
              🔍 Query Transport Fee
            </button>
          </div>
        </div>

        <!-- Results Table -->
        <div x-show="showResult" x-transition:enter="transition ease-out duration-700" x-transition:leave="transition ease-in duration-500" class="mt-10">
          <h2 class="text-lg font-semibold text-gray-700 mb-4">Shipping Quote Details</h2>
          <div class="overflow-auto">
            <table class="w-full text-sm text-gray-600 border border-gray-300 rounded-lg overflow-hidden text-center">
              <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                <tr>
                  <th class="px-4 py-2">Port of Loading</th>
                  <th class="px-4 py-2">Port of Discharge</th>
                  <th class="px-4 py-2">Weight (kg)</th>
                  <th class="px-4 py-2">Volume (m³)</th>
                  <th class="px-4 py-2">Air Freight (USD)</th>
                </tr>
              </thead>
              <tbody>
                <tr class="bg-white border-t">
                  <td class="px-4 py-2" x-text="loadingPort"></td>
                  <td class="px-4 py-2" x-text="dischargePort"></td>
                  <td class="px-4 py-2" x-text="weight"></td>
                  <td class="px-4 py-2" x-text="volume"></td>
                  <td class="px-4 py-2 font-semibold text-green-600">$<span x-text="airAmount"></span></td>
                </tr>
              </tbody>
            </table>

            <table class="w-full text-sm text-gray-600 border border-gray-300 rounded-lg overflow-hidden text-center mt-6">
              <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
                <tr>
                  <th class="px-4 py-2">Port of Loading</th>
                  <th class="px-4 py-2">Port of Discharge</th>
                  <th class="px-4 py-2">Volume (m³)</th>
                  <th class="px-4 py-2">Sea Freight (USD)</th>
                </tr>
              </thead>
              <tbody>
                <tr class="bg-white border-t">
                  <td class="px-4 py-2" x-text="loadingPort"></td>
                  <td class="px-4 py-2" x-text="dischargePort"></td>
                  <td class="px-4 py-2" x-text="volume"></td>
                  <td class="px-4 py-2 font-semibold text-blue-600">$<span x-text="seaAmount"></span></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Other Tabs -->
      <div x-show="tab !== 'groupage'" class="text-gray-500 text-sm mt-6">Other tab content goes here...</div>
    </div>
  </div>
</section>
