<!-- Hero Background -->
<div class="w-full trial-bg h-screen flex flex-col justify-center">
  <div class="px-6 md:px-16 mt-24 md:mt-0 text-white">
    <h1 class="text-2xl md:text-5xl font-bold uppercase mb-4">Benjamin Cargo & Logistics</h1>
    <p class="text-sm md:text-md lg:text-lg lg:max-w-xl">
     Fast, reliable cargo shipping and logistics. Choose your port, fill the cargo info, and get your transport fee instantly. Lorem ipsum dolor sit amet consectetur adipisicing elit. Hic quidem voluptatum eveniet asperiores dolores laboriosam, qui veniam et, maxime numquam aliquam ullam necessitatibus deleniti placeat? Dolores perspiciatis accusamus vel laborum omnis sunt debitis possimus dolorem itaque dignissimos reiciendis, recusandae placeat.
    </p>
  </div>
</div>
<section class="relative w-full overflow-hidden" 
  x-data="{ 
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
    showResult: false
}">
  <div class="mt-5 z-10 px-6 md:px-16 p-10">
    <div class="bg-white rounded-lg shadow-2xl p-6 md:p-8 mx-auto">

      <!-- Inputs -->
      <div class="flex flex-wrap gap-4">
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
            <option value="normal">Normal Goods</option>
            <option value="special">Special Goods</option>
            <option value="battery">Battery Goods</option>
            <option value="phone">Mobile Phone</option>
            <option value="tablet">Tablet</option>
            <option value="laptop">Laptop</option>
          </select>
        </div>

        <!-- Dimensions -->
        <div class="flex-1 min-w-[100px]">
          <label class="block text-sm font-medium">Length (cm)</label>
          <input type="number" x-model="length" class="w-full p-2 border rounded" placeholder="e.g., 50">
        </div>
        <div class="flex-1 min-w-[100px]">
          <label class="block text-sm font-medium">Width (cm)</label>
          <input type="number" x-model="width" class="w-full p-2 border rounded" placeholder="e.g., 20">
        </div>
        <div class="flex-1 min-w-[100px]">
          <label class="block text-sm font-medium">Height (cm)</label>
          <input type="number" x-model="height" class="w-full p-2 border rounded" placeholder="e.g., 30">
        </div>

        <!-- Weight / Pieces -->
        <div class="flex-1 min-w-[100px]" x-show="goodsType==='normal' || goodsType==='special' || goodsType==='battery'">
          <label class="block text-sm font-medium">Weight (kg)</label>
          <input type="number" x-model="weight" class="w-full p-2 border rounded" placeholder="e.g., 45">
        </div>
        <div class="flex-1 min-w-[100px]" x-show="goodsType==='phone' || goodsType==='tablet' || goodsType==='laptop'">
          <label class="block text-sm font-medium">Pieces</label>
          <input type="number" x-model="pieces" class="w-full p-2 border rounded">
        </div>

        <!-- Button -->
        <div class="flex-1 min-w-[150px]">
          <button @click="
            if(location && goodsType){
              // ---- Calculate Sea Freight ----
              let rawCbm = (length/100 * width/100 * height/100);
              if(rawCbm < 0.1) rawCbm = 0.1;   // minimum CBM
              let seaRate = 0;
              if(goodsType==='normal') seaRate = (location==='Accra') ? 240 : 260;
              if(goodsType==='special') seaRate = (location==='Accra') ? 250 : 270;
              if(goodsType==='battery') seaRate = 270;
              let allowedWeight = rawCbm * 500;
              let cbmFinal = rawCbm;
              if(weight && weight > allowedWeight){
                cbmFinal = Math.ceil(weight/500); // add CBM until fits
              }
              cbm = cbmFinal.toFixed(2);
              seaCost = (cbmFinal * seaRate).toFixed(2);

              // ---- Calculate Air Freight ----
              let airRate = 0;
              if(goodsType==='normal') airRate = (location==='Accra') ? 20 : 22;
              if(goodsType==='special') airRate = (location==='Accra') ? 22 : 24;
              if(goodsType==='phone') airRate = 25;
              if(goodsType==='tablet') airRate = 30;
              if(goodsType==='laptop') airRate = 50;
              if(goodsType==='battery') airRate = 50;

              if(goodsType==='normal' || goodsType==='special' || goodsType==='battery'){
                airCost = (weight * airRate).toFixed(2);
              } else {
                airCost = (pieces * airRate).toFixed(2);
              }

              showResult = true;
            } else {
              alert('Please fill in destination, goods type, and package details.');
            }
          " 
          class="bg-blue-900 text-white p-2 rounded w-full lg:mt-5">Get Quote</button>
        </div>
      </div>

      <!-- Results Table -->
      <div x-show="showResult" class="mt-8">
        <h2 class="font-semibold text-lg mb-4">Freight Quote</h2>
        <div class="overflow-auto">
          <table class="w-full text-sm text-gray-600 border border-gray-300 rounded-lg overflow-hidden text-center">
            <thead class="bg-gray-100 text-gray-700 uppercase text-xs">
              <tr>
                <th class="px-4 py-2">Type</th>
                <th class="px-4 py-2">Destination</th>
                <th class="px-4 py-2">Goods</th>
                <th class="px-4 py-2">CBM</th>
                <th class="px-4 py-2">Weight/Pieces</th>
                <th class="px-4 py-2">Cost (USD)</th>
              </tr>
            </thead>
            <tbody>
              <!-- Sea Freight Row -->
              <tr class="bg-white border-t">
                <td class="px-4 py-2 font-medium text-blue-600">Sea Freight</td>
                <td class="px-4 py-2" x-text="location"></td>
                <td class="px-4 py-2" x-text="goodsType"></td>
                <td class="px-4 py-2" x-text="cbm"></td>
                <td class="px-4 py-2" x-text="weight"></td>
                <td class="px-4 py-2 font-bold text-blue-600">$<span x-text="seaCost"></span></td>
              </tr>
              <!-- Air Freight Row -->
              <tr class="bg-gray-50 border-t">
                <td class="px-4 py-2 font-medium text-green-600">Air Freight</td>
                <td class="px-4 py-2" x-text="location"></td>
                <td class="px-4 py-2" x-text="goodsType"></td>
                <td class="px-4 py-2">-</td>
                <td class="px-4 py-2" x-text="(goodsType==='phone' || goodsType==='tablet' || goodsType==='laptop') ? pieces : weight"></td>
                <td class="px-4 py-2 font-bold text-green-600">$<span x-text="airCost"></span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</section>
